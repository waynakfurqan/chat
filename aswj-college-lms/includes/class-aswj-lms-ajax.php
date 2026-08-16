<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Front-end AJAX endpoints: gated video fetch and lesson progress ticking.
 */
class ASWJ_LMS_Ajax {

	public static function init() {
		add_action( 'wp_ajax_aswj_get_video', array( __CLASS__, 'get_video' ) );
		add_action( 'wp_ajax_nopriv_aswj_get_video', array( __CLASS__, 'get_video' ) );
		add_action( 'wp_ajax_aswj_toggle_lesson', array( __CLASS__, 'toggle_lesson' ) );
	}

	public static function get_video() {
		check_ajax_referer( 'aswj_lms_nonce', 'nonce' );

		$lesson_id = isset( $_POST['lesson_id'] ) ? absint( $_POST['lesson_id'] ) : 0;
		$lesson    = get_post( $lesson_id );
		if ( ! $lesson || 'aswj_lesson' !== $lesson->post_type || 'publish' !== $lesson->post_status ) {
			wp_send_json_error( array( 'message' => __( 'Lesson not found.', 'aswj-lms' ) ), 404 );
		}

		$course_id = ASWJ_LMS_Post_Types::get_lesson_course_id( $lesson_id );
		$user_id   = get_current_user_id();
		$check     = ASWJ_LMS_Access::check( $user_id, $course_id );

		if ( ! $check['allowed'] ) {
			$notice = ASWJ_LMS_Access::denial_notice( $course_id, $check['reason'] );
			wp_send_json_error( array( 'message' => $notice['message'] ), 403 );
		}

		// Auto-enroll logged-in users on free courses so they appear in "My Courses".
		if ( $user_id && 'free' === ASWJ_LMS_Access::get_access_type( $course_id ) && ! ASWJ_LMS_Enrollment::is_enrolled( $user_id, $course_id ) ) {
			ASWJ_LMS_Enrollment::enroll( $user_id, $course_id, 'free' );
		}

		wp_send_json_success( array( 'html' => ASWJ_LMS_Video::embed_html( $lesson_id ) ) );
	}

	public static function toggle_lesson() {
		check_ajax_referer( 'aswj_lms_nonce', 'nonce' );

		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			wp_send_json_error( array( 'message' => __( 'Please log in to track your progress.', 'aswj-lms' ) ), 401 );
		}

		$lesson_id = isset( $_POST['lesson_id'] ) ? absint( $_POST['lesson_id'] ) : 0;
		$lesson    = get_post( $lesson_id );
		if ( ! $lesson || 'aswj_lesson' !== $lesson->post_type ) {
			wp_send_json_error( array( 'message' => __( 'Lesson not found.', 'aswj-lms' ) ), 404 );
		}

		$course_id = ASWJ_LMS_Post_Types::get_lesson_course_id( $lesson_id );
		$check     = ASWJ_LMS_Access::check( $user_id, $course_id );
		if ( ! $check['allowed'] ) {
			wp_send_json_error( array( 'message' => __( 'You do not have access to this course.', 'aswj-lms' ) ), 403 );
		}

		// Ensure the student is recorded as enrolled once they start ticking lessons.
		if ( ! ASWJ_LMS_Enrollment::is_enrolled( $user_id, $course_id ) ) {
			ASWJ_LMS_Enrollment::enroll( $user_id, $course_id, ASWJ_LMS_Access::get_access_type( $course_id ) === 'free' ? 'free' : 'manual' );
		}

		$completed = isset( $_POST['completed'] ) && '1' === $_POST['completed'];
		if ( $completed ) {
			ASWJ_LMS_Progress::mark_complete( $user_id, $lesson_id );
		} else {
			ASWJ_LMS_Progress::mark_incomplete( $user_id, $lesson_id );
		}

		$progress = ASWJ_LMS_Progress::get_course_progress( $user_id, $course_id );

		wp_send_json_success(
			array(
				'completed' => $completed,
				'progress'  => $progress,
			)
		);
	}
}
