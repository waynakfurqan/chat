<?php
/**
 * Registration page: welcome panel + Fluent Forms registration form.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( is_user_logged_in() ) {
	?>
	<div class="aswj-lms">
		<div class="aswj-notice aswj-notice-info">
			<h3><?php esc_html_e( 'You already have an account', 'aswj-lms' ); ?></h3>
			<p><?php esc_html_e( 'You are logged in — head to your portal to continue learning.', 'aswj-lms' ); ?></p>
			<p><a class="aswj-btn" href="<?php echo esc_url( ASWJ_LMS_Settings::portal_url() ); ?>"><?php esc_html_e( 'Go to My Portal', 'aswj-lms' ); ?></a></p>
		</div>
	</div>
	<?php
	return;
}

$form_id = (int) ASWJ_LMS_Settings::get( 'registration_form_id' );
?>
<div class="aswj-lms aswj-auth-page">
	<div class="aswj-auth-side">
		<h2><?php esc_html_e( 'Create your student account', 'aswj-lms' ); ?></h2>
		<p><?php esc_html_e( 'One free account for everything at ASWJ Islamic College:', 'aswj-lms' ); ?></p>
		<ul class="aswj-check-list">
			<li><?php esc_html_e( 'Access free courses instantly', 'aswj-lms' ); ?></li>
			<li><?php esc_html_e( 'Track your lesson progress in your own portal', 'aswj-lms' ); ?></li>
			<li><?php esc_html_e( 'Register for new courses without re-typing your details', 'aswj-lms' ); ?></li>
			<li><?php esc_html_e( 'Enroll in paid courses or subscribe for all-access', 'aswj-lms' ); ?></li>
		</ul>
		<p class="aswj-small aswj-muted">
			<?php esc_html_e( 'Already have an account?', 'aswj-lms' ); ?>
			<a href="<?php echo esc_url( ASWJ_LMS_Settings::login_url_page( ASWJ_LMS_Settings::portal_url() ) ); ?>"><?php esc_html_e( 'Log in here', 'aswj-lms' ); ?></a>
		</p>
	</div>
	<div class="aswj-auth-form aswj-card">
		<?php
		if ( $form_id ) {
			echo do_shortcode( '[fluentform id="' . $form_id . '"]' );
		} else {
			echo '<p class="aswj-muted">' . esc_html__( 'Registration form coming soon — set the Registration form ID in ASWJ Courses → Settings.', 'aswj-lms' ) . '</p>';
		}
		?>
	</div>
</div>
