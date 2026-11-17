<?php
/**
 * Registration: split layout — navy panel with the ayah and trial
 * benefits, the Fluent Forms registration form on the right.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( is_user_logged_in() ) {
	?>
	<div class="aswj-lms aw">
		<div class="aw-notice aw-notice--info">
			<h3><?php esc_html_e( 'You already have an account', 'aswj-lms' ); ?></h3>
			<p><?php esc_html_e( 'You are signed in — head to your portal to continue learning.', 'aswj-lms' ); ?></p>
			<p><a class="aw-btn aw-btn--primary" href="<?php echo esc_url( ASWJ_LMS_Settings::portal_url() ); ?>"><?php esc_html_e( 'Go to my portal', 'aswj-lms' ); ?></a></p>
		</div>
	</div>
	<?php
	return;
}

$form_id = (int) ASWJ_LMS_Settings::get( 'registration_form_id' );
?>
<div class="aswj-lms aw">
	<div class="aw-auth">
		<div class="aw-deep aw-pattern aw-auth__panel">
			<?php echo ASWJ_LMS_Templates::mark_svg(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<p class="aw-auth__ayah" lang="ar" dir="rtl">يَرْفَعِ اللَّهُ الَّذِينَ آمَنُوا مِنكُمْ وَالَّذِينَ أُوتُوا الْعِلْمَ دَرَجَاتٍ</p>
			<p class="aw-small" style="position:relative"><?php esc_html_e( '“Allah raises those who believe among you, and those given knowledge, by degrees.” — Surah al-Mujadilah, 58:11', 'aswj-lms' ); ?></p>
			<ul class="aw-checks" style="position:relative">
				<li><?php esc_html_e( 'Free courses, open from the day you join', 'aswj-lms' ); ?></li>
				<li><?php esc_html_e( 'Your own portal with lesson-by-lesson progress', 'aswj-lms' ); ?></li>
				<li><?php esc_html_e( 'Your details remembered for every future course registration', 'aswj-lms' ); ?></li>
				<li><?php esc_html_e( 'Enrol in single courses, or subscribe for all-access', 'aswj-lms' ); ?></li>
			</ul>
		</div>
		<div class="aw-auth__card aw-card">
			<p class="aw-eyebrow"><?php esc_html_e( 'Account', 'aswj-lms' ); ?></p>
			<h2 class="aw-heading-2" style="margin-bottom:18px"><?php esc_html_e( 'Create your student account', 'aswj-lms' ); ?></h2>
			<?php
			if ( $form_id && shortcode_exists( 'fluentform' ) ) {
				echo do_shortcode( '[fluentform id="' . $form_id . '"]' );
			} else {
				echo '<p class="aw-muted">' . esc_html__( 'Registration form coming soon — set the Registration form ID in ASWJ Courses → Settings (requires Fluent Forms).', 'aswj-lms' ) . '</p>';
			}
			?>
			<p class="aw-auth__links">
				<?php esc_html_e( 'Already have an account?', 'aswj-lms' ); ?>
				<a href="<?php echo esc_url( ASWJ_LMS_Settings::login_url_page( ASWJ_LMS_Settings::portal_url() ) ); ?>"><?php esc_html_e( 'Sign in', 'aswj-lms' ); ?></a>
			</p>
		</div>
	</div>
</div>
