<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcodes:
 *  [aswj_courses]  — course catalog grid (filterable by access type).
 *  [aswj_portal]   — logged-in student portal (my courses, progress, profile).
 */
class ASWJ_LMS_Shortcodes {

	public static function init() {
		add_shortcode( 'aswj_courses', array( __CLASS__, 'courses' ) );
		add_shortcode( 'aswj_portal', array( __CLASS__, 'portal' ) );
	}

	public static function courses( $atts ) {
		$atts = shortcode_atts(
			array(
				'type'    => '', // free|paid|subscription|diploma
				'sisters' => '', // '1' to only show sisters-only courses
			),
			$atts,
			'aswj_courses'
		);

		$args = array(
			'post_type'      => 'aswj_course',
			'posts_per_page' => -1,
			'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
			'meta_query'     => array(),
		);
		if ( $atts['type'] && in_array( $atts['type'], ASWJ_LMS_Access::TYPES, true ) ) {
			$args['meta_query'][] = array(
				'key'   => '_aswj_access_type',
				'value' => $atts['type'],
			);
		}
		if ( '1' === $atts['sisters'] ) {
			$args['meta_query'][] = array(
				'key'   => '_aswj_sisters_only',
				'value' => '1',
			);
		}

		$courses = get_posts( $args );
		$user_id = get_current_user_id();

		ob_start();
		echo '<div class="aswj-lms aswj-course-grid">';
		if ( ! $courses ) {
			echo '<p class="aswj-muted">' . esc_html__( 'No courses available yet. Please check back soon, in shaa Allah.', 'aswj-lms' ) . '</p>';
		}
		foreach ( $courses as $course ) {
			ASWJ_LMS_Templates::part(
				'course-card',
				array(
					'course'  => $course,
					'user_id' => $user_id,
				)
			);
		}
		echo '</div>';
		return ob_get_clean();
	}

	public static function portal( $atts ) {
		if ( ! is_user_logged_in() ) {
			$login_url    = wp_login_url( ASWJ_LMS_Settings::portal_url() );
			$register_url = ASWJ_LMS_Settings::registration_url();
			return sprintf(
				'<div class="aswj-lms aswj-notice aswj-notice-info">
					<h3>%s</h3><p>%s</p>
					<p><a class="aswj-btn" href="%s">%s</a> <a class="aswj-btn aswj-btn-outline" href="%s">%s</a></p>
				</div>',
				esc_html__( 'Assalamu alaykum', 'aswj-lms' ),
				esc_html__( 'Please log in to access your student portal, or create an account to begin your journey of knowledge.', 'aswj-lms' ),
				esc_url( $login_url ),
				esc_html__( 'Log In', 'aswj-lms' ),
				esc_url( $register_url ),
				esc_html__( 'Create Account', 'aswj-lms' )
			);
		}

		ob_start();
		ASWJ_LMS_Templates::part( 'portal', array( 'user' => wp_get_current_user() ) );
		return ob_get_clean();
	}
}
