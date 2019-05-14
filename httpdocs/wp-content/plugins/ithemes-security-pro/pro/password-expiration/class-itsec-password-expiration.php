<?php

class ITSEC_Password_Expiration {

	private $settings;

	function run() {

		$this->settings = ITSEC_Modules::get_settings( 'password-expiration' );

		add_action( 'itsec_register_password_requirements', array( $this, 'register_requirements' ) );

		add_action( 'itsec_password_requirements_enqueue_scripts_and_styles', array( $this, 'enqueue_force_scripts' ) );
		add_action( 'itsec_password_requirements_settings_before', array( $this, 'render_force_button' ) );
		add_action( 'itsec_password_requirements_ajax_force', array( $this, 'handle_force_button' ) );
	}

	public function register_requirements() {
		ITSEC_Lib_Password_Requirements::register( 'age', array(
			'flag_check'      => array( $this, 'check_age' ),
			'reason'          => array( $this, 'age_reason' ),
			'defaults'        => array( 'role' => 'subscriber', 'expire_max' => 120 ),
			'settings_config' => array( $this, 'get_settings_config' ),
		) );

		ITSEC_Lib_Password_Requirements::register( 'force', array(
			'flag_check' => array( $this, 'check_force' ),
			'reason'     => array( $this, 'force_reason' ),
			'label'      => esc_html__( 'Force Password Change', 'it-l10n-ithemes-security-pro' ),
		) );
	}

	/**
	 * Check if the user should have their password flagged for a change because it is too old.
	 *
	 * @param WP_User $user
	 * @param array   $settings
	 *
	 * @return bool
	 */
	public function check_age( $user, $settings ) {

		require_once( ITSEC_Core::get_core_dir() . '/lib/class-itsec-lib-canonical-roles.php' );

		if ( ! ITSEC_Lib_Canonical_Roles::is_user_at_least( $settings['role'], $user ) ) {
			return false;
		}

		$days   = isset( $settings['expire_max'] ) ? absint( $settings['expire_max'] ) : 120;
		$period = $days * DAY_IN_SECONDS;

		$oldest_allowed = ITSEC_Core::get_current_time_gmt() - $period;

		return ITSEC_Lib_Password_Requirements::password_last_changed( $user ) <= $oldest_allowed;
	}

	public function get_settings_config() {
		return array(
			'label'       => esc_html__( 'Password Expiration', 'it-l10n-ithemes-security-pro' ),
			'description' => esc_html__( 'Strengthen the passwords on the site with automated password expiration.', 'it-l10n-ithemes-security-pro' ),
			'render'      => array( $this, 'render_settings' ),
			'sanitize'    => array( $this, 'sanitize_settings' ),
		);
	}

	/**
	 * Render the Settings Page.
	 *
	 * @param ITSEC_Form $form
	 */
	public function render_settings( $form ) {

		$href = 'http://codex.wordpress.org/Roles_and_Capabilities';
		$link = '<a href="' . $href . '" target="_blank" rel="noopener noreferrer">' . $href . '</a>';
		?>
		<tr>
			<th scope="row">
				<label for="itsec-password-requirements-requirement_settings-age-role">
					<?php esc_html_e( 'Minimum Role', 'it-l10n-ithemes-security-pro' ); ?>
				</label>
			</th>
			<td>
				<?php $form->add_canonical_roles( 'role' ); ?>
				<br/>
				<label for="itsec-password-requirements-requirement_settings-age-role"><?php esc_html_e( 'Minimum role at which password expiration is enforced.', 'it-l10n-ithemes-security-pro' ); ?></label>
				<p class="description"><?php esc_html_e( 'We suggest enabling this setting for all users, but it may lead to users forgetting their passwords. The minimum role option above allows you to select the lowest user role to apply strong password generation.', 'it-l10n-ithemes-security-pro' ); ?></p>
				<p class="description"><?php printf( esc_html__( 'For more information on WordPress roles and capabilities please see %s.', 'it-l10n-ithemes-security-pro' ), $link ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="itsec-password-requirements-requirement_settings-age-expire_max"><?php esc_html_e( 'Maximum Password Age', 'it-l10n-ithemes-security-pro' ); ?></label></th>
			<td>
				<?php $form->add_text( 'expire_max', array( 'class' => 'small-text code' ) ); ?>
				<label for="itsec-password-requirements-requirement_settings-age-expire_max"><?php esc_html_e( 'Days', 'it-l10n-ithemes-security-pro' ); ?></label>
				<p class="description"><?php esc_html_e( 'The maximum number of days a password may be kept before it is expired.', 'it-l10n-ithemes-security-pro' ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Get a list of the sanitizer rules to apply.
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public function sanitize_settings( $settings ) {
		return array(
			array( 'string', 'role', esc_html__( 'Minimum Role for Password Expiration', 'it-l10n-ithemes-security-pro' ) ),
			array( 'canonical-roles', 'role', esc_html__( 'Minimum Role for Password Expiration', 'it-l10n-ithemes-security-pro' ) ),
			array( 'positive-int', 'expire_max', esc_html__( 'Maximum Password Age', 'it-l10n-ithemes-security-pro' ) ),
		);
	}

	/**
	 * Check if the user should have their password flagged for a chance because the admin forced a password reset.
	 *
	 * @param WP_User $user
	 *
	 * @return bool
	 */
	public function check_force( $user ) {

		if ( ! isset( $this->settings['expire_force'] ) || $this->settings['expire_force'] <= 0 ) {
			return false;
		}

		return ITSEC_Lib_Password_Requirements::password_last_changed( $user ) <= $this->settings['expire_force'];
	}

	/**
	 * Get the reason description for why a password change was set to 'age'.
	 *
	 * @param mixed $_
	 * @param array $settings
	 *
	 * @return string
	 */
	public function age_reason( $_, $settings ) {

		$period = isset( $settings['expire_max'] ) ? absint( $settings['expire_max'] ) : 120;

		return sprintf( esc_html__( 'Your password has expired. You must create a new password every %d days.', 'it-l10n-ithemes-security-pro' ), $period );
	}

	/**
	 * Get the reason description for why a password change was set to 'force'.
	 *
	 * @return string
	 */
	public function force_reason() {
		return esc_html__( 'An admin has required you to reset your password.', 'it-l10n-ithemes-security-pro' );
	}

	public function enqueue_force_scripts() {
		wp_enqueue_script( 'itsec-password-expiration-settings', plugin_dir_url( __FILE__ ) . 'js/settings-page.js', array( 'jquery', 'itsec-util' ), ITSEC_Core::get_plugin_build() );
	}

	/**
	 * Render the force password change AJAX button.
	 *
	 * @param ITSEC_Form $form
	 */
	public function render_force_button( $form ) {

		?>
		<div class="itsec-password-requirements-password-expiration-force">
			<p><?php _e( 'Press the button below to force all users to change their password upon their next login.', 'it-l10n-ithemes-security-pro' ); ?></p>
			<p><?php $form->add_button( 'force-expiration', array( 'value' => esc_html__( 'Force Password Change', 'it-l10n-ithemes-security-pro' ), 'class' => 'button' ) ); ?></p>
			<div id="itsec_password_expiration_undo"><?php echo $this->get_force_in_effect_notice(); ?></div>
			<div id="itsec_password_expiration_status"></div>
		</div>
		<?php
	}

	/**
	 * Get the notice whether
	 *
	 * @return string
	 */
	private function get_force_in_effect_notice() {

		if ( ! $force = ITSEC_Modules::get_setting( 'password-expiration', 'expire_force' ) ) {
			return '';
		}

		$html = '<p>';
		$html .= sprintf(
			esc_html__( 'Passwords created before %1$s are required to be reset. %2$sUndo force password change%3$s.', 'it-l10n-ithemes-security-pro' ),
			ITSEC_Lib::date_format_i18n_and_local_timezone( $force ),
			'<button class="button-link" id="itsec-password-requirements-force-expiration-undo">',
			'</button>'
		);
		$html .= '</p>';

		return $html;
	}

	/**
	 * Handle the force reset button request.
	 *
	 * @param array $data
	 */
	public function handle_force_button( $data ) {
		if ( 'force-expiration' === $data['method'] ) {
			$response = ITSEC_Modules::set_setting( 'password-expiration', 'expire_force', true );

			if ( is_wp_error( $response ) ) {
				ITSEC_Response::add_error( $response );
			} elseif ( $response['saved'] ) {
				ITSEC_Response::add_message( esc_html__( 'Passwords will be reset on next login.', 'it-l10n-ithemes-security-pro' ) );
				ITSEC_Response::set_response( $this->get_force_in_effect_notice() );
			}
		} elseif ( 'force-expiration-undo' === $data['method'] ) {
			$response = ITSEC_Modules::set_setting( 'password-expiration', 'expire_force', false );

			if ( is_wp_error( $response ) ) {
				ITSEC_Response::add_error( $response );
			} elseif ( $response['saved'] ) {
				ITSEC_Response::add_message( esc_html__( 'Passwords reset is no longer required.', 'it-l10n-ithemes-security-pro' ) );
				ITSEC_Response::set_response( $this->get_force_in_effect_notice() );
			}
		}
	}
}
