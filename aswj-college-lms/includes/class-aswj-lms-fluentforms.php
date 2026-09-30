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

		// Autofill known fields (name, email, phone, gender, age) for
		// logged-in students so returning students only fill in what's new.
		foreach ( array( 'input_name', 'input_email', 'input_text', 'input_number', 'phone', 'select', 'input_radio', 'input_date' ) as $element ) {
			add_filter( 'fluentform/rendering_field_data_' . $element, array( __CLASS__, 'autofill_field' ), 10, 2 );
			add_filter( 'fluentform_rendering_field_data_' . $element, array( __CLASS__, 'autofill_field' ), 10, 2 ); // pre-5.0
		}
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

		self::sync_profile_from_entry( $user_id, $entry );
	}

	public static function on_submission( $entry_id, $form_data, $form ) {
		$form_id = is_object( $form ) && isset( $form->id ) ? (int) $form->id : 0;
		if ( ! $form_id ) {
			return;
		}

		$user = self::resolve_submitting_user( $form_data );

		// Account registration form: ensure role + profile sync.
		if ( $form_id === (int) ASWJ_LMS_Settings::get( 'registration_form_id' ) && $user ) {
			if ( ! user_can( $user, 'manage_options' ) && ! in_array( 'aswj_student', (array) $user->roles, true ) ) {
				$user->add_role( 'aswj_student' );
			}
			self::sync_profile_from_entry( $user->ID, $form_data );
		}

		// Course registration/payment form: record the student immediately
		// as PENDING. "Pay now" students are activated moments later by the
		// paid-status hook; bank-transfer/cash students stay pending until
		// the admin verifies and approves them on the Students screen.
		$course_ids = self::courses_for_payment_form( $form_id );
		if ( $course_ids && $user ) {
			self::sync_profile_from_entry( $user->ID, $form_data );
			foreach ( $course_ids as $course_id ) {
				ASWJ_LMS_Enrollment::add_pending( $user->ID, $course_id, 'offline' );
			}
		}
	}

	/**
	 * The user who submitted a form: the logged-in visitor if any,
	 * otherwise matched by the submitted email address.
	 *
	 * @return WP_User|null
	 */
	private static function resolve_submitting_user( $form_data ) {
		if ( is_user_logged_in() ) {
			return wp_get_current_user();
		}
		$email = self::extract_email( $form_data );
		if ( $email ) {
			$user = get_user_by( 'email', $email );
			if ( $user ) {
				return $user;
			}
		}
		return null;
	}

	/** @return int[] Course IDs whose payment form matches. */
	private static function courses_for_payment_form( $form_id ) {
		$ids = get_posts(
			array(
				'post_type'      => 'aswj_course',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => '_aswj_payment_form_id',
				'meta_value'     => (int) $form_id,
			)
		);
		return array_map( 'intval', $ids );
	}

	/**
	 * Copy profile details (gender/sister flag, phone, age) from a form
	 * entry into the user account, using the field names from Settings.
	 */
	private static function sync_profile_from_entry( $user_id, $entry ) {
		self::maybe_flag_sister_from_entry( $user_id, $entry );

		$data = self::entry_to_array( $entry );

		$map = array(
			'phone_field_name'  => 'aswj_phone',
			'age_field_name'    => 'aswj_age',
			'gender_field_name' => 'aswj_gender',
		);
		foreach ( $map as $setting => $meta_key ) {
			$field = (string) ASWJ_LMS_Settings::get( $setting );
			if ( '' === $field || ! isset( $data[ $field ] ) ) {
				continue;
			}
			$value = $data[ $field ];
			if ( is_array( $value ) ) {
				$value = implode( ' ', array_filter( array_map( 'strval', $value ) ) );
			}
			$value = sanitize_text_field( (string) $value );
			if ( '' !== $value ) {
				update_user_meta( $user_id, $meta_key, $value );
			}
		}

		// Date of birth: stored raw for autofill + normalized for age
		// calculation (so forms can ask for DOB instead of a static age).
		$dob_field = (string) ASWJ_LMS_Settings::get( 'dob_field_name' );
		if ( $dob_field && isset( $data[ $dob_field ] ) && ! is_array( $data[ $dob_field ] ) ) {
			ASWJ_LMS_Profile::save_dob( $user_id, (string) $data[ $dob_field ] );
		}
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
	 * Autofill for returning students
	 * ------------------------------------------------------------------- */

	/**
	 * Pre-fill known fields on any Fluent Forms form for logged-in users so
	 * returning students only need to complete new fields (e.g. payment).
	 * Never overwrites a default the admin set on the field.
	 */
	public static function autofill_field( $data, $form ) {
		if ( ! is_user_logged_in() || ! is_array( $data ) ) {
			return $data;
		}
		$user    = wp_get_current_user();
		$element = isset( $data['element'] ) ? $data['element'] : '';
		$name    = isset( $data['attributes']['name'] ) ? strtolower( (string) $data['attributes']['name'] ) : '';

		$phone_field  = strtolower( (string) ASWJ_LMS_Settings::get( 'phone_field_name' ) );
		$age_field    = strtolower( (string) ASWJ_LMS_Settings::get( 'age_field_name' ) );
		$gender_field = strtolower( (string) ASWJ_LMS_Settings::get( 'gender_field_name' ) );
		$dob_field    = strtolower( (string) ASWJ_LMS_Settings::get( 'dob_field_name' ) );

		// Composite name field (first/last).
		if ( 'input_name' === $element && ! empty( $data['fields'] ) && is_array( $data['fields'] ) ) {
			$parts = array(
				'first_name' => $user->first_name ? $user->first_name : $user->display_name,
				'last_name'  => $user->last_name,
			);
			foreach ( $parts as $key => $value ) {
				if ( $value && isset( $data['fields'][ $key ]['attributes'] ) && empty( $data['fields'][ $key ]['attributes']['value'] ) ) {
					$data['fields'][ $key ]['attributes']['value'] = $value;
				}
			}
			return $data;
		}

		if ( ! isset( $data['attributes'] ) || ! empty( $data['attributes']['value'] ) ) {
			return $data;
		}

		$value = '';
		if ( 'input_email' === $element ) {
			$value = $user->user_email;
		} elseif ( 'phone' === $element || ( $phone_field && $name === $phone_field ) ) {
			$value = (string) get_user_meta( $user->ID, 'aswj_phone', true );
		} elseif ( $dob_field && $name === $dob_field ) {
			$value = (string) get_user_meta( $user->ID, 'aswj_dob_raw', true );
		} elseif ( 'input_date' === $element ) {
			$value = (string) get_user_meta( $user->ID, 'aswj_dob_raw', true );
		} elseif ( $age_field && $name === $age_field ) {
			$value = ASWJ_LMS_Profile::get_age( $user->ID );
		} elseif ( $gender_field && $name === $gender_field ) {
			$value = (string) get_user_meta( $user->ID, 'aswj_gender', true );
		} elseif ( 'input_text' === $element && in_array( $name, array( 'name', 'full_name', 'your_name' ), true ) ) {
			$value = $user->display_name;
		}

		if ( '' !== $value ) {
			$data['attributes']['value'] = $value;
		}
		return $data;
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
			ASWJ_LMS_Access::set_subscription_active( $user_id, false );
			ASWJ_LMS_Enrollment::revoke_subscription_enrollments( $user_id );
		}
	}

	private static function grant_subscription_access( $user_id ) {
		// All-access flag: unlocks every paid/subscription course, including
		// courses published after the student subscribed.
		ASWJ_LMS_Access::set_subscription_active( $user_id, true );
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
