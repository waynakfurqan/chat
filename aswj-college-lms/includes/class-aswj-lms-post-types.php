<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the Course and Lesson post types.
 */
class ASWJ_LMS_Post_Types {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
	}

	public static function register() {
		register_post_type(
			'aswj_course',
			array(
				'labels'       => array(
					'name'          => __( 'Courses', 'aswj-lms' ),
					'singular_name' => __( 'Course', 'aswj-lms' ),
					'add_new_item'  => __( 'Add New Course', 'aswj-lms' ),
					'edit_item'     => __( 'Edit Course', 'aswj-lms' ),
					'menu_name'     => __( 'ASWJ Courses', 'aswj-lms' ),
				),
				'public'       => true,
				'has_archive'  => false,
				'menu_icon'    => 'dashicons-welcome-learn-more',
				'menu_position'=> 25,
				'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes' ),
				'rewrite'      => array( 'slug' => 'courses' ),
				'show_in_rest' => true,
			)
		);

		register_post_type(
			'aswj_lesson',
			array(
				'labels'       => array(
					'name'          => __( 'Lessons', 'aswj-lms' ),
					'singular_name' => __( 'Lesson', 'aswj-lms' ),
					'add_new_item'  => __( 'Add New Lesson', 'aswj-lms' ),
					'edit_item'     => __( 'Edit Lesson', 'aswj-lms' ),
					'all_items'     => __( 'Lessons', 'aswj-lms' ),
				),
				'public'       => true,
				'has_archive'  => false,
				'show_in_menu' => 'edit.php?post_type=aswj_course',
				'supports'     => array( 'title', 'editor', 'page-attributes' ),
				'rewrite'      => array( 'slug' => 'lessons' ),
				'show_in_rest' => true,
			)
		);
	}

	/**
	 * Ordered lessons belonging to a course.
	 *
	 * @return WP_Post[]
	 */
	public static function get_course_lessons( $course_id ) {
		return get_posts(
			array(
				'post_type'      => 'aswj_lesson',
				'posts_per_page' => -1,
				'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'ASC' ),
				'meta_key'       => '_aswj_course_id',
				'meta_value'     => (int) $course_id,
			)
		);
	}

	public static function get_lesson_course_id( $lesson_id ) {
		return (int) get_post_meta( $lesson_id, '_aswj_course_id', true );
	}
}
