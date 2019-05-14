<?php
/**
 * Class for creating a Time-Based One-Time Password provider.
 *
 * @package Two_Factor
 */

/**
 * Class Two_Factor_Totp
 */
class Two_Factor_Totp extends Two_Factor_Provider implements ITSEC_Two_Factor_Provider_On_Boardable {

	public $recommended = true;

	/**
	 * The user meta token key.
	 * @var string
	 */
	const SECRET_META_KEY = '_two_factor_totp_key';

	/**
	 * The user meta token key.
	 * @var string
	 */
	const NOTICES_META_KEY = '_two_factor_totp_notices';

	const DEFAULT_KEY_BIT_SIZE = 160;
	const DEFAULT_CRYPTO = 'sha1';
	const DEFAULT_DIGIT_COUNT = 6;
	const DEFAULT_TIME_STEP_SEC = 30;
	const DEFAULT_TIME_STEP_ALLOWANCE = 4;
	private $_base_32_chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

	private $dashboard_config_notices = array();

	/**
	 * Class constructor. Sets up hooks, etc.
	 */
	protected function __construct() {
		add_action( 'admin_enqueue_scripts',                 array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_two-factor-totp-get-code',      array( $this, 'ajax_new_code' ) );
		add_action( 'wp_ajax_two-factor-totp-verify-code',   array( $this, 'ajax_verify_code' ) );
		add_action( 'two-factor-user-options-' . __CLASS__,  array( $this, 'user_two_factor_options' ) );
		add_action( 'two-factor-admin-options-' . __CLASS__, array( $this, 'description' ) );
		add_action( 'personal_options_update',               array( $this, 'user_two_factor_options_update' ), 11 );
		add_action( 'edit_user_profile_update',              array( $this, 'user_two_factor_options_update' ), 11 );

		return parent::__construct();
	}

	/**
	 * Ensures only one instance of this class exists in memory at any one time.
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
	 * Returns the name of the provider.
	 */
	public function get_label() {
		return _x( 'Mobile App', 'Provider Label', 'it-l10n-ithemes-security-pro' );
	}

	/**
	 * Enqueue assets.
	 *
	 * @since 0.1-dev
	 *
	 * @access public
	 *
	 * @param string $hook Current page.
	 */
	public function enqueue_assets( $hook ) {
		if ( ! in_array( $hook, array( 'user-edit.php', 'profile.php' ) ) ) {
			return;
		}

		wp_enqueue_script( 'two-factor-totp-admin', plugins_url( 'js/totp-admin.js', __FILE__ ), array( 'jquery' ), null, true );
	}

	/**
	 * Display TOTP options on the user settings page.
	 *
	 * @param WP_User $user The current user being edited.
	 */
	public function user_two_factor_options( $user ) {
		if ( ! is_admin() ) {
			$this->enqueue_assets( '' );
		}
		wp_nonce_field( 'user_two_factor_totp_options', '_nonce_user_two_factor_totp_options', false );
		$totp_key = $this->get_key( $user );
		$this->admin_notices();
		?>
		<p></p>
		<a class="button button-secondary" href="javascript:;" onclick="jQuery('#two-factor-totp-options').toggle();"><?php esc_html_e( 'View QR Code &amp; Secret Key', 'it-l10n-ithemes-security-pro' ); ?></a>
		<div id="two-factor-totp-options" style="display:none;">
			<?php
			if ( $totp_key->already_active ) {
				?>
				<p class="success"><?php esc_html_e( 'This is already successfully enabled. To add another device, rescan this code. You can also use the "Generate new secret" button to generate a new secret to use. Successfully verifying a code with a new secret will invalidate all codes generated with the old one.', 'it-l10n-ithemes-security-pro' ); ?></p>
				<?php
			}
			$site_name = get_bloginfo( 'name', 'display' );
			?>
			<p><?php esc_html_e( 'To generate Time-Based One-Time Password codes, you need to install and configure an app on your mobile device:', 'it-l10n-ithemes-security-pro' ); ?></p>
			<p><?php printf( __( 'For Android devices, the <a href="%1$s">Authy</a>, <a href="%2$s">Google Authenticator</a>, <a href="%3$s">FreeOTP Authenticator</a>, or <a href="%4$s">Toopher</a> apps are the most popular token generators.', 'it-l10n-ithemes-security-pro' ), esc_url( 'https://play.google.com/store/apps/details?id=com.authy.authy&hl=en' ), esc_url( 'https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2&hl=en' ), esc_url( 'https://play.google.com/store/apps/details?id=org.fedorahosted.freeotp' ), esc_url( 'https://play.google.com/store/apps/details?id=com.toopher.android&hl=en' ) ); ?></p>
			<p><?php printf( __( 'For iOS devices, the <a href="%1$s">Authy</a>, <a href="%2$s">Google Authenticator</a>, <a href="%3$s">FreeOTP Authenticator</a>, or <a href="%4$s">Toopher</a> apps are the most popular token generators.', 'it-l10n-ithemes-security-pro' ), esc_url( 'https://itunes.apple.com/us/app/authy/id494168017?mt=8' ), esc_url( 'https://itunes.apple.com/us/app/google-authenticator/id388497605?mt=8' ), esc_url( 'https://itunes.apple.com/us/app/freeotp-authenticator/id872559395?mt=8' ), esc_url( 'https://itunes.apple.com/us/app/toopher/id562592093?mt=8' ) ); ?></p>
			<img src="<?php echo esc_attr( $this->get_google_qr_code( $site_name . ':' . $user->user_login, $totp_key->key, $site_name ) ); ?>" id="two-factor-totp-qrcode" width="200" height="200" />
			<p><?php printf( _x( 'Secret: %s', 'display time-based one-time password secret', 'it-l10n-ithemes-security-pro' ), '<code><strong id="two-factor-totp-key-text">' . esc_html( $totp_key->key ) . '</strong></code>' ); ?></p>
			<div id="two-factor-totp-verify-code"<?php if ( $totp_key->already_active ) { echo ' style="display: none;"'; } ?>>
				<p><?php esc_html_e( 'Please scan the QR code or manually enter the secret, then enter an authentication code from your app in order to complete setup', 'it-l10n-ithemes-security-pro' ); ?></p>
				<label for="two-factor-totp-authcode"><?php esc_html_e( 'Authentication Code:', 'it-l10n-ithemes-security-pro' ); ?></label>
				<input type="hidden" name="two-factor-totp-key" id="two-factor-totp-key" value="<?php echo esc_attr( $totp_key->key ) ?>" />
				<input type="tel" name="two-factor-totp-authcode" id="two-factor-totp-authcode" autocomplete="off" class="input" value="" size="20" pattern="[0-9]*" />
				<button id="two-factor-totp-verify-authcode" class="button button-two-factor-totp-verify-code button-secondary hide-if-no-js"><?php esc_html_e( 'Verify', 'it-l10n-ithemes-security-pro' ); ?></button>
			</div>
			<button id="two-factor-totp-new-secret" class="button button-two-factor-totp-new-secret button-secondary hide-if-no-js"><?php esc_html_e( 'Generate new secret', 'it-l10n-ithemes-security-pro' ); ?></button>
		</div>
		<?php
	}

	/**
	 * Display TOTP options on the user settings page.
	 *
	 * @param WP_User $user The current user being edited.
	 *
	 * @return object {
	 *     @type bool   $already_active  Whether a key already existed
	 *     @type string $key             The TOTP key
	 * }
	 */
	public function get_key( $user = null ) {
		if ( ! isset( $user ) ) {
			$user = wp_get_current_user();
		}
		$return = new stdClass();
		$return->already_active = true;
		$return->key = get_user_meta( $user->ID, self::SECRET_META_KEY, true );

		if ( empty( $return->key ) ) {
			$return->key = $this->generate_key();
			$return->already_active = false;
		}
		return $return;
	}

	/**
	 * Save the options specified in `::user_two_factor_options()`
	 *
	 * @param integer $user_id The user ID whose options are being updated.
	 */
	public function user_two_factor_options_update( $user_id ) {
		if ( isset( $_POST['_nonce_user_two_factor_totp_options'] ) ) {
			check_admin_referer( 'user_two_factor_totp_options', '_nonce_user_two_factor_totp_options' );

			// If there is no authcode provided
			if ( empty( $_POST['two-factor-totp-authcode'] ) ) {
				$two_factor_core = ITSEC_Two_Factor::get_instance();
				// Check to see if TOTP is enabled, and if not then go no further
				if ( ! in_array( 'Two_Factor_Totp', $two_factor_core->get_enabled_providers_for_user() ) ) {
					return;
				}
			}

			$current_key = get_user_meta( $user_id, self::SECRET_META_KEY, true );
			// If the key hasn't changed or is invalid, do nothing.
			if ( $current_key === $_POST['two-factor-totp-key'] || ! preg_match( '/^[' . $this->_base_32_chars . ']+$/', $_POST['two-factor-totp-key'] ) ) {
				return;
			}

			$notices = array();

			if ( empty( $_POST['two-factor-totp-authcode'] ) ) {
				$notices['error'][] = __( 'Two-Factor Authentication not activated, you must specify authcode to ensure it is properly set up. Please re-scan the QR code and enter the code provided by your application.', 'it-l10n-ithemes-security-pro' );
			} else {
				if ( $this->_is_valid_authcode( $_POST['two-factor-totp-key'], $_POST['two-factor-totp-authcode'] ) ) {
					if ( ! update_user_meta( $user_id, self::SECRET_META_KEY, $_POST['two-factor-totp-key'] ) ) {
						$notices['error'][] = __( 'Unable to save Two-Factor Authentication code. Please re-scan the QR code and enter the code provided by your application.', 'it-l10n-ithemes-security-pro' );
					}
				} else {
					$notices['error'][] = __( 'Two-Factor Authentication not activated, the authentication code you entered was not valid. Please re-scan the QR code and enter the code provided by your application.', 'it-l10n-ithemes-security-pro' );
				}
			}

			if ( ! empty( $notices ) ) {
				update_user_meta( $user_id, self::NOTICES_META_KEY, $notices );
			}
		}
	}

	public function ajax_new_code() {
		check_ajax_referer( 'user_two_factor_totp_options', '_nonce_user_two_factor_totp_options' );
		$site_name = get_bloginfo( 'name', 'display' );
		$return = array();
		$return['key'] = $this->generate_key();
		$return['qrcode_url'] = $this->get_google_qr_code( $site_name . ':' . $_POST['user_login'], $return['key'], $site_name );
		wp_send_json_success( $return );
	}

	public function ajax_verify_code() {
		check_ajax_referer( 'user_two_factor_totp_options', '_nonce_user_two_factor_totp_options' );
		if ( ! current_user_can( 'edit_user', $_POST['user_id'] ) ) {
			wp_send_json_error( __('You do not have permission to edit this user.', 'it-l10n-ithemes-security-pro') );
		}

		if ( $this->_is_valid_authcode( $_POST['key'], $_POST['authcode'] ) ) {
			if ( ! update_user_meta( $_POST['user_id'], self::SECRET_META_KEY, $_POST['key'] ) ) {
				wp_send_json_error( __( 'Unable to save two-factor secret.', 'it-l10n-ithemes-security-pro' ) );
			}
			wp_send_json_success( __( 'Success!', 'it-l10n-ithemes-security-pro' ) );
		} else {
			wp_send_json_error( __('The code you supplied is not valid.', 'it-l10n-ithemes-security-pro') );
		}

		$site_name = get_bloginfo( 'name', 'display' );
		$return = array();
		$return['key'] = $this->generate_key();
		$return['qrcode_url'] = $this->get_google_qr_code( $site_name . ':' . $_POST['user_login'], $return['key'], $site_name );
		wp_send_json( $return );
	}

	/**
	 * Display any available admin notices.
	 *
	 * @param array $notices Keys are CSS class to use, values are an array of messages.
	 */
	public function admin_notices( $notices = null ) {
		if ( ! isset( $notices ) ) {
			$notices = get_user_meta( get_current_user_id(), self::NOTICES_META_KEY, true );
			delete_user_meta( get_current_user_id(), self::NOTICES_META_KEY );
		}

		if ( ! empty( $notices ) ) {
			foreach ( $notices as $class => $messages ) {
				?>
				<div class="<?php echo esc_attr( $class ) ?>">
					<?php
					foreach ( $messages as $msg ) {
						?>
						<p>
							<span><?php echo esc_html( $msg ); ?><span>
						</p>
						<?php
					}
					?>
				</div>
				<?php
			}
		}
	}

	/**
	 * Validates authentication.
	 *
	 * @param WP_User $user WP_User object of the logged-in user.
	 *
	 * @return bool Whether the user gave a valid code
	 */
	public function validate_authentication( $user ) {
		$key = get_user_meta( $user->ID, self::SECRET_META_KEY, true );
		return $this->_is_valid_authcode( $key, trim( $_REQUEST['authcode'] ) );
	}

	/**
	 * Checks if a given code is valid for a given key, allowing for a certain amount of time drift
	 *
	 * @param string $key      The share secret key to use.
	 * @param string $authcode The code to test.
	 *
	 * @return bool Whether the code is valid within the time frame
	 */
	private function _is_valid_authcode( $key, $authcode ) {
		/**
		 * Filter the maximum ticks to allow when checking valid codes.
		 *
		 * Ticks are the allowed offset from the correct time in 30 second increments,
		 * so the default of 4 allows codes that are two minutes to either side of server time
		 *
		 * @param int $max_ticks Max ticks of time correction to allow. Default 4.
		 */
		$max_ticks = apply_filters( 'two-factor-totp-time-step-allowance', self::DEFAULT_TIME_STEP_ALLOWANCE );

		// Array of all ticks to allow, sorted using absolute value to test closest match first.
		$ticks = range( - $max_ticks, $max_ticks );
		usort( $ticks, array( $this, 'abssort' ) );

		$time = time() / self::DEFAULT_TIME_STEP_SEC;

		foreach ( $ticks as $offset ) {
			$log_time = $time + $offset;
			if ( $this->calc_totp( $key, $log_time ) === $authcode ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Generates key
	 *
	 * @param int $bitsize Nume of bits to use for key.
	 *
	 * @return string $bitsize long string composed of available base32 chars.
	 */
	public function generate_key( $bitsize = self::DEFAULT_KEY_BIT_SIZE ) {
		$bytes = ceil( $bitsize / 8 );

		$secret = wp_generate_password( $bytes, true, true );

		return $this->base32_encode( $secret );
	}

	/**
	 * Returns data in base32 encoding.
	 *
	 * @param string $data Data to be converted into base32.
	 *
	 * @return string Data in base32 encoding without padding.
	 */
	public function base32_encode( $data ) {
		if ( empty( $data ) ) {
			return '';
		}

		$binary_string = '';

		foreach ( str_split( $data ) as $character ) {
			$binary_string .= str_pad( base_convert( ord( $character ), 10, 2 ), 8, '0', STR_PAD_LEFT );
		}

		$five_bit_sections = str_split( $binary_string, 5 );
		$base32 = '';

		foreach ( $five_bit_sections as $five_bit_section ) {
			$base32 .= $this->_base_32_chars[base_convert( str_pad( $five_bit_section, 5, '0' ), 2, 10 )];
		}

		return $base32;
	}

	/**
	 * Pack stuff
	 *
	 * @param string $value The value to be packed.
	 *
	 * @return string Binary packed string.
	 */
	private static function pack64( $value ) {
		// 64bit mode (PHP_INT_SIZE == 8)
		if ( PHP_INT_SIZE >= 8 ) {
			// We can use the new 64bit pack functionality if we're on PHP 5.6.3+
			if ( version_compare( PHP_VERSION, '5.6.3', '>=' ) && PHP_INT_SIZE >= 8 ) {
				return pack( 'J', $value );
			}
			$highmap = 0xffffffff << 32;
			$higher  = ( $value & $highmap ) >> 32;
		} else {
			// 32bit PHP can't shift 32 bits like that, so we have to assume 0 for the higher and not pack anything beyond it's limits
			$higher = 0;
		}
		$lowmap  = 0xffffffff;
		$lower   = $value & $lowmap;
		return pack( 'NN', $higher, $lower );
	}

	/**
	 * Calculate a valid code given the shared secret key
	 *
	 * @param string $key        The shared secret key to use for calculating code.
	 * @param mixed  $step_count The time step used to calculate the code, which is the floor of time() divided by step size.
	 * @param int    $digits     The number of digits in the returned code.
	 * @param string $hash       The hash used to calculate the code.
	 * @param int    $time_step  The size of the time step.
	 *
	 * @return string The totp code
	 */
	private function calc_totp( $key, $step_count = false, $digits = self::DEFAULT_DIGIT_COUNT, $hash = self::DEFAULT_CRYPTO, $time_step = self::DEFAULT_TIME_STEP_SEC ) {
		$secret = $this->base32_decode( $key );

		if ( false === $step_count ) {
			$step_count = floor( time() / $time_step );
		}

		$timestamp = $this->pack64( $step_count );

		$hash = hash_hmac( $hash, $timestamp, $secret, true );

		$offset = ord( $hash[19] ) & 0xf;

		$code = (
				( ( ord( $hash[ $offset + 0 ] ) & 0x7f ) << 24 ) |
				( ( ord( $hash[ $offset + 1 ] ) & 0xff ) << 16 ) |
				( ( ord( $hash[ $offset + 2 ] ) & 0xff ) << 8 ) |
				( ord( $hash[ $offset + 3 ] ) & 0xff )
			) % pow( 10, $digits );

		return str_pad( $code, $digits, '0', STR_PAD_LEFT );
	}

	/**
	 * Uses the Google Charts API to build a QR Code for use with an otpauth url
	 *
	 * @param string $name  The name to display in the Authentication app.
	 * @param string $key   The secret key to share with the Authentication app.
	 * @param string $title The title to display in the Authentication app.
	 * @param array  $opts Additional options.
	 *
	 * @return string A URL to use as an img src to display the QR code
	 */
	public function get_google_qr_code( $name, $key, $title = null, $opts = array() ) {
		// rawurlencode() $name and $title because iOS chokes otherwise
		$payload = urlencode( 'otpauth://totp/' . rawurlencode( $name ) . '?secret=' . $key );
		if ( isset( $title ) ) {
			$payload .= urlencode( '&issuer=' . rawurlencode( $title ) );
		}

		$size = isset( $opts['size'] ) ? absint( $opts['size'] ) : 200;

		$url = "https://qr-code.ithemes.com/?size={$size}&data={$payload}";

		/**
		 * Filter the image URL for the QR code.
		 *
		 * @param string $url     The image src attr URL.
		 * @param string $payload The payload to embed in the QR code.
		 * @param string $name    The name to display in the Authentication app.
		 * @param string $title   The title to display in the Authentication app.
		 * @param array  $opts    Additional options.
		 */
		$url = apply_filters( 'itsec_two_factor_qr_code_url', $url, $payload, $name, $key, $title, $opts );

		return $url;
	}

	/**
	 * Whether this Two-Factor provider is configured and available for the user specified.
	 *
	 * @param WP_User $user WP_User object of the logged-in user.
	 * @return boolean
	 */
	public function is_available_for_user( $user ) {
		// Only available if the secret key has been saved for the user.
		$key = get_user_meta( $user->ID, self::SECRET_META_KEY, true );

		return ! empty( $key );
	}

	/**
	 * Prints the form that prompts the user to authenticate.
	 *
	 * @param WP_User $user WP_User object of the logged-in user.
	 */
	public function authentication_page( $user ) {
		require_once( ABSPATH .  '/wp-admin/includes/template.php' );
		?>
		<p>
			<label for="authcode"><?php esc_html_e( 'Authentication Code:', 'it-l10n-ithemes-security-pro' ); ?></label>
			<input type="tel" name="authcode" id="authcode" class="input" value="" size="20" pattern="[0-9]*" />
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
		submit_button( __( 'Authenticate', 'it-l10n-ithemes-security-pro' ) );
	}

	/**
	 * Decode a base32 string and return a binary representation
	 *
	 * @param string $base32_string The base 32 string to decode.
	 *
	 * @throws Exception If string contains non-base32 characters.
	 *
	 * @return string Binary representation of decoded string
	 */
	public function base32_decode( $base32_string ) {

		$base32_string 	= strtoupper( $base32_string );

		if ( ! preg_match( '/^[' . $this->_base_32_chars . ']+$/', $base32_string, $match ) ) {
			throw new Exception( 'Invalid characters in the base32 string.' );
		}

		$l 	= strlen( $base32_string );
		$n	= 0;
		$j	= 0;
		$binary = '';

		for ( $i = 0; $i < $l; $i++ ) {

			$n = $n << 5; // Move buffer left by 5 to make room.
			$n = $n + strpos( $this->_base_32_chars, $base32_string[ $i ] ); 	// Add value into buffer.
			$j += 5; // Keep track of number of bits in buffer.

			if ( $j >= 8 ) {
				$j -= 8;
				$binary .= chr( ( $n & ( 0xFF << $j ) ) >> $j );
			}
		}

		return $binary;
	}

	/**
	 * Used with usort to sort an array by distance from 0
	 *
	 * @param int $a First array element.
	 * @param int $b Second array element.
	 *
	 * @return int -1, 0, or 1 as needed by usort
	 */
	private function abssort( $a, $b ) {
		$a = abs( $a );
		$b = abs( $b );
		if ( $a === $b ) {
			return 0;
		}
		return ($a < $b) ? -1 : 1;
	}

	public function description() {
		echo '<p class="description">' . sprintf( wp_kses( __( 'Use a two-factor mobile app such as <a href="%1$s">Authy</a> or Google Authenticator (<a href="%2$s">Android</a>, <a href="%3$s">iOS</a>). The mobile app generates a time-sensitive code that must be supplied when logging in.', 'it-l10n-ithemes-security-pro' ), array( 'a' => array( 'href' => array() ) ) ), esc_url( 'https://www.authy.com/app/mobile/' ), esc_url( 'https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2&hl=en' ), esc_url( 'https://itunes.apple.com/us/app/google-authenticator/id388497605?mt=8' ) ) . '</p>';
	}

	/**
	 * @inheritDoc
	 */
	public function get_on_board_dashicon() {
		return 'smartphone';
	}

	/**
	 * @inheritDoc
	 */
	public function get_on_board_label() {
		return esc_html__( 'Mobile App', 'it-l10n-ithemes-security-pro' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_on_board_description() {
		return esc_html__( 'Log in to WordPress using a mobile app like Authy or Google Authenticator.', 'it-l10n-ithemes-security-pro' );
	}

	/**
	 * @inheritDoc
	 */
	public function has_on_board_configuration() {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function get_on_board_config( WP_User $user ) {

		$key  = $this->get_key( $user );
		$blog = get_bloginfo( 'name', 'display' );

		return array(
			'secret' => $key->key,
			'qr'     => $this->get_google_qr_code( $blog . ':' . $user->user_login, $key->key, $blog, array( 'size' => 300 ) ),
		);
	}

	/**
	 * @inheritDoc
	 */
	public function handle_ajax_on_board( WP_User $user, array $data ) {
		if ( $data['itsec_method'] !== 'verify-totp-code' ) {
			return;
		}

		if ( ! isset( $data['itsec_totp_secret'], $data['itsec_totp_code'] ) ) {
			wp_send_json_error( array(
				'message' => esc_html__( 'Invalid Request Format', 'it-l10n-ithemes-security-pro' ),
			) );
		}

		$secret = $data['itsec_totp_secret'];

		if ( $this->_is_valid_authcode( $secret, $data['itsec_totp_code'] ) ) {
			if ( $secret !== get_user_meta( $user->ID, self::SECRET_META_KEY, true ) && ! update_user_meta( $user->ID, self::SECRET_META_KEY, $secret ) ) {
				wp_send_json_error( array(
					'message' => esc_html__( 'Unable to save two-factor secret.', 'it-l10n-ithemes-security-pro' ),
				) );
			}
			wp_send_json_success( array(
				'message' => esc_html__( 'Success!', 'it-l10n-ithemes-security-pro' ),
			) );
		} else {
			wp_send_json_error( array(
				'message' => esc_html__( 'The code you supplied is not valid.', 'it-l10n-ithemes-security-pro' ),
			) );
		}
	}
}
