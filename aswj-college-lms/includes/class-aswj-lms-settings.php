<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin options accessor.
 */
class ASWJ_LMS_Settings {

	const OPTION_KEY = 'aswj_lms_settings';

	public static function defaults() {
		return array(
			'portal_page_id'         => 0,
			'catalog_page_id'        => 0,
			'registration_page_id'   => 0,
			'registration_form_id'   => 0,
			'gender_field_name'      => 'gender',
			'sister_field_value'     => 'female',
			'subscription_form_ids'  => '',
			'contact_email'          => get_option( 'admin_email' ),
		);
	}

	public static function all() {
		$saved = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		return array_merge( self::defaults(), $saved );
	}

	public static function get( $key ) {
		$all = self::all();
		return isset( $all[ $key ] ) ? $all[ $key ] : null;
	}

	public static function update( array $values ) {
		$all = array_merge( self::all(), $values );
		update_option( self::OPTION_KEY, $all );
	}

	/** @return int[] */
	public static function subscription_form_ids() {
		$raw = (string) self::get( 'subscription_form_ids' );
		return array_filter( array_map( 'absint', array_map( 'trim', explode( ',', $raw ) ) ) );
	}

	public static function portal_url() {
		$id = (int) self::get( 'portal_page_id' );
		return $id ? get_permalink( $id ) : home_url( '/' );
	}

	public static function catalog_url() {
		$id = (int) self::get( 'catalog_page_id' );
		return $id ? get_permalink( $id ) : home_url( '/' );
	}

	public static function registration_url() {
		$id = (int) self::get( 'registration_page_id' );
		return $id ? get_permalink( $id ) : wp_registration_url();
	}
}
