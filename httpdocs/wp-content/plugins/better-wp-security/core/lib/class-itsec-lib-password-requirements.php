<?php

/**
 * Class ITSEC_Lib_Password_Requirements
 */
class ITSEC_Lib_Password_Requirements {

	/** @var array[] */
	private static $requirements;

	/**
	 * Get all registered password requirements.
	 *
	 * @return array
	 */
	public static function get_registered() {
		if ( null === self::$requirements ) {
			self::$requirements = array();

			/**
			 * Fires when password requirements should be registered.
			 */
			do_action( 'itsec_register_password_requirements' );
		}

		return self::$requirements;
	}

	/**
	 * Register a password requirement.
	 *
	 * @param string $reason_code
	 * @param array  $opts
	 */
	public static function register( $reason_code, $opts ) {
		$merged = wp_parse_args( $opts, array(
			'evaluate'                => null,
			'validate'                => null,
			'flag_check'              => null,
			'reason'                  => null,
			'defaults'                => null,
			'settings_config'         => null, // Callable returning label, description, render & sanitize callbacks.
			'meta'                    => "_itsec_password_evaluation_{$reason_code}",
			'evaluate_if_not_enabled' => false,
		) );

		if (
			( array_key_exists( 'validate', $opts ) || array_key_exists( 'evaluate', $opts ) ) &&
			( ! is_callable( $merged['validate'] ) || ! is_callable( $merged['evaluate'] ) )
		) {
			return;
		}

		if ( array_key_exists( 'flag_check', $opts ) && ! is_callable( $merged['flag_check'] ) ) {
			return;
		}

		if ( array_key_exists( 'defaults', $opts ) ) {
			if ( ! is_array( $merged['defaults'] ) ) {
				return;
			}

			if ( ! array_key_exists( 'settings_config', $opts ) ) {
				return;
			}
		}

		if ( array_key_exists( 'settings_config', $opts ) && ! is_callable( $merged['settings_config'] ) ) {
			return;
		}

		self::$requirements[ $reason_code ] = $merged;
	}

	/**
	 * Get a message indicating to the user why a password change is required.
	 *
	 * @param WP_User $user
	 *
	 * @return string
	 */
	public static function get_message_for_password_change_reason( $user ) {

		if ( ! $reason = self::password_change_required( $user ) ) {
			return '';
		}

		$message = '';

		$registered = self::get_registered();

		if ( isset( $registered[ $reason ] ) ) {
			$settings = self::get_requirement_settings( $reason );
			$message  = call_user_func( $registered[ $reason ]['reason'], get_user_meta( $user->ID, $registered[ $reason ]['meta'], true ), $settings );
		}

		/**
		 * Retrieve a human readable description as to why a password change has been required for the current user.
		 *
		 * Modules MUST HTML escape their reason strings before returning them with this filter.
		 *
		 * @param string  $message
		 * @param WP_User $user
		 */
		$message = apply_filters( "itsec_password_change_requirement_description_for_{$reason}", $message, $user );

		if ( $message ) {
			return $message;
		}

		return esc_html__( 'A password change is required for your account.', 'better-wp-security' );
	}

	/**
	 * Validate a user's password.
	 *
	 * @param WP_User|stdClass|int $user
	 * @param string               $new_password
	 * @param array                $args
	 *
	 * @return WP_Error Error object with new errors.
	 */
	public static function validate_password( $user, $new_password, $args = array() ) {

		$args = wp_parse_args( $args, array(
			'error'   => new WP_Error(),
			'context' => '',
		) );

		/** @var WP_Error $error */
		$error = $args['error'];
		$user  = $user instanceof stdClass ? $user : ITSEC_Lib::get_user( $user );

		if ( ! $user ) {
			$error->add( 'invalid_user', esc_html__( 'Invalid User', 'better-wp-security' ) );

			return $error;
		}

		if ( ! empty( $user->ID ) && wp_check_password( $new_password, get_userdata( $user->ID )->user_pass, $user->ID ) ) {
			$message = wp_kses( __( '<strong>ERROR</strong>: The password you have chosen appears to have been used before. You must choose a new password.', 'better-wp-security' ), array( 'strong' => array() ) );
			$error->add( 'pass', $message );

			return $error;
		}

		require_once( ITSEC_Core::get_core_dir() . '/lib/class-itsec-lib-canonical-roles.php' );

		if ( isset( $args['role'] ) && $user instanceof WP_User ) {
			$canonical = ITSEC_Lib_Canonical_Roles::get_canonical_role_from_role_and_user( $args['role'], $user );
		} elseif ( isset( $args['role'] ) ) {
			$canonical = ITSEC_Lib_Canonical_Roles::get_canonical_role_from_role( $args['role'] );
		} elseif ( empty( $user->ID ) || ! is_numeric( $user->ID ) ) {
			$canonical = ITSEC_Lib_Canonical_Roles::get_canonical_role_from_role( get_option( 'default_role', 'subscriber' ) );
		} else {
			$canonical = ITSEC_Lib_Canonical_Roles::get_user_role( $user );
		}

		$args['canonical'] = $canonical;

		/**
		 * Fires when modules should validate a password according to their rules.
		 *
		 * @since 3.9.0
		 *
		 * @param \WP_Error         $error
		 * @param \WP_User|stdClass $user
		 * @param string            $new_password
		 * @param array             $args
		 */
		do_action( 'itsec_validate_password', $error, $user, $new_password, $args );

		return $error;
	}

	/**
	 * Flag that a password change is required for a user.
	 *
	 * @param WP_User|int $user
	 * @param string      $reason
	 */
	public static function flag_password_change_required( $user, $reason ) {
		$user = ITSEC_Lib::get_user( $user );

		if ( $user ) {
			update_user_meta( $user->ID, 'itsec_password_change_required', $reason );
		}
	}

	/**
	 * Check if a password change is required for the given user.
	 *
	 * @param WP_User|int $user
	 *
	 * @return string|false Either the reason code a change is required, or false.
	 */
	public static function password_change_required( $user ) {
		$user = ITSEC_Lib::get_user( $user );

		if ( ! $user ) {
			return false;
		}

		$reason = get_user_meta( $user->ID, 'itsec_password_change_required', true );

		if ( ! $reason ) {
			return false;
		}

		$registered = self::get_registered();

		if ( isset( $registered[ $reason ] ) ) {
			return self::is_requirement_enabled( $reason ) ? $reason : false;
		}

		if ( ! has_filter( "itsec_password_change_requirement_description_for_{$reason}" ) ) {
			return false;
		}

		return $reason;
	}

	/**
	 * Globally clear all required password changes with a particular reason code.
	 *
	 * @param string $reason
	 */
	public static function global_clear_required_password_change( $reason ) {
		delete_metadata( 'user', 0, 'itsec_password_change_required', $reason, true );
	}

	/**
	 * Get the GMT time the user's password has last been changed.
	 *
	 * @param WP_User|int $user
	 *
	 * @return int
	 */
	public static function password_last_changed( $user ) {

		$user = ITSEC_Lib::get_user( $user );

		if ( ! $user ) {
			return 0;
		}

		$changed    = (int) get_user_meta( $user->ID, 'itsec_last_password_change', true );
		$deprecated = (int) get_user_meta( $user->ID, 'itsec-password-updated', true );

		if ( $deprecated > $changed ) {
			return $deprecated;
		}

		if ( ! $changed ) {
			return strtotime( $user->user_registered );
		}

		return $changed;
	}

	/**
	 * Is a password requirement enabled.
	 *
	 * @param string $requirement
	 *
	 * @return bool
	 */
	public static function is_requirement_enabled( $requirement ) {

		$requirements = self::get_registered();

		if ( ! isset( $requirements[ $requirement ] ) ) {
			return false;
		}

		// If the requirement does not have any settings, than it is always enabled.
		if ( null === $requirements[ $requirement ]['settings_config'] ) {
			return true;
		}

		$enabled = ITSEC_Modules::get_setting( 'password-requirements', 'enabled_requirements' );

		if ( ! empty( $enabled[ $requirement ] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get requirement settings.
	 *
	 * @param string $requirement
	 *
	 * @return array|false
	 */
	public static function get_requirement_settings( $requirement ) {

		$requirements = self::get_registered();

		if ( ! isset( $requirements[ $requirement ] ) ) {
			return false;
		}

		if ( null === $requirements[ $requirement ]['settings_config'] ) {
			return false;
		}

		$all_settings = ITSEC_Modules::get_setting( 'password-requirements', 'requirement_settings' );
		$settings     = isset( $all_settings[ $requirement ] ) ? $all_settings[ $requirement ] : array();

		return wp_parse_args( $settings, $requirements[ $requirement ]['defaults'] );
	}
}