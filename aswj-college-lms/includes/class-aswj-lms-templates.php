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

	/**
	 * The brand chevron mark as inline SVG (never recoloured).
	 */
	public static function mark_svg( $class = 'aw-mark' ) {
		return '<svg class="' . esc_attr( $class ) . '" viewBox="0 0 150 180" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">'
			. '<path d="M6 22 L64 6 L64 62 L35 71 L35 158 L6 140 Z" fill="#0089c9"/>'
			. '<path d="M40 37 L98 21 L98 77 L69 86 L69 173 L40 155 Z" fill="#409928" opacity="0.96"/>'
			. '</svg>';
	}

	/**
	 * Rotate typeset-cover colourways so neighbouring cards differ.
	 */
	public static function cover_class( $course_id ) {
		$covers = array( 'aw-cover--deep', 'aw-cover--parchment', 'aw-cover--blue', 'aw-cover--ivory', 'aw-cover--green', 'aw-cover--ink' );
		return $covers[ (int) $course_id % count( $covers ) ];
	}

	/**
	 * Access badge class + label for a course.
	 *
	 * @return array{class: string, label: string}
	 */
	public static function access_badge( $course_id, $on_dark = false ) {
		$type    = ASWJ_LMS_Access::get_access_type( $course_id );
		$classes = array(
			'free'         => 'aw-badge--green',
			'paid'         => $on_dark ? 'aw-badge--glass' : 'aw-badge--deep',
			'subscription' => $on_dark ? 'aw-badge--glass' : 'aw-badge--blue',
			'diploma'      => $on_dark ? 'aw-badge--glass' : '',
		);
		return array(
			'class' => isset( $classes[ $type ] ) ? $classes[ $type ] : '',
			'label' => ASWJ_LMS_Access::badge_label( $course_id ),
		);
	}
}
