<?php

/**
 * Class ITSEC_Two_Factor_On_Board
 */
class ITSEC_Two_Factor_On_Board extends ITSEC_Login_Interstitial {

	const LAST_PROMPT_META_KEY = '_itsec_2fa_last_prompt';
	const SKIP_TIMES_META_KEY = '_itsec_2fa_skips';

	/** @var ITSEC_Two_Factor */
	private $two_factor;

	/**
	 * ITSEC_Two_Factor_On_Board constructor.
	 *
	 * @param ITSEC_Two_Factor $two_factor
	 */
	public function __construct( ITSEC_Two_Factor $two_factor ) { $this->two_factor = $two_factor; }

	public function show_on_wp_login_only( WP_User $user ) {
		return true;
	}

	/**
	 * Whether the on board prompt should be shown to the given user.
	 *
	 * @param WP_User $user
	 * @param bool    $requested
	 *
	 * @return bool
	 */
	public function show_to_user( WP_User $user, $requested ) {

		if ( $this->two_factor->is_user_excluded( $user ) ) {
			return false;
		}

		if ( ! $this->get_available_providers( $user ) ) {
			return false;
		}

		if ( $this->two_factor->get_available_providers_for_user( $user, false ) ) {
			return false;
		}

		if ( $requested ) {
			return true;
		}

		if ( 'user_type' === $this->two_factor->get_two_factor_requirement_reason( $user->ID ) ) {
			return true;
		}

		$last_prompt  = (int) get_user_meta( $user->ID, self::LAST_PROMPT_META_KEY, true );
		$time_elapsed = ITSEC_Core::get_current_time_gmt() - $last_prompt;

		if ( $time_elapsed / WEEK_IN_SECONDS > 2 ) {
			return true;
		}

		return false;
	}

	public function is_completion_forced( ITSEC_Login_Interstitial_Session $session ) {
		$reason = $this->two_factor->get_two_factor_requirement_reason( $session->get_user()->ID );

		if ( ! $reason || 'vulnerable_site' === $reason ) {
			return false;
		}

		return true;
	}

	public function has_submit() {
		return true;
	}

	public function submit( ITSEC_Login_Interstitial_Session $session, array $data ) {

		require_once( dirname( __FILE__ ) . '/providers/class.two-factor-backup-codes.php' );

		$user = $session->get_user();

		if ( ! empty( $data['itsec_skip'] ) ) {
			if ( $this->is_completion_forced( $session ) ) {
				return new WP_Error(
					'itsec-2fa-on-board-cannot-skip',
					esc_html__( 'Your account is required to setup Two Factor authentication.', 'it-l10n-ithemes-security-pro' )
				);
			}

			if ( get_user_meta( $user->ID, Two_Factor_Backup_Codes::TEMP_FLAG_META_KEY, true ) ) {
				delete_user_meta( $user->ID, Two_Factor_Backup_Codes::BACKUP_CODES_META_KEY );
				delete_user_meta( $user->ID, Two_Factor_Backup_Codes::TEMP_FLAG_META_KEY );
			}

			update_user_meta( $user->ID, self::LAST_PROMPT_META_KEY, ITSEC_Core::get_current_time_gmt() );

			$skips = (int) get_user_meta( $user->ID, self::SKIP_TIMES_META_KEY, true );
			update_user_meta( $user->ID, self::SKIP_TIMES_META_KEY, $skips + 1 );

			return null;
		}

		if ( empty( $data['itsec_two_factor_on_board_data'] ) ) {
			return new WP_Error(
				'itsec-2fa-on-board-no-data',
				esc_html__( 'No On-Board data provided.', 'it-l10n-ithemes-security-pro' )
			);
		}

		$providers = json_decode( wp_unslash( $data['itsec_two_factor_on_board_data'] ), true );

		if ( null === $providers || ( function_exists( 'json_last_error' ) && json_last_error() ) ) {
			return new WP_Error(
				'itsec-2fa-on-board-invalid-json',
				sprintf( esc_html__( 'Invalid On-Board data: %s', 'it-l10n-ithemes-security-pro' ), json_last_error_msg() )
			);
		}

		$enabled = array();
		$primary = false;

		foreach ( $providers as $provider ) {
			if ( $provider['status'] !== 'disabled' ) {
				$enabled[] = $provider['id'];
			}
		}

		if ( in_array( 'Two_Factor_Totp', $enabled, true ) ) {
			$primary = 'Two_Factor_Totp';
		} elseif ( in_array( 'Two_Factor_Email', $enabled, true ) ) {
			$primary = 'Two_Factor_Email';
		}

		if ( ! in_array( 'Two_Factor_Backup_Codes', $enabled, true ) && get_user_meta( $user->ID, Two_Factor_Backup_Codes::TEMP_FLAG_META_KEY, true ) ) {
			delete_user_meta( $user->ID, Two_Factor_Backup_Codes::BACKUP_CODES_META_KEY );
		}
		delete_user_meta( $user->ID, Two_Factor_Backup_Codes::TEMP_FLAG_META_KEY );

		$this->two_factor->set_enabled_providers_for_user( $enabled, $user->ID );

		if ( $primary ) {
			$this->two_factor->set_primary_provider_for_user( $primary, $user->ID );
		}

		update_user_meta( $user->ID, self::LAST_PROMPT_META_KEY, ITSEC_Core::get_current_time_gmt() );
		delete_user_meta( $user->ID, Two_Factor_Backup_Codes::TEMP_FLAG_META_KEY );

		if ( $session = ITSEC_Core::get_login_interstitial()->get_current_session() ) {
			$session->add_completed_interstitial( '2fa' );
			$session->save();
		}

		return null;
	}

	public function has_ajax_handlers() {
		return true;
	}

	public function handle_ajax( ITSEC_Login_Interstitial_Session $session, array $data ) {

		if ( empty( $data['itsec_method'] ) ) {
			wp_send_json_error( array(
				'message' => esc_html__( 'Invalid ajax method.', 'it-l10n-ithemes-security-pro' ),
			) );
		}

		foreach ( $this->get_available_providers( $session->get_user() ) as $provider ) {
			$provider->handle_ajax_on_board( $session->get_user(), $data );
		}

		wp_send_json_error( array(
			'message' => esc_html__( 'Invalid ajax method.', 'it-l10n-ithemes-security-pro' ),
		) );
	}

	public function has_async_action() {
		return true;
	}

	public function handle_async_action( ITSEC_Login_Interstitial_Session $session, $action, array $args ) {
		if ( '2fa-verify-email' !== $action ) {
			return null;
		}

		$session->set_state( array(
			'email_verified' => true,
		) );
		$session->save();

		return array(
			'message'            => esc_html__( 'Email confirmed. Please continue setting up Two-Factor in your original browser window.', 'it-l10n-ithemes-security-pro' ),
			'allow_same_browser' => false,
		);
	}

	public function render( ITSEC_Login_Interstitial_Session $session, array $args ) {

		$user      = $session->get_user();
		$can_skip  = ! $this->is_completion_forced( $session );
		$providers = array();

		foreach ( $this->get_available_providers( $user ) as $provider ) {
			$providers[] = array(
				'id'           => get_class( $provider ),
				'label'        => $provider->get_on_board_label(),
				'description'  => $provider->get_on_board_description(),
				'dashicon'     => $provider->get_on_board_dashicon(),
				'configurable' => $provider->has_on_board_configuration(),
				'config'       => $provider->get_on_board_config( $user ),
				'status'       => $this->get_provider_status( $session, $provider ),
			);
		}

		$confirm_email = ITSEC_Core::get_notification_center()->is_notification_enabled( 'two-factor-confirm-email' );

		$list = apply_filters( 'wp_sprintf_l', array(
			/* translators: used to join items in a list with more than 2 items */
			'between'          => sprintf( __( '%1$s, %2$s' ), '', '' ),
			/* translators: used to join last two items in a list with more than 2 times */
			'between_last_two' => sprintf( __( '%1$s, and %2$s' ), '', '' ),
			/* translators: used to join items in a list with only 2 items */
			'between_only_two' => sprintf( __( '%1$s and %2$s' ), '', '' ),
		) );

		/* translators: 1. List of enabled Two-Factor methods. */
		$summary = __( "Two-Factor is all setup and ready to go. The next time you login, you'll be asked to enter an Authentication Code from your %l.", 'it-l10n-ithemes-security-pro' );
		$summary = apply_filters( 'itsec_two_factor_on_board_summary', $summary, $user );

		wp_enqueue_style( 'itsec-2fa-on-board', plugin_dir_url( __FILE__ ) . 'css/on-board.css', array( 'dashicons' ) );
		wp_enqueue_script( 'itsec-2fa-on-board', plugin_dir_url( __FILE__ ) . 'js/on-board.js', array( 'jquery', 'wp-backbone', 'underscore', 'wp-a11y' ), 5 );
		wp_localize_script( 'itsec-2fa-on-board', 'ITSEC2FAOnBoard', array(
			'user'          => $user->ID,
			'list'          => $list,
			'can_skip'      => $can_skip,
			'providers'     => $providers,
			'confirm_email' => apply_filters( 'itsec_two_factor_on_board_confirm_email', $confirm_email, $user ),
			'l10n'          => array(
				'enabled'              => __( 'Enabled', 'it-l10n-ithemes-security-pro' ),
				'disabled'             => __( 'Disabled', 'it-l10n-ithemes-security-pro' ),
				'not-configured'       => __( 'Unconfigured', 'it-l10n-ithemes-security-pro' ),
				'summary'              => $summary,
				'require_notice'       => $this->two_factor->get_reason_description( $this->two_factor->get_two_factor_requirement_reason( $user->ID ) ),
				'backup_codes_warning' => sprintf(
					esc_html__( 'Make sure to copy or download the backup codes before proceeding. %1$s Ok %2$s', 'it-l10n-ithemes-security-pro' ),
					'<button class="button-link">',
					'</button>'
				),
			),
		) );

		$confirm_email_message = sprintf(
		/* translators: 1. User's email address, 2. WP email address, 3. Configured Subject Line */
			esc_html__( 'Check your email, %1$s, for a message from %2$s. It should have "%3$s" in the subject line.', 'it-l10n-ithemes-security-pro' ),
			esc_html( $user->user_email ),
			esc_html( $this->get_from_email() ),
			esc_html( ITSEC_Core::get_notification_center()->get_subject( 'two-factor-confirm-email' ) )
		);

		$two_factor_info = ITSEC_Modules::get_setting( 'two-factor', 'on_board_welcome' );

		/**
		 * Filter the info about Two-Factor provided on the first screen of the Two Factor On-Board flow.
		 *
		 * @param string  $two_factor_info Info text.
		 * @param WP_User $user            The user being shown the flow. Do Not use wp_get_current_user().
		 */
		$two_factor_info = apply_filters( 'itsec_two_factor_on_board_info', $two_factor_info, $user );
		$two_factor_info = wptexturize( wpautop( $two_factor_info ) );
		?>

		<div id="itsec-2fa-on-board-app">
			<div class="itsec-screen itsec-screen--intro">
				<div class="itsec-screen__content">
					<noscript><?php esc_html_e( 'JavaScript is required to setup Two-Factor Authentication.', 'it-l10n-ithemes-security-pro' ); ?></noscript>
					<h2 style="margin-bottom: .5em"><?php esc_html_e( 'Setup Two-Factor', 'it-l10n-ithemes-security-pro' ); ?></h2>
					<?php echo $two_factor_info; ?>
				</div>
				<div class="itsec-screen__actions">
					<?php if ( $can_skip ) : ?>
						<button class="button-link itsec-screen__actions--skip" name="itsec_skip" value="skip" type="submit" disabled>
							<?php esc_html_e( 'Skip', 'it-l10n-ithemes-security-pro' ); ?>
						</button>
					<?php endif; ?>
					<button class="button button-primary itsec-screen__actions--continue" disabled>
						<?php esc_html_e( 'Continue', 'it-l10n-ithemes-security-pro' ); ?>
					</button>
				</div>
			</div>
		</div>
		<?php

		require_once( dirname( __FILE__ ) . '/includes/template.php' );
	}

	/**
	 * Get the available providers.
	 *
	 * @param WP_User $user
	 *
	 * @return ITSEC_Two_Factor_Provider_On_Boardable[]
	 */
	private function get_available_providers( $user ) {
		$providers = array();

		foreach ( $this->two_factor->get_helper()->get_enabled_provider_instances() as $provider ) {
			if ( $provider instanceof ITSEC_Two_Factor_Provider_On_Boardable ) {
				$providers[] = $provider;
			}
		}

		return $providers;
	}

	/**
	 * Get the provider status.
	 *
	 * @param ITSEC_Login_Interstitial_Session $session
	 * @param Two_Factor_Provider              $provider
	 *
	 * @return string
	 */
	private function get_provider_status( ITSEC_Login_Interstitial_Session $session, $provider ) {

		$user          = $session->get_user();
		$is_configured = (bool) $this->two_factor->get_available_providers_for_user( $user, false );

		$default_enabled = ! $this->is_completion_forced( $session ) ? array() : array(
			'Two_Factor_Backup_Codes',
			'Two_Factor_Email',
		);

		$enabled = $this->two_factor->get_enabled_providers_for_user( $user );

		if ( ! $is_configured && in_array( get_class( $provider ), $default_enabled, true ) ) {
			return $provider->is_available_for_user( $user ) ? 'enabled' : 'not-configured';
		}

		if ( ! isset( $enabled[ get_class( $provider ) ] ) ) {
			return 'disabled';
		}

		if ( $provider->is_available_for_user( $user ) ) {
			return 'enabled';
		}

		return 'not-configured';
	}

	private function get_from_email() {

		if ( $email = ITSEC_Modules::get_setting( 'notification-center', 'from_email' ) ) {
			return $email;
		}

		// Get the site domain and get rid of www.
		$sitename = strtolower( $_SERVER['SERVER_NAME'] );

		if ( substr( $sitename, 0, 4 ) == 'www.' ) {
			$sitename = substr( $sitename, 4 );
		}

		$from_email = 'wordpress@' . $sitename;

		// This filter is documented in wp-includes/pluggable.php
		return apply_filters( 'wp_mail_from', $from_email );
	}
}
