<?php
/**
 * Subscription page: all-access pitch + Fluent Forms subscription form.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id = get_current_user_id();
$has_sub = $user_id && ASWJ_LMS_Access::has_active_subscription( $user_id );
$form_ids = ASWJ_LMS_Settings::subscription_form_ids();
$form_id  = $form_ids ? (int) $form_ids[0] : 0;

$paid_count = count(
	get_posts(
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
	)
);
?>
<div class="aswj-lms aswj-subscribe-page">
	<?php if ( $has_sub ) : ?>
		<div class="aswj-notice aswj-notice-info">
			<h3><?php esc_html_e( 'You are an All-Access subscriber — ma shaa Allah!', 'aswj-lms' ); ?></h3>
			<p><?php esc_html_e( 'Every paid course on the site is already unlocked for you.', 'aswj-lms' ); ?></p>
			<p><a class="aswj-btn" href="<?php echo esc_url( ASWJ_LMS_Settings::catalog_url() ); ?>"><?php esc_html_e( 'Browse Courses', 'aswj-lms' ); ?></a></p>
		</div>
	<?php else : ?>
		<div class="aswj-auth-page">
			<div class="aswj-auth-side">
				<span class="aswj-badge aswj-badge-free"><?php esc_html_e( 'Monthly All-Access', 'aswj-lms' ); ?></span>
				<h2><?php esc_html_e( 'Every course. One subscription.', 'aswj-lms' ); ?></h2>
				<ul class="aswj-check-list">
					<li>
						<?php
						printf(
							/* translators: %d: number of paid courses */
							esc_html( _n( 'Unlock %d paid course instantly', 'Unlock all %d paid courses instantly', $paid_count, 'aswj-lms' ) ),
							(int) $paid_count
						);
						?>
					</li>
					<li><?php esc_html_e( 'New courses included automatically at no extra cost', 'aswj-lms' ); ?></li>
					<li><?php esc_html_e( 'Learn at your own pace on any device', 'aswj-lms' ); ?></li>
					<li><?php esc_html_e( 'Cancel anytime — your individually purchased courses are always yours', 'aswj-lms' ); ?></li>
					<li><?php esc_html_e( 'Support the college and the spread of authentic knowledge', 'aswj-lms' ); ?></li>
				</ul>
				<?php if ( ! $user_id ) : ?>
					<p class="aswj-small aswj-muted">
						<?php esc_html_e( 'Tip: create your free account first so your subscription links to your student portal.', 'aswj-lms' ); ?>
						<a href="<?php echo esc_url( ASWJ_LMS_Settings::registration_url() ); ?>"><?php esc_html_e( 'Create account', 'aswj-lms' ); ?></a>
					</p>
				<?php endif; ?>
			</div>
			<div class="aswj-auth-form aswj-card">
				<?php
				if ( $form_id && shortcode_exists( 'fluentform' ) ) {
					echo do_shortcode( '[fluentform id="' . $form_id . '"]' );
				} else {
					echo '<p class="aswj-muted">' . esc_html__( 'Subscription form coming soon — add its form ID under ASWJ Courses → Settings → Subscription form IDs (requires Fluent Forms).', 'aswj-lms' ) . '</p>';
				}
				?>
			</div>
		</div>
	<?php endif; ?>
</div>
