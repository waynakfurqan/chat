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
			'phone_field_name'       => 'phone',
			'age_field_name'         => 'age',
			'dob_field_name'         => 'dob',
			'subscription_form_ids'  => '',
			'subscribe_page_id'      => 0,
			'login_page_id'          => 0,
			'youtube_api_key'        => '',
			'contact_email'          => get_option( 'admin_email' ),
			'logo_url'               => '',
			'instagram_url'          => 'https://www.instagram.com/aswjcollege',
			'facebook_url'           => 'https://www.facebook.com/aswjcollegemelb',
			'hero_title'             => __( 'Seek Authentic Islamic Knowledge', 'aswj-lms' ),
			'hero_subtitle'          => __( 'Structured courses upon the Quran and the Sunnah with the understanding of the righteous predecessors — study online, at your own pace, wherever you are.', 'aswj-lms' ),
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

	public static function subscribe_url() {
		$id = (int) self::get( 'subscribe_page_id' );
		return $id ? get_permalink( $id ) : '';
	}

	public static function login_url_page( $redirect = '' ) {
		$id = (int) self::get( 'login_page_id' );
		if ( $id ) {
			$url = get_permalink( $id );
			return $redirect ? add_query_arg( 'redirect_to', rawurlencode( $redirect ), $url ) : $url;
		}
		return wp_login_url( $redirect );
	}
}
