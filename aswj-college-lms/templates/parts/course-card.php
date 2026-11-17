<?php
/**
 * Course card: typeset cover (or poster image), kicker, serif title,
 * one-line description and a meta row. The whole card is one link.
 * Expects: $course (WP_Post), $user_id (int).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$course_id    = $course->ID;
$progress     = $user_id ? ASWJ_LMS_Progress::get_course_progress( $user_id, $course_id ) : null;
$enrolled     = $user_id ? ASWJ_LMS_Enrollment::is_enrolled( $user_id, $course_id ) : false;
$has_access   = $user_id ? ASWJ_LMS_Access::can_view_course( $course_id, $user_id ) : false;
$price        = get_post_meta( $course_id, '_aswj_price_label', true );
$arabic_title = get_post_meta( $course_id, '_aswj_arabic_title', true );
$lessons_n    = count( ASWJ_LMS_Post_Types::get_course_lessons( $course_id ) );
$cover_class  = ASWJ_LMS_Templates::cover_class( $course_id );
$on_dark      = ! in_array( $cover_class, array( 'aw-cover--parchment', 'aw-cover--ivory' ), true );
$badge        = ASWJ_LMS_Templates::access_badge( $course_id, $on_dark );
$show_prog    = ( $enrolled || $has_access ) && $progress && $progress['total'] > 0 && $progress['completed'] > 0;
?>
<a class="aw-course aw-lift" href="<?php echo esc_url( get_permalink( $course_id ) ); ?>">
	<span class="aw-cover <?php echo esc_attr( $cover_class ); ?>">
		<?php if ( has_post_thumbnail( $course_id ) ) : ?>
			<?php echo get_the_post_thumbnail( $course_id, 'medium_large' ); ?>
			<span class="aw-badge aw-badge--glass"><?php echo esc_html( $badge['label'] ); ?></span>
		<?php else : ?>
			<span class="aw-badge <?php echo esc_attr( $badge['class'] ); ?>"><?php echo esc_html( $badge['label'] ); ?></span>
			<?php if ( $arabic_title ) : ?>
				<span class="aw-cover__arabic" lang="ar" dir="rtl"><?php echo esc_html( $arabic_title ); ?></span>
				<span class="aw-cover__en"><?php echo esc_html( get_the_title( $course_id ) ); ?></span>
			<?php else : ?>
				<span class="aw-cover__arabic" style="font-family:var(--aw-font-serif);font-size:clamp(20px,2.2vw,26px)"><?php echo esc_html( get_the_title( $course_id ) ); ?></span>
			<?php endif; ?>
		<?php endif; ?>
	</span>
	<span class="aw-course__body">
		<span class="aw-course__kicker">
			<?php
			printf(
				/* translators: %d: lesson count */
				esc_html( _n( '%d lesson', '%d lessons', $lessons_n, 'aswj-lms' ) ),
				(int) $lessons_n
			);
			?>
		</span>
		<span class="aw-course__title"><?php echo esc_html( get_the_title( $course_id ) ); ?></span>
		<?php if ( has_excerpt( $course_id ) ) : ?>
			<span class="aw-course__desc"><?php echo esc_html( wp_trim_words( get_the_excerpt( $course_id ), 20 ) ); ?></span>
		<?php endif; ?>

		<?php if ( $show_prog ) : ?>
			<span class="aw-progress" role="progressbar" aria-valuenow="<?php echo esc_attr( $progress['percent'] ); ?>" aria-valuemin="0" aria-valuemax="100"><i style="width:<?php echo esc_attr( $progress['percent'] ); ?>%"></i></span>
		<?php endif; ?>

		<span class="aw-course__meta">
			<span>
				<?php
				if ( $show_prog ) {
					printf(
						/* translators: %d: percent complete */
						esc_html__( '%d%% complete', 'aswj-lms' ),
						(int) $progress['percent']
					);
				} elseif ( $price && in_array( ASWJ_LMS_Access::get_access_type( $course_id ), array( 'paid', 'subscription' ), true ) ) {
					echo '<strong>' . esc_html( $price ) . '</strong>';
				} else {
					esc_html_e( 'Self-paced', 'aswj-lms' );
				}
				?>
			</span>
			<strong><?php echo ( $enrolled || $has_access ) && $user_id ? esc_html__( 'Continue', 'aswj-lms' ) : esc_html__( 'View course', 'aswj-lms' ); ?> &rarr;</strong>
		</span>
	</span>
</a>
