<?php
/**
 * Login: a centred card on the faint light lattice, with the mark,
 * the salam, and the styled WordPress login form.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( is_user_logged_in() ) {
	?>
	<div class="aswj-lms aw">
		<div class="aw-notice aw-notice--info">
			<p><?php esc_html_e( 'As-salamu alaykum — you are already signed in.', 'aswj-lms' ); ?></p>
			<p><a class="aw-btn aw-btn--primary" href="<?php echo esc_url( ASWJ_LMS_Settings::portal_url() ); ?>"><?php esc_html_e( 'Go to my portal', 'aswj-lms' ); ?></a></p>
		</div>
	</div>
	<?php
	return;
}

$redirect = isset( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) : ASWJ_LMS_Settings::portal_url(); // phpcs:ignore WordPress.Security.NonceVerification
?>
<div class="aswj-lms aw">
	<div class="aw-pattern--light aw-pattern" style="position:relative;border-radius:var(--aw-radius-xl);padding:clamp(8px,2vw,24px)">
		<div class="aw-auth-narrow">
			<div class="aw-arch--outline aw-arch" style="overflow:visible">
				<div class="aw-auth__card aw-card">
					<?php echo ASWJ_LMS_Templates::mark_svg(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<p class="aw-auth-narrow__salam" lang="ar" dir="rtl">السلام عليكم</p>
					<h2 class="aw-heading-2" style="text-align:center;margin-bottom:18px"><?php esc_html_e( 'Welcome back', 'aswj-lms' ); ?></h2>

					<?php if ( isset( $_GET['login'] ) && 'failed' === $_GET['login'] ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
						<div class="aw-notice aw-notice--danger"><p><?php esc_html_e( 'That sign-in did not match our records. Check your email and password and try again.', 'aswj-lms' ); ?></p></div>
					<?php endif; ?>

					<?php
					wp_login_form(
						array(
							'redirect'       => $redirect,
							'label_username' => __( 'Username or email', 'aswj-lms' ),
							'label_password' => __( 'Password', 'aswj-lms' ),
							'label_remember' => __( 'Keep me signed in', 'aswj-lms' ),
							'label_log_in'   => __( 'Sign in', 'aswj-lms' ),
							'remember'       => true,
							'value_remember' => true,
						)
					);
					?>
					<p class="aw-auth__links">
						<a href="<?php echo esc_url( wp_lostpassword_url( $redirect ) ); ?>"><?php esc_html_e( 'Forgot password?', 'aswj-lms' ); ?></a>
						&middot;
						<a href="<?php echo esc_url( ASWJ_LMS_Settings::registration_url() ); ?>"><?php esc_html_e( 'New student? Create an account', 'aswj-lms' ); ?></a>
					</p>
				</div>
			</div>
		</div>
	</div>
</div>
