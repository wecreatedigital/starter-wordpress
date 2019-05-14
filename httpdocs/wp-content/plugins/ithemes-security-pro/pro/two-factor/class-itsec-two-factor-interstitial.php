<?php

class ITSEC_Two_Factor_Interstitial extends ITSEC_Login_Interstitial {

	const SAME_BROWSER_COOKIE_NAME = 'itsec-2fa-same-session';
	const SAME_BROWSER_ACTION = '2fa-same-session';

	/** @var ITSEC_Two_Factor */
	private $two_factor;

	/** @var string */
	private $failed_provider_label;

	/** @var string */
	private $current_provider_class;

	/**
	 * ITSEC_Two_Factor_Interstitial constructor.
	 *
	 * @param ITSEC_Two_Factor $two_factor
	 */
	public function __construct( ITSEC_Two_Factor $two_factor ) { $this->two_factor = $two_factor; }

	/**
	 * Run initialization code for the interstitial.
	 */
	public function run() {
		add_action( 'itsec_two_factor_override', array( $this, 'proceed_on_override' ) );
	}

	/**
	 * Proceed to the next interstitial when the user's 2fa code is overridden.
	 *
	 * @param WP_User $user
	 */
	public function proceed_on_override( $user ) {
		if ( ! $user instanceof WP_User ) {
			return;
		}

		foreach ( ITSEC_Login_Interstitial_Session::get_all( $user ) as $session ) {
			if ( '2fa' === $session->get_current_interstitial() ) {
				ITSEC_Core::get_login_interstitial()->proceed_to_next( $session );
			}
		}
	}

	/**
	 * @inheritDoc
	 */
	public function render( ITSEC_Login_Interstitial_Session $session, array $args ) {

		$user = $session->get_user();

		$available_providers = $this->two_factor->get_available_providers_for_user( $user );

		if ( ! $provider = $this->get_provider( $session ) ) {
			echo '<div style="background-color: #fbeaea;border-left: 4px solid #dc3232;padding: 6px 12px;display: block;margin: 0 0 1.5em 0;"><p>';
			printf( esc_html__( 'Invalid Two-Factor provider. Please try %1$slogging in again%2$s.', 'it-l10n-ithemes-security-pro' ), '<a href="' . esc_url( $args['wp_login_url'] ) . '">', '</a>' );
			echo '</p></div>';

			return;
		}

		$provider_class   = get_class( $provider );
		$backup_providers = array_diff_key( $available_providers, array( $provider_class => null ) );
		?>
		<input type="hidden" name="provider" id="provider" value="<?php echo esc_attr( $provider_class ); ?>"/>
		<?php $provider->authentication_page( $user ); ?>

		<?php if ( $this->two_factor->is_remember_allowed( $user ) ): ?>
			<p>
				<label for="itsec-remember-2fa" style="font-size: 12px">
					<input type="checkbox" name="itsec_remember_2fa" id="itsec-remember-2fa">
					<?php esc_html_e( 'Remember Device for 30 Days', 'it-l10n-ithemes-security-pro' ); ?>
				</label>
			</p>
		<?php endif; ?>

		<?php if ( $backup_providers ) : ?>
			<div class="itsec-backup-methods" style="clear:both;margin-top:4em;padding-top:2em;border-top:1px solid #ddd;">
				<p><?php esc_html_e( 'Or, use a backup method:', 'it-l10n-ithemes-security-pro' ); ?></p>
				<ul style="margin-left:1em;">
					<?php foreach ( $backup_providers as $backup_classname => $backup_provider ) : ?>
						<li>
							<a href="<?php echo esc_url( add_query_arg( urlencode_deep( array(
								'action'                                => 'itsec-2fa',
								'provider'                              => $backup_classname,
								ITSEC_Lib_Login_Interstitial::R_USER    => $user->ID,
								ITSEC_Lib_Login_Interstitial::R_TOKEN   => $session->get_signature(),
								ITSEC_Lib_Login_Interstitial::R_SESSION => $session->get_id(),
							) ), $args['wp_login_url'] ) ); ?>">
								<?php $backup_provider->print_label(); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>
		<?php
	}

	public function pre_render( ITSEC_Login_Interstitial_Session $session ) {
		if ( $provider = $this->get_provider( $session ) ) {
			$provider->pre_render_authentication_page( $session->get_user() );
		}
	}

	/**
	 * Get the provider to use.
	 *
	 * @param ITSEC_Login_Interstitial_Session $session
	 *
	 * @return Two_Factor_Provider|null
	 */
	private function get_provider( ITSEC_Login_Interstitial_Session $session ) {
		$available_providers = $this->two_factor->get_available_providers_for_user( $session->get_user() );

		$provider = empty( $_GET['provider'] ) ? '' : $_GET['provider'];

		if ( ! $provider ) {
			$provider = $this->two_factor->get_primary_provider_for_user( $session->get_user()->ID );
		} elseif ( is_string( $provider ) ) {
			if ( ! isset( $available_providers[ $provider ] ) || ! method_exists( $provider, 'get_instance' ) ) {
				return null;
			}

			$provider = call_user_func( array( $provider, 'get_instance' ) );
		}

		return $provider;
	}

	public function has_submit() {
		return true;
	}

	public function submit( ITSEC_Login_Interstitial_Session $session, array $post_data ) {

		$user    = $session->get_user();
		$user_id = $user->ID;

		if ( isset( $post_data['provider'] ) ) {
			$providers = $this->two_factor->get_available_providers_for_user( $user );
			if ( isset( $providers[ $post_data['provider'] ] ) ) {
				$provider = $providers[ $post_data['provider'] ];
			} else {
				ITSEC_Log::add_debug( 'two_factor', "failed_authentication::$user_id,missing_provider", compact( 'user_id', 'post_data' ), compact( 'user_id' ) );

				return new WP_Error(
					'itsec-two-factor-missing-provider',
					esc_html__( 'Invalid Two Factor provider.', 'it-l10n-ithemes-security-pro' )
				);
			}
		} else {
			$provider = $this->two_factor->get_primary_provider_for_user( $user->ID );
		}

		$provider_class = get_class( $provider );
		$user_id        = $user->ID;

		if ( true !== $provider->validate_authentication( $user ) ) {
			ITSEC_Log::add_debug( 'two_factor', "failed_authentication::$user_id,$provider_class,invalid_code", compact( 'user_id', 'provider_class', 'post_data' ), compact( 'user_id' ) );

			$this->failed_provider_label = $provider->get_label();
			add_filter( 'itsec-filter-failed-login-details', array( $this, 'filter_failed_login_details' ) );

			do_action( 'wp_login_failed', $user->user_login );

			return new WP_Error(
				'itsec-two-factor-invalid-code',
				esc_html__( 'ERROR: Invalid Authentication Code.', 'it-l10n-ithemes-security-pro' )
			);
		}

		$this->current_provider_class = $provider_class;

		if ( ! empty( $post_data['itsec_remember_2fa'] ) && $this->two_factor->is_remember_allowed( $user ) ) {
			$this->two_factor->set_remember_cookie( $user );
		}

		return null;
	}

	public function has_async_action() {
		return true;
	}

	public function handle_async_action( ITSEC_Login_Interstitial_Session $session, $action, array $args ) {
		if ( '2fa-verify-email' !== $action ) {
			return null;
		}

		ITSEC_Core::get_login_interstitial()->proceed_to_next( $session );

		return array(
			'message' => esc_html__( 'Login authorized. Please continue in your original browser.', 'it-l10n-ithemes-security-pro' ),
		);
	}

	public function after_submit( ITSEC_Login_Interstitial_Session $session, array $post_data ) {

		$user_id        = $session->get_user()->ID;
		$provider_class = $this->current_provider_class;

		ITSEC_Log::add_debug( 'two_factor', "successful_authentication::$user_id,$provider_class", compact( 'user_id', 'provider_class', 'post_data' ), compact( 'user_id' ) );
		do_action( 'itsec-two-factor-successful-authentication', $user_id, $provider_class );
	}

	public function show_to_user( WP_User $user, $is_requested ) {

		if ( ! $this->two_factor->is_user_using_two_factor( $user->ID ) ) {
			// The user is logged in and not using two factor, remove user meta to show recommended notice again.
			delete_user_meta( $user->ID, 'itsec-two-factor-hide-recommended-notice-this-session' );

			return false;
		}

		if ( $this->two_factor->is_sync_override_active( $user->ID ) ) {
			// Sync override is active. Do not request the authentication code.
			return false;
		}

		if ( did_action( 'jetpack_sso_handle_login' ) ) {
			// This is a Jetpack Single Sign On login.
			return false;
		}

		if ( ITSEC_Modules::get_setting( 'two-factor', 'disable_first_login' ) && ! get_user_meta( $user->ID, '_itsec_has_logged_in', true ) ) {
			return false;
		}

		if ( empty( $_COOKIE[ ITSEC_Two_Factor::REMEMBER_COOKIE ] ) || ! $this->two_factor->is_remember_allowed( $user ) ) {
			return true;
		}

		$token = $_COOKIE[ ITSEC_Two_Factor::REMEMBER_COOKIE ];

		foreach ( get_user_meta( $user->ID, ITSEC_Two_Factor::REMEMBER_META_KEY ) as $possible ) {
			if ( empty( $possible['hashed'] ) || empty( $possible['expires'] ) || $possible['expires'] < ITSEC_Core::get_current_time_gmt() ) {
				delete_user_meta( $user->ID, ITSEC_Two_Factor::REMEMBER_META_KEY, $possible );

				continue;
			}

			if ( ITSEC_Lib::verify_token( $token, $possible['hashed'] ) ) {

				require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-lib-fingerprinting.php' );
				$match = ITSEC_Lib_Fingerprinting::check_global_state_fingerprint_for_match( $user );

				if ( ! $match || $match->get_match_percent() < 85 ) {
					$this->two_factor->clear_remember_cookie();
					ITSEC_Log::add_debug( 'two_factor', 'remember_fingerprint_failed', array( 'match' => $match ), array( 'user_id' => $user->ID ) );

					return true;
				}

				ITSEC_Log::add_debug( 'two_factor', 'remember_success', $possible, array( 'user_id' => $user->ID ) );

				delete_user_meta( $user->ID, ITSEC_Two_Factor::REMEMBER_META_KEY, $possible );
				$this->two_factor->set_remember_cookie( $user );

				return false;
			}
		}

		$this->two_factor->clear_remember_cookie();

		ITSEC_Log::add_debug( 'two_factor', 'remember_failed', false, array( 'user_id' => $user->ID ) );

		return true;
	}

	public function get_priority() {
		return 1;
	}

	/**
	 * Filter the failed login details.
	 *
	 * @param array $details
	 *
	 * @return array
	 */
	public function filter_failed_login_details( $details ) {
		if ( empty( $this->failed_provider_label ) ) {
			$details['authentication_types'] = array( __( 'unknown_two_factor_provider', 'it-l10n-ithemes-security-pro' ) );
		} else {
			$details['authentication_types'] = array( $this->failed_provider_label );
		}

		return $details;
	}
}
