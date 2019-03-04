<?php

/**
 * Class ITSEC_Password_Requirements
 */
class ITSEC_Password_Requirements {

	const META_KEY = '_itsec_password_requirements';

	public function run() {

		add_action( 'user_profile_update_errors', array( $this, 'forward_profile_pass_update' ), 0, 3 );
		add_action( 'validate_password_reset', array( $this, 'forward_reset_pass' ), 10, 2 );

		add_action( 'profile_update', array( $this, 'handle_update_user' ), 10, 2 );
		add_action( 'password_reset', array( $this, 'handle_password_reset' ), 10, 2 );
		add_filter( 'wp_authenticate_user', array( $this, 'check_password_on_login' ), 999, 2 );

		add_action( 'add_user_role', array( $this, 'handle_role_change' ) );
		add_action( 'set_user_role', array( $this, 'handle_role_change' ) );
		add_action( 'remove_user_role', array( $this, 'handle_role_change' ) );

		add_action( 'itsec_validate_password', array( $this, 'validate_password' ), 10, 4 );

		add_action( 'wp_login', array( $this, 'flag_check' ), 9, 2 );

		add_action( 'itsec_login_interstitial_init', array( $this, 'register_interstitial' ) );
	}

	/**
	 * When a user's password is updated, or a new user created, verify that the new password is valid.
	 *
	 * @param WP_Error         $errors
	 * @param bool             $update
	 * @param WP_User|stdClass $user
	 */
	public function forward_profile_pass_update( $errors, $update, $user ) {

		if ( $errors->get_error_message( 'pass' ) ) {
			return;
		}

		if ( isset( $user->user_pass ) ) {
			$this->handle_profile_update_password( $errors, $update, $user );
		} elseif ( $update && isset( $user->role ) ) {
			$this->handle_profile_update_role( $errors, $user );
		}
	}

	/**
	 * Handle the password being updated for a user.
	 *
	 * @param WP_Error         $errors
	 * @param bool             $update
	 * @param WP_User|stdClass $user
	 */
	private function handle_profile_update_password( $errors, $update, $user ) {
		if ( ! $update ) {
			$context = 'admin-user-create';
		} elseif ( isset( $user->ID ) && $user->ID === get_current_user_id() ) {
			$context = 'profile-update';
		} else {
			$context = 'admin-profile-update';
		}

		$args = array(
			'error'   => $errors,
			'context' => $context
		);

		if ( isset( $user->role ) ) {
			$args['role'] = $user->role;
		}

		ITSEC_Lib_Password_Requirements::validate_password( $user, $user->user_pass, $args );
	}

	/**
	 * Handle the user's role being updated.
	 *
	 * @param WP_Error         $errors
	 * @param WP_User|stdClass $user
	 */
	private function handle_profile_update_role( $errors, $user ) {

		$settings = ITSEC_Modules::get_setting( 'password-requirements', 'requirement_settings' );

		foreach ( ITSEC_Lib_Password_Requirements::get_registered() as $code => $requirement ) {

			if ( ! $requirement['validate'] || ! ITSEC_Lib_Password_Requirements::is_requirement_enabled( $code ) ) {
				continue;
			}

			$evaluation = get_user_meta( $user->ID, $requirement['meta'], true );

			if ( '' === $evaluation ) {
				continue;
			}

			require_once( ITSEC_Core::get_core_dir() . '/lib/class-itsec-lib-canonical-roles.php' );

			$args = array(
				'role'      => $user->role,
				'canonical' => ITSEC_Lib_Canonical_Roles::get_canonical_role_from_role_and_user( $user->role, $user ),
			);

			$validated = call_user_func( $requirement['validate'], $evaluation, $user, $settings[ $code ], $args );

			if ( true === $validated ) {
				continue;
			}

			$message = $validated ? $validated : esc_html__( "The provided password does not meet this site's requirements.", 'better-wp-security' );
			$errors->add( 'pass', $message );
		}
	}

	/**
	 * When a user attempts to reset their password, verify that the new password is valid.
	 *
	 * @param WP_Error $errors
	 * @param WP_User  $user
	 */
	public function forward_reset_pass( $errors, $user ) {

		if ( ! isset( $_POST['pass1'] ) || is_wp_error( $user ) ) {
			// The validate_password_reset action fires when first rendering the reset page and when handling the form
			// submissions. Since the pass1 data is missing, this must be the initial page render. So, we don't need to
			// do anything yet.
			return;
		}

		ITSEC_Lib_Password_Requirements::validate_password( $user, $_POST['pass1'], array(
			'error'   => $errors,
			'context' => 'reset-password',
		) );
	}

	/**
	 * Whenever a user object is updated, set when their password was last updated.
	 *
	 * @param int    $user_id
	 * @param object $old_user_data
	 */
	public function handle_update_user( $user_id, $old_user_data ) {

		$user = get_userdata( $user_id );

		if ( $user->user_pass === $old_user_data->user_pass ) {
			return;
		}

		$this->handle_password_updated( $user );
	}

	/**
	 * When a user resets their password, update the last change time.
	 *
	 * For some unknown reason, the password reset routine uses {@see wp_set_password()} instead of {@see wp_update_user()}.
	 *
	 * @param WP_User $user
	 * @param string  $new_password
	 */
	public function handle_password_reset( $user, $new_password ) {
		$this->handle_password_updated( $user );
		$this->handle_plain_text_password_available( $user, $new_password );
	}

	/**
	 * When a user logs in, if their password hasn't been validated yet,
	 * validate it.
	 *
	 * @param WP_User $user
	 * @param string  $password
	 *
	 * @return WP_User
	 */
	public function check_password_on_login( $user, $password ) {

		if ( ! $user instanceof WP_User ) {
			return $user;
		}

		if ( ! wp_check_password( $password, $user->user_pass, $user->ID ) ) {
			return $user;
		}

		$this->handle_plain_text_password_available( $user, $password );

		return $user;
	}

	/**
	 * When a password is updated, set the last updated time and delete any pending required change.
	 *
	 * @param WP_User $user
	 */
	protected function handle_password_updated( $user ) {
		delete_user_meta( $user->ID, 'itsec_password_change_required' );
		update_user_meta( $user->ID, 'itsec_last_password_change', ITSEC_Core::get_current_time_gmt() );
	}

	/**
	 * When a plain text password is available, we perform any evaluations that have not yet been performed for this password.
	 *
	 * @param WP_User $user
	 * @param string  $password
	 */
	protected function handle_plain_text_password_available( $user, $password ) {

		$config = wp_parse_args( get_user_meta( $user->ID, self::META_KEY, true ), array(
			'evaluation_times' => array(),
		) );

		$last_updated = ITSEC_Lib_Password_Requirements::password_last_changed( $user );

		$settings = ITSEC_Modules::get_setting( 'password-requirements', 'requirement_settings' );

		foreach ( ITSEC_Lib_Password_Requirements::get_registered() as $code => $requirement ) {

			if ( ! $requirement['evaluate'] ) {
				continue;
			}

			if ( ! $requirement['evaluate_if_not_enabled'] && ! ITSEC_Lib_Password_Requirements::is_requirement_enabled( $code ) ) {
				continue;
			}

			if ( isset( $config['evaluation_times'][ $code ] ) && $config['evaluation_times'][ $code ] >= $last_updated ) {
				continue;
			}

			$evaluation = call_user_func( $requirement['evaluate'], $password, $user );

			if ( is_wp_error( $evaluation ) ) {
				continue;
			}

			$config['evaluation_times'][ $code ] = ITSEC_Core::get_current_time_gmt();
			update_user_meta( $user->ID, $requirement['meta'], $evaluation );

			if ( ! ITSEC_Lib_Password_Requirements::is_requirement_enabled( $code ) ) {
				continue;
			}

			$validated = call_user_func( $requirement['validate'], $evaluation, $user, $settings[ $code ], array() );

			if ( true === $validated ) {
				continue;
			}

			ITSEC_Lib_Password_Requirements::flag_password_change_required( $user, $code );
		}

		update_user_meta( $user->ID, self::META_KEY, $config );
	}

	/**
	 * Validate password.
	 *
	 * @param \WP_Error         $error
	 * @param \WP_User|stdClass $user
	 * @param string            $new_password
	 * @param array             $args
	 */
	public function validate_password( $error, $user, $new_password, $args ) {

		$settings = ITSEC_Modules::get_setting( 'password-requirements', 'requirement_settings' );

		foreach ( ITSEC_Lib_Password_Requirements::get_registered() as $code => $requirement ) {

			if ( ! $requirement['evaluate'] || ! ITSEC_Lib_Password_Requirements::is_requirement_enabled( $code ) ) {
				continue;
			}

			$evaluation = call_user_func( $requirement['evaluate'], $new_password, $user );

			if ( is_wp_error( $evaluation ) ) {
				continue;
			}

			$validated = call_user_func( $requirement['validate'], $evaluation, $user, $settings[ $code ], $args );

			if ( true === $validated ) {
				continue;
			}

			// The default error message is a safeguard that should never occur.
			$message = $validated ? $validated : esc_html__( "The provided password does not meet this site's requirements.", 'better-wp-security' );

			switch ( $args['context'] ) {
				case 'admin-user-create':
					$message .= ' ' . __( 'The user has not been created.', 'better-wp-security' );
					break;
				case 'admin-profile-update':
					$message .= ' ' . __( 'The user changes have not been saved.', 'better-wp-security' );
					break;
				case 'profile-update':
					$message .= ' ' . __( 'Your profile has not been updated.', 'better-wp-security' );
					break;
				case 'reset-password':
					$message .= ' ' . __( 'The password has not been updated.', 'better-wp-security' );
					break;
			}

			$error->add( 'pass', $message );
		}
	}

	/**
	 * When a user logs in, run any flag checks to see if a password change should be forced.
	 *
	 * @param string       $username
	 * @param WP_User|null $user
	 */
	public function flag_check( $username, $user = null ) {

		if ( ! $user && is_user_logged_in() ) {
			$user = wp_get_current_user();
		}

		if ( ! $user instanceof WP_User || ! $user->exists() ) {
			return;
		}

		foreach ( ITSEC_Lib_Password_Requirements::get_registered() as $code => $requirement ) {
			if ( ! ITSEC_Lib_Password_Requirements::is_requirement_enabled( $code ) ) {
				continue;
			}

			$settings = ITSEC_Lib_Password_Requirements::get_requirement_settings( $code );

			if ( $requirement['flag_check'] && call_user_func( $requirement['flag_check'], $user, $settings ) ) {
				ITSEC_Lib_Password_Requirements::flag_password_change_required( $user, $code );

				return;
			}
		}
	}

	/**
	 * Is a given requirement enabled.
	 *
	 * @param string $requirement
	 *
	 * @return bool
	 */
	protected function is_requirement_enabled( $requirement ) {

		$requirements = ITSEC_Lib_Password_Requirements::get_registered();

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
	 * When a user's role changes, clear all the evaluation times as evaluat
	 *
	 * @param int $user_id
	 */
	public function handle_role_change( $user_id ) {

		$config = get_user_meta( $user_id, self::META_KEY, true );

		if ( ! $config || ! is_array( $config ) ) {
			return;
		}

		$config['evaluation_times'] = array();

		update_user_meta( $user_id, self::META_KEY, $config );
	}

	/**
	 * Register the password change interstitial.
	 *
	 * @param ITSEC_Lib_Login_Interstitial $lib
	 */
	public function register_interstitial( $lib ) {
		$lib->register( 'update-password', array( $this, 'render_interstitial' ), array(
			'show_to_user' => array( 'ITSEC_Lib_Password_Requirements', 'password_change_required' ),
			'info_message' => array( 'ITSEC_Lib_Password_Requirements', 'get_message_for_password_change_reason' ),
			'submit'       => array( $this, 'submit' ),
		) );
	}

	/**
	 * Render the interstitial.
	 *
	 * @param WP_User $user
	 */
	public function render_interstitial( $user ) {
		wp_enqueue_script( 'user-profile' );

		do_action( 'itsec_password_requirements_change_form', $user );
		?>

		<div class="user-pass1-wrap">
			<p><label for="pass1"><?php _e( 'New Password', 'better-wp-security' ); ?></label></p>
		</div>

		<div class="wp-pwd">
				<span class="password-input-wrapper">
					<input type="password" data-reveal="1"
						   data-pw="<?php echo esc_attr( wp_generate_password( 16 ) ); ?>" name="pass1" id="pass1"
						   class="input" size="20" value="" autocomplete="off" aria-describedby="pass-strength-result"/>
				</span>
			<div id="pass-strength-result" class="hide-if-no-js" aria-live="polite"><?php _e( 'Strength indicator', 'better-wp-security' ); ?></div>
			<div class="pw-weak">
				<label>
					<input type="checkbox" name="pw_weak" class="pw-checkbox" />
					<?php _e( 'Confirm use of weak password' ); ?>
				</label>
			</div>
		</div>

		<p class="user-pass2-wrap">
			<label for="pass2"><?php _e( 'Confirm new password' ) ?></label><br/>
			<input type="password" name="pass2" id="pass2" class="input" size="20" value="" autocomplete="off"/>
		</p>

		<p class="description indicator-hint"><?php echo wp_get_password_hint(); ?></p>
		<br class="clear"/>

		<p class="submit">
			<input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e( 'Update Password', 'better-wp-security' ); ?>"/>
		</p>

		<?php
	}

	/**
	 * Handle the request to update the user's password.
	 *
	 * @param WP_User $user
	 * @param array   $data POSTed data.
	 *
	 * @return WP_Error|null
	 */
	public function submit( $user, $data ) {

		if ( empty( $data['pass1'] ) ) {
			return new WP_Error(
				'itsec-password-requirements-empty-password',
				__( 'Please enter your new password.', 'better-wp-security' )
			);
		}

		$error = ITSEC_Lib_Password_Requirements::validate_password( $user, $data['pass1'], array(
			'context' => 'interstitial',
		) );

		if ( $error->get_error_message() ) {
			return $error;
		}

		$error = wp_update_user( array(
			'ID'        => $user->ID,
			'user_pass' => $data['pass1']
		) );

		if ( is_wp_error( $error ) ) {
			return $error;
		}

		return null;
	}
}
