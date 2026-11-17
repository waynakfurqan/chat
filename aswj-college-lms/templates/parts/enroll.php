<?php
/**
 * Enrollment/payment page for one course. Expects: $course (WP_Post).
 * Auto-created when a course is given a payment form.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$course_id    = $course->ID;
$user_id      = get_current_user_id();
$check        = ASWJ_LMS_Access::check( $user_id, $course_id );
$price        = get_post_meta( $course_id, '_aswj_price_label', true );
$form_id      = (int) get_post_meta( $course_id, '_aswj_payment_form_id', true );
$lessons      = ASWJ_LMS_Post_Types::get_course_lessons( $course_id );
$pending      = $user_id && ASWJ_LMS_Enrollment::is_pending( $user_id, $course_id );
$arabic_title = get_post_meta( $course_id, '_aswj_arabic_title', true );
$badge        = ASWJ_LMS_Templates::access_badge( $course_id, true );
?>
<div class="aswj-lms aw">
	<div class="aw-auth">
		<div class="aw-deep aw-pattern aw-auth__panel">
			<p class="aw-breadcrumb" style="position:relative"><a href="<?php echo esc_url( get_permalink( $course_id ) ); ?>" style="color:var(--aw-on-deep-muted) !important">&larr; <?php esc_html_e( 'Back to the course', 'aswj-lms' ); ?></a></p>
			<span class="aw-badge <?php echo esc_attr( $badge['class'] ); ?>" style="align-self:flex-start"><?php echo esc_html( $badge['label'] ); ?></span>
			<?php if ( $arabic_title ) : ?>
				<p class="aw-auth__ayah" lang="ar" dir="rtl" style="margin:0"><?php echo esc_html( $arabic_title ); ?></p>
			<?php endif; ?>
			<h2 class="aw-heading-2" style="position:relative"><?php echo esc_html( get_the_title( $course_id ) ); ?></h2>
			<?php if ( has_excerpt( $course_id ) ) : ?>
				<p class="aw-small" style="position:relative"><?php echo esc_html( get_the_excerpt( $course_id ) ); ?></p>
			<?php endif; ?>
			<p class="aw-small" style="position:relative">
				<?php
				printf(
					/* translators: %d: lesson count */
					esc_html( _n( '%d lesson', '%d lessons', count( $lessons ), 'aswj-lms' ) ),
					count( $lessons )
				);
				?>
				<?php if ( $price ) : ?>
					&middot; <strong style="color:var(--aw-on-deep)"><?php echo esc_html( $price ); ?></strong>
				<?php endif; ?>
			</p>
			<ul class="aw-checks" style="position:relative">
				<li><?php esc_html_e( 'Pay online for instant access', 'aswj-lms' ); ?></li>
				<li><?php esc_html_e( 'Or bank transfer / cash in person — unlocked once the college verifies your payment', 'aswj-lms' ); ?></li>
				<li><?php esc_html_e( 'Lifetime access to this course', 'aswj-lms' ); ?></li>
			</ul>
		</div>
		<div class="aw-auth__card aw-card">
			<?php if ( $check['allowed'] ) : ?>
				<div class="aw-notice aw-notice--info">
					<h3><?php esc_html_e( 'You already have access', 'aswj-lms' ); ?></h3>
					<p><a class="aw-btn aw-btn--primary" href="<?php echo esc_url( get_permalink( $course_id ) ); ?>"><?php esc_html_e( 'Go to the course', 'aswj-lms' ); ?></a></p>
				</div>
			<?php elseif ( $pending ) : ?>
				<div class="aw-notice aw-notice--warn">
					<h3><?php esc_html_e( 'Registration received', 'aswj-lms' ); ?></h3>
					<p><?php esc_html_e( 'Your registration is awaiting payment verification. Access will be unlocked once the college confirms your payment, in shaa Allah.', 'aswj-lms' ); ?></p>
				</div>
			<?php else : ?>
				<p class="aw-eyebrow"><?php esc_html_e( 'Enrolment', 'aswj-lms' ); ?></p>
				<h2 class="aw-heading-3" style="margin-bottom:18px"><?php esc_html_e( 'Register for this course', 'aswj-lms' ); ?></h2>
				<?php if ( ! $user_id ) : ?>
					<p class="aw-small aw-muted" style="margin-bottom:16px">
						<?php esc_html_e( 'Have an account? Sign in first and your details will be filled in for you.', 'aswj-lms' ); ?>
						<a href="<?php echo esc_url( ASWJ_LMS_Settings::login_url_page( get_permalink() ) ); ?>"><?php esc_html_e( 'Sign in', 'aswj-lms' ); ?></a>
					</p>
				<?php endif; ?>
				<?php if ( $form_id && shortcode_exists( 'fluentform' ) ) : ?>
					<?php echo do_shortcode( '[fluentform id="' . $form_id . '"]' ); ?>
				<?php else : ?>
					<p class="aw-muted"><?php esc_html_e( 'Enrolment opens soon, in shaa Allah.', 'aswj-lms' ); ?></p>
				<?php endif; ?>
			<?php endif; ?>
		</div>
	</div>
</div>
