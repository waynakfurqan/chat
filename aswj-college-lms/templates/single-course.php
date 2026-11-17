<?php
/**
 * Single course: sunken page header, serif title, progress, lesson list.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	$course_id    = get_the_ID();
	$user_id      = get_current_user_id();
	$check        = ASWJ_LMS_Access::check( $user_id, $course_id );
	$lessons      = ASWJ_LMS_Post_Types::get_course_lessons( $course_id );
	$progress     = $user_id ? ASWJ_LMS_Progress::get_course_progress( $user_id, $course_id ) : null;
	$done_ids     = $user_id ? ASWJ_LMS_Progress::get_completed_lesson_ids( $user_id, $course_id ) : array();
	$price        = get_post_meta( $course_id, '_aswj_price_label', true );
	$arabic_title = get_post_meta( $course_id, '_aswj_arabic_title', true );
	$badge        = ASWJ_LMS_Templates::access_badge( $course_id );

	// First unfinished lesson, for Start/Continue.
	$resume = null;
	foreach ( $lessons as $l ) {
		if ( ! in_array( $l->ID, $done_ids, true ) ) {
			$resume = $l;
			break;
		}
	}
	$is_finished = $progress && $progress['total'] > 0 && $progress['completed'] >= $progress['total'];
	?>
	<div class="aswj-lms aw aw-single-course">
		<header class="aw-pagehead">
			<?php if ( $arabic_title ) : ?>
				<span class="aw-pagehead__arabic" lang="ar" dir="rtl" aria-hidden="true"><?php echo esc_html( $arabic_title ); ?></span>
			<?php endif; ?>
			<p class="aw-breadcrumb"><a href="<?php echo esc_url( ASWJ_LMS_Settings::catalog_url() ); ?>">&larr; <?php esc_html_e( 'All courses', 'aswj-lms' ); ?></a></p>
			<p class="aw-eyebrow"><?php echo esc_html( $badge['label'] ); ?></p>
			<h1><?php the_title(); ?></h1>
			<?php if ( has_excerpt() ) : ?>
				<p class="aw-lede"><?php echo esc_html( get_the_excerpt() ); ?></p>
			<?php endif; ?>
			<div class="aw-pagehead__meta">
				<span>
					<?php
					printf(
						/* translators: %d: lesson count */
						esc_html( _n( '%d lesson', '%d lessons', count( $lessons ), 'aswj-lms' ) ),
						count( $lessons )
					);
					?>
				</span>
				<?php if ( $price && in_array( ASWJ_LMS_Access::get_access_type( $course_id ), array( 'paid', 'subscription' ), true ) ) : ?>
					<span><strong><?php echo esc_html( $price ); ?></strong></span>
				<?php endif; ?>
				<span><?php esc_html_e( 'Self-paced', 'aswj-lms' ); ?></span>
			</div>

			<?php if ( $check['allowed'] && $user_id && $progress && $progress['total'] > 0 ) : ?>
				<div style="max-width:420px;margin-bottom:16px">
					<div class="aw-progress" data-aswj-course-progress role="progressbar" aria-valuenow="<?php echo esc_attr( $progress['percent'] ); ?>" aria-valuemin="0" aria-valuemax="100"><i class="aswj-progress-bar" style="width:<?php echo esc_attr( $progress['percent'] ); ?>%"></i></div>
					<p class="aw-small aw-muted" style="margin:8px 0 0" data-aswj-progress-text>
						<?php
						printf(
							/* translators: 1: completed, 2: total, 3: percent */
							esc_html__( '%1$d of %2$d lessons complete (%3$d%%)', 'aswj-lms' ),
							(int) $progress['completed'],
							(int) $progress['total'],
							(int) $progress['percent']
						);
						?>
					</p>
				</div>
			<?php endif; ?>

			<?php if ( $check['allowed'] && $lessons ) : ?>
				<p style="margin:4px 0 0">
					<?php if ( $is_finished ) : ?>
						<a class="aw-btn aw-btn--secondary" href="<?php echo esc_url( get_permalink( $lessons[0] ) ); ?>"><?php esc_html_e( 'Review lessons', 'aswj-lms' ); ?></a>
						<span class="aw-badge aw-badge--green" style="margin-left:8px"><?php esc_html_e( 'Completed — ma shaa Allah', 'aswj-lms' ); ?></span>
					<?php else : ?>
						<a class="aw-btn aw-btn--primary" href="<?php echo esc_url( get_permalink( $resume ? $resume : $lessons[0] ) ); ?>">
							<?php echo ( $progress && $progress['completed'] > 0 ) ? esc_html__( 'Continue learning', 'aswj-lms' ) : esc_html__( 'Start the course', 'aswj-lms' ); ?>
						</a>
					<?php endif; ?>
				</p>
			<?php endif; ?>
		</header>

		<div class="aw-course-layout">
			<div>
				<?php if ( ! $check['allowed'] ) : ?>
					<?php $notice = ASWJ_LMS_Access::denial_notice( $course_id, $check['reason'] ); ?>
					<div class="aw-notice <?php echo 'pending' === $check['reason'] ? 'aw-notice--warn' : 'aw-notice--info'; ?>">
						<h3><?php echo 'pending' === $check['reason'] ? esc_html__( 'Awaiting payment verification', 'aswj-lms' ) : esc_html__( 'Course locked', 'aswj-lms' ); ?></h3>
						<p><?php echo esc_html( $notice['message'] ); ?></p>
						<?php if ( $notice['cta_url'] ) : ?>
							<p>
								<a class="aw-btn aw-btn--primary" href="<?php echo esc_url( $notice['cta_url'] ); ?>"><?php echo esc_html( $notice['cta_label'] ); ?></a>
								<?php if ( 'payment' === $check['reason'] && ASWJ_LMS_Settings::subscribe_url() ) : ?>
									<a class="aw-btn aw-btn--secondary" href="<?php echo esc_url( ASWJ_LMS_Settings::subscribe_url() ); ?>"><?php esc_html_e( 'Subscribe monthly', 'aswj-lms' ); ?></a>
								<?php endif; ?>
							</p>
						<?php endif; ?>
						<?php if ( ! $user_id ) : ?>
							<p class="aw-small"><a href="<?php echo esc_url( ASWJ_LMS_Settings::registration_url() ); ?>"><?php esc_html_e( 'New here? Create a free account', 'aswj-lms' ); ?></a></p>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<section>
					<p class="aw-eyebrow"><?php esc_html_e( 'About this course', 'aswj-lms' ); ?></p>
					<?php the_content(); ?>
				</section>
			</div>

			<aside class="aw-sidecard">
				<h2><?php esc_html_e( 'Lessons', 'aswj-lms' ); ?></h2>
				<?php if ( ! $lessons ) : ?>
					<p class="aw-muted"><?php esc_html_e( 'Lessons coming soon, in shaa Allah.', 'aswj-lms' ); ?></p>
				<?php else : ?>
					<ol class="aswj-lesson-list">
						<?php foreach ( $lessons as $i => $lesson ) : ?>
							<?php $done = in_array( $lesson->ID, $done_ids, true ); ?>
							<li class="aswj-lesson-item<?php echo $done ? ' is-complete' : ''; ?><?php echo $check['allowed'] ? '' : ' is-locked'; ?>">
								<span class="aswj-lesson-tick" aria-hidden="true"><?php echo $done ? '&#10003;' : (int) ( $i + 1 ); ?></span>
								<?php if ( $check['allowed'] ) : ?>
									<a class="aswj-lesson-link" href="<?php echo esc_url( get_permalink( $lesson ) ); ?>"><?php echo esc_html( get_the_title( $lesson ) ); ?></a>
								<?php else : ?>
									<span class="aswj-lesson-link"><?php echo esc_html( get_the_title( $lesson ) ); ?></span>
									<svg class="aw-lock-ico" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ol>
				<?php endif; ?>
			</aside>
		</div>
	</div>
	<?php
endwhile;

get_footer();
