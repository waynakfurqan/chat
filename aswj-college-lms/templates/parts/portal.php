<?php
/**
 * Student portal. Expects: $user (WP_User).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id     = $user->ID;
$course_ids  = ASWJ_LMS_Enrollment::get_user_course_ids( $user_id );
$pending_ids = ASWJ_LMS_Enrollment::get_user_pending_course_ids( $user_id );
$is_sister   = ASWJ_LMS_Access::is_sister( $user_id );
$is_diploma  = ASWJ_LMS_Access::is_diploma_student( $user_id );
$has_sub     = ASWJ_LMS_Access::has_active_subscription( $user_id );
$phone       = get_user_meta( $user_id, 'aswj_phone', true );

// Diploma students see all diploma courses; subscribers see all paid/subscription courses.
if ( $is_diploma ) {
	$diploma_courses = get_posts(
		array(
			'post_type'      => 'aswj_course',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_key'       => '_aswj_access_type',
			'meta_value'     => 'diploma',
		)
	);
	$course_ids = array_merge( $course_ids, array_map( 'intval', $diploma_courses ) );
}
if ( $has_sub ) {
	$sub_courses = get_posts(
		array(
			'post_type'      => 'aswj_course',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'     => '_aswj_access_type',
					'value'   => array( 'paid', 'subscription' ),
					'compare' => 'IN',
				),
			),
		)
	);
	$course_ids = array_merge( $course_ids, array_map( 'intval', $sub_courses ) );
}
$course_ids = array_values( array_unique( $course_ids ) );

// Hide courses this student cannot actually open (e.g. brothers-restricted lists).
$course_ids = array_values(
	array_filter(
		$course_ids,
		function ( $cid ) use ( $user_id ) {
			return ASWJ_LMS_Access::can_view_course( $cid, $user_id );
		}
	)
);

$courses = $course_ids ? get_posts(
	array(
		'post_type'      => 'aswj_course',
		'posts_per_page' => -1,
		'post__in'       => $course_ids,
		'orderby'        => 'post__in',
	)
) : array();

$profile_updated = isset( $_GET['aswj_profile_updated'] ); // phpcs:ignore WordPress.Security.NonceVerification
$profile_errors  = isset( $_GET['aswj_profile_error'] ) ? array_filter( explode( ',', sanitize_text_field( wp_unslash( $_GET['aswj_profile_error'] ) ) ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification
?>
<div class="aswj-lms aswj-portal">
	<header class="aswj-portal-header">
		<div>
			<p class="aswj-portal-salam"><?php esc_html_e( 'Assalamu alaykum,', 'aswj-lms' ); ?></p>
			<h2 class="aswj-portal-name"><?php echo esc_html( $user->display_name ); ?></h2>
			<p class="aswj-portal-tags">
				<span class="aswj-tag"><?php esc_html_e( 'Student', 'aswj-lms' ); ?></span>
				<?php if ( $is_diploma ) : ?>
					<span class="aswj-tag aswj-tag-gold"><?php esc_html_e( 'Diploma Program', 'aswj-lms' ); ?></span>
				<?php endif; ?>
				<?php if ( $is_sister ) : ?>
					<span class="aswj-tag aswj-tag-green"><?php esc_html_e( 'Sister', 'aswj-lms' ); ?></span>
				<?php endif; ?>
				<?php if ( $has_sub ) : ?>
					<span class="aswj-tag aswj-tag-green"><?php esc_html_e( 'All-Access Subscriber', 'aswj-lms' ); ?></span>
				<?php endif; ?>
			</p>
		</div>
		<div class="aswj-portal-actions">
			<a class="aswj-btn aswj-btn-outline" href="<?php echo esc_url( ASWJ_LMS_Settings::catalog_url() ); ?>"><?php esc_html_e( 'Browse Courses', 'aswj-lms' ); ?></a>
			<a class="aswj-btn aswj-btn-outline" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>"><?php esc_html_e( 'Log Out', 'aswj-lms' ); ?></a>
		</div>
	</header>

	<?php if ( $pending_ids ) : ?>
		<section class="aswj-portal-section">
			<div class="aswj-notice aswj-notice-locked">
				<h3><?php esc_html_e( 'Awaiting payment verification', 'aswj-lms' ); ?></h3>
				<p><?php esc_html_e( 'We have received your registration for the course(s) below. Access will be unlocked once your bank transfer or in-person payment is verified by the college.', 'aswj-lms' ); ?></p>
				<ul>
					<?php foreach ( $pending_ids as $pid ) : ?>
						<li><strong><?php echo esc_html( get_the_title( $pid ) ); ?></strong></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
	<?php endif; ?>

	<section class="aswj-portal-section">
		<h3><?php esc_html_e( 'My Courses', 'aswj-lms' ); ?></h3>
		<?php if ( ! $courses ) : ?>
			<div class="aswj-notice aswj-notice-info">
				<p><?php esc_html_e( 'You are not enrolled in any courses yet.', 'aswj-lms' ); ?></p>
				<p><a class="aswj-btn" href="<?php echo esc_url( ASWJ_LMS_Settings::catalog_url() ); ?>"><?php esc_html_e( 'Explore Courses', 'aswj-lms' ); ?></a></p>
			</div>
		<?php else : ?>
			<div class="aswj-course-grid">
				<?php
				foreach ( $courses as $portal_course ) {
					ASWJ_LMS_Templates::part(
						'course-card',
						array(
							'course'  => $portal_course,
							'user_id' => $user_id,
						)
					);
				}
				?>
			</div>
		<?php endif; ?>
	</section>

	<section class="aswj-portal-section" id="aswj-profile">
		<h3><?php esc_html_e( 'My Profile', 'aswj-lms' ); ?></h3>

		<?php if ( $profile_updated ) : ?>
			<div class="aswj-notice aswj-notice-info"><p><?php esc_html_e( 'Your profile has been updated.', 'aswj-lms' ); ?></p></div>
		<?php endif; ?>
		<?php foreach ( $profile_errors as $error_code ) : ?>
			<div class="aswj-notice aswj-notice-locked"><p><?php echo esc_html( ASWJ_LMS_Profile::error_message( $error_code ) ); ?></p></div>
		<?php endforeach; ?>

		<div class="aswj-profile-card">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="aswj-profile-form">
				<input type="hidden" name="action" value="aswj_update_profile" />
				<?php wp_nonce_field( 'aswj_update_profile' ); ?>

				<div class="aswj-form-grid">
					<p class="aswj-form-field">
						<label for="aswj-first-name"><?php esc_html_e( 'First name', 'aswj-lms' ); ?></label>
						<input type="text" id="aswj-first-name" name="first_name" value="<?php echo esc_attr( $user->first_name ); ?>" />
					</p>
					<p class="aswj-form-field">
						<label for="aswj-last-name"><?php esc_html_e( 'Last name', 'aswj-lms' ); ?></label>
						<input type="text" id="aswj-last-name" name="last_name" value="<?php echo esc_attr( $user->last_name ); ?>" />
					</p>
					<p class="aswj-form-field">
						<label for="aswj-email"><?php esc_html_e( 'Email', 'aswj-lms' ); ?></label>
						<input type="email" id="aswj-email" name="email" value="<?php echo esc_attr( $user->user_email ); ?>" />
					</p>
					<p class="aswj-form-field">
						<label for="aswj-phone"><?php esc_html_e( 'Phone number', 'aswj-lms' ); ?></label>
						<input type="text" id="aswj-phone" name="phone" value="<?php echo esc_attr( $phone ); ?>" />
					</p>
					<p class="aswj-form-field">
						<label for="aswj-new-password"><?php esc_html_e( 'New password', 'aswj-lms' ); ?></label>
						<input type="password" id="aswj-new-password" name="new_password" autocomplete="new-password" placeholder="<?php esc_attr_e( 'Leave blank to keep current', 'aswj-lms' ); ?>" />
					</p>
					<p class="aswj-form-field">
						<label for="aswj-confirm-password"><?php esc_html_e( 'Confirm new password', 'aswj-lms' ); ?></label>
						<input type="password" id="aswj-confirm-password" name="confirm_password" autocomplete="new-password" />
					</p>
				</div>

				<p>
					<button type="submit" class="aswj-btn"><?php esc_html_e( 'Save Profile', 'aswj-lms' ); ?></button>
				</p>
			</form>
			<p class="aswj-muted aswj-small">
				<?php
				printf(
					/* translators: 1: username, 2: member since date, 3: contact email link */
					esc_html__( 'Username: %1$s · Member since %2$s · Need help? Contact us at %3$s.', 'aswj-lms' ),
					esc_html( $user->user_login ),
					esc_html( date_i18n( get_option( 'date_format' ), strtotime( $user->user_registered ) ) ),
					'<a href="mailto:' . esc_attr( ASWJ_LMS_Settings::get( 'contact_email' ) ) . '">' . esc_html( ASWJ_LMS_Settings::get( 'contact_email' ) ) . '</a>'
				);
				?>
			</p>
		</div>
	</section>
</div>
