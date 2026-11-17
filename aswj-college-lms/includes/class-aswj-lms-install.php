<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Activation: create custom tables, student role, and default options.
 */
class ASWJ_LMS_Install {

	const DB_VERSION = '1.0.0';

	public static function activate() {
		self::create_tables();
		self::create_role();
		update_option( 'aswj_lms_db_version', self::DB_VERSION );

		// Register CPTs then flush so pretty permalinks work immediately.
		ASWJ_LMS_Post_Types::register();
		flush_rewrite_rules();
	}

	public static function maybe_upgrade() {
		if ( get_option( 'aswj_lms_db_version' ) !== self::DB_VERSION ) {
			self::create_tables();
			self::create_role();
			update_option( 'aswj_lms_db_version', self::DB_VERSION );
		}
	}

	private static function create_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();

		$enrollments = "CREATE TABLE {$wpdb->prefix}aswj_enrollments (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL,
			course_id BIGINT UNSIGNED NOT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'active',
			source VARCHAR(40) NOT NULL DEFAULT 'manual',
			enrolled_at DATETIME NOT NULL,
			expires_at DATETIME NULL DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY user_course (user_id, course_id),
			KEY course_id (course_id)
		) $charset;";

		$progress = "CREATE TABLE {$wpdb->prefix}aswj_progress (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL,
			lesson_id BIGINT UNSIGNED NOT NULL,
			course_id BIGINT UNSIGNED NOT NULL,
			completed_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY user_lesson (user_id, lesson_id),
			KEY user_course (user_id, course_id)
		) $charset;";

		dbDelta( $enrollments );
		dbDelta( $progress );
	}

	private static function create_role() {
		if ( ! get_role( 'aswj_student' ) ) {
			add_role(
				'aswj_student',
				__( 'Student', 'aswj-lms' ),
				array( 'read' => true )
			);
		}
	}
}
