<?php
/**
 * Single course page: hero, description, lesson list with ticks, progress.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	$course_id = get_the_ID();
	$user_id   = get_current_user_id();
	$check     = ASWJ_LMS_Access::check( $user_id, $course_id );
	$lessons   = ASWJ_LMS_Post_Types::get_course_lessons( $course_id );
	$progress  = $user_id ? ASWJ_LMS_Progress::get_course_progress( $user_id, $course_id ) : null;
	$done_ids  = $user_id ? ASWJ_LMS_Progress::get_completed_lesson_ids( $user_id, $course_id ) : array();
	$type      = ASWJ_LMS_Access::get_access_type( $course_id );
	$price     = get_post_meta( $course_id, '_aswj_price_label', true );
	?>
	<div class="aswj-lms aswj-single-course">
		<header class="aswj-course-hero">
			<div class="aswj-course-hero-inner">
				<p class="aswj-breadcrumb"><a href="<?php echo esc_url( ASWJ_LMS_Settings::catalog_url() ); ?>">&larr; <?php esc_html_e( 'All Courses', 'aswj-lms' ); ?></a></p>
				<span class="aswj-badge aswj-badge-<?php echo esc_attr( $type ); ?>"><?php echo esc_html( ASWJ_LMS_Access::badge_label( $course_id ) ); ?></span>
				<h1 class="aswj-course-title"><?php the_title(); ?></h1>
				<?php if ( has_excerpt() ) : ?>
					<p class="aswj-course-tagline"><?php echo esc_html( get_the_excerpt() ); ?></p>
				<?php endif; ?>
				<p class="aswj-course-meta">
					<?php
					printf(
						/* translators: %d: lesson count */
						esc_html( _n( '%d lesson', '%d lessons', count( $lessons ), 'aswj-lms' ) ),
						count( $lessons )
					);
					if ( $price && in_array( $type, array( 'paid', 'subscription' ), true ) ) {
						echo ' &middot; ' . esc_html( $price );
					}
					?>
				</p>
				<?php if ( $check['allowed'] && $user_id && $progress && $progress['total'] > 0 ) : ?>
					<div class="aswj-progress aswj-progress-hero" data-aswj-course-progress role="progressbar" aria-valuenow="<?php echo esc_attr( $progress['percent'] ); ?>" aria-valuemin="0" aria-valuemax="100">
						<div class="aswj-progress-bar" style="width: <?php echo esc_attr( $progress['percent'] ); ?>%"></div>
					</div>
					<p class="aswj-small" data-aswj-progress-text>
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
				<?php endif; ?>
				<?php if ( $check['allowed'] && $lessons ) : ?>
					<?php
					// Jump to the first lesson the student has not finished yet.
					$resume = $lessons[0];
					foreach ( $lessons as $l ) {
						if ( ! in_array( $l->ID, $done_ids, true ) ) {
							$resume = $l;
							break;
						}
					}
					$is_finished = $progress && $progress['total'] > 0 && $progress['completed'] >= $progress['total'];
					?>
					<?php if ( $is_finished ) : ?>
						<p class="aswj-course-finished">&#127882; <?php esc_html_e( 'Ma shaa Allah — you have completed this course!', 'aswj-lms' ); ?></p>
						<p><a class="aswj-btn aswj-btn-hero" href="<?php echo esc_url( get_permalink( $lessons[0] ) ); ?>"><?php esc_html_e( 'Review Lessons', 'aswj-lms' ); ?></a></p>
					<?php else : ?>
						<p><a class="aswj-btn aswj-btn-hero" href="<?php echo esc_url( get_permalink( $resume ) ); ?>">
							<?php echo ( $progress && $progress['completed'] > 0 ) ? esc_html__( 'Continue Learning', 'aswj-lms' ) : esc_html__( 'Start Course', 'aswj-lms' ); ?>
						</a></p>
					<?php endif; ?>
				<?php endif; ?>
			</div>
		</header>

		<div class="aswj-course-layout">
			<div class="aswj-course-main">
				<?php if ( ! $check['allowed'] ) : ?>
					<?php $notice = ASWJ_LMS_Access::denial_notice( $course_id, $check['reason'] ); ?>
					<div class="aswj-notice aswj-notice-locked">
						<h3><?php esc_html_e( 'Course Locked', 'aswj-lms' ); ?></h3>
						<p><?php echo esc_html( $notice['message'] ); ?></p>
						<?php if ( $notice['cta_url'] ) : ?>
							<p><a class="aswj-btn" href="<?php echo esc_url( $notice['cta_url'] ); ?>"><?php echo esc_html( $notice['cta_label'] ); ?></a></p>
						<?php endif; ?>
						<?php if ( ! $user_id ) : ?>
							<p class="aswj-small"><a href="<?php echo esc_url( ASWJ_LMS_Settings::registration_url() ); ?>"><?php esc_html_e( 'New here? Create an account', 'aswj-lms' ); ?></a></p>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<section class="aswj-course-description">
					<h2><?php esc_html_e( 'About this course', 'aswj-lms' ); ?></h2>
					<?php the_content(); ?>
				</section>
			</div>

			<aside class="aswj-course-side">
				<h2><?php esc_html_e( 'Lessons', 'aswj-lms' ); ?></h2>
				<?php if ( ! $lessons ) : ?>
					<p class="aswj-muted"><?php esc_html_e( 'Lessons coming soon, in shaa Allah.', 'aswj-lms' ); ?></p>
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
									<span class="aswj-lock" aria-hidden="true">&#128274;</span>
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
