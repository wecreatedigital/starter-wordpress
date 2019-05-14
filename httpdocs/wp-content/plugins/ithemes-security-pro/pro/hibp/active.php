<?php

/**
 * Class ITSEC_HIBP
 */
class ITSEC_HIBP {

	/**
	 * Initialize the module.
	 */
	public function run() {
		add_action( 'itsec_register_password_requirements', array( $this, 'register_requirement' ) );
	}

	public function register_requirement() {
		ITSEC_Lib_Password_Requirements::register( 'hibp', array(
			'evaluate'        => array( $this, 'evaluate' ),
			'validate'        => array( $this, 'validate' ),
			'reason'          => array( $this, 'reason' ),
			'defaults'        => array( 'role' => 'subscriber' ),
			'settings_config' => array( $this, 'get_settings_config' ),
		) );
	}

	public function evaluate( $password ) {

		require_once( dirname( __FILE__ ) . '/class-itsec-hibp-api.php' );

		return ITSEC_HIBP_API::check_breach_count( $password );
	}

	public function validate( $breaches, $user, $settings, $args ) {
		if ( ! $breaches ) {
			return true;
		}

		require_once( ITSEC_Core::get_core_dir() . '/lib/class-itsec-lib-canonical-roles.php' );

		$role = isset( $args['canonical'] ) ? $args['canonical'] : ITSEC_Lib_Canonical_Roles::get_user_role( $user );

		if ( ! ITSEC_Lib_Canonical_Roles::is_canonical_role_at_least( $settings['role'], $role ) ) {
			return true;
		}

		return esc_html( sprintf( _n( 'This password appeared in a breach %s time. Please choose a new password.', 'This password appeared in a breach %s times. Please choose a new password.', $breaches, 'it-l10n-ithemes-security-pro' ), number_format_i18n( $breaches ) ) );
	}

	public function reason( $breaches ) {
		$text = 'Your password was detected 300 times in password breaches of other websites. Your account hasn’t been compromised on MyWebsite.com, but to keep your account secure, you must update your password now.';
		
		$message = _n(
			'Your password was detected %1$s time in password breaches of other websites. Your account hasn\'t been compromised on %2$s, but to keep your account secure, you must update your password now.',
			'Your password was detected %1$s times in password breaches of other websites. Your account hasn\'t been compromised on %2$s, but to keep your account secure, you must update your password now.',
			$breaches,
			'it-l10n-ithemes-security-pro'
		);
		
		$link = '<a href="' . esc_attr( home_url( '/' ) ) . '">' . get_bloginfo( 'title', 'display' ) . '</a>';
		
		$message = esc_html( $message );
		$message = wptexturize( $message );
		$message = sprintf( $message, number_format_i18n( $breaches ), $link );
		
		return $message;
	}

	public function get_settings_config() {
		$link = 'https://www.troyhunt.com/ive-just-launched-pwned-passwords-version-2/#cloudflareprivacyandkanonymity';

		$description = sprintf(
			esc_html__( 'Force users to use passwords which do not appear in any password breaches tracked by %1$sHave I Been Pwned%2$s.', 'it-l10n-ithemes-security-pro' ),
		 	'<a href="https://haveibeenpwned.com" target="_blank" rel="noopener noreferrer">',
		 	'</a>'
		);
		$description .= ' ' . sprintf(
			esc_html__( 'Plaintext passwords are never sent to Have I Been Pwned. Instead, 5 characters of the hashed password are sent over an encrypted connection to their API. Read the %1$stechnical details here%2$s.', 'it-l10n-ithemes-security-pro' ),
			'<a href="' . esc_attr( $link ) . '"  target="_blank" rel="noopener noreferrer">',
			'</a>'
		);

		return array(
			'label'       => esc_html__( 'Refuse Compromised Passwords', 'it-l10n-ithemes-security-pro' ),
			'description' => $description,
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
				<label for="itsec-password-requirements-requirement_settings-hibp-role">
					<?php esc_html_e( 'Minimum Role', 'it-l10n-ithemes-security-pro' ); ?>
				</label>
			</th>
			<td>
				<?php $form->add_canonical_roles( 'role' ); ?>
				<br/>
				<label for="itsec-password-requirements-requirement_settings-hibp-role"><?php _e( "Minimum role at which a user's password must not appear in a breach.", 'it-l10n-ithemes-security-pro' ); ?></label>
				<p class="description"><?php printf( __( 'For more information on WordPress roles and capabilities please see %s.', 'it-l10n-ithemes-security-pro' ), $link ); ?></p>
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
			array( 'string', 'role', esc_html__( 'Minimum Role for Have I Been Pwned', 'it-l10n-ithemes-security-pro' ) ),
			array( 'canonical-roles', 'role', esc_html__( 'Minimum Role for Have I Been Pwned', 'it-l10n-ithemes-security-pro' ) ),
		);
	}
}

$itesc_hibp = new ITSEC_Hibp();
$itesc_hibp->run();