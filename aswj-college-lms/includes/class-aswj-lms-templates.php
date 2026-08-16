<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Routes single course/lesson views to plugin templates.
 * A theme can override by placing files in {theme}/aswj-lms/.
 */
class ASWJ_LMS_Templates {

	public static function init() {
		add_filter( 'template_include', array( __CLASS__, 'template_include' ) );
	}

	public static function template_include( $template ) {
		if ( is_singular( 'aswj_course' ) ) {
			return self::locate( 'single-course.php', $template );
		}
		if ( is_singular( 'aswj_lesson' ) ) {
			return self::locate( 'single-lesson.php', $template );
		}
		return $template;
	}

	private static function locate( $file, $fallback ) {
		$theme_file = locate_template( 'aswj-lms/' . $file );
		if ( $theme_file ) {
			return $theme_file;
		}
		$plugin_file = ASWJ_LMS_DIR . 'templates/' . $file;
		return file_exists( $plugin_file ) ? $plugin_file : $fallback;
	}

	/**
	 * Render a template part from templates/parts/ with variables in scope.
	 */
	public static function part( $name, array $vars = array() ) {
		$file = ASWJ_LMS_DIR . 'templates/parts/' . $name . '.php';
		if ( ! file_exists( $file ) ) {
			return;
		}
		extract( $vars ); // phpcs:ignore WordPress.PHP.DontExtract
		include $file;
	}
}
