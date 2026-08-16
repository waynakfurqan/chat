<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Decides whether a user may view a course's content.
 *
 * Course access model:
 *  - Access type (one of): free, paid, subscription, diploma.
 *  - "Sisters only" is a separate restriction that composes with any type,
 *    so a course can be e.g. free + sisters-only, or paid + sisters-only.
 */
class ASWJ_LMS_Access {

	const TYPES = array( 'free', 'paid', 'subscription', 'diploma' );

	public static function get_access_type( $course_id ) {
		$type = get_post_meta( $course_id, '_aswj_access_type', true );
		return in_array( $type, self::TYPES, true ) ? $type : 'free';
	}

	public static function is_sisters_only( $course_id ) {
		return '1' === get_post_meta( $course_id, '_aswj_sisters_only', true );
	}

	public static function is_sister( $user_id ) {
		return '1' === get_user_meta( $user_id, 'aswj_is_sister', true );
	}

	public static function is_diploma_student( $user_id ) {
		return '1' === get_user_meta( $user_id, 'aswj_is_diploma', true );
	}

	/**
	 * @return array{allowed: bool, reason: string}
	 *         reason: ok|login|sisters|payment|subscription|diploma
	 */
	public static function check( $user_id, $course_id ) {
		$user_id   = (int) $user_id;
		$course_id = (int) $course_id;

		if ( $user_id && user_can( $user_id, 'manage_options' ) ) {
			return array( 'allowed' => true, 'reason' => 'ok' );
		}

		if ( ! $user_id ) {
			// Guests may watch free, non-restricted courses ("open to anyone").
			if ( 'free' === self::get_access_type( $course_id ) && ! self::is_sisters_only( $course_id ) ) {
				return array( 'allowed' => true, 'reason' => 'ok' );
			}
			return array( 'allowed' => false, 'reason' => 'login' );
		}

		if ( self::is_sisters_only( $course_id ) && ! self::is_sister( $user_id ) ) {
			return array( 'allowed' => false, 'reason' => 'sisters' );
		}

		$type = self::get_access_type( $course_id );

		// Manual/admin enrollment always wins.
		if ( ASWJ_LMS_Enrollment::is_enrolled( $user_id, $course_id ) ) {
			return array( 'allowed' => true, 'reason' => 'ok' );
		}

		switch ( $type ) {
			case 'free':
				return array( 'allowed' => true, 'reason' => 'ok' );

			case 'diploma':
				if ( self::is_diploma_student( $user_id ) ) {
					return array( 'allowed' => true, 'reason' => 'ok' );
				}
				return array( 'allowed' => false, 'reason' => 'diploma' );

			case 'subscription':
				return array( 'allowed' => false, 'reason' => 'subscription' );

			case 'paid':
			default:
				return array( 'allowed' => false, 'reason' => 'payment' );
		}
	}

	public static function can_view_course( $course_id, $user_id = null ) {
		$user_id = null === $user_id ? get_current_user_id() : (int) $user_id;
		$result  = self::check( $user_id, $course_id );
		return $result['allowed'];
	}

	/**
	 * Human-readable label for the course access badge.
	 */
	public static function badge_label( $course_id ) {
		$labels = array(
			'free'         => __( 'Free', 'aswj-lms' ),
			'paid'         => __( 'Paid', 'aswj-lms' ),
			'subscription' => __( 'Subscription', 'aswj-lms' ),
			'diploma'      => __( 'Diploma Students', 'aswj-lms' ),
		);
		$label = $labels[ self::get_access_type( $course_id ) ];
		if ( self::is_sisters_only( $course_id ) ) {
			$label .= ' · ' . __( 'Sisters Only', 'aswj-lms' );
		}
		return $label;
	}

	/**
	 * Message + call-to-action for users who are denied access.
	 *
	 * @return array{message: string, cta_url: string, cta_label: string}
	 */
	public static function denial_notice( $course_id, $reason ) {
		switch ( $reason ) {
			case 'login':
				return array(
					'message'   => __( 'Please log in or create a free account to access this course.', 'aswj-lms' ),
					'cta_url'   => wp_login_url( get_permalink( $course_id ) ),
					'cta_label' => __( 'Log In', 'aswj-lms' ),
				);
			case 'sisters':
				return array(
					'message'   => __( 'This course is exclusively for our sisters. If you believe you should have access, please contact the college.', 'aswj-lms' ),
					'cta_url'   => '',
					'cta_label' => '',
				);
			case 'diploma':
				return array(
					'message'   => __( 'This course is exclusive to our Diploma Program students. Contact the college to join the program.', 'aswj-lms' ),
					'cta_url'   => '',
					'cta_label' => '',
				);
			case 'subscription':
				return array(
					'message'   => __( 'This course requires an active subscription.', 'aswj-lms' ),
					'cta_url'   => self::purchase_url( $course_id ),
					'cta_label' => __( 'Subscribe', 'aswj-lms' ),
				);
			case 'payment':
			default:
				return array(
					'message'   => __( 'This is a paid course. Enroll to get full access.', 'aswj-lms' ),
					'cta_url'   => self::purchase_url( $course_id ),
					'cta_label' => __( 'Enroll Now', 'aswj-lms' ),
				);
		}
	}

	/**
	 * URL of the page holding the Fluent Forms payment form for this course.
	 */
	public static function purchase_url( $course_id ) {
		$page_id = (int) get_post_meta( $course_id, '_aswj_purchase_page_id', true );
		return $page_id ? get_permalink( $page_id ) : '';
	}
}
