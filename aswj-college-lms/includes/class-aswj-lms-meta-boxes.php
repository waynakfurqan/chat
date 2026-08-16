<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Course and Lesson edit-screen meta boxes.
 */
class ASWJ_LMS_Meta_Boxes {

	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'register' ) );
		add_action( 'save_post_aswj_course', array( __CLASS__, 'save_course' ) );
		add_action( 'save_post_aswj_lesson', array( __CLASS__, 'save_lesson' ) );
		add_action( 'admin_notices', array( __CLASS__, 'import_notices' ) );
	}

	public static function register() {
		add_meta_box( 'aswj_course_access', __( 'Course Access', 'aswj-lms' ), array( __CLASS__, 'render_course_box' ), 'aswj_course', 'side', 'high' );
		add_meta_box( 'aswj_course_playlist', __( 'Import Lessons from YouTube Playlist', 'aswj-lms' ), array( __CLASS__, 'render_playlist_box' ), 'aswj_course', 'normal', 'high' );
		add_meta_box( 'aswj_lesson_settings', __( 'Lesson Settings', 'aswj-lms' ), array( __CLASS__, 'render_lesson_box' ), 'aswj_lesson', 'side', 'high' );
	}

	public static function render_playlist_box( $post ) {
		$playlist_id = get_post_meta( $post->ID, '_aswj_playlist_id', true );
		$base_url    = wp_nonce_url(
			add_query_arg(
				array(
					'action'    => 'aswj_import_playlist',
					'course_id' => (int) $post->ID,
				),
				admin_url( 'admin-post.php' )
			),
			'aswj_import_playlist_' . $post->ID
		);
		$lesson_count = count( ASWJ_LMS_Post_Types::get_course_lessons( $post->ID ) );
		?>
		<p>
			<?php esc_html_e( 'Paste your Unlisted playlist link and every video becomes a lesson automatically, in playlist order. Re-import any time — videos already imported are skipped, so new playlist videos become new lessons.', 'aswj-lms' ); ?>
		</p>
		<p style="display:flex;gap:8px;align-items:center">
			<input type="text" id="aswj-playlist-input" value="<?php echo esc_attr( $playlist_id ); ?>" style="flex:1" placeholder="https://www.youtube.com/playlist?list=…" />
			<button type="button" class="button button-primary" id="aswj-playlist-import"><?php esc_html_e( 'Import Lessons', 'aswj-lms' ); ?></button>
		</p>
		<p class="description">
			<?php
			printf(
				/* translators: %d: current lesson count */
				esc_html__( 'This course currently has %d lesson(s). Save the course before importing so drafts are not lost.', 'aswj-lms' ),
				(int) $lesson_count
			);
			?>
		</p>
		<script>
		(function(){
			var btn = document.getElementById('aswj-playlist-import');
			if (!btn) { return; }
			btn.addEventListener('click', function () {
				var value = document.getElementById('aswj-playlist-input').value.trim();
				if (!value) { window.alert('<?php echo esc_js( __( 'Please paste a playlist link first.', 'aswj-lms' ) ); ?>'); return; }
				window.location = <?php echo wp_json_encode( $base_url ); ?> + '&aswj_playlist=' + encodeURIComponent(value);
			});
		})();
		</script>
		<?php
	}

	public static function import_notices() {
		if ( ! isset( $_GET['aswj_imported'] ) && ! isset( $_GET['aswj_import_error'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		if ( isset( $_GET['aswj_imported'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$count = absint( $_GET['aswj_imported'] ); // phpcs:ignore WordPress.Security.NonceVerification
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html(
					sprintf(
						/* translators: %d: number of lessons created */
						_n( '%d lesson imported from the playlist.', '%d lessons imported from the playlist.', $count, 'aswj-lms' ),
						$count
					)
				)
			);
			return;
		}
		$code     = sanitize_key( wp_unslash( $_GET['aswj_import_error'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$messages = array(
			'bad_playlist'        => __( 'That does not look like a YouTube playlist link. Please paste the full playlist URL.', 'aswj-lms' ),
			'api_request_failed'  => __( 'Could not reach the YouTube API. Please try again.', 'aswj-lms' ),
			'api_error'           => __( 'The YouTube API rejected the request — check your API key in ASWJ Courses → Settings.', 'aswj-lms' ),
			'page_request_failed' => __( 'Could not reach YouTube. Please try again.', 'aswj-lms' ),
			'page_empty'          => __( 'YouTube returned an empty page. Please try again.', 'aswj-lms' ),
			'page_parse_failed'   => __( 'Could not read the playlist without an API key. Add a free YouTube Data API key in ASWJ Courses → Settings and try again.', 'aswj-lms' ),
		);
		$message = isset( $messages[ $code ] ) ? $messages[ $code ] : __( 'The playlist import failed. Please try again.', 'aswj-lms' );
		printf( '<div class="notice notice-error is-dismissible"><p>%s</p></div>', esc_html( $message ) );
	}

	public static function render_course_box( $post ) {
		wp_nonce_field( 'aswj_course_meta', 'aswj_course_meta_nonce' );

		$type            = ASWJ_LMS_Access::get_access_type( $post->ID );
		$sisters_only    = ASWJ_LMS_Access::is_sisters_only( $post->ID );
		$price           = get_post_meta( $post->ID, '_aswj_price_label', true );
		$payment_form_id = get_post_meta( $post->ID, '_aswj_payment_form_id', true );
		$purchase_page   = get_post_meta( $post->ID, '_aswj_purchase_page_id', true );

		$types = array(
			'free'         => __( 'Free — open to everyone', 'aswj-lms' ),
			'paid'         => __( 'Paid — one-time payment', 'aswj-lms' ),
			'subscription' => __( 'Subscription — active subscribers', 'aswj-lms' ),
			'diploma'      => __( 'Diploma Program students only', 'aswj-lms' ),
		);
		?>
		<p>
			<label for="aswj_access_type"><strong><?php esc_html_e( 'Access type', 'aswj-lms' ); ?></strong></label><br />
			<select name="aswj_access_type" id="aswj_access_type" style="width:100%">
				<?php foreach ( $types as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $type, $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label>
				<input type="checkbox" name="aswj_sisters_only" value="1" <?php checked( $sisters_only ); ?> />
				<?php esc_html_e( 'Sisters only (restrict to sister accounts)', 'aswj-lms' ); ?>
			</label>
		</p>
		<p>
			<label for="aswj_price_label"><strong><?php esc_html_e( 'Price label (display only)', 'aswj-lms' ); ?></strong></label>
			<input type="text" name="aswj_price_label" id="aswj_price_label" value="<?php echo esc_attr( $price ); ?>" style="width:100%" placeholder="<?php esc_attr_e( 'e.g. $49 AUD or $10/month', 'aswj-lms' ); ?>" />
		</p>
		<p>
			<label for="aswj_payment_form_id"><strong><?php esc_html_e( 'Fluent Forms payment form ID', 'aswj-lms' ); ?></strong></label>
			<input type="number" name="aswj_payment_form_id" id="aswj_payment_form_id" value="<?php echo esc_attr( $payment_form_id ); ?>" style="width:100%" min="0" />
			<span class="description"><?php esc_html_e( 'A paid payment on this form enrolls the student in this course.', 'aswj-lms' ); ?></span>
		</p>
		<p>
			<label for="aswj_purchase_page_id"><strong><?php esc_html_e( 'Purchase page', 'aswj-lms' ); ?></strong></label>
			<?php
			wp_dropdown_pages(
				array(
					'name'              => 'aswj_purchase_page_id',
					'id'                => 'aswj_purchase_page_id',
					'selected'          => (int) $purchase_page,
					'show_option_none'  => __( '— None —', 'aswj-lms' ),
					'option_none_value' => '0',
				)
			);
			?>
			<span class="description"><?php esc_html_e( 'The page containing the payment form. "Enroll Now" buttons link here.', 'aswj-lms' ); ?></span>
		</p>
		<?php
	}

	public static function save_course( $post_id ) {
		if ( ! isset( $_POST['aswj_course_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['aswj_course_meta_nonce'] ), 'aswj_course_meta' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$type = isset( $_POST['aswj_access_type'] ) ? sanitize_key( $_POST['aswj_access_type'] ) : 'free';
		if ( ! in_array( $type, ASWJ_LMS_Access::TYPES, true ) ) {
			$type = 'free';
		}
		update_post_meta( $post_id, '_aswj_access_type', $type );
		update_post_meta( $post_id, '_aswj_sisters_only', isset( $_POST['aswj_sisters_only'] ) ? '1' : '0' );
		update_post_meta( $post_id, '_aswj_price_label', isset( $_POST['aswj_price_label'] ) ? sanitize_text_field( wp_unslash( $_POST['aswj_price_label'] ) ) : '' );
		update_post_meta( $post_id, '_aswj_payment_form_id', isset( $_POST['aswj_payment_form_id'] ) ? absint( $_POST['aswj_payment_form_id'] ) : 0 );
		update_post_meta( $post_id, '_aswj_purchase_page_id', isset( $_POST['aswj_purchase_page_id'] ) ? absint( $_POST['aswj_purchase_page_id'] ) : 0 );
	}

	public static function render_lesson_box( $post ) {
		wp_nonce_field( 'aswj_lesson_meta', 'aswj_lesson_meta_nonce' );

		$course_id = ASWJ_LMS_Post_Types::get_lesson_course_id( $post->ID );
		$video_id  = ASWJ_LMS_Video::get_lesson_video_id( $post->ID );

		$courses = get_posts(
			array(
				'post_type'      => 'aswj_course',
				'posts_per_page' => -1,
				'post_status'    => array( 'publish', 'draft', 'private' ),
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
		?>
		<p>
			<label for="aswj_course_id"><strong><?php esc_html_e( 'Course', 'aswj-lms' ); ?></strong></label><br />
			<select name="aswj_course_id" id="aswj_course_id" style="width:100%">
				<option value="0"><?php esc_html_e( '— Select a course —', 'aswj-lms' ); ?></option>
				<?php foreach ( $courses as $course ) : ?>
					<option value="<?php echo (int) $course->ID; ?>" <?php selected( $course_id, $course->ID ); ?>><?php echo esc_html( $course->post_title ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="aswj_youtube"><strong><?php esc_html_e( 'YouTube video URL or ID', 'aswj-lms' ); ?></strong></label>
			<input type="text" name="aswj_youtube" id="aswj_youtube" value="<?php echo esc_attr( $video_id ); ?>" style="width:100%" placeholder="https://youtu.be/…" />
			<span class="description"><?php esc_html_e( 'Use an Unlisted video. The player only appears to students with course access.', 'aswj-lms' ); ?></span>
		</p>
		<p class="description"><?php esc_html_e( 'Tip: set lesson order with the "Order" field in the Page Attributes box.', 'aswj-lms' ); ?></p>
		<?php
	}

	public static function save_lesson( $post_id ) {
		if ( ! isset( $_POST['aswj_lesson_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['aswj_lesson_meta_nonce'] ), 'aswj_lesson_meta' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		update_post_meta( $post_id, '_aswj_course_id', isset( $_POST['aswj_course_id'] ) ? absint( $_POST['aswj_course_id'] ) : 0 );

		$raw_video = isset( $_POST['aswj_youtube'] ) ? sanitize_text_field( wp_unslash( $_POST['aswj_youtube'] ) ) : '';
		update_post_meta( $post_id, '_aswj_youtube_id', ASWJ_LMS_Video::normalize_youtube_id( $raw_video ) );
	}
}
