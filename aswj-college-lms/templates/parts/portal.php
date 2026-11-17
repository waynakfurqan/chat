<?php
/**
 * Student portal: greeting with Gregorian + Hijri dates, the deep
 * continue-learning card, my courses, pending notices and profile.
 * Expects: $user (WP_User).
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id     = $user->ID;
$course_ids  = ASWJ_LMS_Enrollment::get_user_course_ids( $user_id );
$pending_ids = ASWJ_LMS_Enrollment::get_user_pending_course_ids( $user_id );
$is_sister   = ASWJ_LMS_Access::is_sister( $user_id );
$is_diploma  = ASWJ_LMS_Access::is_diploma_student( $user_id );
$has_sub     = ASWJ_LMS_Access::has_active_subscription( $user_id );
$phone       = get_user_meta( $user_id, 'aswj_phone', true );

// Diploma students see all diploma courses; subscribers all paid courses.
if ( $is_diploma ) {
	$diploma_courses = get_posts(
		array(
			'post_type'      => 'aswj_course',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_key'       => '_aswj_access_type',
			'meta_value'     => 'diploma',
		)
	);
	$course_ids = array_merge( $course_ids, array_map( 'intval', $diploma_courses ) );
}
if ( $has_sub ) {
	$sub_courses = get_posts(
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
	);
	$course_ids = array_merge( $course_ids, array_map( 'intval', $sub_courses ) );
}
$course_ids = array_values(
	array_filter(
		array_unique( $course_ids ),
		function ( $cid ) use ( $user_id ) {
			return ASWJ_LMS_Access::can_view_course( $cid, $user_id );
		}
	)
);

$courses = $course_ids ? get_posts(
	array(
		'post_type'      => 'aswj_course',
		'posts_per_page' => -1,
		'post__in'       => $course_ids,
		'orderby'        => 'post__in',
	)
) : array();

// Continue-learning: the course furthest along but unfinished.
$continue_course = null;
$continue_prog   = null;
$continue_lesson = null;
foreach ( $courses as $maybe ) {
	$p = ASWJ_LMS_Progress::get_course_progress( $user_id, $maybe->ID );
	if ( $p['total'] > 0 && $p['completed'] > 0 && $p['completed'] < $p['total'] ) {
		if ( ! $continue_prog || $p['percent'] > $continue_prog['percent'] ) {
			$continue_course = $maybe;
			$continue_prog   = $p;
		}
	}
}
if ( ! $continue_course ) {
	foreach ( $courses as $maybe ) {
		$p = ASWJ_LMS_Progress::get_course_progress( $user_id, $maybe->ID );
		if ( $p['total'] > 0 && $p['completed'] < $p['total'] ) {
			$continue_course = $maybe;
			$continue_prog   = $p;
			break;
		}
	}
}
if ( $continue_course ) {
	$done = ASWJ_LMS_Progress::get_completed_lesson_ids( $user_id, $continue_course->ID );
	foreach ( ASWJ_LMS_Post_Types::get_course_lessons( $continue_course->ID ) as $l ) {
		if ( ! in_array( $l->ID, $done, true ) ) {
			$continue_lesson = $l;
			break;
		}
	}
}

// Gregorian + Hijri dates.
$date_line = date_i18n( get_option( 'date_format' ) );
if ( class_exists( 'IntlDateFormatter' ) ) {
	try {
		$hijri_fmt = new IntlDateFormatter(
			'en_AU@calendar=islamic-umalqura',
			IntlDateFormatter::LONG,
			IntlDateFormatter::NONE,
			wp_timezone(),
			IntlDateFormatter::TRADITIONAL
		);
		$hijri = $hijri_fmt->format( time() );
		if ( $hijri ) {
			$date_line .= ' · ' . $hijri;
		}
	} catch ( Exception $e ) {
		// Hijri date is decorative; fall back silently to Gregorian only.
	}
}

$profile_updated = isset( $_GET['aswj_profile_updated'] ); // phpcs:ignore WordPress.Security.NonceVerification
$profile_errors  = isset( $_GET['aswj_profile_error'] ) ? array_filter( explode( ',', sanitize_text_field( wp_unslash( $_GET['aswj_profile_error'] ) ) ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification

$cont_arabic = $continue_course ? get_post_meta( $continue_course->ID, '_aswj_arabic_title', true ) : '';
?>
<div class="aswj-lms aw aw-portal">
	<header class="aw-portal-head">
		<div>
			<p class="aw-portal-head__salam" lang="ar" dir="rtl">السلام عليكم ورحمة الله</p>
			<h2><?php echo esc_html( $user->display_name ); ?></h2>
			<p class="aw-portal-head__dates"><?php echo esc_html( $date_line ); ?></p>
			<div class="aw-portal-tags">
				<span class="aw-badge"><?php esc_html_e( 'Student', 'aswj-lms' ); ?></span>
				<?php if ( $is_diploma ) : ?>
					<span class="aw-badge aw-badge--deep"><?php esc_html_e( 'Diploma program', 'aswj-lms' ); ?></span>
				<?php endif; ?>
				<?php if ( $is_sister ) : ?>
					<span class="aw-badge aw-badge--green"><?php esc_html_e( 'Sister', 'aswj-lms' ); ?></span>
				<?php endif; ?>
				<?php if ( $has_sub ) : ?>
					<span class="aw-badge aw-badge--blue"><?php esc_html_e( 'All-access member', 'aswj-lms' ); ?></span>
				<?php endif; ?>
			</div>
		</div>
		<div class="aw-portal-actions">
			<a class="aw-btn aw-btn--secondary aw-btn--sm" href="<?php echo esc_url( ASWJ_LMS_Settings::catalog_url() ); ?>"><?php esc_html_e( 'Browse courses', 'aswj-lms' ); ?></a>
			<a class="aw-btn aw-btn--ghost aw-btn--sm" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>"><?php esc_html_e( 'Sign out', 'aswj-lms' ); ?></a>
		</div>
	</header>

	<?php if ( $continue_course && $continue_lesson ) : ?>
		<section class="aw-deep aw-pattern aw-continue">
			<div class="aw-continue__cover aw-arch">
				<?php if ( has_post_thumbnail( $continue_course->ID ) ) : ?>
					<?php echo get_the_post_thumbnail( $continue_course->ID, 'medium' ); ?>
				<?php elseif ( $cont_arabic ) : ?>
					<span class="aw-cover__arabic" lang="ar" dir="rtl"><?php echo esc_html( $cont_arabic ); ?></span>
				<?php else : ?>
					<?php echo ASWJ_LMS_Templates::mark_svg( 'aw-mark' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php endif; ?>
			</div>
			<div class="aw-continue__body">
				<p class="aw-eyebrow aw-eyebrow--on-deep"><?php esc_html_e( 'Continue learning', 'aswj-lms' ); ?></p>
				<h3><?php echo esc_html( get_the_title( $continue_course ) ); ?></h3>
				<p class="aw-small"><?php echo esc_html( get_the_title( $continue_lesson ) ); ?></p>
				<div class="aw-continue__progressrow">
					<div class="aw-progress"><i style="width:<?php echo esc_attr( $continue_prog['percent'] ); ?>%"></i></div>
					<span>
						<?php
						printf(
							/* translators: 1: completed, 2: total */
							esc_html__( '%1$d of %2$d lessons', 'aswj-lms' ),
							(int) $continue_prog['completed'],
							(int) $continue_prog['total']
						);
						?>
					</span>
				</div>
				<div class="aw-continue__actions">
					<a class="aw-btn aw-btn--light" href="<?php echo esc_url( get_permalink( $continue_lesson ) ); ?>"><?php esc_html_e( 'Resume lesson', 'aswj-lms' ); ?></a>
					<a class="aw-btn aw-btn--outline-light" href="<?php echo esc_url( get_permalink( $continue_course ) ); ?>"><?php esc_html_e( 'Course overview', 'aswj-lms' ); ?></a>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $pending_ids ) : ?>
		<section class="aw-section" style="margin-bottom:40px">
			<div class="aw-notice aw-notice--warn">
				<h3><?php esc_html_e( 'Awaiting payment verification', 'aswj-lms' ); ?></h3>
				<p><?php esc_html_e( 'We have received your registration for the course(s) below. Access is unlocked once your bank transfer or in-person payment is verified by the college.', 'aswj-lms' ); ?></p>
				<ul style="margin:0">
					<?php foreach ( $pending_ids as $pid ) : ?>
						<li><strong><?php echo esc_html( get_the_title( $pid ) ); ?></strong></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
	<?php endif; ?>

	<section class="aw-section">
		<div class="aw-section-head">
			<p class="aw-eyebrow"><?php esc_html_e( 'My courses', 'aswj-lms' ); ?></p>
			<h2 class="aw-heading-2"><?php esc_html_e( 'Your learning', 'aswj-lms' ); ?></h2>
		</div>
		<?php if ( ! $courses ) : ?>
			<div class="aw-notice aw-notice--info">
				<p><?php esc_html_e( 'You are not enrolled in any courses yet.', 'aswj-lms' ); ?></p>
				<p><a class="aw-btn aw-btn--primary" href="<?php echo esc_url( ASWJ_LMS_Settings::catalog_url() ); ?>"><?php esc_html_e( 'Browse courses', 'aswj-lms' ); ?></a></p>
			</div>
		<?php else : ?>
			<div class="aw-course-grid">
				<?php
				foreach ( $courses as $portal_course ) {
					ASWJ_LMS_Templates::part(
						'course-card',
						array(
							'course'  => $portal_course,
							'user_id' => $user_id,
						)
					);
				}
				?>
			</div>
		<?php endif; ?>
	</section>

	<section class="aw-section" id="aswj-profile">
		<div class="aw-section-head">
			<p class="aw-eyebrow"><?php esc_html_e( 'My profile', 'aswj-lms' ); ?></p>
			<h2 class="aw-heading-2"><?php esc_html_e( 'Your details', 'aswj-lms' ); ?></h2>
		</div>

		<?php if ( $profile_updated ) : ?>
			<div class="aw-notice aw-notice--info"><p><?php esc_html_e( 'Your profile has been updated.', 'aswj-lms' ); ?></p></div>
		<?php endif; ?>
		<?php foreach ( $profile_errors as $error_code ) : ?>
			<div class="aw-notice aw-notice--danger"><p><?php echo esc_html( ASWJ_LMS_Profile::error_message( $error_code ) ); ?></p></div>
		<?php endforeach; ?>

		<div class="aw-card" style="padding:clamp(20px,3vw,32px)">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="aw-profile-form">
				<input type="hidden" name="action" value="aswj_update_profile" />
				<?php wp_nonce_field( 'aswj_update_profile' ); ?>

				<div class="aw-grid-2">
					<p class="aw-field">
						<label class="aw-label" for="aswj-first-name"><?php esc_html_e( 'First name', 'aswj-lms' ); ?></label>
						<input class="aw-input" type="text" id="aswj-first-name" name="first_name" value="<?php echo esc_attr( $user->first_name ); ?>" />
					</p>
					<p class="aw-field">
						<label class="aw-label" for="aswj-last-name"><?php esc_html_e( 'Last name', 'aswj-lms' ); ?></label>
						<input class="aw-input" type="text" id="aswj-last-name" name="last_name" value="<?php echo esc_attr( $user->last_name ); ?>" />
					</p>
					<p class="aw-field">
						<label class="aw-label" for="aswj-email"><?php esc_html_e( 'Email', 'aswj-lms' ); ?></label>
						<input class="aw-input" type="email" id="aswj-email" name="email" value="<?php echo esc_attr( $user->user_email ); ?>" />
					</p>
					<p class="aw-field">
						<label class="aw-label" for="aswj-phone"><?php esc_html_e( 'Phone number', 'aswj-lms' ); ?></label>
						<input class="aw-input" type="text" id="aswj-phone" name="phone" value="<?php echo esc_attr( $phone ); ?>" />
					</p>
					<p class="aw-field">
						<label class="aw-label" for="aswj-dob"><?php esc_html_e( 'Date of birth', 'aswj-lms' ); ?></label>
						<input class="aw-input" type="text" id="aswj-dob" name="dob" value="<?php echo esc_attr( get_user_meta( $user_id, 'aswj_dob_raw', true ) ); ?>" placeholder="DD/MM/YYYY" />
					</p>
					<p class="aw-field">
						<label class="aw-label" for="aswj-new-password"><?php esc_html_e( 'New password', 'aswj-lms' ); ?></label>
						<input class="aw-input" type="password" id="aswj-new-password" name="new_password" autocomplete="new-password" placeholder="<?php esc_attr_e( 'Leave blank to keep current', 'aswj-lms' ); ?>" />
					</p>
					<p class="aw-field">
						<label class="aw-label" for="aswj-confirm-password"><?php esc_html_e( 'Confirm new password', 'aswj-lms' ); ?></label>
						<input class="aw-input" type="password" id="aswj-confirm-password" name="confirm_password" autocomplete="new-password" />
					</p>
				</div>

				<p style="margin:8px 0 0">
					<button type="submit" class="aw-btn aw-btn--primary"><?php esc_html_e( 'Save profile', 'aswj-lms' ); ?></button>
				</p>
			</form>
			<p class="aw-caption" style="margin-top:14px">
				<?php
				printf(
					/* translators: 1: username, 2: member since date, 3: contact email link */
					esc_html__( 'Username %1$s · Member since %2$s · Need help? Contact %3$s', 'aswj-lms' ),
					esc_html( $user->user_login ),
					esc_html( date_i18n( get_option( 'date_format' ), strtotime( $user->user_registered ) ) ),
					'<a href="mailto:' . esc_attr( ASWJ_LMS_Settings::get( 'contact_email' ) ) . '">' . esc_html( ASWJ_LMS_Settings::get( 'contact_email' ) ) . '</a>'
				);
				?>
			</p>
		</div>
	</section>
</div>
