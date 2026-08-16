<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Imports lessons from a YouTube playlist (works with Unlisted playlists).
 *
 * Preferred path: YouTube Data API v3 (free API key, reliable, paginated).
 * Fallback: parse the playlist's public watch page (no key needed, but
 * limited to roughly the first 100 videos and more fragile).
 *
 * Import is idempotent: videos that already exist as lessons in the course
 * are skipped, so re-importing picks up newly added playlist videos.
 */
class ASWJ_LMS_Playlist {

	public static function init() {
		add_action( 'admin_post_aswj_import_playlist', array( __CLASS__, 'handle_import' ) );
	}

	/**
	 * Extract the playlist ID from a URL or raw ID.
	 */
	public static function normalize_playlist_id( $input ) {
		$input = trim( (string) $input );
		if ( '' === $input ) {
			return '';
		}
		if ( preg_match( '/[?&]list=([A-Za-z0-9_-]+)/', $input, $m ) ) {
			return $m[1];
		}
		if ( preg_match( '/^[A-Za-z0-9_-]{10,}$/', $input ) ) {
			return $input;
		}
		return '';
	}

	public static function handle_import() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'aswj-lms' ) );
		}
		// Reached via a GET link from the course edit screen (the meta box
		// sits inside the post form, so a nested POST form is not possible).
		$course_id = isset( $_REQUEST['course_id'] ) ? absint( $_REQUEST['course_id'] ) : 0;
		check_admin_referer( 'aswj_import_playlist_' . $course_id );

		$course = get_post( $course_id );
		if ( ! $course || 'aswj_course' !== $course->post_type ) {
			wp_die( esc_html__( 'Course not found.', 'aswj-lms' ) );
		}

		$playlist_input = isset( $_REQUEST['aswj_playlist'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['aswj_playlist'] ) ) : '';
		$playlist_id    = self::normalize_playlist_id( $playlist_input );
		$redirect       = get_edit_post_link( $course_id, 'raw' );

		if ( '' === $playlist_id ) {
			wp_safe_redirect( add_query_arg( 'aswj_import_error', 'bad_playlist', $redirect ) );
			exit;
		}

		update_post_meta( $course_id, '_aswj_playlist_id', $playlist_id );

		$videos = self::fetch_playlist_videos( $playlist_id );
		if ( is_wp_error( $videos ) ) {
			wp_safe_redirect( add_query_arg( 'aswj_import_error', $videos->get_error_code(), $redirect ) );
			exit;
		}

		$created = self::create_lessons( $course_id, $videos );

		wp_safe_redirect( add_query_arg( 'aswj_imported', $created, $redirect ) );
		exit;
	}

	/**
	 * @return array<int, array{id: string, title: string}>|WP_Error
	 */
	public static function fetch_playlist_videos( $playlist_id ) {
		$api_key = (string) ASWJ_LMS_Settings::get( 'youtube_api_key' );
		if ( '' !== $api_key ) {
			$videos = self::fetch_via_api( $playlist_id, $api_key );
			if ( ! is_wp_error( $videos ) ) {
				return $videos;
			}
			// Fall through to the scrape if the API failed.
		}
		return self::fetch_via_page( $playlist_id );
	}

	private static function fetch_via_api( $playlist_id, $api_key ) {
		$videos     = array();
		$page_token = '';
		$guard      = 0;

		do {
			$url = add_query_arg(
				array(
					'part'       => 'snippet',
					'maxResults' => 50,
					'playlistId' => rawurlencode( $playlist_id ),
					'key'        => rawurlencode( $api_key ),
					'pageToken'  => rawurlencode( $page_token ),
				),
				'https://www.googleapis.com/youtube/v3/playlistItems'
			);

			$response = wp_remote_get( $url, array( 'timeout' => 20 ) );
			if ( is_wp_error( $response ) ) {
				return new WP_Error( 'api_request_failed' );
			}
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) || ! is_array( $body ) ) {
				return new WP_Error( 'api_error' );
			}

			foreach ( (array) ( isset( $body['items'] ) ? $body['items'] : array() ) as $item ) {
				$snippet  = isset( $item['snippet'] ) ? $item['snippet'] : array();
				$video_id = isset( $snippet['resourceId']['videoId'] ) ? $snippet['resourceId']['videoId'] : '';
				$title    = isset( $snippet['title'] ) ? $snippet['title'] : '';
				// Skip deleted/private placeholders.
				if ( $video_id && $title && ! in_array( $title, array( 'Deleted video', 'Private video' ), true ) ) {
					$videos[] = array(
						'id'    => $video_id,
						'title' => $title,
					);
				}
			}

			$page_token = isset( $body['nextPageToken'] ) ? (string) $body['nextPageToken'] : '';
			$guard++;
		} while ( '' !== $page_token && $guard < 20 );

		return $videos;
	}

	private static function fetch_via_page( $playlist_id ) {
		$response = wp_remote_get(
			'https://www.youtube.com/playlist?list=' . rawurlencode( $playlist_id ) . '&hl=en',
			array(
				'timeout'    => 20,
				'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
			)
		);
		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'page_request_failed' );
		}
		$html = wp_remote_retrieve_body( $response );
		if ( '' === $html ) {
			return new WP_Error( 'page_empty' );
		}

		// Current YouTube markup: each playlist item is a lockupViewModel
		// containing the video title (lockupMetadataViewModel) and contentId.
		// The tempered pattern (?:(?!"lockupViewModel").)*? keeps each match
		// inside a single item so titles and IDs cannot be mispaired.
		preg_match_all(
			'/"lockupViewModel":\{(?:(?!"lockupViewModel").)*?"lockupMetadataViewModel":\{"title":\{"content":"((?:[^"\\\\]|\\\\.)*)"(?:(?!"lockupViewModel").)*?"contentId":"([A-Za-z0-9_-]{11})"/s',
			$html,
			$matches,
			PREG_SET_ORDER
		);
		$title_index = 1;
		$id_index    = 2;

		// Legacy markup (pre-2025 playlist pages): playlistVideoRenderer.
		if ( ! $matches ) {
			preg_match_all(
				'/"playlistVideoRenderer":\{"videoId":"([A-Za-z0-9_-]{11})".*?"title":\{"runs":\[\{"text":"((?:[^"\\\\]|\\\\.)*)"/s',
				$html,
				$matches,
				PREG_SET_ORDER
			);
			$title_index = 2;
			$id_index    = 1;
		}

		if ( ! $matches ) {
			return new WP_Error( 'page_parse_failed' );
		}

		$videos = array();
		$seen   = array();
		foreach ( $matches as $m ) {
			$video_id = $m[ $id_index ];
			if ( isset( $seen[ $video_id ] ) ) {
				continue;
			}
			$seen[ $video_id ] = true;
			$title             = json_decode( '"' . $m[ $title_index ] . '"' );
			$videos[]          = array(
				'id'    => $video_id,
				'title' => is_string( $title ) && '' !== $title ? $title : $video_id,
			);
		}
		return $videos;
	}

	/**
	 * Create lessons for videos not already in the course. Returns count created.
	 */
	public static function create_lessons( $course_id, array $videos ) {
		$existing_lessons = ASWJ_LMS_Post_Types::get_course_lessons( $course_id );
		$existing_ids     = array();
		$max_order        = 0;
		foreach ( $existing_lessons as $lesson ) {
			$vid = ASWJ_LMS_Video::get_lesson_video_id( $lesson->ID );
			if ( $vid ) {
				$existing_ids[ $vid ] = true;
			}
			$max_order = max( $max_order, (int) $lesson->menu_order );
		}

		$created = 0;
		foreach ( $videos as $video ) {
			if ( isset( $existing_ids[ $video['id'] ] ) ) {
				continue;
			}
			$max_order++;
			$lesson_id = wp_insert_post(
				array(
					'post_type'   => 'aswj_lesson',
					'post_title'  => wp_strip_all_tags( $video['title'] ),
					'post_status' => 'publish',
					'menu_order'  => $max_order,
				)
			);
			if ( $lesson_id && ! is_wp_error( $lesson_id ) ) {
				update_post_meta( $lesson_id, '_aswj_course_id', $course_id );
				update_post_meta( $lesson_id, '_aswj_youtube_id', $video['id'] );
				$created++;
			}
		}
		return $created;
	}
}
