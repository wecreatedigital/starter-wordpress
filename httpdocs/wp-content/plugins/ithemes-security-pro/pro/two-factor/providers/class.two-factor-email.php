<?php
/**
 * Class for creating an email provider.
 *
 * @since 0.1-dev
 *
 * @package Two_Factor
 */
class Two_Factor_Email extends Two_Factor_Provider implements ITSEC_Two_Factor_Provider_On_Boardable {

	/**
	 * The user meta token key.
	 * @type string
	 */
	const TOKEN_META_KEY = '_two_factor_email_token';

	/**
	 * Ensures only one instance of this class exists in memory at any one time.
	 *
	 * @since 0.1-dev
	 */
	static function get_instance() {
		static $instance;
		$class = __CLASS__;
		if ( ! is_a( $instance, $class ) ) {
			$instance = new $class;
		}
		return $instance;
	}

	/**
	 * Class constructor.
	 *
	 * @since 0.1-dev
	 */
	protected function __construct() {
		add_action( 'two-factor-user-options-' . __CLASS__, array( $this, 'user_options' ) );
		add_action( 'two-factor-admin-options-' . __CLASS__, array( $this, 'description' ) );
		return parent::__construct();
	}

	/**
	 * Returns the name of the provider.
	 *
	 * @since 0.1-dev
	 */
	public function get_label() {
		return _x( 'Email', 'Provider Label', 'it-l10n-ithemes-security-pro' );
	}

	/**
	 * Generate the user token.
	 *
	 * @since 0.1-dev
	 *
	 * @param int $user_id User ID.
	 * @return string
	 */
	public function generate_token( $user_id ) {
		$token = $this->get_code();
		update_user_meta( $user_id, self::TOKEN_META_KEY, wp_hash( $token ) );
		return $token;
	}

	/**
	 * Validate the user token.
	 *
	 * @since 0.1-dev
	 *
	 * @param int    $user_id User ID.
	 * @param string $token User token.
	 * @return boolean
	 */
	public function validate_token( $user_id, $token ) {
		$hashed_token = get_user_meta( $user_id, self::TOKEN_META_KEY, true );
		if ( wp_hash( $token ) !== $hashed_token ) {
			$this->delete_token( $user_id );
			return false;
		}
		return true;
	}

	/**
	 * Delete the user token.
	 *
	 * @since 0.1-dev
	 *
	 * @param int $user_id User ID.
	 */
	public function delete_token( $user_id ) {
		delete_user_meta( $user_id, self::TOKEN_META_KEY );
	}

	/**
	 * Generate and email the user token.
	 *
	 * @since 0.1-dev
	 *
	 * @param WP_User $user User object of the logged-in user.
	 * @param bool    $is_signup
	 */
	public function generate_and_email_token( $user, $is_signup = false ) {
		$token = $this->generate_token( $user->ID );

		$nc = ITSEC_Core::get_notification_center();

		$parsed = parse_url( site_url() );
		$url = $parsed['host'];

		if ( ! empty( $parsed['path'] ) ) {
			$url .= $parsed['path'];
		}

		if ( $is_signup ) {
			$subject = $nc->get_subject( 'two-factor-confirm-email' );
			$message = $nc->get_message( 'two-factor-confirm-email' );
		} else {
			$subject = $nc->get_subject( 'two-factor-email' );
			$message = $nc->get_message( 'two-factor-email' );
		}

		/* translators: 1: site URL, 2: email subject */
		$subject = sprintf( __( '[%1$s] %2$s', 'it-l10n-ithemes-security-pro' ), $url, $subject );

		$message = ITSEC_Lib::replace_tags( $message, array(
			'username'     => $user->user_login,
			'display_name' => $user->display_name,
			'site_title'   => get_bloginfo( 'name', 'display' ),
		) );

		$mail = $nc->mail();
		$mail->set_recipients( array( $user->user_email ) );
		$mail->set_subject( $subject, false );
		$mail->add_header(
			$is_signup ? esc_html__( 'Finish Setting Up Two-Factor', 'it-l10n-ithemes-security-pro' ) : esc_html__( 'Continue Logging On', 'it-l10n-ithemes-security-pro' ),
			'',
			true
		);
		$mail->add_text( $message );

		if ( $session = ITSEC_Core::get_login_interstitial()->get_current_session() ) {
			ITSEC_Core::get_login_interstitial()->initialize_same_browser( $session );
			$mail->add_large_button(
				esc_html__( 'Continue', 'it-l10n-ithemes-security-pro' ),
				ITSEC_Core::get_login_interstitial()->get_async_action_url( $session, '2fa-verify-email' )
			);
		}

		$mail->add_small_code( $token );
		$mail->add_user_footer();

		$nc->send( 'two-factor-email', $mail );
	}

	public function pre_render_authentication_page( $user ) {
		$this->generate_and_email_token( $user );
	}

	/**
	 * Prints the form that prompts the user to authenticate.
	 *
	 * @since 0.1-dev
	 *
	 * @param WP_User $user WP_User object of the logged-in user.
	 */
	public function authentication_page( $user ) {
		require_once( ABSPATH .  '/wp-admin/includes/template.php' );
		$subject = ITSEC_Core::get_notification_center()->get_subject( 'two-factor-email' );
		?>
		<p style="padding-bottom:1em;"><?php printf( esc_html__( 'An Authentication Code has been sent to the email address associated with your account. Look for an email with "%s" in the subject line.', 'it-l10n-ithemes-security-pro' ), $subject ); ?></p>
		<p>
			<label for="authcode"><?php esc_html_e( 'Authentication Code:', 'it-l10n-ithemes-security-pro' ); ?></label>
			<input type="tel" name="two-factor-email-code" id="authcode" class="input" value="" size="20" pattern="[0-9]*" />
		</p>
		<script type="text/javascript">
			setTimeout( function(){
				var d;
				try{
					d = document.getElementById('authcode');
					d.value = '';
					d.focus();
				} catch(e){}
			}, 200);
		</script>
		<?php
		submit_button( __( 'Log In', 'it-l10n-ithemes-security-pro' ) );
	}

	/**
	 * Validates the users input token.
	 *
	 * @since 0.1-dev
	 *
	 * @param WP_User $user WP_User object of the logged-in user.
	 * @return boolean
	 */
	public function validate_authentication( $user ) {
		return $this->validate_token( $user->ID, trim( $_REQUEST['two-factor-email-code'] ) );
	}

	/**
	 * Whether this Two-Factor provider is configured and available for the user specified.
	 *
	 * @since 0.1-dev
	 *
	 * @param WP_User $user WP_User object of the logged-in user.
	 * @return boolean
	 */
	public function is_available_for_user( $user ) {
		return true;
	}

	/**
	 * Inserts markup at the end of the user profile field for this provider.
	 *
	 * @since 0.1-dev
	 *
	 * @param WP_User $user WP_User object of the logged-in user.
	 */
	public function user_options( $user ) {
		$email = $user->user_email;
		?>
		<div>
			<?php echo esc_html( sprintf( __( 'Authentication codes will be sent to %1$s.', 'it-l10n-ithemes-security-pro' ), $email ) ); ?>
		</div>
		<?php
	}

	public function description() {
		echo '<p class="description">' . __( 'Time-sensitive codes are supplied via email to the email address associated with the user\'s account. Note: This WordPress site must support sending emails for this method to work (for example, sending WordPress-generated emails such as password reset and new account emails).', 'it-l10n-ithemes-security-pro' ) . '</p>';
	}

	/**
	 * @inheritDoc
	 */
	public function get_on_board_dashicon() {
		return 'email-alt';
	}

	/**
	 * @inheritDoc
	 */
	public function get_on_board_label() {
		return esc_html__( 'Email', 'it-l10n-ithemes-security-pro' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_on_board_description() {
		return esc_html__( 'Receive an email every time you login.', 'it-l10n-ithemes-security-pro' );
	}

	/**
	 * @inheritDoc
	 */
	public function has_on_board_configuration() {
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function get_on_board_config( WP_User $user ) {
		return array();
	}

	/**
	 * @inheritDoc
	 */
	public function handle_ajax_on_board( WP_User $user, array $data ) {
		switch ( $data['itsec_method'] ) {
			case 'verify-email-code':
				if ( ! isset( $data['itsec_email_code'] ) ) {
					wp_send_json_error( array(
						'message' => esc_html__( 'Invalid Request Format', 'it-l10n-ithemes-security-pro' ),
					) );
				}

				if ( $this->validate_token( $user->ID, $data['itsec_email_code'] ) ) {
					wp_send_json_success( array(
						'message' => esc_html__( 'Success!', 'it-l10n-ithemes-security-pro' ),
					) );
				} else {
					$this->generate_and_email_token( $user, true );

					wp_send_json_error( array(
						'message' => esc_html__( 'The code you supplied is not valid. Please check your email for a new code.', 'it-l10n-ithemes-security-pro' ),
					) );
				}
				break;
			case 'send-email-code':
				$this->generate_and_email_token( $user, true );

				wp_send_json_success( array(
					'message' => esc_html__( 'Email sent!', 'it-l10n-ithemes-security-pro' ),
				) );
				break;
		}
	}
}
