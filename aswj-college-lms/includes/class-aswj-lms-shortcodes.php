<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcodes:
 *  [aswj_home]      — full home page (hero, current courses, features, pathways, CTA).
 *  [aswj_courses]   — course catalog grid (filterable by access type / running).
 *  [aswj_portal]    — logged-in student portal (my courses, progress, profile).
 *  [aswj_register]  — account registration page (Fluent Forms form + welcome panel).
 *  [aswj_login]     — styled login form.
 *  [aswj_subscribe] — monthly all-access subscription page.
 *  [aswj_enroll]    — per-course enrollment/payment page (auto-created).
 */
class ASWJ_LMS_Shortcodes {

	public static function init() {
		add_shortcode( 'aswj_home', array( __CLASS__, 'home' ) );
		add_shortcode( 'aswj_courses', array( __CLASS__, 'courses' ) );
		add_shortcode( 'aswj_portal', array( __CLASS__, 'portal' ) );
		add_shortcode( 'aswj_register', array( __CLASS__, 'register' ) );
		add_shortcode( 'aswj_login', array( __CLASS__, 'login' ) );
		add_shortcode( 'aswj_subscribe', array( __CLASS__, 'subscribe' ) );
		add_shortcode( 'aswj_enroll', array( __CLASS__, 'enroll' ) );
	}

	public static function home() {
		ob_start();
		ASWJ_LMS_Templates::part( 'home' );
		return ob_get_clean();
	}

	public static function register() {
		ob_start();
		ASWJ_LMS_Templates::part( 'register' );
		return ob_get_clean();
	}

	public static function login() {
		ob_start();
		ASWJ_LMS_Templates::part( 'login' );
		return ob_get_clean();
	}

	public static function subscribe() {
		ob_start();
		ASWJ_LMS_Templates::part( 'subscribe' );
		return ob_get_clean();
	}

	public static function enroll( $atts ) {
		$atts   = shortcode_atts( array( 'course_id' => 0 ), $atts, 'aswj_enroll' );
		$course = get_post( absint( $atts['course_id'] ) );
		if ( ! $course || 'aswj_course' !== $course->post_type ) {
			return '';
		}
		ob_start();
		ASWJ_LMS_Templates::part( 'enroll', array( 'course' => $course ) );
		return ob_get_clean();
	}

	public static function courses( $atts ) {
		$atts = shortcode_atts(
			array(
				'type'    => '', // free|paid|subscription|diploma
				'sisters' => '', // '1' to only show sisters-only courses
				'running' => '', // '1' to only show currently-running courses
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
		if ( '1' === $atts['running'] ) {
			$args['meta_query'][] = array(
				'key'   => '_aswj_running',
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
			$login_url    = ASWJ_LMS_Settings::login_url_page( ASWJ_LMS_Settings::portal_url() );
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
