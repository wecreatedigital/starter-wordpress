<?php

final class ITSEC_Strong_Passwords {

	const STRENGTH_KEY = 'itsec-password-strength';

	public function __construct() {

		add_action( 'itsec_register_password_requirements', array( $this, 'register_requirements' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'add_scripts' ) );
		add_action( 'resetpass_form', array( $this, 'add_scripts_to_wp_login' ) );
		add_action( 'itsec_password_requirements_change_form', array( $this, 'add_scripts_to_wp_login' ) );
	}

	/**
	 * Register the Strong Passwords requirement.
	 */
	public function register_requirements() {
		ITSEC_Lib_Password_Requirements::register( 'strength', array(
			'evaluate'                => array( $this, 'evaluate' ),
			'validate'                => array( $this, 'validate' ),
			'reason'                  => array( $this, 'reason' ),
			'meta'                    => self::STRENGTH_KEY,
			'evaluate_if_not_enabled' => true,
			'defaults'                => array( 'role' => 'administrator' ),
			'settings_config'         => array( $this, 'get_settings_config' ),
		) );
	}

	/**
	 * Enqueue script to hide the acknowledge weak password checkbox.
	 *
	 * @return void
	 */
	public function add_scripts() {

		global $pagenow;

		if ( 'profile.php' !== $pagenow ) {
			return;
		}

		if ( ! ITSEC_Lib_Password_Requirements::is_requirement_enabled( 'strength' ) ) {
			return;
		}

		$settings = ITSEC_Lib_Password_Requirements::get_requirement_settings( 'strength' );
		$role     = isset( $settings['role'] ) ? $settings['role'] : 'administrator';

		require_once( ITSEC_Core::get_core_dir() . '/lib/class-itsec-lib-canonical-roles.php' );

		if ( ITSEC_Lib_Canonical_Roles::is_user_at_least( $role ) ) {
			wp_enqueue_script( 'itsec_strong_passwords', plugins_url( 'js/script.js', __FILE__ ), array( 'jquery' ), ITSEC_Core::get_plugin_build() );
		}
	}

	/**
	 * On the reset password and login interstitial form, render the Strong Passwords JS to hide the acknowledge weak password checkbox.
	 *
	 * We have to do this in these late actions so we have access to the correct user data.
	 *
	 * @param WP_User $user
	 */
	public function add_scripts_to_wp_login( $user ) {

		if ( ! ITSEC_Lib_Password_Requirements::is_requirement_enabled( 'strength' ) ) {
			return;
		}

		$settings = ITSEC_Lib_Password_Requirements::get_requirement_settings( 'strength' );
		$role     = isset( $settings['role'] ) ? $settings['role'] : 'administrator';

		require_once( ITSEC_Core::get_core_dir() . '/lib/class-itsec-lib-canonical-roles.php' );

		if ( ITSEC_Lib_Canonical_Roles::is_user_at_least( $role, $user ) ) {
			wp_enqueue_script( 'itsec_strong_passwords', plugins_url( 'js/script.js', __FILE__ ), array( 'jquery' ), ITSEC_Core::get_plugin_build() );
		}
	}

	/**
	 * Provide the reason string displayed to users on the change password form.
	 *
	 * @param $evaluation
	 *
	 * @return string
	 */
	public function reason( $evaluation ) {
		return esc_html__( 'Due to site rules, a strong password is required for your account. Please choose a new password that rates as strong on the meter.', 'better-wp-security' );
	}

	/**
	 * Evaluate the strength of a password.
	 *
	 * @param string  $password
	 * @param WP_User $user
	 *
	 * @return int
	 */
	public function evaluate( $password, $user ) {
		return $this->get_password_strength( $user, $password );
	}

	/**
	 * Validate whether a password strength is acceptable for a given user.
	 *
	 * @param int              $strength
	 * @param WP_User|stdClass $user
	 * @param array            $settings
	 * @param array            $args
	 *
	 * @return bool
	 */
	public function validate( $strength, $user, $settings, $args ) {

		if ( (int) $strength === 4 ) {
			return true;
		}

		require_once( ITSEC_Core::get_core_dir() . '/lib/class-itsec-lib-canonical-roles.php' );

		$role = isset( $args['canonical'] ) ? $args['canonical'] : ITSEC_Lib_Canonical_Roles::get_user_role( $user );

		if ( ! ITSEC_Lib_Canonical_Roles::is_canonical_role_at_least( $settings['role'], $role ) ) {
			return true;
		}

		return $this->make_error_message();
	}

	public function get_settings_config() {
		return array(
			'label'       => esc_html__( 'Strong Passwords', 'better-wp-security' ),
			'description' => esc_html__( 'Force users to use strong passwords as rated by the WordPress password meter.', 'better-wp-security' ),
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
				<label for="itsec-password-requirements-requirement_settings-strength-role">
					<?php esc_html_e( 'Minimum Role', 'better-wp-security' ); ?>
				</label>
			</th>
			<td>
				<?php $form->add_canonical_roles( 'role' ); ?>
				<br/>
				<label for="itsec-password-requirements-requirement_settings-strength-role"><?php _e( 'Minimum role at which a user must choose a strong password.', 'better-wp-security' ); ?></label>
				<p class="description"><?php printf( __( 'For more information on WordPress roles and capabilities please see %s.', 'better-wp-security' ), $link ); ?></p>
				<p class="warningtext description"><?php _e( 'Warning: If your site invites public registrations setting the role too low may annoy your members.', 'better-wp-security' ); ?></p>
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
			array( 'string', 'role', esc_html__( 'Minimum Role for Strong Passwords', 'better-wp-security' ) ),
			array( 'canonical-roles', 'role', esc_html__( 'Minimum Role for Strong Passwords', 'better-wp-security' ) ),
		);
	}

	/**
	 * Get the strong password error message according to the given context.
	 *
	 * @return string
	 */
	private function make_error_message() {
		$message = __( '<strong>Error</strong>: Due to site rules, a strong password is required. Please choose a new password that rates as <strong>Strong</strong> on the meter.', 'better-wp-security' );

		return wp_kses( $message, array( 'strong' => array() ) );
	}

	/**
	 * Calculate the strength of a password.
	 *
	 * @param WP_User $user
	 * @param string  $password
	 *
	 * @return int
	 */
	private function get_password_strength( $user, $password ) {

		$penalty_strings = array(
			get_site_option( 'admin_email' )
		);
		$user_properties = array( 'user_login', 'first_name', 'last_name', 'nickname', 'display_name', 'user_email', 'user_url', 'description' );

		foreach ( $user_properties as $user_property ) {
			if ( isset( $user->$user_property ) ) {
				$penalty_strings[] = $user->$user_property;
			}
		}

		$results = ITSEC_Lib::get_password_strength_results( $password, $penalty_strings );

		return $results->score;
	}
}

new ITSEC_Strong_Passwords();
