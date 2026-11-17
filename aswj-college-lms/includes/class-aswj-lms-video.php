<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gated YouTube playback.
 *
 * The video ID is never printed in the page HTML. The page renders a
 * placeholder; the player iframe is fetched via an authenticated AJAX
 * call that re-checks course access on the server. This deters casual
 * link-sharing (a determined user with devtools can still find the ID —
 * that is a YouTube platform limitation).
 */
class ASWJ_LMS_Video {

	/**
	 * Normalize any YouTube URL or raw ID into a video ID.
	 *
	 * @return string Empty string when unparseable.
	 */
	public static function normalize_youtube_id( $input ) {
		$input = trim( (string) $input );
		if ( '' === $input ) {
			return '';
		}
		// Already a bare ID.
		if ( preg_match( '/^[A-Za-z0-9_-]{11}$/', $input ) ) {
			return $input;
		}
		$patterns = array(
			'/youtu\.be\/([A-Za-z0-9_-]{11})/',
			'/[?&]v=([A-Za-z0-9_-]{11})/',
			'/embed\/([A-Za-z0-9_-]{11})/',
			'/shorts\/([A-Za-z0-9_-]{11})/',
			'/live\/([A-Za-z0-9_-]{11})/',
		);
		foreach ( $patterns as $pattern ) {
			if ( preg_match( $pattern, $input, $m ) ) {
				return $m[1];
			}
		}
		return '';
	}

	public static function get_lesson_video_id( $lesson_id ) {
		return (string) get_post_meta( $lesson_id, '_aswj_youtube_id', true );
	}

	/**
	 * Placeholder markup printed on the lesson page. No video ID inside.
	 */
	public static function placeholder( $lesson_id ) {
		$has_video = '' !== self::get_lesson_video_id( $lesson_id );
		if ( ! $has_video ) {
			return '<div class="aswj-video-empty">' . esc_html__( 'No video has been added to this lesson yet.', 'aswj-lms' ) . '</div>';
		}
		return sprintf(
			'<div class="aswj-video-frame" data-aswj-video-lesson="%d">
				<div class="aswj-video-loading"><span class="aswj-spinner"></span><p>%s</p></div>
			</div>',
			(int) $lesson_id,
			esc_html__( 'Loading video…', 'aswj-lms' )
		);
	}

	/**
	 * Privacy-enhanced embed HTML, only built after access has been verified.
	 */
	public static function embed_html( $lesson_id ) {
		$video_id = self::get_lesson_video_id( $lesson_id );
		if ( '' === $video_id ) {
			return '';
		}

		$src = add_query_arg(
			array(
				'rel'            => 0,
				'modestbranding' => 1,
				'playsinline'    => 1,
				'iv_load_policy' => 3,
				'origin'         => rawurlencode( home_url() ),
			),
			'https://www.youtube-nocookie.com/embed/' . rawurlencode( $video_id )
		);

		return sprintf(
			'<div class="aswj-video-player">
				<iframe src="%s" title="%s" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen referrerpolicy="strict-origin"></iframe>
				<div class="aswj-video-shield" aria-hidden="true"></div>
			</div>',
			esc_url( $src ),
			esc_attr( get_the_title( $lesson_id ) )
		);
	}
}
