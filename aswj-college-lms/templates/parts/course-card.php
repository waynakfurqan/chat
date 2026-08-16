<?php
/**
 * Course card. Expects: $course (WP_Post), $user_id (int).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$course_id = $course->ID;
$progress  = $user_id ? ASWJ_LMS_Progress::get_course_progress( $user_id, $course_id ) : null;
$enrolled  = $user_id ? ASWJ_LMS_Enrollment::is_enrolled( $user_id, $course_id ) : false;
$price     = get_post_meta( $course_id, '_aswj_price_label', true );
$type      = ASWJ_LMS_Access::get_access_type( $course_id );
?>
<article class="aswj-course-card">
	<a class="aswj-course-card-thumb" href="<?php echo esc_url( get_permalink( $course_id ) ); ?>">
		<?php if ( has_post_thumbnail( $course_id ) ) : ?>
			<?php echo get_the_post_thumbnail( $course_id, 'medium_large' ); ?>
		<?php else : ?>
			<span class="aswj-thumb-placeholder" aria-hidden="true">&#xFDFD;</span>
		<?php endif; ?>
		<span class="aswj-badge aswj-badge-<?php echo esc_attr( $type ); ?>"><?php echo esc_html( ASWJ_LMS_Access::badge_label( $course_id ) ); ?></span>
	</a>
	<div class="aswj-course-card-body">
		<h3 class="aswj-course-card-title">
			<a href="<?php echo esc_url( get_permalink( $course_id ) ); ?>"><?php echo esc_html( get_the_title( $course_id ) ); ?></a>
		</h3>
		<p class="aswj-course-card-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt( $course_id ), 22 ) ); ?></p>

		<?php if ( $enrolled && $progress && $progress['total'] > 0 ) : ?>
			<div class="aswj-progress" role="progressbar" aria-valuenow="<?php echo esc_attr( $progress['percent'] ); ?>" aria-valuemin="0" aria-valuemax="100">
				<div class="aswj-progress-bar" style="width: <?php echo esc_attr( $progress['percent'] ); ?>%"></div>
			</div>
			<p class="aswj-muted aswj-small">
				<?php
				printf(
					/* translators: 1: completed lessons, 2: total lessons, 3: percent */
					esc_html__( '%1$d of %2$d lessons complete (%3$d%%)', 'aswj-lms' ),
					(int) $progress['completed'],
					(int) $progress['total'],
					(int) $progress['percent']
				);
				?>
			</p>
		<?php elseif ( $price && in_array( $type, array( 'paid', 'subscription' ), true ) ) : ?>
			<p class="aswj-price"><?php echo esc_html( $price ); ?></p>
		<?php endif; ?>

		<a class="aswj-btn aswj-btn-block" href="<?php echo esc_url( get_permalink( $course_id ) ); ?>">
			<?php echo $enrolled ? esc_html__( 'Continue Learning', 'aswj-lms' ) : esc_html__( 'View Course', 'aswj-lms' ); ?>
		</a>
	</div>
</article>
