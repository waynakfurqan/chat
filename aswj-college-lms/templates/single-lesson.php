<?php
/**
 * Single lesson page: gated video, notes, mark-complete, prev/next navigation.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();

	$lesson_id = get_the_ID();
	$course_id = ASWJ_LMS_Post_Types::get_lesson_course_id( $lesson_id );
	$user_id   = get_current_user_id();
	$check     = $course_id ? ASWJ_LMS_Access::check( $user_id, $course_id ) : array( 'allowed' => false, 'reason' => 'login' );
	$lessons   = $course_id ? ASWJ_LMS_Post_Types::get_course_lessons( $course_id ) : array();
	$done_ids  = $user_id && $course_id ? ASWJ_LMS_Progress::get_completed_lesson_ids( $user_id, $course_id ) : array();
	$is_done   = in_array( $lesson_id, $done_ids, true );

	// Locate previous/next lessons within the course order.
	$prev = null;
	$next = null;
	foreach ( $lessons as $i => $l ) {
		if ( $l->ID === $lesson_id ) {
			$prev = $i > 0 ? $lessons[ $i - 1 ] : null;
			$next = isset( $lessons[ $i + 1 ] ) ? $lessons[ $i + 1 ] : null;
			break;
		}
	}
	?>
	<div class="aswj-lms aswj-single-lesson">
		<div class="aswj-lesson-layout">
			<div class="aswj-lesson-main">
				<p class="aswj-breadcrumb">
					<?php if ( $course_id ) : ?>
						<a href="<?php echo esc_url( get_permalink( $course_id ) ); ?>">&larr; <?php echo esc_html( get_the_title( $course_id ) ); ?></a>
					<?php endif; ?>
				</p>
				<h1 class="aswj-lesson-title"><?php the_title(); ?></h1>

				<?php if ( $check['allowed'] ) : ?>
					<?php echo ASWJ_LMS_Video::placeholder( $lesson_id ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

					<div class="aswj-lesson-actions">
						<?php if ( $user_id ) : ?>
							<button type="button"
								class="aswj-btn aswj-complete-toggle<?php echo $is_done ? ' is-complete' : ''; ?>"
								data-aswj-lesson="<?php echo (int) $lesson_id; ?>"
								data-completed="<?php echo $is_done ? '1' : '0'; ?>">
								<span class="aswj-toggle-label-done"><?php esc_html_e( '✓ Completed — tap to undo', 'aswj-lms' ); ?></span>
								<span class="aswj-toggle-label-todo"><?php esc_html_e( 'Mark lesson as complete', 'aswj-lms' ); ?></span>
							</button>
						<?php else : ?>
							<p class="aswj-muted aswj-small">
								<a href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>"><?php esc_html_e( 'Log in', 'aswj-lms' ); ?></a>
								<?php esc_html_e( 'to track your progress on this course.', 'aswj-lms' ); ?>
							</p>
						<?php endif; ?>

						<div class="aswj-lesson-nav">
							<?php if ( $prev ) : ?>
								<a class="aswj-btn aswj-btn-outline" href="<?php echo esc_url( get_permalink( $prev ) ); ?>">&larr; <?php esc_html_e( 'Previous', 'aswj-lms' ); ?></a>
							<?php endif; ?>
							<?php if ( $next ) : ?>
								<a class="aswj-btn aswj-btn-outline" href="<?php echo esc_url( get_permalink( $next ) ); ?>"><?php esc_html_e( 'Next', 'aswj-lms' ); ?> &rarr;</a>
							<?php endif; ?>
						</div>
					</div>

					<?php if ( trim( get_the_content() ) ) : ?>
						<section class="aswj-lesson-notes">
							<h2><?php esc_html_e( 'Lesson notes', 'aswj-lms' ); ?></h2>
							<?php the_content(); ?>
						</section>
					<?php endif; ?>
				<?php else : ?>
					<?php $notice = $course_id ? ASWJ_LMS_Access::denial_notice( $course_id, $check['reason'] ) : array( 'message' => __( 'This lesson is not available.', 'aswj-lms' ), 'cta_url' => '', 'cta_label' => '' ); ?>
					<div class="aswj-notice aswj-notice-locked">
						<h3><?php esc_html_e( 'Lesson Locked', 'aswj-lms' ); ?></h3>
						<p><?php echo esc_html( $notice['message'] ); ?></p>
						<?php if ( ! empty( $notice['cta_url'] ) ) : ?>
							<p><a class="aswj-btn" href="<?php echo esc_url( $notice['cta_url'] ); ?>"><?php echo esc_html( $notice['cta_label'] ); ?></a></p>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>

			<aside class="aswj-lesson-side">
				<h2><?php esc_html_e( 'Course lessons', 'aswj-lms' ); ?></h2>
				<ol class="aswj-lesson-list">
					<?php foreach ( $lessons as $i => $l ) : ?>
						<?php $done = in_array( $l->ID, $done_ids, true ); ?>
						<li class="aswj-lesson-item<?php echo $done ? ' is-complete' : ''; ?><?php echo $l->ID === $lesson_id ? ' is-current' : ''; ?>">
							<span class="aswj-lesson-tick" aria-hidden="true"><?php echo $done ? '&#10003;' : (int) ( $i + 1 ); ?></span>
							<?php if ( $check['allowed'] ) : ?>
								<a class="aswj-lesson-link" href="<?php echo esc_url( get_permalink( $l ) ); ?>"><?php echo esc_html( get_the_title( $l ) ); ?></a>
							<?php else : ?>
								<span class="aswj-lesson-link"><?php echo esc_html( get_the_title( $l ) ); ?></span>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ol>
			</aside>
		</div>
	</div>
	<?php
endwhile;

get_footer();
