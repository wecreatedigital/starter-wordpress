<?php

/**
 * Two-Factor Execution
 *
 * Handles all two-factor execution once the feature has been
 * enabled by the user.
 *
 * @since   1.2.0
 *
 * @package iThemes_Security
 */
class ITSEC_Two_Factor {
	private static $instance = false;

	const REMEMBER_COOKIE = 'itsec_remember_2fa';
	const REMEMBER_META_KEY = '_itsec_remember_2fa';

	/**
	 * Helper class
	 *
	 * @access private
	 * @var ITSEC_Two_Factor_Helper
	 */
	private $helper;

	/**
	 * The user meta provider key.
	 *
	 * @access private
	 * @var string
	 */
	private $_provider_user_meta_key = '_two_factor_provider';

	/**
	 * The user meta enabled providers key.
	 *
	 * @access private
	 * @var string
	 */
	private $_enabled_providers_user_meta_key = '_two_factor_enabled_providers';

	private function __construct() {

		add_action( 'itsec_login_interstitial_init', array( $this, 'register_interstitial' ) );
		add_action( 'updated_post_meta', array( $this, 'clear_remember_on_password_change' ), 10, 3 );

		add_action( 'show_user_profile', array( $this, 'user_two_factor_options' ) );
		add_action( 'edit_user_profile', array( $this, 'user_two_factor_options' ) );
		add_action( 'personal_options_update', array( $this, 'user_two_factor_options_update' ) );
		add_action( 'edit_user_profile_update', array( $this, 'user_two_factor_options_update' ) );

		add_action( 'ithemes_sync_register_verbs', array( $this, 'register_sync_verbs' ) );
		add_filter( 'itsec-filter-itsec-get-everything-verbs', array( $this, 'register_sync_get_everything_verbs' ) );

		add_action( 'admin_init', array( $this, 'admin_init' ) );

		add_action( 'load-profile.php', array( $this, 'add_profile_page_styling' ) );
		add_action( 'load-user-edit.php', array( $this, 'add_profile_page_styling' ) );

		add_filter( 'itsec_notifications', array( $this, 'register_notifications' ) );
		add_filter( 'itsec_two-factor-email_notification_strings', array( $this, 'two_factor_email_method_strings' ) );
		add_filter( 'itsec_two-factor-confirm-email_notification_strings', array( $this, 'two_factor_confirm_email_method_strings' ) );

		$this->load_helper();
	}

	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	private function load_helper() {
		if ( ! isset( $this->helper ) ) {
			require_once( dirname( __FILE__ ) . '/class-itsec-two-factor-helper.php' );
			$this->helper  = ITSEC_Two_Factor_Helper::get_instance();
		}
	}

	/**
	 * On every admin page, determine if the user needs to be reminded about setting up Two Factor for their account.
	 */
	public function admin_init() {
		global $pagenow;

		if ( isset( $_GET['itsec-action'] ) && 'configure-two-factor' === $_GET['itsec-action'] && 'profile.php' !== $pagenow ) {
			wp_safe_redirect( admin_url( 'profile.php#two-factor-user-options' ) );
		}


		if ( defined( 'ITSEC_DISABLE_TWO_FACTOR' ) && ITSEC_DISABLE_TWO_FACTOR ) {
			if ( is_multisite() ) {
				add_action( 'network_admin_notices', array( $this, 'show_two_factor_disabled_warning' ) );
			} else {
				add_action( 'admin_notices', array( $this, 'show_two_factor_disabled_warning' ) );
			}
		}
	}

	public function show_two_factor_disabled_warning() {
		if ( ! current_user_can( ITSEC_Core::get_required_cap() ) ) {
			return;
		}

		echo '<div class="error"><p><strong>';
		echo wp_kses( __( 'The <code>ITSEC_DISABLE_TWO_FACTOR</code> define is present. As long as the define is present, two-factor authentication is disabled for all users which makes your site more vulnerable. Please make any necessary changes and remove the define as soon as possible.', 'it-l10n-ithemes-security-pro' ), array( 'code' => array() ) );
		echo '</strong></p></div>';
	}

	/**
	 * Register verbs for Sync.
	 *
	 * @since 3.6.0
	 *
	 * @param Ithemes_Sync_API Sync API object.
	 */
	public function register_sync_verbs( $api ) {
		$api->register( 'itsec-get-two-factor-users', 'Ithemes_Sync_Verb_ITSEC_Get_Two_Factor_Users', dirname( __FILE__ ) . '/sync-verbs/itsec-get-two-factor-users.php' );
		$api->register( 'itsec-override-two-factor-user', 'Ithemes_Sync_Verb_ITSEC_Override_Two_Factor_User', dirname( __FILE__ ) . '/sync-verbs/itsec-override-two-factor-user.php' );
	}

	/**
	 * Filter to add verbs to the response for the itsec-get-everything verb.
	 *
	 * @since 3.6.0
	 *
	 * @param  array Array of verbs.
	 *
	 * @return array Array of verbs.
	 */
	public function register_sync_get_everything_verbs( $verbs ) {
		$verbs['two_factor'][] = 'itsec-get-two-factor-users';

		return $verbs;
	}

	/**
	 * Add user profile fields.
	 *
	 * This executes during the `show_user_profile` & `edit_user_profile` actions.
	 *
	 * @param WP_User $user WP_User object of the logged-in user.
	 */
	public function user_two_factor_options( $user ) {
		$this->load_helper();

		$enabled_providers = get_user_meta( $user->ID, $this->_enabled_providers_user_meta_key, true );
		if ( empty( $enabled_providers ) ) {
			// Because get_user_meta() has no way of providing a default value.
			$enabled_providers = array();
		}
		$primary_provider = get_user_meta( $user->ID, $this->_provider_user_meta_key, true );
		wp_nonce_field( 'user_two_factor_options', '_nonce_user_two_factor_options', false );
		?>
		<h3 id="two-factor-user-options"><?php esc_html_e( 'Two-Factor Authentication Options', 'it-l10n-ithemes-security-pro' ); ?></h3>
		<p><?php esc_html_e( 'Enabling two-factor authentication greatly increases the security of your user account on this site. With two-factor authentication enabled, after you login with your username and password, you will be asked for an authentication code before you can successfully log in.');?><strong> <?php esc_html_e('Two-factor authentication codes can come from an app that runs on your mobile device, an email that is sent to you after you login with your username and password, or from a pre-generated list of codes.');?></strong> <?php esc_html_e('The settings below allow you to configure which of these authentication code providers are enabled for your user.', 'it-l10n-ithemes-security-pro' ); ?></p>

		<table class="two-factor-methods-table widefat wp-list-table striped">
			<thead>
				<tr>
					<th scope="col" class="manage-column column-primary column-method"><?php esc_html_e( 'Provider', 'it-l10n-ithemes-security-pro' ); ?></th>
					<th scope="col" class="manage-column column-enable"><?php esc_html_e( 'Enabled', 'it-l10n-ithemes-security-pro' ); ?></th>
					<th scope="col" class="manage-column column-make-primary"><?php esc_html_e( 'Primary', 'it-l10n-ithemes-security-pro' ); ?></th>
				</tr>
			</thead>
			<tbody id="the-list">
			<?php foreach ( $this->helper->get_enabled_provider_instances() as $class => $object ) : ?>
				<tr>
					<td class="column-method column-primary" style="width:60%;vertical-align:top;">
						<strong><?php $object->print_label(); ?></strong>
						<?php do_action( 'two-factor-user-options-' . $class, $user ); ?>
						<button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button>
					</td>
					<td class="column-enable" style="width:20%;vertical-align:top;">
						<input type="checkbox" name="<?php echo esc_attr( $this->_enabled_providers_user_meta_key ); ?>[]" id="<?php echo esc_attr( $this->_enabled_providers_user_meta_key . '-' . $class ); ?>" value="<?php echo esc_attr( $class ); ?>" <?php checked( in_array( $class, $enabled_providers ) ); ?> />
						<label for="<?php echo esc_attr( $this->_enabled_providers_user_meta_key . '-' . $class ); ?>">
							<?php esc_html_e( 'Enable', 'it-l10n-ithemes-security-pro' )  ?>
							<?php
							if ( $object->recommended ) {
								echo ' <strong>' . __( '(recommended)', 'it-l10n-ithemes-security-pro' ) . '</strong>';
							}
							?>
						</label>
					</td>
					<td class="column-make-primary" style="width:20%;vertical-align:top;">
						<input type="radio" name="<?php echo esc_attr( $this->_provider_user_meta_key ); ?>" value="<?php echo esc_attr( $class ); ?>" id="<?php echo esc_attr( $this->_provider_user_meta_key . '-' . $class ); ?>" <?php checked( $class, $primary_provider ); ?> />
						<label for="<?php echo esc_attr( $this->_provider_user_meta_key . '-' . $class ); ?>">
							<?php esc_html_e( 'Make Primary', 'it-l10n-ithemes-security-pro' )  ?>
							<?php
							if ( $object->recommended ) {
								echo ' <strong>' . __( '(recommended)', 'it-l10n-ithemes-security-pro' ) . '</strong>';
							}
							?>
						</label>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
			<tfoot>
				<tr>
					<th scope="col" class="manage-column column-primary column-method"><?php esc_html_e( 'Method', 'it-l10n-ithemes-security-pro' ); ?></th>
					<th scope="col" class="manage-column column-enable"><?php esc_html_e( 'Enabled', 'it-l10n-ithemes-security-pro' ); ?></th>
					<th scope="col" class="manage-column column-make-primary"><?php esc_html_e( 'Primary', 'it-l10n-ithemes-security-pro' ); ?></th>
				</tr>
			</tfoot>
		</table>
		<?php
		/**
		 * Fires after the Two Factor methods table.
		 *
		 * To be used by Two Factor methods to add settings UI.
		 */
		do_action( 'show_user_security_settings', $user );
	}

	/**
	 * Update the user meta value.
	 *
	 * This executes during the `personal_options_update` & `edit_user_profile_update` actions.
	 *
	 * @param int $user_id User ID.
	 */
	public function user_two_factor_options_update( $user_id ) {
		$this->load_helper();

		if ( isset( $_POST['_nonce_user_two_factor_options'] ) ) {
			check_admin_referer( 'user_two_factor_options', '_nonce_user_two_factor_options' );
			$providers         = $this->helper->get_enabled_provider_instances();
			// If there are no providers enabled for the site, then let's not worry about this.
			if ( empty( $providers ) ) {
				return;
			}

			$enabled_providers = isset( $_POST[ $this->_enabled_providers_user_meta_key ] )? $_POST[$this->_enabled_providers_user_meta_key] : array();
			$this->set_enabled_providers_for_user( $enabled_providers, $user_id );

			// Whitelist the new values to only the available classes and empty.
			$primary_provider = isset( $_POST[ $this->_provider_user_meta_key ] )? $_POST[ $this->_provider_user_meta_key ]:'';
			$this->set_primary_provider_for_user( $primary_provider, $user_id );
		}
	}

	/**
	 * Update the list of enabled Two Factor providers for a user.
	 *
	 * @param array    $enabled_providers
	 * @param int|null $user_id
	 */
	public function set_enabled_providers_for_user( $enabled_providers, $user_id = null ) {
		$this->load_helper();

		$providers = $this->helper->get_enabled_providers();
		// If there are no providers enabled for the site, then let's not worry about this.
		if ( empty( $providers ) ) {
			return;
		}
		if ( ! isset( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		if ( ! is_array( $enabled_providers ) ) {
			// Make sure enabled providers is an array
			$enabled_providers = array();
		} else {
			// Only site-enabled providers can be enabled for a user
			$enabled_providers = array_intersect( $enabled_providers, array_keys( $providers ) );
		}
		update_user_meta( $user_id, $this->_enabled_providers_user_meta_key, $enabled_providers );
	}

	/**
	 * Set the primary provider for a user.
	 *
	 * @param string   $primary_provider
	 * @param int|null $user_id
	 */
	public function set_primary_provider_for_user( $primary_provider, $user_id = null ) {
		$this->load_helper();

		$providers = $this->helper->get_enabled_providers();
		// If there are no providers enabled for the site, then let's not worry about this.
		if ( empty( $providers ) ) {
			return;
		}
		if ( ! isset( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		if ( empty( $primary_provider ) || array_key_exists( $primary_provider, $providers ) ) {
			update_user_meta( $user_id, $this->_provider_user_meta_key, $primary_provider );
		}
	}

	/**
	 * Get all Two-Factor Auth providers that are enabled for the specified|current user.
	 *
	 * @param WP_User $user WP_User object of the logged-in user.
	 *
	 * @return array
	 */
	public function get_enabled_providers_for_user( $user = null ) {
		$this->load_helper();

		if ( empty( $user ) || ! is_a( $user, 'WP_User' ) ) {
			$user = wp_get_current_user();
		}

		$providers         = $this->helper->get_enabled_provider_instances();
		$enabled_providers = get_user_meta( $user->ID, $this->_enabled_providers_user_meta_key, true );
		if ( empty( $enabled_providers ) ) {
			$enabled_providers = array();
		}
		$enabled_providers = array_intersect( $enabled_providers, array_keys( $providers ) );

		return $enabled_providers;
	}

	/**
	 * Get all Two-Factor Auth providers that are both enabled and configured for the specified|current user.
	 *
	 * @param WP_User $user WP_User object of the logged-in user.
	 * @param bool    $add_enforced Whether to add in the email provider if 2fa is enforced for the user's account.
	 *
	 * @return Two_Factor_Provider[]
	 */
	public function get_available_providers_for_user( $user = null, $add_enforced = true ) {
		$this->load_helper();

		if ( empty( $user ) || ! is_a( $user, 'WP_User' ) ) {
			$user = wp_get_current_user();
		}

		if ( ! ( $user instanceof WP_User ) ) {
			return array();
		}

		$providers            = $this->helper->get_enabled_provider_instances();
		$enabled_providers    = $this->get_enabled_providers_for_user( $user );
		$configured_providers = array();

		foreach ( $providers as $classname => $provider ) {
			if ( in_array( $classname, $enabled_providers ) && $provider->is_available_for_user( $user ) ) {
				$configured_providers[ $classname ] = $provider;
			}
		}

		if ( $add_enforced && ! isset( $configured_providers['Two_Factor_Email'] ) && isset( $providers['Two_Factor_Email'] ) && $this->user_requires_two_factor( $user->ID ) ) {
			$configured_providers['Two_Factor_Email'] = $providers['Two_Factor_Email'];
		}

		return $configured_providers;
	}

	/**
	 * Get the reason that two factor is required for a given user.
	 *
	 * 'user_type' - Required because all users are required, their role requires it, or they are a privileged user.
	 * 'vulnerable_users' - Requried because they have a weak password.
	 * 'vulnerable_site' - Required because the site is running outdated versions of plugins.
	 *
	 * @param int|null $user_id
	 *
	 * @return string|false
	 */
	public function get_two_factor_requirement_reason( $user_id = null ) {
		$this->load_helper();

		if ( empty( $user_id ) || ! is_numeric( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		$providers = $this->helper->get_enabled_provider_instances();

		if ( ! isset( $providers['Two_Factor_Email'] ) ) {
			// Two-factor can't be a requirement if the Email method is not available.
			return false;
		}

		$user = get_userdata( $user_id );

		if ( ! ( $user instanceof WP_User ) ) {
			return false;
		}

		$settings = ITSEC_Modules::get_settings( 'two-factor' );

		if ( 'all_users' === $settings['protect_user_type'] ) {
			return 'user_type';
		} else if ( 'privileged_users' === $settings['protect_user_type'] ) {
			require_once( ITSEC_Core::get_core_dir() . '/lib/class-itsec-lib-canonical-roles.php' );

			if ( ITSEC_Lib_Canonical_Roles::is_user_at_least( 'contributor', $user ) ) {
				return 'user_type';
			}
		} else if ( 'custom' === $settings['protect_user_type'] ) {
			if ( is_object( $user ) && isset( $user->roles ) && is_array( $user->roles ) ) {
				$shared_roles = array_intersect( $settings['protect_user_type_roles'], $user->roles );
			}

			if ( ! empty( $shared_roles ) ) {
				return 'user_type';
			}
		}

		if ( $settings['protect_vulnerable_users'] && ! $this->is_user_excluded( $user ) ) {
			$password_strength = get_user_meta( $user_id, 'itsec-password-strength', true );

			if ( ( is_string( $password_strength ) || is_int( $password_strength ) ) && $password_strength >= 0 && $password_strength <= 2 ) {
				return 'vulnerable_users';
			}
		}

		if ( $settings['protect_vulnerable_site'] && ITSEC_Modules::is_active( 'version-management' ) && ! $this->is_user_excluded( $user ) ) {
			$version_management_settings = ITSEC_Modules::get_settings( 'version-management' );

			if ( $version_management_settings['is_software_outdated'] ) {
				return 'vulnerable_site';
			}
		}
	}

	/**
	 * Is the user excluded from Two-Factor authentication.
	 *
	 * @param int|WP_User|string $user
	 *
	 * @return bool
	 */
	public function is_user_excluded( $user ) {

		if ( 'custom' !== ITSEC_Modules::get_setting( 'two-factor', 'exclude_type' ) ) {
			return false;
		}

		$roles = ITSEC_Modules::get_setting( 'two-factor', 'exclude_roles' );

		if ( ! $roles ) {
			return false;
		}

		$user = ITSEC_Lib::get_user( $user );

		foreach ( $user->roles as $role ) {
			if ( in_array( $role, $roles, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get a description for the reason Two Factor is required.
	 *
	 * @param string $reason
	 *
	 * @return string
	 */
	public function get_reason_description( $reason ) {
		if ( 'user_type' === $reason ) {
			return esc_html__( 'Your user requires two-factor in order to log in.', 'it-l10n-ithemes-security-pro' );
		} else if ( 'vulnerable_users' === $reason ) {
			return esc_html__( 'The site requires any user with a weak password to use two-factor in order to log in.', 'it-l10n-ithemes-security-pro' );
		} else if ( 'vulnerable_site' === $reason ) {
			return esc_html__( 'This site requires two-factor in order to log in.', 'it-l10n-ithemes-security-pro' );
		} else {
			return '';
		}
	}

	/**
	 * Does the given user require Two Factor to be enabled.
	 *
	 * @param int|null $user_id
	 *
	 * @return bool
	 */
	public function user_requires_two_factor( $user_id = null ) {
		$reason = $this->get_two_factor_requirement_reason( $user_id );

		if ( empty( $reason ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Gets the Two-Factor Auth provider for the specified|current user.
	 *
	 * @param int $user_id Optional. User ID. Default is 'null'.
	 *
	 * @return Two_Factor_Provider|null
	 */
	public function get_primary_provider_for_user( $user_id = null ) {
		$this->load_helper();

		if ( empty( $user_id ) || ! is_numeric( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		$providers      = $this->helper->get_enabled_provider_instances();
		$user_providers = $this->get_available_providers_for_user( get_userdata( $user_id ) );

		if ( empty( $user_providers ) ) {
			return null;
		} else if ( 1 === count( $user_providers ) ) {
			$provider = key( $user_providers );
		} else {
			$provider = get_user_meta( $user_id, $this->_provider_user_meta_key, true );

			// If the provider specified isn't enabled, just grab the first one that is.
			if ( ! isset( $user_providers[ $provider ] ) ) {
				$provider = key( $user_providers );
			}
		}

		/**
		 * Filter the two-factor authentication provider used for this user.
		 *
		 * @param string $provider The provider currently being used.
		 * @param int    $user_id  The user ID.
		 */
		$provider = apply_filters( 'two_factor_primary_provider_for_user', $provider, $user_id );

		if ( isset( $providers[ $provider ] ) ) {
			return $providers[ $provider ];
		}

		return null;
	}

	/**
	 * Quick boolean check for whether a given user is using two-step.
	 *
	 * @param int $user_id Optional. User ID. Default is 'null'.
	 *
	 * @return bool|null True if they are using it. False if not using it. Null if disabled site-wide.
	 */
	public function is_user_using_two_factor( $user_id = null ) {
		if ( defined( 'ITSEC_DISABLE_TWO_FACTOR' ) && ITSEC_DISABLE_TWO_FACTOR ) {
			return;
		}

		$provider = $this->get_primary_provider_for_user( $user_id );
		return ! empty( $provider );
	}

	/**
	 * Determine if a Sync Two-Factor override is active.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return bool True if the override is active. False otherwise.
	 */
	public function is_sync_override_active( $user_id ) {
		$sync_override = intval( get_user_option( 'itsec_two_factor_override', $user_id ) );

		if ( 1 !== $sync_override ) {
			return false;
		}

		$override_expires = intval( get_user_option( 'itsec_two_factor_override_expires', $user_id ) );

		if ( current_time( 'timestamp' ) > $override_expires ) {
			return false;
		}

		$post_data = $_POST;
		ITSEC_Log::add_debug( 'two_factor', "sync_override::$user_id", compact( 'user_id', 'sync_override', 'override_expires', 'post_data' ), compact( 'user_id' ) );

		return true;
	}

	/**
	 * Register the 2fa interstitial.
	 *
	 * @param ITSEC_Lib_Login_Interstitial $lib
	 */
	public function register_interstitial( $lib ) {
		require_once( dirname( __FILE__ ) . '/class-itsec-two-factor-interstitial.php' );
		require_once( dirname( __FILE__ ) . '/class-itsec-two-factor-on-board.php' );

		$interstitial = new ITSEC_Two_Factor_Interstitial( $this );
		$interstitial->run();
		$lib->register( '2fa', $interstitial );
		$lib->register( '2fa-on-board', new ITSEC_Two_Factor_On_Board( $this ) );
	}

	/**
	 * Set the remember 2fa cookie.
	 *
	 * @param WP_User $user
	 *
	 * @return bool
	 */
	public function set_remember_cookie( $user ) {

		if ( ! $token = ITSEC_Lib::generate_token() ) {
			return false;
		}

		if ( ! $hashed = ITSEC_Lib::hash_token( $token ) ) {
			return false;
		}

		$expires = ITSEC_Core::get_current_time_gmt() + MONTH_IN_SECONDS;

		if ( ! add_user_meta( $user->ID, self::REMEMBER_META_KEY, $data = compact( 'hashed', 'expires' ) ) ) {
			return false;
		}

		ITSEC_Log::add_debug( 'two_factor', 'remember_generated', $data, array( 'user_id' => $user->ID ) );

		return setcookie( self::REMEMBER_COOKIE, $token, $expires, ITSEC_Lib::get_home_root(), COOKIE_DOMAIN, is_ssl(), true );
	}

	/**
	 * Clear the remember 2fa cookie.
	 *
	 * @return bool
	 */
	public function clear_remember_cookie() {
		return setcookie( self::REMEMBER_COOKIE, ' ', ITSEC_Core::get_current_time_gmt() - YEAR_IN_SECONDS, ITSEC_Lib::get_home_root(), COOKIE_DOMAIN, is_ssl(), true );
	}

	/**
	 * Is the user allowed to remember 2fa.
	 *
	 * @param WP_User $user
	 *
	 * @return bool
	 */
	public function is_remember_allowed( $user ) {

		if ( ! ITSEC_Modules::is_active( 'fingerprinting' ) ) {
			return false;
		}

		$remember = ITSEC_Modules::get_setting( 'two-factor', 'allow_remember' );

		switch ( $remember ) {
			case 'non-privileged':
				require_once( ITSEC_Core::get_core_dir() . '/lib/class-itsec-lib-canonical-roles.php' );

				return ! ITSEC_Lib_Canonical_Roles::is_user_at_least( 'contributor', $user );
			case 'custom':
				$roles = ITSEC_Modules::get_setting( 'two-factor', 'allow_remember_roles' );

				foreach ( $user->roles as $role ) {
					if ( in_array( $role, $roles, true ) ) {
						return true;
					}
				}

				return false;
			case 'all':
				return true;
			case 'none':
			default:
				return false;
		}
	}

	/**
	 * When a user's password is updated, clear any remember me meta keys.
	 *
	 * @param int $meta_id
	 * @param int $user_id
	 * @param string $meta_key
	 */
	public function clear_remember_on_password_change( $meta_id, $user_id, $meta_key ) {
		if ( 'itsec_last_password_change' === $meta_key ) {
			delete_user_meta( $user_id, self::REMEMBER_META_KEY );
		}
	}

	/**
	 * Admin notice telling users that it's recommended to set up two factor
	 */
	public function recommend_2fa_dashboard_notice() {
		$activate_link = apply_filters( 'itsec-two-factor-notice-active-link', get_edit_user_link() . '#two-factor-user-options' );
		echo  '<div class="updated itsec-notice itsec-two-factor-notice"><span class="it-icon-itsec"></span>'
		    . __( 'Two Factor Authentication has been enabled for this site. It is highly recommended that you take advantage of this feature to secure your user login &amp; password.', 'it-l10n-ithemes-security-pro' )
		    . '<p><a class="itsec-notice-button" href="' . esc_url( $activate_link ) . '">' . __( 'Activate Two-Factor Authentication', 'it-l10n-ithemes-security-pro' ) . '</a>'
		    . '<button class="itsec-notice-button itsec-notice-hide" data-nonce="' . wp_create_nonce( 'dismiss-2fa-recommended-dashboard-notice-remind-again' ) . '" data-source="2fa-recommended-remind-again">' . __( 'Remind Me Later', 'it-l10n-ithemes-security-pro' ) . '</button></p>'
		    . '<button class="itsec-notice-hide" data-nonce="' . wp_create_nonce( 'dismiss-2fa-recommended-dashboard-notice' ) . '" data-source="2fa-recommended">&times;</button>'
		    . '</div>';
	}

	/**
	 * Enqueue the css/profile-page.css file.
	 */
	public function add_profile_page_styling() {
		wp_enqueue_style( 'itsec-two-factor-profile-page', plugins_url( 'css/profile-page.css', __FILE__ ), array(), ITSEC_Core::get_plugin_build() );

		$this->load_helper();
		$this->helper->get_enabled_provider_instances();
	}


	/**
	 * Register the Two Factor Email method notification.
	 *
	 * @param array $notifications
	 *
	 * @return array
	 */
	public function register_notifications( $notifications ) {

		$notifications['two-factor-email'] = array(
			'slug'             => 'two-factor-email',
			'schedule'         => ITSEC_Notification_Center::S_NONE,
			'recipient'        => ITSEC_Notification_Center::R_USER,
			'subject_editable' => true,
			'message_editable' => true,
			'tags'             => array( 'username', 'display_name', 'site_title' ),
			'module'           => 'two-factor',
		);

		$notifications['two-factor-confirm-email'] = array(
			'slug'             => 'two-factor-confirm-email',
			'schedule'         => ITSEC_Notification_Center::S_NONE,
			'recipient'        => ITSEC_Notification_Center::R_USER,
			'subject_editable' => true,
			'message_editable' => true,
			'tags'             => array( 'username', 'display_name', 'site_title' ),
			'module'           => 'two-factor',
			'optional'         => true,
		);

		return $notifications;
	}

	/**
	 * Provide translated strings for the Two Factor Email method notification.
	 *
	 * @return array
	 */
	public function two_factor_email_method_strings() {
		/* translators: Do not translate the curly brackets or their contents, those are placeholders. */
		$message = esc_html__( 'Hi {{ $display_name }},

<b>Click the button to continue</b> or manually enter the authentication code below to finish logging in.', 'it-l10n-ithemes-security-pro' );

		return array(
			'label'       => esc_html__( 'Two-Factor Email', 'it-l10n-ithemes-security-pro' ),
			'description' => sprintf( esc_html__( 'The %1$sTwo-Factor Authentication%2$s module sends an email containing the Authentication Code for users using email as their two-factor provider.', 'it-l10n-ithemes-security-pro' ), '<a href="#" data-module-link="two-factor">', '</a>' ),
			'subject'     => esc_html__( 'Login Authentication Code', 'it-l10n-ithemes-security-pro' ),
			'message'     => $message,
			'tags'        => array(
				'username'     => esc_html__( "The recipient's WordPress username.", 'it-l10n-ithemes-security-pro' ),
				'display_name' => esc_html__( "The recipient's WordPress display name.", 'it-l10n-ithemes-security-pro' ),
				'site_title'   => esc_html__( 'The WordPress Site Title. Can be changed under Settings -> General -> Site Title', 'it-l10n-ithemes-security-pro' ),
			)
		);
	}

	/**
	 * Provide translated strings for the Two Factor Confirm Email method notification.
	 *
	 * @return array
	 */
	public function two_factor_confirm_email_method_strings() {
		/* translators: Do not translate the curly brackets or their contents, those are placeholders. */
		$message = esc_html__( 'Hi {{ $display_name }},

<b>Click the button to continue</b> or manually enter the authentication code below to finish setting up Two-Factor.', 'it-l10n-ithemes-security-pro' );

		$desc = sprintf(
			esc_html__( 'The %1$sTwo-Factor Authentication%2$s module sends an email containing the Authentication Code for users when they are setting up Two-Factor. Try to keep the email similar to the Two Factor Email.', 'it-l10n-ithemes-security-pro' ),
			'<a href="#" data-module-link="two-factor">', '</a>'
		);
		$desc .= ' ' . esc_html__( 'Disabling this email will disable the Two-Factor Email Confirmation flow.', 'it-l10n-ithemes-security-pro' );

		return array(
			'label'       => esc_html__( 'Two-Factor Email Confirmation', 'it-l10n-ithemes-security-pro' ),
			'description' => $desc,
			'subject'     => esc_html__( 'Login Authentication Code', 'it-l10n-ithemes-security-pro' ),
			'message'     => $message,
			'tags'        => array(
				'username'     => esc_html__( "The recipient's WordPress username.", 'it-l10n-ithemes-security-pro' ),
				'display_name' => esc_html__( "The recipient's WordPress display name.", 'it-l10n-ithemes-security-pro' ),
				'site_title'   => esc_html__( 'The WordPress Site Title. Can be changed under Settings -> General -> Site Title', 'it-l10n-ithemes-security-pro' ),
			)
		);
	}

	public function get_helper() {
		return $this->helper;
	}
}
