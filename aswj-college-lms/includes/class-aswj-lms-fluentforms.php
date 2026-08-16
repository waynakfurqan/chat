<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fluent Forms (Pro) integration.
 *
 * Registration:
 *  - Account creation itself is handled by Fluent Forms Pro's
 *    "User Registration" module (mapped in the form's settings).
 *  - This class listens to submissions on the configured registration form
 *    and copies the gender field into the `aswj_is_sister` user meta flag,
 *    and assigns the Student role.
 *
 * Payments:
 *  - Each paid course points at a Fluent Forms payment form (course meta
 *    `_aswj_payment_form_id`). When a payment on that form is confirmed,
 *    the paying user is enrolled in the course.
 *  - Forms listed in Settings → "Subscription form IDs" instead grant
 *    access to ALL courses of type "subscription" while the subscription
 *    is active, and revoke on cancellation/refund.
 *
 * All hooks are guarded so the plugin never fatals if Fluent Forms is
 * missing or an API shape changes between versions.
 */
class ASWJ_LMS_FluentForms {

	public static function init() {
		// Fires for every completed submission (free + paid forms).
		add_action( 'fluentform/submission_inserted', array( __CLASS__, 'on_submission' ), 20, 3 );
		add_action( 'fluentform_submission_inserted', array( __CLASS__, 'on_submission' ), 20, 3 ); // pre-5.0 hook name

		// Payment status changes (one-time payments). Fires with ($newStatus, $submission).
		add_action( 'fluentform/after_payment_status_change', array( __CLASS__, 'on_payment_status_change' ), 20, 2 );

		// Subscription lifecycle.
		// do_action('fluentform/subscription_payment_' . $newStatus, $subscription, $submission, false)
		add_action( 'fluentform/subscription_payment_active', array( __CLASS__, 'on_subscription_active' ), 20, 3 );
		add_action( 'fluentform/subscription_received_payment', array( __CLASS__, 'on_subscription_renewal' ), 20, 2 );
		add_action( 'fluentform/subscription_payment_canceled', array( __CLASS__, 'on_subscription_canceled' ), 20, 3 );
		add_action( 'fluentform/subscription_payment_cancelled', array( __CLASS__, 'on_subscription_canceled' ), 20, 3 );

		// When Fluent Forms Pro creates the user account.
		add_action( 'fluentform/user_registration_completed', array( __CLASS__, 'on_user_registered' ), 20, 4 );
	}

	/* ---------------------------------------------------------------------
	 * Registration
	 * ------------------------------------------------------------------- */

	public static function on_user_registered( $user_id, $feed, $entry, $form = null ) {
		$user = get_user_by( 'id', (int) $user_id );
		if ( ! $user ) {
			return;
		}

		// Make sure new sign-ups get the Student role.
		if ( ! user_can( $user, 'manage_options' ) && ! in_array( 'aswj_student', (array) $user->roles, true ) ) {
			$user->add_role( 'aswj_student' );
		}

		self::maybe_flag_sister_from_entry( $user_id, $entry );
	}

	public static function on_submission( $entry_id, $form_data, $form ) {
		$form_id = is_object( $form ) && isset( $form->id ) ? (int) $form->id : 0;
		if ( ! $form_id || $form_id !== (int) ASWJ_LMS_Settings::get( 'registration_form_id' ) ) {
			return;
		}

		// The user may have been created by the registration feed within
		// this request; try to resolve them by the submitted email.
		$email = self::extract_email( $form_data );
		if ( ! $email ) {
			return;
		}
		$user = get_user_by( 'email', $email );
		if ( ! $user ) {
			return;
		}

		if ( ! user_can( $user, 'manage_options' ) && ! in_array( 'aswj_student', (array) $user->roles, true ) ) {
			$user->add_role( 'aswj_student' );
		}

		self::maybe_flag_sister_from_entry( $user->ID, $form_data );
	}

	private static function maybe_flag_sister_from_entry( $user_id, $entry ) {
		$field  = (string) ASWJ_LMS_Settings::get( 'gender_field_name' );
		$target = strtolower( (string) ASWJ_LMS_Settings::get( 'sister_field_value' ) );
		if ( '' === $field || '' === $target ) {
			return;
		}

		$data = self::entry_to_array( $entry );
		if ( ! isset( $data[ $field ] ) ) {
			return;
		}

		$value = $data[ $field ];
		if ( is_array( $value ) ) {
			$value = implode( ' ', $value );
		}

		if ( false !== stripos( (string) $value, $target ) ) {
			update_user_meta( $user_id, 'aswj_is_sister', '1' );
		}
	}

	/* ---------------------------------------------------------------------
	 * One-time payments
	 * ------------------------------------------------------------------- */

	public static function on_payment_status_change( $new_status, $submission ) {
		if ( 'paid' !== $new_status ) {
			return;
		}
		self::handle_paid_submission( $submission );
	}

	private static function handle_paid_submission( $submission ) {
		$submission = self::to_object( $submission );
		if ( ! $submission || empty( $submission->form_id ) ) {
			return;
		}
		$form_id = (int) $submission->form_id;

		$user_id = self::resolve_user_id( $submission );
		if ( ! $user_id ) {
			return;
		}

		// Subscription form? Handled by subscription hooks, but grant
		// access here too in case only the paid hook fires.
		if ( in_array( $form_id, ASWJ_LMS_Settings::subscription_form_ids(), true ) ) {
			self::grant_subscription_access( $user_id );
			return;
		}

		// Otherwise enroll into every course whose payment form matches.
		$course_ids = get_posts(
			array(
				'post_type'      => 'aswj_course',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => '_aswj_payment_form_id',
				'meta_value'     => $form_id,
			)
		);
		foreach ( $course_ids as $course_id ) {
			ASWJ_LMS_Enrollment::enroll( $user_id, (int) $course_id, 'payment' );
		}
	}

	/* ---------------------------------------------------------------------
	 * Subscriptions
	 * ------------------------------------------------------------------- */

	public static function on_subscription_active( $subscription, $submission, $is_renewal = false ) {
		$submission = self::to_object( $submission );
		if ( ! $submission ) {
			return;
		}
		$user_id = self::resolve_user_id( $submission );
		if ( $user_id ) {
			self::grant_subscription_access( $user_id );
		}
	}

	public static function on_subscription_renewal( $subscription, $submission ) {
		self::on_subscription_active( $subscription, $submission, true );
	}

	public static function on_subscription_canceled( $subscription, $submission, $vendor_data = null ) {
		$submission = self::to_object( $submission );
		if ( ! $submission ) {
			return;
		}
		$user_id = self::resolve_user_id( $submission );
		if ( $user_id ) {
			ASWJ_LMS_Enrollment::revoke_subscription_enrollments( $user_id );
		}
	}

	private static function grant_subscription_access( $user_id ) {
		$course_ids = get_posts(
			array(
				'post_type'      => 'aswj_course',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => '_aswj_access_type',
				'meta_value'     => 'subscription',
			)
		);
		foreach ( $course_ids as $course_id ) {
			ASWJ_LMS_Enrollment::enroll( $user_id, (int) $course_id, 'subscription' );
		}
	}

	/* ---------------------------------------------------------------------
	 * Helpers
	 * ------------------------------------------------------------------- */

	private static function to_object( $thing ) {
		if ( is_object( $thing ) ) {
			return $thing;
		}
		if ( is_array( $thing ) ) {
			return (object) $thing;
		}
		return null;
	}

	/**
	 * Find the WP user a submission belongs to: the logged-in submitter if
	 * recorded, otherwise matched by submitted email address.
	 */
	private static function resolve_user_id( $submission ) {
		if ( ! empty( $submission->user_id ) ) {
			return (int) $submission->user_id;
		}

		$response = array();
		if ( ! empty( $submission->response ) ) {
			$response = is_string( $submission->response ) ? json_decode( $submission->response, true ) : (array) $submission->response;
		}

		$email = self::extract_email( $response );
		if ( $email ) {
			$user = get_user_by( 'email', $email );
			if ( $user ) {
				return (int) $user->ID;
			}
		}
		return 0;
	}

	private static function extract_email( $entry ) {
		$data = self::entry_to_array( $entry );
		foreach ( $data as $value ) {
			if ( is_string( $value ) && is_email( $value ) ) {
				return $value;
			}
			if ( is_array( $value ) ) {
				foreach ( $value as $inner ) {
					if ( is_string( $inner ) && is_email( $inner ) ) {
						return $inner;
					}
				}
			}
		}
		return '';
	}

	private static function entry_to_array( $entry ) {
		if ( is_array( $entry ) ) {
			return $entry;
		}
		if ( is_object( $entry ) ) {
			if ( isset( $entry->response ) ) {
				$response = $entry->response;
				return is_string( $response ) ? (array) json_decode( $response, true ) : (array) $response;
			}
			return (array) $entry;
		}
		if ( is_string( $entry ) ) {
			$decoded = json_decode( $entry, true );
			return is_array( $decoded ) ? $decoded : array();
		}
		return array();
	}
}
