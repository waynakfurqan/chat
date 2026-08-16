<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin screens: LMS Settings and Students (groups + manual enrollment).
 */
class ASWJ_LMS_Admin {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_aswj_save_settings', array( __CLASS__, 'save_settings' ) );
		add_action( 'admin_post_aswj_update_student', array( __CLASS__, 'update_student' ) );
	}

	public static function menu() {
		add_submenu_page(
			'edit.php?post_type=aswj_course',
			__( 'Students', 'aswj-lms' ),
			__( 'Students', 'aswj-lms' ),
			'manage_options',
			'aswj-students',
			array( __CLASS__, 'render_students' )
		);
		add_submenu_page(
			'edit.php?post_type=aswj_course',
			__( 'LMS Settings', 'aswj-lms' ),
			__( 'Settings', 'aswj-lms' ),
			'manage_options',
			'aswj-settings',
			array( __CLASS__, 'render_settings' )
		);
	}

	/* ------------------------------------------------------------------ */
	/* Settings                                                            */
	/* ------------------------------------------------------------------ */

	public static function render_settings() {
		$s = ASWJ_LMS_Settings::all();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'ASWJ College LMS — Settings', 'aswj-lms' ); ?></h1>
			<?php if ( isset( $_GET['updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'aswj-lms' ); ?></p></div>
			<?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="aswj_save_settings" />
				<?php wp_nonce_field( 'aswj_save_settings' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="portal_page_id"><?php esc_html_e( 'Student Portal page', 'aswj-lms' ); ?></label></th>
						<td>
							<?php self::pages_dropdown( 'portal_page_id', (int) $s['portal_page_id'] ); ?>
							<p class="description"><?php esc_html_e( 'The page containing the [aswj_portal] shortcode.', 'aswj-lms' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="catalog_page_id"><?php esc_html_e( 'Course Catalog page', 'aswj-lms' ); ?></label></th>
						<td>
							<?php self::pages_dropdown( 'catalog_page_id', (int) $s['catalog_page_id'] ); ?>
							<p class="description"><?php esc_html_e( 'The page containing the [aswj_courses] shortcode.', 'aswj-lms' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="registration_page_id"><?php esc_html_e( 'Registration page', 'aswj-lms' ); ?></label></th>
						<td>
							<?php self::pages_dropdown( 'registration_page_id', (int) $s['registration_page_id'] ); ?>
							<p class="description"><?php esc_html_e( 'The page containing your Fluent Forms registration form.', 'aswj-lms' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="registration_form_id"><?php esc_html_e( 'Registration form ID', 'aswj-lms' ); ?></label></th>
						<td>
							<input type="number" min="0" name="registration_form_id" id="registration_form_id" value="<?php echo esc_attr( $s['registration_form_id'] ); ?>" class="small-text" />
							<p class="description"><?php esc_html_e( 'The Fluent Forms form (with a User Registration feed) used for sign-ups.', 'aswj-lms' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="gender_field_name"><?php esc_html_e( 'Gender field name', 'aswj-lms' ); ?></label></th>
						<td>
							<input type="text" name="gender_field_name" id="gender_field_name" value="<?php echo esc_attr( $s['gender_field_name'] ); ?>" class="regular-text" />
							<p class="description"><?php esc_html_e( 'The "Name attribute" of the gender field on your registration form (default: gender).', 'aswj-lms' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="sister_field_value"><?php esc_html_e( 'Sister field value', 'aswj-lms' ); ?></label></th>
						<td>
							<input type="text" name="sister_field_value" id="sister_field_value" value="<?php echo esc_attr( $s['sister_field_value'] ); ?>" class="regular-text" />
							<p class="description"><?php esc_html_e( 'When the gender field contains this value (e.g. female), the account is flagged as a sister.', 'aswj-lms' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="phone_field_name"><?php esc_html_e( 'Phone field name', 'aswj-lms' ); ?></label></th>
						<td>
							<input type="text" name="phone_field_name" id="phone_field_name" value="<?php echo esc_attr( $s['phone_field_name'] ); ?>" class="regular-text" />
							<p class="description"><?php esc_html_e( 'Name attribute of the phone field on your forms (default: phone). Saved to the student profile and auto-filled on future forms.', 'aswj-lms' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="age_field_name"><?php esc_html_e( 'Age field name', 'aswj-lms' ); ?></label></th>
						<td>
							<input type="text" name="age_field_name" id="age_field_name" value="<?php echo esc_attr( $s['age_field_name'] ); ?>" class="regular-text" />
							<p class="description"><?php esc_html_e( 'Name attribute of the age field on your forms (default: age).', 'aswj-lms' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="youtube_api_key"><?php esc_html_e( 'YouTube Data API key', 'aswj-lms' ); ?></label></th>
						<td>
							<input type="text" name="youtube_api_key" id="youtube_api_key" value="<?php echo esc_attr( $s['youtube_api_key'] ); ?>" class="regular-text" autocomplete="off" />
							<p class="description"><?php esc_html_e( 'Optional but recommended for the playlist import (free — see SETUP.md). Without a key the plugin falls back to reading the public playlist page.', 'aswj-lms' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="subscription_form_ids"><?php esc_html_e( 'Subscription form IDs', 'aswj-lms' ); ?></label></th>
						<td>
							<input type="text" name="subscription_form_ids" id="subscription_form_ids" value="<?php echo esc_attr( $s['subscription_form_ids'] ); ?>" class="regular-text" placeholder="e.g. 12, 15" />
							<p class="description"><?php esc_html_e( 'Comma-separated Fluent Forms form IDs. An active subscription paid via these forms unlocks ALL paid and subscription courses (all-access).', 'aswj-lms' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="subscribe_page_id"><?php esc_html_e( 'Subscribe page', 'aswj-lms' ); ?></label></th>
						<td>
							<?php self::pages_dropdown( 'subscribe_page_id', (int) $s['subscribe_page_id'] ); ?>
							<p class="description"><?php esc_html_e( 'The page containing your monthly subscription form. Locked paid courses will offer this as an option.', 'aswj-lms' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="contact_email"><?php esc_html_e( 'Student contact email', 'aswj-lms' ); ?></label></th>
						<td>
							<input type="email" name="contact_email" id="contact_email" value="<?php echo esc_attr( $s['contact_email'] ); ?>" class="regular-text" />
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Save Settings', 'aswj-lms' ) ); ?>
			</form>
		</div>
		<?php
	}

	private static function pages_dropdown( $name, $selected ) {
		wp_dropdown_pages(
			array(
				'name'              => $name,
				'id'                => $name,
				'selected'          => $selected,
				'show_option_none'  => __( '— Select —', 'aswj-lms' ),
				'option_none_value' => '0',
			)
		);
	}

	public static function save_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'aswj-lms' ) );
		}
		check_admin_referer( 'aswj_save_settings' );

		ASWJ_LMS_Settings::update(
			array(
				'portal_page_id'        => isset( $_POST['portal_page_id'] ) ? absint( $_POST['portal_page_id'] ) : 0,
				'catalog_page_id'       => isset( $_POST['catalog_page_id'] ) ? absint( $_POST['catalog_page_id'] ) : 0,
				'registration_page_id'  => isset( $_POST['registration_page_id'] ) ? absint( $_POST['registration_page_id'] ) : 0,
				'registration_form_id'  => isset( $_POST['registration_form_id'] ) ? absint( $_POST['registration_form_id'] ) : 0,
				'gender_field_name'     => isset( $_POST['gender_field_name'] ) ? sanitize_text_field( wp_unslash( $_POST['gender_field_name'] ) ) : 'gender',
				'sister_field_value'    => isset( $_POST['sister_field_value'] ) ? sanitize_text_field( wp_unslash( $_POST['sister_field_value'] ) ) : 'female',
				'phone_field_name'      => isset( $_POST['phone_field_name'] ) ? sanitize_text_field( wp_unslash( $_POST['phone_field_name'] ) ) : 'phone',
				'age_field_name'        => isset( $_POST['age_field_name'] ) ? sanitize_text_field( wp_unslash( $_POST['age_field_name'] ) ) : 'age',
				'youtube_api_key'       => isset( $_POST['youtube_api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['youtube_api_key'] ) ) : '',
				'subscription_form_ids' => isset( $_POST['subscription_form_ids'] ) ? sanitize_text_field( wp_unslash( $_POST['subscription_form_ids'] ) ) : '',
				'subscribe_page_id'     => isset( $_POST['subscribe_page_id'] ) ? absint( $_POST['subscribe_page_id'] ) : 0,
				'contact_email'         => isset( $_POST['contact_email'] ) ? sanitize_email( wp_unslash( $_POST['contact_email'] ) ) : '',
			)
		);

		wp_safe_redirect( add_query_arg( 'updated', '1', admin_url( 'edit.php?post_type=aswj_course&page=aswj-settings' ) ) );
		exit;
	}

	/* ------------------------------------------------------------------ */
	/* Students                                                            */
	/* ------------------------------------------------------------------ */

	public static function render_students() {
		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$paged  = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification
		$per    = 30;

		$query = new WP_User_Query(
			array(
				'number'  => $per,
				'offset'  => ( $paged - 1 ) * $per,
				'search'  => $search ? '*' . $search . '*' : '',
				'orderby' => 'registered',
				'order'   => 'DESC',
			)
		);
		$users = $query->get_results();
		$total = $query->get_total();

		$courses = get_posts(
			array(
				'post_type'      => 'aswj_course',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Students', 'aswj-lms' ); ?></h1>
			<?php if ( isset( $_GET['updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Student updated.', 'aswj-lms' ); ?></p></div>
			<?php endif; ?>

			<form method="get">
				<input type="hidden" name="post_type" value="aswj_course" />
				<input type="hidden" name="page" value="aswj-students" />
				<p class="search-box">
					<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search students…', 'aswj-lms' ); ?>" />
					<?php submit_button( __( 'Search', 'aswj-lms' ), 'secondary', '', false ); ?>
				</p>
			</form>

			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Student', 'aswj-lms' ); ?></th>
						<th><?php esc_html_e( 'Groups', 'aswj-lms' ); ?></th>
						<th><?php esc_html_e( 'Enrolled courses', 'aswj-lms' ); ?></th>
						<th><?php esc_html_e( 'Manage', 'aswj-lms' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $users as $user ) : ?>
					<?php
					$is_sister  = ASWJ_LMS_Access::is_sister( $user->ID );
					$is_diploma = ASWJ_LMS_Access::is_diploma_student( $user->ID );
					$has_sub    = ASWJ_LMS_Access::has_active_subscription( $user->ID );
					$enrolled   = ASWJ_LMS_Enrollment::get_user_course_ids( $user->ID );
					$pending    = ASWJ_LMS_Enrollment::get_user_pending_course_ids( $user->ID );
					$phone      = get_user_meta( $user->ID, 'aswj_phone', true );
					?>
					<tr>
						<td>
							<strong><?php echo esc_html( $user->display_name ); ?></strong><br />
							<span class="description"><?php echo esc_html( $user->user_email ); ?></span>
							<?php if ( $phone ) : ?>
								<br /><span class="description"><?php echo esc_html( $phone ); ?></span>
							<?php endif; ?>
						</td>
						<td>
							<?php
							$tags = array();
							if ( $is_diploma ) {
								$tags[] = __( 'Diploma', 'aswj-lms' );
							}
							if ( $is_sister ) {
								$tags[] = __( 'Sister', 'aswj-lms' );
							}
							if ( $has_sub ) {
								$tags[] = __( 'Subscriber', 'aswj-lms' );
							}
							echo esc_html( $tags ? implode( ', ', $tags ) : '—' );
							?>
						</td>
						<td>
							<?php
							$bits = array();
							foreach ( $enrolled as $cid ) {
								$bits[] = esc_html( get_the_title( $cid ) );
							}
							foreach ( $pending as $cid ) {
								$bits[] = '<span style="color:#b45309;font-weight:600">' . esc_html( get_the_title( $cid ) ) . ' ' . esc_html__( '(awaiting payment)', 'aswj-lms' ) . '</span>';
							}
							echo $bits ? wp_kses_post( implode( '<br />', $bits ) ) : '—';
							?>
						</td>
						<td>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">
								<input type="hidden" name="action" value="aswj_update_student" />
								<input type="hidden" name="user_id" value="<?php echo (int) $user->ID; ?>" />
								<?php wp_nonce_field( 'aswj_update_student_' . $user->ID ); ?>
								<label><input type="checkbox" name="is_diploma" value="1" <?php checked( $is_diploma ); ?> /> <?php esc_html_e( 'Diploma', 'aswj-lms' ); ?></label>
								<label><input type="checkbox" name="is_sister" value="1" <?php checked( $is_sister ); ?> /> <?php esc_html_e( 'Sister', 'aswj-lms' ); ?></label>
								<?php if ( $pending ) : ?>
									<select name="approve_course_id">
										<option value="0"><?php esc_html_e( '— Approve payment for —', 'aswj-lms' ); ?></option>
										<?php foreach ( $pending as $cid ) : ?>
											<option value="<?php echo (int) $cid; ?>"><?php echo esc_html( get_the_title( $cid ) ); ?></option>
										<?php endforeach; ?>
									</select>
								<?php endif; ?>
								<select name="enroll_course_id">
									<option value="0"><?php esc_html_e( '— Enroll in course —', 'aswj-lms' ); ?></option>
									<?php foreach ( $courses as $course ) : ?>
										<option value="<?php echo (int) $course->ID; ?>"><?php echo esc_html( $course->post_title ); ?></option>
									<?php endforeach; ?>
								</select>
								<label><input type="checkbox" name="enroll_as_sponsored" value="1" /> <?php esc_html_e( 'as sponsored/excused', 'aswj-lms' ); ?></label>
								<select name="unenroll_course_id">
									<option value="0"><?php esc_html_e( '— Remove from course —', 'aswj-lms' ); ?></option>
									<?php foreach ( array_unique( array_merge( $enrolled, $pending ) ) as $cid ) : ?>
										<option value="<?php echo (int) $cid; ?>"><?php echo esc_html( get_the_title( $cid ) ); ?></option>
									<?php endforeach; ?>
								</select>
								<?php submit_button( __( 'Save', 'aswj-lms' ), 'small', '', false ); ?>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
				<?php if ( ! $users ) : ?>
					<tr><td colspan="4"><?php esc_html_e( 'No users found.', 'aswj-lms' ); ?></td></tr>
				<?php endif; ?>
				</tbody>
			</table>

			<?php
			$pages = (int) ceil( $total / $per );
			if ( $pages > 1 ) {
				echo '<p>';
				for ( $p = 1; $p <= $pages; $p++ ) {
					$url = add_query_arg(
						array(
							'post_type' => 'aswj_course',
							'page'      => 'aswj-students',
							'paged'     => $p,
							's'         => $search,
						),
						admin_url( 'edit.php' )
					);
					if ( $p === $paged ) {
						echo '<strong style="margin-right:8px">' . (int) $p . '</strong>';
					} else {
						echo '<a style="margin-right:8px" href="' . esc_url( $url ) . '">' . (int) $p . '</a>';
					}
				}
				echo '</p>';
			}
			?>
		</div>
		<?php
	}

	public static function update_student() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'aswj-lms' ) );
		}
		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
		check_admin_referer( 'aswj_update_student_' . $user_id );

		if ( $user_id ) {
			update_user_meta( $user_id, 'aswj_is_diploma', isset( $_POST['is_diploma'] ) ? '1' : '0' );
			update_user_meta( $user_id, 'aswj_is_sister', isset( $_POST['is_sister'] ) ? '1' : '0' );

			$approve = isset( $_POST['approve_course_id'] ) ? absint( $_POST['approve_course_id'] ) : 0;
			if ( $approve ) {
				ASWJ_LMS_Enrollment::enroll( $user_id, $approve, 'payment' );
			}

			$enroll = isset( $_POST['enroll_course_id'] ) ? absint( $_POST['enroll_course_id'] ) : 0;
			if ( $enroll ) {
				$source = isset( $_POST['enroll_as_sponsored'] ) ? 'sponsored' : 'manual';
				ASWJ_LMS_Enrollment::enroll( $user_id, $enroll, $source );
			}
			$unenroll = isset( $_POST['unenroll_course_id'] ) ? absint( $_POST['unenroll_course_id'] ) : 0;
			if ( $unenroll ) {
				ASWJ_LMS_Enrollment::unenroll( $user_id, $unenroll );
			}
		}

		wp_safe_redirect( add_query_arg( 'updated', '1', admin_url( 'edit.php?post_type=aswj_course&page=aswj-students' ) ) );
		exit;
	}
}
