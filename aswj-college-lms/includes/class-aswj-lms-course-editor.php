<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The "easy" course creator: one dashboard form that collects everything a
 * course needs (details, poster, access, price, playlist) and then builds
 * the course page, imports the lessons and creates the enrollment page.
 *
 * Replaces the default WordPress editor flow: "Add New Course" links and
 * post-new.php for this post type land here instead.
 */
class ASWJ_LMS_Course_Editor {

	const PAGE_SLUG = 'aswj-course-editor';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 9 );
		add_action( 'admin_post_aswj_save_course_easy', array( __CLASS__, 'handle_save' ) );
		add_action( 'load-post-new.php', array( __CLASS__, 'redirect_post_new' ) );
		add_filter( 'post_row_actions', array( __CLASS__, 'row_action' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
	}

	public static function menu() {
		add_submenu_page(
			'edit.php?post_type=aswj_course',
			__( 'Add New Course', 'aswj-lms' ),
			__( 'Add New Course', 'aswj-lms' ),
			'manage_options',
			self::PAGE_SLUG,
			array( __CLASS__, 'render' )
		);
	}

	public static function url( $course_id = 0 ) {
		$url = admin_url( 'edit.php?post_type=aswj_course&page=' . self::PAGE_SLUG );
		return $course_id ? add_query_arg( 'course_id', (int) $course_id, $url ) : $url;
	}

	/**
	 * Send the default "Add New" editor to the easy form instead.
	 */
	public static function redirect_post_new() {
		$type = isset( $_GET['post_type'] ) ? sanitize_key( $_GET['post_type'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		if ( 'aswj_course' === $type && empty( $_GET['aswj_classic'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			wp_safe_redirect( self::url() );
			exit;
		}
	}

	public static function row_action( $actions, $post ) {
		if ( 'aswj_course' === $post->post_type && current_user_can( 'manage_options' ) ) {
			$actions = array_merge(
				array(
					'aswj_easy' => '<a href="' . esc_url( self::url( $post->ID ) ) . '"><strong>' . esc_html__( 'Course Editor', 'aswj-lms' ) . '</strong></a>',
				),
				$actions
			);
		}
		return $actions;
	}

	public static function assets( $hook ) {
		if ( false === strpos( $hook, self::PAGE_SLUG ) ) {
			return;
		}
		wp_enqueue_media();
	}

	public static function render() {
		$course_id = isset( $_GET['course_id'] ) ? absint( $_GET['course_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		$course    = $course_id ? get_post( $course_id ) : null;
		if ( $course && 'aswj_course' !== $course->post_type ) {
			$course    = null;
			$course_id = 0;
		}

		$meta = function ( $key, $default = '' ) use ( $course_id ) {
			if ( ! $course_id ) {
				return $default;
			}
			$value = get_post_meta( $course_id, $key, true );
			return '' === $value ? $default : $value;
		};

		$type         = $course_id ? ASWJ_LMS_Access::get_access_type( $course_id ) : 'free';
		$thumbnail_id = $course_id ? (int) get_post_thumbnail_id( $course_id ) : 0;
		$lesson_count = $course_id ? count( ASWJ_LMS_Post_Types::get_course_lessons( $course_id ) ) : 0;

		$types = array(
			'free'         => __( 'Free — open to everyone', 'aswj-lms' ),
			'paid'         => __( 'Paid — one-time payment', 'aswj-lms' ),
			'subscription' => __( 'Subscription — all-access subscribers', 'aswj-lms' ),
			'diploma'      => __( 'Diploma Program students only', 'aswj-lms' ),
		);
		?>
		<div class="wrap aswj-course-editor">
			<h1><?php echo $course_id ? esc_html__( 'Edit Course', 'aswj-lms' ) : esc_html__( 'Add New Course', 'aswj-lms' ); ?></h1>

			<?php self::notices(); ?>

			<?php if ( $course_id ) : ?>
				<p>
					<a href="<?php echo esc_url( get_permalink( $course_id ) ); ?>" target="_blank"><?php esc_html_e( 'View course page', 'aswj-lms' ); ?></a>
					&middot; <?php printf( /* translators: %d: lesson count */ esc_html( _n( '%d lesson', '%d lessons', $lesson_count, 'aswj-lms' ) ), (int) $lesson_count ); ?>
					&middot; <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=aswj_lesson' ) ); ?>"><?php esc_html_e( 'manage lessons', 'aswj-lms' ); ?></a>
				</p>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="aswj_save_course_easy" />
				<input type="hidden" name="course_id" value="<?php echo (int) $course_id; ?>" />
				<?php wp_nonce_field( 'aswj_save_course_easy' ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="aswj_title"><?php esc_html_e( 'Course title', 'aswj-lms' ); ?> *</label></th>
						<td><input type="text" name="aswj_title" id="aswj_title" class="large-text" required value="<?php echo esc_attr( $course ? $course->post_title : '' ); ?>" placeholder="<?php esc_attr_e( 'e.g. The Forty Hadith of Imam an-Nawawi', 'aswj-lms' ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="aswj_slug"><?php esc_html_e( 'Page link (slug)', 'aswj-lms' ); ?></label></th>
						<td>
							<code><?php echo esc_html( home_url( '/courses/' ) ); ?></code>
							<input type="text" name="aswj_slug" id="aswj_slug" value="<?php echo esc_attr( $course ? $course->post_name : '' ); ?>" placeholder="<?php esc_attr_e( 'left blank = made from the title', 'aswj-lms' ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="aswj_arabic_title"><?php esc_html_e( 'Arabic title (cover)', 'aswj-lms' ); ?></label></th>
						<td>
							<input type="text" name="aswj_arabic_title" id="aswj_arabic_title" class="regular-text" dir="rtl" lang="ar" value="<?php echo esc_attr( $meta( '_aswj_arabic_title' ) ); ?>" placeholder="الأربعون النووية" />
							<p class="description"><?php esc_html_e( 'Shown as the typeset course cover when no poster image is set — e.g. the Arabic title of the classical text.', 'aswj-lms' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="aswj_tagline"><?php esc_html_e( 'Short tagline', 'aswj-lms' ); ?></label></th>
						<td><input type="text" name="aswj_tagline" id="aswj_tagline" class="large-text" value="<?php echo esc_attr( $course ? $course->post_excerpt : '' ); ?>" placeholder="<?php esc_attr_e( 'One sentence shown on course cards and the course header.', 'aswj-lms' ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="aswj_description"><?php esc_html_e( 'Description', 'aswj-lms' ); ?></label></th>
						<td>
							<?php
							wp_editor(
								$course ? $course->post_content : '',
								'aswj_description',
								array(
									'textarea_name' => 'aswj_description',
									'textarea_rows' => 8,
									'media_buttons' => false,
								)
							);
							?>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Poster image', 'aswj-lms' ); ?></th>
						<td>
							<div id="aswj-poster-preview" style="margin-bottom:8px;<?php echo $thumbnail_id ? '' : 'display:none;'; ?>">
								<?php
								if ( $thumbnail_id ) {
									echo wp_get_attachment_image( $thumbnail_id, 'medium' );
								}
								?>
							</div>
							<input type="hidden" name="aswj_thumbnail_id" id="aswj_thumbnail_id" value="<?php echo (int) $thumbnail_id; ?>" />
							<button type="button" class="button" id="aswj-poster-pick"><?php esc_html_e( 'Choose image', 'aswj-lms' ); ?></button>
							<button type="button" class="button" id="aswj-poster-remove" <?php echo $thumbnail_id ? '' : 'style="display:none"'; ?>><?php esc_html_e( 'Remove', 'aswj-lms' ); ?></button>
							<p class="description"><?php esc_html_e( 'Optional. Without a poster, a typeset Arabic cover is generated automatically.', 'aswj-lms' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="aswj_access_type"><?php esc_html_e( 'Access type', 'aswj-lms' ); ?></label></th>
						<td>
							<select name="aswj_access_type" id="aswj_access_type">
								<?php foreach ( $types as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $type, $value ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Options', 'aswj-lms' ); ?></th>
						<td>
							<label style="display:block;margin-bottom:6px"><input type="checkbox" name="aswj_sisters_only" value="1" <?php checked( '1' === $meta( '_aswj_sisters_only', '0' ) ); ?> /> <?php esc_html_e( 'Sisters only', 'aswj-lms' ); ?></label>
							<label style="display:block"><input type="checkbox" name="aswj_running" value="1" <?php checked( '1' === $meta( '_aswj_running', '0' ) ); ?> /> <?php esc_html_e( 'Currently running (show in the home page "Current Courses" section)', 'aswj-lms' ); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="aswj_price_label"><?php esc_html_e( 'Price label', 'aswj-lms' ); ?></label></th>
						<td><input type="text" name="aswj_price_label" id="aswj_price_label" value="<?php echo esc_attr( $meta( '_aswj_price_label' ) ); ?>" placeholder="<?php esc_attr_e( 'e.g. A$50', 'aswj-lms' ); ?>" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="aswj_payment_form_id"><?php esc_html_e( 'Fluent Forms payment form ID', 'aswj-lms' ); ?></label></th>
						<td>
							<input type="number" min="0" name="aswj_payment_form_id" id="aswj_payment_form_id" class="small-text" value="<?php echo esc_attr( $meta( '_aswj_payment_form_id', '0' ) ); ?>" />
							<p class="description"><?php esc_html_e( 'For paid courses. The enrollment page is created automatically when this is set.', 'aswj-lms' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="aswj_playlist"><?php esc_html_e( 'YouTube playlist link', 'aswj-lms' ); ?></label></th>
						<td>
							<input type="url" name="aswj_playlist" id="aswj_playlist" class="large-text" value="<?php echo esc_attr( $meta( '_aswj_playlist_id' ) ? 'https://www.youtube.com/playlist?list=' . $meta( '_aswj_playlist_id' ) : '' ); ?>" placeholder="https://www.youtube.com/playlist?list=…" />
							<p class="description"><?php esc_html_e( 'Unlisted playlist. Every video becomes a lesson automatically, in order. Saving again later imports only the new videos.', 'aswj-lms' ); ?></p>
						</td>
					</tr>
				</table>

				<?php submit_button( $course_id ? __( 'Save Course', 'aswj-lms' ) : __( 'Create Course', 'aswj-lms' ) ); ?>
			</form>
		</div>
		<script>
		jQuery(function ($) {
			var frame;
			$('#aswj-poster-pick').on('click', function (e) {
				e.preventDefault();
				if (!frame) {
					frame = wp.media({ title: '<?php echo esc_js( __( 'Choose a poster image', 'aswj-lms' ) ); ?>', multiple: false, library: { type: 'image' } });
					frame.on('select', function () {
						var att = frame.state().get('selection').first().toJSON();
						$('#aswj_thumbnail_id').val(att.id);
						var url = (att.sizes && att.sizes.medium) ? att.sizes.medium.url : att.url;
						$('#aswj-poster-preview').html('<img src="' + url + '" style="max-width:300px;height:auto" />').show();
						$('#aswj-poster-remove').show();
					});
				}
				frame.open();
			});
			$('#aswj-poster-remove').on('click', function () {
				$('#aswj_thumbnail_id').val('0');
				$('#aswj-poster-preview').hide().empty();
				$(this).hide();
			});
		});
		</script>
		<?php
	}

	private static function notices() {
		// phpcs:disable WordPress.Security.NonceVerification
		if ( isset( $_GET['saved'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Course saved — the course page is live.', 'aswj-lms' ) . '</p></div>';
		}
		if ( isset( $_GET['imported'] ) ) {
			$count = absint( $_GET['imported'] );
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
		}
		if ( isset( $_GET['enroll_page'] ) ) {
			$page_id = absint( $_GET['enroll_page'] );
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s <a href="%s" target="_blank">%s</a></p></div>',
				esc_html__( 'Enrollment page created:', 'aswj-lms' ),
				esc_url( get_permalink( $page_id ) ),
				esc_html( get_permalink( $page_id ) )
			);
		}
		if ( isset( $_GET['import_error'] ) ) {
			$code     = sanitize_key( wp_unslash( $_GET['import_error'] ) );
			$messages = array(
				'bad_playlist'        => __( 'The course was saved, but that does not look like a YouTube playlist link — paste the full playlist URL and save again.', 'aswj-lms' ),
				'api_error'           => __( 'The course was saved, but the YouTube API rejected the playlist request — check your API key in Settings and save again.', 'aswj-lms' ),
				'page_parse_failed'   => __( 'The course was saved, but the playlist could not be read. Add a free YouTube Data API key in Settings and save again.', 'aswj-lms' ),
				'api_request_failed'  => __( 'The course was saved, but YouTube could not be reached. Save again to retry the import.', 'aswj-lms' ),
				'page_request_failed' => __( 'The course was saved, but YouTube could not be reached. Save again to retry the import.', 'aswj-lms' ),
				'page_empty'          => __( 'The course was saved, but YouTube returned an empty page. Save again to retry the import.', 'aswj-lms' ),
			);
			$message = isset( $messages[ $code ] ) ? $messages[ $code ] : __( 'The course was saved, but the playlist import failed. Save again to retry.', 'aswj-lms' );
			printf( '<div class="notice notice-warning is-dismissible"><p>%s</p></div>', esc_html( $message ) );
		}
		// phpcs:enable
	}

	public static function handle_save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'aswj-lms' ) );
		}
		check_admin_referer( 'aswj_save_course_easy' );

		$course_id = isset( $_POST['course_id'] ) ? absint( $_POST['course_id'] ) : 0;
		$title     = isset( $_POST['aswj_title'] ) ? sanitize_text_field( wp_unslash( $_POST['aswj_title'] ) ) : '';
		if ( '' === $title ) {
			wp_safe_redirect( self::url( $course_id ) );
			exit;
		}

		$postarr = array(
			'post_type'    => 'aswj_course',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_excerpt' => isset( $_POST['aswj_tagline'] ) ? sanitize_text_field( wp_unslash( $_POST['aswj_tagline'] ) ) : '',
			'post_content' => isset( $_POST['aswj_description'] ) ? wp_kses_post( wp_unslash( $_POST['aswj_description'] ) ) : '',
		);
		$slug    = isset( $_POST['aswj_slug'] ) ? sanitize_title( wp_unslash( $_POST['aswj_slug'] ) ) : '';
		if ( $slug ) {
			$postarr['post_name'] = $slug;
		}

		if ( $course_id ) {
			$postarr['ID'] = $course_id;
			wp_update_post( $postarr );
		} else {
			$course_id = wp_insert_post( $postarr );
			if ( ! $course_id || is_wp_error( $course_id ) ) {
				wp_die( esc_html__( 'The course could not be created.', 'aswj-lms' ) );
			}
		}

		// Poster image.
		$thumbnail_id = isset( $_POST['aswj_thumbnail_id'] ) ? absint( $_POST['aswj_thumbnail_id'] ) : 0;
		if ( $thumbnail_id ) {
			set_post_thumbnail( $course_id, $thumbnail_id );
		} else {
			delete_post_thumbnail( $course_id );
		}

		// Access + options.
		$type = isset( $_POST['aswj_access_type'] ) ? sanitize_key( $_POST['aswj_access_type'] ) : 'free';
		if ( ! in_array( $type, ASWJ_LMS_Access::TYPES, true ) ) {
			$type = 'free';
		}
		update_post_meta( $course_id, '_aswj_access_type', $type );
		update_post_meta( $course_id, '_aswj_sisters_only', isset( $_POST['aswj_sisters_only'] ) ? '1' : '0' );
		update_post_meta( $course_id, '_aswj_running', isset( $_POST['aswj_running'] ) ? '1' : '0' );
		update_post_meta( $course_id, '_aswj_price_label', isset( $_POST['aswj_price_label'] ) ? sanitize_text_field( wp_unslash( $_POST['aswj_price_label'] ) ) : '' );
		update_post_meta( $course_id, '_aswj_arabic_title', isset( $_POST['aswj_arabic_title'] ) ? sanitize_text_field( wp_unslash( $_POST['aswj_arabic_title'] ) ) : '' );

		$redirect = add_query_arg( 'saved', '1', self::url( $course_id ) );

		// Payment form → auto enrollment page.
		$payment_form_id = isset( $_POST['aswj_payment_form_id'] ) ? absint( $_POST['aswj_payment_form_id'] ) : 0;
		update_post_meta( $course_id, '_aswj_payment_form_id', $payment_form_id );
		if ( $payment_form_id && ! (int) get_post_meta( $course_id, '_aswj_purchase_page_id', true ) ) {
			$page_id = ASWJ_LMS_Meta_Boxes::create_enroll_page( $course_id );
			update_post_meta( $course_id, '_aswj_purchase_page_id', $page_id );
			if ( $page_id ) {
				$redirect = add_query_arg( 'enroll_page', $page_id, $redirect );
			}
		}

		// Playlist → lessons.
		$playlist_input = isset( $_POST['aswj_playlist'] ) ? sanitize_text_field( wp_unslash( $_POST['aswj_playlist'] ) ) : '';
		if ( '' !== $playlist_input ) {
			$playlist_id = ASWJ_LMS_Playlist::normalize_playlist_id( $playlist_input );
			if ( '' === $playlist_id ) {
				$redirect = add_query_arg( 'import_error', 'bad_playlist', $redirect );
			} else {
				update_post_meta( $course_id, '_aswj_playlist_id', $playlist_id );
				$videos = ASWJ_LMS_Playlist::fetch_playlist_videos( $playlist_id );
				if ( is_wp_error( $videos ) ) {
					$redirect = add_query_arg( 'import_error', $videos->get_error_code(), $redirect );
				} else {
					$created  = ASWJ_LMS_Playlist::create_lessons( $course_id, $videos );
					$redirect = add_query_arg( 'imported', $created, $redirect );
				}
			}
		}

		wp_safe_redirect( $redirect );
		exit;
	}
}
