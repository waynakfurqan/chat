<?php
/**
 * Student portal. Expects: $user (WP_User).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id    = $user->ID;
$course_ids = ASWJ_LMS_Enrollment::get_user_course_ids( $user_id );
$is_sister  = ASWJ_LMS_Access::is_sister( $user_id );
$is_diploma = ASWJ_LMS_Access::is_diploma_student( $user_id );

// Also surface diploma courses for flagged students even without an enrollment row.
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
	$course_ids = array_unique( array_merge( $course_ids, array_map( 'intval', $diploma_courses ) ) );
}

$courses = $course_ids ? get_posts(
	array(
		'post_type'      => 'aswj_course',
		'posts_per_page' => -1,
		'post__in'       => $course_ids,
		'orderby'        => 'post__in',
	)
) : array();
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
			</p>
		</div>
		<div class="aswj-portal-actions">
			<a class="aswj-btn aswj-btn-outline" href="<?php echo esc_url( ASWJ_LMS_Settings::catalog_url() ); ?>"><?php esc_html_e( 'Browse Courses', 'aswj-lms' ); ?></a>
			<a class="aswj-btn aswj-btn-outline" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>"><?php esc_html_e( 'Log Out', 'aswj-lms' ); ?></a>
		</div>
	</header>

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

	<section class="aswj-portal-section">
		<h3><?php esc_html_e( 'My Profile', 'aswj-lms' ); ?></h3>
		<div class="aswj-profile-card">
			<dl class="aswj-profile-list">
				<dt><?php esc_html_e( 'Name', 'aswj-lms' ); ?></dt>
				<dd><?php echo esc_html( $user->display_name ); ?></dd>
				<dt><?php esc_html_e( 'Username', 'aswj-lms' ); ?></dt>
				<dd><?php echo esc_html( $user->user_login ); ?></dd>
				<dt><?php esc_html_e( 'Email', 'aswj-lms' ); ?></dt>
				<dd><?php echo esc_html( $user->user_email ); ?></dd>
				<dt><?php esc_html_e( 'Member since', 'aswj-lms' ); ?></dt>
				<dd><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $user->user_registered ) ) ); ?></dd>
			</dl>
			<p class="aswj-muted aswj-small">
				<?php
				printf(
					/* translators: %s: admin email */
					esc_html__( 'Need to update your details or having trouble? Contact us at %s.', 'aswj-lms' ),
					'<a href="mailto:' . esc_attr( ASWJ_LMS_Settings::get( 'contact_email' ) ) . '">' . esc_html( ASWJ_LMS_Settings::get( 'contact_email' ) ) . '</a>'
				);
				?>
			</p>
		</div>
	</section>
</div>
