<?php
/**
 * Subscribe: the featured all-access plan (deep, lattice) beside the
 * subscription form.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id  = get_current_user_id();
$has_sub  = $user_id && ASWJ_LMS_Access::has_active_subscription( $user_id );
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
<div class="aswj-lms aw">
	<?php if ( $has_sub ) : ?>
		<div class="aw-notice aw-notice--info">
			<h3><?php esc_html_e( 'You are an all-access member — ma shaa Allah', 'aswj-lms' ); ?></h3>
			<p><?php esc_html_e( 'Every paid course on the site is already unlocked for you.', 'aswj-lms' ); ?></p>
			<p><a class="aw-btn aw-btn--primary" href="<?php echo esc_url( ASWJ_LMS_Settings::catalog_url() ); ?>"><?php esc_html_e( 'Browse courses', 'aswj-lms' ); ?></a></p>
		</div>
	<?php else : ?>
		<div class="aw-section-head aw-section-head--center" style="margin-top:14px">
			<p class="aw-eyebrow aw-eyebrow--center"><?php esc_html_e( 'Membership', 'aswj-lms' ); ?></p>
			<h1 class="aw-heading-1"><?php esc_html_e( 'Every course,', 'aswj-lms' ); ?> <em><?php esc_html_e( 'one membership.', 'aswj-lms' ); ?></em></h1>
			<p class="aw-lede"><?php esc_html_e( 'A single monthly subscription that unlocks the whole catalogue and supports the work of the college.', 'aswj-lms' ); ?></p>
		</div>
		<div class="aw-auth">
			<div class="aw-plan aw-plan--featured aw-pattern">
				<span class="aw-badge aw-badge--glass" style="align-self:flex-start"><?php esc_html_e( 'All-access', 'aswj-lms' ); ?></span>
				<h3 class="aw-heading-3"><?php esc_html_e( 'Monthly membership', 'aswj-lms' ); ?></h3>
				<ul class="aw-checks">
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
					<li><?php esc_html_e( 'Self-paced lessons on any device', 'aswj-lms' ); ?></li>
					<li><?php esc_html_e( 'Cancel anytime — purchased courses stay yours', 'aswj-lms' ); ?></li>
					<li><?php esc_html_e( 'Support the spread of authentic knowledge', 'aswj-lms' ); ?></li>
				</ul>
				<?php if ( ! $user_id ) : ?>
					<p class="aw-small" style="position:relative">
						<?php esc_html_e( 'Tip: create your free account first so the membership links to your portal.', 'aswj-lms' ); ?>
						<a href="<?php echo esc_url( ASWJ_LMS_Settings::registration_url() ); ?>" style="color:var(--aw-on-deep)"><?php esc_html_e( 'Create account', 'aswj-lms' ); ?></a>
					</p>
				<?php endif; ?>
			</div>
			<div class="aw-auth__card aw-card">
				<p class="aw-eyebrow"><?php esc_html_e( 'Subscribe', 'aswj-lms' ); ?></p>
				<h2 class="aw-heading-3" style="margin-bottom:18px"><?php esc_html_e( 'Start your membership', 'aswj-lms' ); ?></h2>
				<?php
				if ( $form_id && shortcode_exists( 'fluentform' ) ) {
					echo do_shortcode( '[fluentform id="' . $form_id . '"]' );
				} else {
					echo '<p class="aw-muted">' . esc_html__( 'Subscription form coming soon — add its form ID under ASWJ Courses → Settings → Subscription form IDs (requires Fluent Forms).', 'aswj-lms' ) . '</p>';
				}
				?>
			</div>
		</div>
	<?php endif; ?>
</div>
