<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lesson completion tracking.
 */
class ASWJ_LMS_Progress {

	public static function init() {
		// Reserved for future hooks (e.g. completion certificates).
	}

	private static function table() {
		global $wpdb;
		return $wpdb->prefix . 'aswj_progress';
	}

	public static function mark_complete( $user_id, $lesson_id ) {
		global $wpdb;
		$user_id   = (int) $user_id;
		$lesson_id = (int) $lesson_id;
		$course_id = ASWJ_LMS_Post_Types::get_lesson_course_id( $lesson_id );

		if ( ! $user_id || ! $lesson_id || ! $course_id ) {
			return false;
		}
		if ( self::is_complete( $user_id, $lesson_id ) ) {
			return true;
		}

		$wpdb->insert(
			self::table(),
			array(
				'user_id'      => $user_id,
				'lesson_id'    => $lesson_id,
				'course_id'    => $course_id,
				'completed_at' => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%d', '%s' )
		);

		do_action( 'aswj_lms_lesson_completed', $user_id, $lesson_id, $course_id );
		return true;
	}

	public static function mark_incomplete( $user_id, $lesson_id ) {
		global $wpdb;
		$wpdb->delete(
			self::table(),
			array(
				'user_id'   => (int) $user_id,
				'lesson_id' => (int) $lesson_id,
			),
			array( '%d', '%d' )
		);
		return true;
	}

	public static function is_complete( $user_id, $lesson_id ) {
		global $wpdb;
		$table = self::table();
		return (bool) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE user_id = %d AND lesson_id = %d",
				(int) $user_id,
				(int) $lesson_id
			)
		);
	}

	/**
	 * Completed lesson IDs for a user within a course.
	 *
	 * @return int[]
	 */
	public static function get_completed_lesson_ids( $user_id, $course_id ) {
		global $wpdb;
		$table = self::table();
		$ids   = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT lesson_id FROM {$table} WHERE user_id = %d AND course_id = %d",
				(int) $user_id,
				(int) $course_id
			)
		);
		return array_map( 'intval', $ids );
	}

	/**
	 * @return array{total: int, completed: int, percent: int}
	 */
	public static function get_course_progress( $user_id, $course_id ) {
		$lessons   = ASWJ_LMS_Post_Types::get_course_lessons( $course_id );
		$total     = count( $lessons );
		$completed = 0;

		if ( $total && $user_id ) {
			$done_ids  = self::get_completed_lesson_ids( $user_id, $course_id );
			$lesson_ids = wp_list_pluck( $lessons, 'ID' );
			$completed = count( array_intersect( $done_ids, $lesson_ids ) );
		}

		return array(
			'total'     => $total,
			'completed' => $completed,
			'percent'   => $total ? (int) round( $completed / $total * 100 ) : 0,
		);
	}
}
