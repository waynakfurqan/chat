<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Front-end profile editing from the student portal.
 */
class ASWJ_LMS_Profile {

	public static function init() {
		add_action( 'admin_post_aswj_update_profile', array( __CLASS__, 'handle_update' ) );
	}

	public static function handle_update() {
		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( wp_login_url( ASWJ_LMS_Settings::portal_url() ) );
			exit;
		}
		check_admin_referer( 'aswj_update_profile' );

		$user_id  = get_current_user_id();
		$redirect = ASWJ_LMS_Settings::portal_url();
		$errors   = array();

		$first = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
		$last  = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';
		$phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$pass1 = isset( $_POST['new_password'] ) ? (string) wp_unslash( $_POST['new_password'] ) : '';
		$pass2 = isset( $_POST['confirm_password'] ) ? (string) wp_unslash( $_POST['confirm_password'] ) : '';

		if ( $email && ! is_email( $email ) ) {
			$errors[] = 'email_invalid';
		}
		if ( $email && is_email( $email ) ) {
			$owner = email_exists( $email );
			if ( $owner && (int) $owner !== $user_id ) {
				$errors[] = 'email_taken';
			}
		}
		if ( $pass1 || $pass2 ) {
			if ( $pass1 !== $pass2 ) {
				$errors[] = 'password_mismatch';
			} elseif ( strlen( $pass1 ) < 8 ) {
				$errors[] = 'password_short';
			}
		}

		if ( $errors ) {
			wp_safe_redirect( add_query_arg( 'aswj_profile_error', implode( ',', $errors ), $redirect ) );
			exit;
		}

		$update = array(
			'ID'         => $user_id,
			'first_name' => $first,
			'last_name'  => $last,
		);
		if ( $first || $last ) {
			$update['display_name'] = trim( $first . ' ' . $last );
		}
		if ( $email ) {
			$update['user_email'] = $email;
		}
		if ( $pass1 ) {
			$update['user_pass'] = $pass1;
		}

		$result = wp_update_user( $update );
		if ( is_wp_error( $result ) ) {
			wp_safe_redirect( add_query_arg( 'aswj_profile_error', 'save_failed', $redirect ) );
			exit;
		}

		update_user_meta( $user_id, 'aswj_phone', $phone );

		// Changing the password logs the browser out; log them straight back in.
		if ( $pass1 ) {
			wp_set_auth_cookie( $user_id, true );
		}

		wp_safe_redirect( add_query_arg( 'aswj_profile_updated', '1', $redirect ) );
		exit;
	}

	public static function error_message( $code ) {
		$messages = array(
			'email_invalid'     => __( 'That email address is not valid.', 'aswj-lms' ),
			'email_taken'       => __( 'That email address is already used by another account.', 'aswj-lms' ),
			'password_mismatch' => __( 'The two passwords do not match.', 'aswj-lms' ),
			'password_short'    => __( 'Please choose a password of at least 8 characters.', 'aswj-lms' ),
			'save_failed'       => __( 'Your profile could not be saved. Please try again.', 'aswj-lms' ),
		);
		return isset( $messages[ $code ] ) ? $messages[ $code ] : $messages['save_failed'];
	}
}
