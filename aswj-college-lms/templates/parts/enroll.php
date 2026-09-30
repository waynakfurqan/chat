<?php
/**
 * Enrollment/payment page for one course. Expects: $course (WP_Post).
 * Auto-created when a course is given a payment form.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$course_id = $course->ID;
$user_id   = get_current_user_id();
$check     = ASWJ_LMS_Access::check( $user_id, $course_id );
$price     = get_post_meta( $course_id, '_aswj_price_label', true );
$form_id   = (int) get_post_meta( $course_id, '_aswj_payment_form_id', true );
$lessons   = ASWJ_LMS_Post_Types::get_course_lessons( $course_id );
$pending   = $user_id && ASWJ_LMS_Enrollment::is_pending( $user_id, $course_id );
?>
<div class="aswj-lms aswj-enroll-page">
	<div class="aswj-auth-page">
		<div class="aswj-auth-side">
			<p class="aswj-breadcrumb"><a href="<?php echo esc_url( get_permalink( $course_id ) ); ?>">&larr; <?php esc_html_e( 'Back to course', 'aswj-lms' ); ?></a></p>
			<span class="aswj-badge aswj-badge-<?php echo esc_attr( ASWJ_LMS_Access::get_access_type( $course_id ) ); ?>"><?php echo esc_html( ASWJ_LMS_Access::badge_label( $course_id ) ); ?></span>
			<h2><?php echo esc_html( get_the_title( $course_id ) ); ?></h2>
			<?php if ( has_excerpt( $course_id ) ) : ?>
				<p><?php echo esc_html( get_the_excerpt( $course_id ) ); ?></p>
			<?php endif; ?>
			<p class="aswj-course-meta">
				<?php
				printf(
					/* translators: %d: lesson count */
					esc_html( _n( '%d lesson', '%d lessons', count( $lessons ), 'aswj-lms' ) ),
					count( $lessons )
				);
				?>
				<?php if ( $price ) : ?>
					&middot; <strong class="aswj-price"><?php echo esc_html( $price ); ?></strong>
				<?php endif; ?>
			</p>
			<ul class="aswj-check-list">
				<li><?php esc_html_e( 'Pay online for instant access', 'aswj-lms' ); ?></li>
				<li><?php esc_html_e( 'Or choose bank transfer / cash in person — access is unlocked once the college verifies your payment', 'aswj-lms' ); ?></li>
				<li><?php esc_html_e( 'Lifetime access to this course', 'aswj-lms' ); ?></li>
			</ul>
			<?php if ( ! $user_id ) : ?>
				<p class="aswj-small aswj-muted">
					<?php esc_html_e( 'Have an account? Log in first and we will fill in your details for you.', 'aswj-lms' ); ?>
					<a href="<?php echo esc_url( ASWJ_LMS_Settings::login_url_page( get_permalink() ) ); ?>"><?php esc_html_e( 'Log in', 'aswj-lms' ); ?></a>
				</p>
			<?php endif; ?>
		</div>
		<div class="aswj-auth-form aswj-card">
			<?php if ( $check['allowed'] ) : ?>
				<div class="aswj-notice aswj-notice-info">
					<h3><?php esc_html_e( 'You already have access', 'aswj-lms' ); ?></h3>
					<p><a class="aswj-btn" href="<?php echo esc_url( get_permalink( $course_id ) ); ?>"><?php esc_html_e( 'Go to the Course', 'aswj-lms' ); ?></a></p>
				</div>
			<?php elseif ( $pending ) : ?>
				<div class="aswj-notice aswj-notice-locked">
					<h3><?php esc_html_e( 'Registration received', 'aswj-lms' ); ?></h3>
					<p><?php esc_html_e( 'Your registration is awaiting payment verification. Access will be unlocked once the college confirms your payment, in shaa Allah.', 'aswj-lms' ); ?></p>
				</div>
			<?php elseif ( $form_id ) : ?>
				<?php echo do_shortcode( '[fluentform id="' . $form_id . '"]' ); ?>
			<?php else : ?>
				<p class="aswj-muted"><?php esc_html_e( 'Enrollment opens soon, in shaa Allah.', 'aswj-lms' ); ?></p>
			<?php endif; ?>
		</div>
	</div>
</div>
