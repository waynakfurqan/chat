<?php
/**
 * Plugin Name:       ASWJ College LMS
 * Plugin URI:        https://aswjcollege.com.au
 * Description:       Islamic knowledge course system for ASWJ College Australia. Courses, lessons, gated YouTube videos, progress tracking, student portal, and Fluent Forms Pro integration for registration and payments.
 * Version:           1.0.0
 * Author:            ASWJ College Australia
 * License:           GPL-2.0-or-later
 * Text Domain:       aswj-lms
 * Requires at least: 5.8
 * Requires PHP:      7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ASWJ_LMS_VERSION', '1.0.0' );
define( 'ASWJ_LMS_FILE', __FILE__ );
define( 'ASWJ_LMS_DIR', plugin_dir_path( __FILE__ ) );
define( 'ASWJ_LMS_URL', plugin_dir_url( __FILE__ ) );

require_once ASWJ_LMS_DIR . 'includes/class-aswj-lms-install.php';
require_once ASWJ_LMS_DIR . 'includes/class-aswj-lms-post-types.php';
require_once ASWJ_LMS_DIR . 'includes/class-aswj-lms-settings.php';
require_once ASWJ_LMS_DIR . 'includes/class-aswj-lms-enrollment.php';
require_once ASWJ_LMS_DIR . 'includes/class-aswj-lms-access.php';
require_once ASWJ_LMS_DIR . 'includes/class-aswj-lms-progress.php';
require_once ASWJ_LMS_DIR . 'includes/class-aswj-lms-video.php';
require_once ASWJ_LMS_DIR . 'includes/class-aswj-lms-ajax.php';
require_once ASWJ_LMS_DIR . 'includes/class-aswj-lms-profile.php';
require_once ASWJ_LMS_DIR . 'includes/class-aswj-lms-playlist.php';
require_once ASWJ_LMS_DIR . 'includes/class-aswj-lms-shortcodes.php';
require_once ASWJ_LMS_DIR . 'includes/class-aswj-lms-templates.php';
require_once ASWJ_LMS_DIR . 'includes/class-aswj-lms-fluentforms.php';
require_once ASWJ_LMS_DIR . 'includes/class-aswj-lms-meta-boxes.php';
require_once ASWJ_LMS_DIR . 'includes/class-aswj-lms-admin.php';

register_activation_hook( __FILE__, array( 'ASWJ_LMS_Install', 'activate' ) );

/**
 * Main plugin bootstrap.
 */
final class ASWJ_LMS {

	/** @var ASWJ_LMS */
	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		ASWJ_LMS_Install::maybe_upgrade();
		ASWJ_LMS_Post_Types::init();
		ASWJ_LMS_Enrollment::init();
		ASWJ_LMS_Progress::init();
		ASWJ_LMS_Ajax::init();
		ASWJ_LMS_Profile::init();
		ASWJ_LMS_Playlist::init();
		ASWJ_LMS_Shortcodes::init();
		ASWJ_LMS_Templates::init();
		ASWJ_LMS_FluentForms::init();

		if ( is_admin() ) {
			ASWJ_LMS_Meta_Boxes::init();
			ASWJ_LMS_Admin::init();
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function enqueue_assets() {
		wp_register_style(
			'aswj-lms',
			ASWJ_LMS_URL . 'assets/css/aswj-lms.css',
			array(),
			ASWJ_LMS_VERSION
		);
		wp_register_script(
			'aswj-lms',
			ASWJ_LMS_URL . 'assets/js/aswj-lms.js',
			array(),
			ASWJ_LMS_VERSION,
			true
		);
		wp_localize_script(
			'aswj-lms',
			'aswjLms',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'aswj_lms_nonce' ),
			)
		);

		// Load everywhere LMS content can appear (shortcodes + CPT singles).
		if ( is_singular( array( 'aswj_course', 'aswj_lesson' ) ) || $this->page_has_lms_shortcode() ) {
			wp_enqueue_style( 'aswj-lms' );
			wp_enqueue_script( 'aswj-lms' );
		}
	}

	private function page_has_lms_shortcode() {
		if ( ! is_singular() ) {
			return false;
		}
		$post = get_post();
		if ( ! $post ) {
			return false;
		}
		foreach ( array( 'aswj_home', 'aswj_courses', 'aswj_portal', 'aswj_register', 'aswj_login', 'aswj_subscribe', 'aswj_enroll' ) as $tag ) {
			if ( has_shortcode( $post->post_content, $tag ) ) {
				return true;
			}
		}
		return false;
	}
}

add_action( 'plugins_loaded', array( 'ASWJ_LMS', 'instance' ) );
