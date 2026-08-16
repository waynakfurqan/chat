<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enrollment records: who has access to which course, from what source.
 */
class ASWJ_LMS_Enrollment {

	public static function init() {
		// Nothing hooked yet; kept for symmetry/future cron (subscription expiry).
	}

	private static function table() {
		global $wpdb;
		return $wpdb->prefix . 'aswj_enrollments';
	}

	/**
	 * Enroll a user in a course (idempotent — re-enrolling reactivates).
	 *
	 * @param string      $source  manual|free|payment|subscription
	 * @param string|null $expires MySQL datetime or null for no expiry.
	 */
	public static function enroll( $user_id, $course_id, $source = 'manual', $expires = null ) {
		global $wpdb;
		$user_id   = (int) $user_id;
		$course_id = (int) $course_id;
		if ( ! $user_id || ! $course_id ) {
			return false;
		}

		$existing = self::get_enrollment( $user_id, $course_id );
		if ( $existing ) {
			$wpdb->update(
				self::table(),
				array(
					'status'     => 'active',
					'source'     => $source,
					'expires_at' => $expires,
				),
				array( 'id' => $existing->id ),
				array( '%s', '%s', '%s' ),
				array( '%d' )
			);
		} else {
			$wpdb->insert(
				self::table(),
				array(
					'user_id'     => $user_id,
					'course_id'   => $course_id,
					'status'      => 'active',
					'source'      => $source,
					'enrolled_at' => current_time( 'mysql' ),
					'expires_at'  => $expires,
				),
				array( '%d', '%d', '%s', '%s', '%s', '%s' )
			);
		}

		do_action( 'aswj_lms_enrolled', $user_id, $course_id, $source );
		return true;
	}

	public static function unenroll( $user_id, $course_id ) {
		global $wpdb;
		$wpdb->update(
			self::table(),
			array( 'status' => 'revoked' ),
			array(
				'user_id'   => (int) $user_id,
				'course_id' => (int) $course_id,
			),
			array( '%s' ),
			array( '%d', '%d' )
		);
	}

	public static function get_enrollment( $user_id, $course_id ) {
		global $wpdb;
		$table = self::table();
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE user_id = %d AND course_id = %d",
				(int) $user_id,
				(int) $course_id
			)
		);
	}

	public static function is_enrolled( $user_id, $course_id ) {
		$row = self::get_enrollment( $user_id, $course_id );
		if ( ! $row || 'active' !== $row->status ) {
			return false;
		}
		if ( $row->expires_at && strtotime( $row->expires_at ) < current_time( 'timestamp' ) ) {
			return false;
		}
		return true;
	}

	/**
	 * All active course IDs for a user.
	 *
	 * @return int[]
	 */
	public static function get_user_course_ids( $user_id ) {
		global $wpdb;
		$table = self::table();
		$now   = current_time( 'mysql' );
		$ids   = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT course_id FROM {$table}
				 WHERE user_id = %d AND status = 'active'
				 AND (expires_at IS NULL OR expires_at > %s)",
				(int) $user_id,
				$now
			)
		);
		return array_map( 'intval', $ids );
	}

	/**
	 * All enrollments for a course (for the admin screen).
	 */
	public static function get_course_enrollments( $course_id ) {
		global $wpdb;
		$table = self::table();
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE course_id = %d ORDER BY enrolled_at DESC",
				(int) $course_id
			)
		);
	}

	/**
	 * Extend/set expiry on all subscription-sourced enrollments for a user.
	 */
	public static function update_subscription_expiry( $user_id, $expires ) {
		global $wpdb;
		$wpdb->update(
			self::table(),
			array( 'expires_at' => $expires, 'status' => 'active' ),
			array(
				'user_id' => (int) $user_id,
				'source'  => 'subscription',
			),
			array( '%s', '%s' ),
			array( '%d', '%s' )
		);
	}

	public static function revoke_subscription_enrollments( $user_id ) {
		global $wpdb;
		$wpdb->update(
			self::table(),
			array( 'status' => 'revoked' ),
			array(
				'user_id' => (int) $user_id,
				'source'  => 'subscription',
			),
			array( '%s' ),
			array( '%d', '%s' )
		);
	}
}
