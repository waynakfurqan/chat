<?php
/**
 * Login page: styled WordPress login form.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( is_user_logged_in() ) {
	?>
	<div class="aswj-lms">
		<div class="aswj-notice aswj-notice-info">
			<p><?php esc_html_e( 'Assalamu alaykum — you are already logged in.', 'aswj-lms' ); ?></p>
			<p><a class="aswj-btn" href="<?php echo esc_url( ASWJ_LMS_Settings::portal_url() ); ?>"><?php esc_html_e( 'Go to My Portal', 'aswj-lms' ); ?></a></p>
		</div>
	</div>
	<?php
	return;
}

$redirect = isset( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) : ASWJ_LMS_Settings::portal_url(); // phpcs:ignore WordPress.Security.NonceVerification
?>
<div class="aswj-lms aswj-auth-page aswj-auth-page-narrow">
	<div class="aswj-auth-form aswj-card">
		<h2><?php esc_html_e( 'Student Login', 'aswj-lms' ); ?></h2>
		<?php if ( isset( $_GET['login'] ) && 'failed' === $_GET['login'] ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="aswj-notice aswj-notice-locked"><p><?php esc_html_e( 'Incorrect username/email or password. Please try again.', 'aswj-lms' ); ?></p></div>
		<?php endif; ?>
		<?php
		wp_login_form(
			array(
				'redirect'       => $redirect,
				'label_username' => __( 'Username or Email', 'aswj-lms' ),
				'label_password' => __( 'Password', 'aswj-lms' ),
				'label_remember' => __( 'Keep me logged in', 'aswj-lms' ),
				'label_log_in'   => __( 'Log In', 'aswj-lms' ),
				'remember'       => true,
				'value_remember' => true,
			)
		);
		?>
		<p class="aswj-small aswj-muted aswj-auth-links">
			<a href="<?php echo esc_url( wp_lostpassword_url( $redirect ) ); ?>"><?php esc_html_e( 'Forgot your password?', 'aswj-lms' ); ?></a>
			&middot;
			<a href="<?php echo esc_url( ASWJ_LMS_Settings::registration_url() ); ?>"><?php esc_html_e( 'New student? Create an account', 'aswj-lms' ); ?></a>
		</p>
	</div>
</div>
