<?php

class ITSEC_Recaptcha {
	const A_LOGIN = 'login';
	const A_REGISTER = 'register';
	const A_COMMENT = 'comment';

	private $settings;
	private $cookie_name;

	// Keep track of the number of recaptcha instances on the page
	private static $captcha_count = 0;


	public function run() {
		$this->cookie_name = 'itsec-recaptcha-opt-in-' . COOKIEHASH;

		// Run on init so that we can use is_user_logged_in()
		// Warning: BuddyPress has issues with using is_user_logged_in() on plugins_loaded
		add_action( 'init', array( $this, 'setup' ) );

		add_filter( 'itsec_lockout_modules', array( $this, 'register_lockout_module' ) );

		// Check for the opt-in and set the cookie.
		if ( isset( $_REQUEST['recaptcha-opt-in'] ) && 'true' === $_REQUEST['recaptcha-opt-in'] ) {
			setcookie( $this->cookie_name, 'true', time() + MONTH_IN_SECONDS, ITSEC_Lib::get_home_root(), COOKIE_DOMAIN, is_ssl(), true );
		}
	}

	public function setup() {
		$this->settings = ITSEC_Modules::get_settings( 'recaptcha' );

		if ( empty( $this->settings['site_key'] ) || empty( $this->settings['secret_key'] ) ) {
			// Only run when the settings are fully filled out.
			return;
		}

		ITSEC_Recaptcha_API::init( $this );

		if ( 'v3' === $this->settings['type'] && 'everywhere' === $this->settings['v3_include_location'] ) {
			add_action( 'wp_footer', array( $this, 'enqueue_everywhere' ), 19 );
		}

		// Logged in users are people, we don't need to re-verify
		if ( is_user_logged_in() ) {
			if ( is_multisite() ) {
				add_action( 'network_admin_notices', array( $this, 'show_last_error' ) );
			} else {
				add_action( 'admin_notices', array( $this, 'show_last_error' ) );
			}

			return;
		}

		add_action( 'login_head', array( $this, 'print_login_styles' ) );

		if ( $this->settings['comments'] ) {

			if ( version_compare( $GLOBALS['wp_version'], '4.2', '>=' ) ) {
				add_filter( 'comment_form_submit_button', array( $this, 'comment_form_submit_button' ) );
			} else {
				add_filter( 'comment_form_field_comment', array( $this, 'comment_form_field_comment' ) );
			}
			add_filter( 'preprocess_comment', array( $this, 'filter_preprocess_comment' ) );

		}

		if ( $this->settings['login'] ) {

			add_action( 'login_form', array( $this, 'login_form' ) );
			add_filter( 'login_form_middle', array( $this, 'wp_login_form' ), 100 );
			add_filter( 'authenticate', array( $this, 'filter_authenticate' ), 30 );

		}

		if ( $this->settings['register'] ) {

			add_action( 'register_form', array( $this, 'register_form' ) );
			add_filter( 'registration_errors', array( $this, 'registration_errors' ) );

		}

	}

	public function show_last_error() {
		if ( ! ITSEC_Core::current_user_can_manage() || $this->settings['validated'] || empty( $this->settings['last_error'] ) ) {
			return;
		}

		echo '<div class="error"><p><strong>';
		printf( wp_kses( __( 'The reCAPTCHA settings for iThemes Security are invalid. %1$s Bots will not be blocked until <a href="%2$s" data-module-link="recaptcha">the reCAPTCHA settings</a> are set properly.', 'it-l10n-ithemes-security-pro' ), array(
			'a' => array(
				'href'             => array(),
				'data-module-link' => array()
			)
		) ), esc_html( $this->settings['last_error'] ), ITSEC_Core::get_settings_module_url( 'recaptcha' ) );
		echo '</strong></p></div>';
	}

	public function enqueue_everywhere() {

		foreach ( wp_scripts()->registered as $handle => $dependency ) {
			if ( ! $dependency instanceof _WP_Dependency || ! $dependency->src ) {
				continue;
			}

			// Quick check
			if ( false === strpos( $dependency->src, 'google.com/recaptcha/api.js' ) ) {
				continue;
			}

			if ( ! $parsed = parse_url( $dependency->src ) ) {
				continue;
			}

			if ( ! isset( $parsed['host'] ) || ( 'www.google.com' !== $parsed['host'] && 'google.com' !== $parsed['host'] ) ) {
				continue;
			}

			if ( ! isset( $parsed['path'] ) || ( '/recaptcha/api.js' !== $parsed['path'] && 'recaptcha/api.js' !== $parsed['path'] ) ) {
				continue;
			}

			if ( wp_script_is( $handle ) || wp_script_is( $handle, 'done' ) ) {
				return;
			}
		}

		wp_enqueue_script( 'itsec-recaptcha-api', $this->build_google_api_script( false ), array(), '', true );
	}

	public function print_login_styles() {
		echo '<style type="text/css">#login { min-width: 350px !important; } </style>';
	}

	/**
	 * Add recaptcha form to comment form
	 *
	 * @since 1.17
	 *
	 * @param string $comment_field The comment field in the comment form
	 *
	 * @return string The comment field with our recaptcha field appended
	 */
	public function comment_form_field_comment( $comment_field ) {

		$comment_field .= $this->get_recaptcha( array( 'action' => self::A_COMMENT ) );

		return $comment_field;

	}

	/**
	 * Preferred method to add recaptcha form to comment form. Used in WP 4.2+
	 *
	 * @since 1.17
	 *
	 * @param string $submit_button The submit button in the comment form
	 *
	 * @return string The submit button with our recaptcha field prepended
	 */
	public function comment_form_submit_button( $submit_button ) {

		$submit_button = $this->get_recaptcha( array( 'action' => self::A_COMMENT ) ) . $submit_button;

		return $submit_button;

	}

	/**
	 * Enqueue assets for the opt-in dialog.
	 *
	 * @param array $args
	 */
	private function enqueue_opt_in( $args ) {
		wp_enqueue_style( 'itsec-recaptcha-opt-in', plugin_dir_url( __FILE__ ) . 'css/itsec-recaptcha.css', array(), ITSEC_Core::get_plugin_build() );

		if ( ! $this->settings['on_page_opt_in'] ) {
			return;
		}

		if ( wp_script_is( 'itsec-recaptcha-opt-in' ) ) {
			return;
		}

		$localize = array(
			'googlejs' => $this->build_google_api_script(),
		);

		switch ( $this->settings['type'] ) {
			case 'v3':
				$localize['onload'] = 'itsecRecaptchav3Load';
				break;
			case 'v2':
			default:
				$localize['onload'] = 'itsecRecaptchav2Load';
				break;
		}

		wp_enqueue_script( 'itsec-recaptcha-opt-in', plugin_dir_url( __FILE__ ) . 'js/optin.js', array( 'jquery', 'itsec-recaptcha-script' ), ITSEC_Core::get_plugin_build() );
		wp_localize_script( 'itsec-recaptcha-opt-in', 'ITSECRecaptchaOptIn', $localize );
		wp_enqueue_script( 'itsec-recaptcha-script', $this->build_itsec_script(), array( 'jquery' ), ITSEC_Core::get_plugin_build() );
	}

	/**
	 * Add the recaptcha field to the login form
	 *
	 * @since 1.13
	 *
	 * @return void
	 */
	public function login_form() {
		$this->show_recaptcha( array( 'action' => self::A_LOGIN ) );
	}

	/**
	 * Add the Recaptcha to the `wp_login_form()` template function.
	 *
	 * @param string $html
	 *
	 * @return string
	 */
	public function wp_login_form( $html ) {
		$html .= $this->get_recaptcha( array( 'action' => self::A_LOGIN, 'margin' => array( 'top' => 10, 'bottom' => 10 ) ) );

		return $html;
	}

	/**
	 * Process recaptcha for comments
	 *
	 * @since 1.13
	 *
	 * @param array $comment_data Comment data.
	 *
	 * @return array Comment data.
	 */
	public function filter_preprocess_comment( $comment_data ) {

		$result = $this->validate_captcha( array( 'action' => self::A_COMMENT ) );

		if ( is_wp_error( $result ) ) {
			wp_die( $result->get_error_message() );
		}

		return $comment_data;

	}

	/**
	 * Add the recaptcha field to the registration form
	 *
	 * @since 1.13
	 *
	 * @return void
	 */
	public function register_form() {
		$this->show_recaptcha( array( 'action' => self::A_REGISTER ) );
	}

	/**
	 * Set the registration error if captcha wasn't validated
	 *
	 * @since 1.13
	 *
	 * @param WP_Error $errors               A WP_Error object containing any errors encountered
	 *                                       during registration.
	 *
	 * @return WP_Error A WP_Error object containing any errors encountered
	 *                                       during registration.
	 */
	public function registration_errors( $errors ) {

		$result = $this->validate_captcha( array( 'action' => self::A_REGISTER ) );

		if ( is_wp_error( $result ) ) {
			$errors->add( $result->get_error_code(), $result->get_error_message() );
		}

		return $errors;

	}

	// Leave this in as iThemes Exchange relies upon it.
	public function show_field( $echo = true, $deprecated1 = true, $margin_top = 0, $margin_right = 0, $margin_bottom = 0, $margin_left = 0, $deprecated2 = null ) {
		$args = compact( 'margin_top', 'margin_right', 'margin_bottom', 'margin_left' );

		if ( $echo ) {
			$this->show_recaptcha( $args );
		} else {
			return $this->get_recaptcha( $args );
		}
	}

	public function show_recaptcha( $args = array(), $margin_right = null, $margin_bottom = null, $margin_left = null ) {
		if ( is_numeric( $args ) ) {
			_deprecated_argument( __METHOD__, '5.7.0', 'Use $args[margin_top] instead. Use ITSEC_Recaptcha_API for the stable interface.' );

			$args = array(
				'margin' => array(
					'top' => $args,
				),
			);
		}

		if ( $margin_right !== null ) {
			$args['margin']['right'] = $margin_right;
			_deprecated_argument( __METHOD__, '5.7.0', 'Use $args[margin_right] instead. Use ITSEC_Recaptcha_API for the stable interface.' );
		}

		if ( $margin_bottom !== null ) {
			$args['margin']['bottom'] = $margin_bottom;
			_deprecated_argument( __METHOD__, '5.7.0', 'Use $args[margin_bottom] instead. Use ITSEC_Recaptcha_API for the stable interface.' );
		}

		if ( $margin_left !== null ) {
			$args['margin']['left'] = $margin_left;
			_deprecated_argument( __METHOD__, '5.7.0', 'Use $args[margin_left] instead. Use ITSEC_Recaptcha_API for the stable interface.' );
		}

		$args['margin'] = wp_parse_args( isset( $args['margin'] ) ? $args['margin'] : array(), array(
			'top'    => 10,
			'bottom' => 10,
		) );

		echo $this->get_recaptcha( $args );
	}

	private function has_visitor_opted_in() {
		if ( isset( $_REQUEST['recaptcha-opt-in'] ) && 'true' === $_REQUEST['recaptcha-opt-in'] ) {
			return true;
		}

		if ( isset( $_COOKIE[ $this->cookie_name ] ) && 'true' === $_COOKIE[ $this->cookie_name ] ) {
			return true;
		}

		return false;
	}

	private function show_opt_in( $args ) {
		if ( ! ITSEC_Modules::get_setting( 'recaptcha', 'gdpr' ) || ITSEC_Modules::get_setting( 'recaptcha', 'type' ) === 'v3' ) {
			return '';
		}

		if ( $this->has_visitor_opted_in() ) {
			return '';
		}

		$this->enqueue_opt_in( $args );

		$url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		if ( false === strpos( $url, '?' ) ) {
			$url .= '?recaptcha-opt-in=true';
		} else {
			$url .= '&recaptcha-opt-in=true';
		}

		/* Translators: 1: Google's privacy policy URL, 2: Google's terms of use URL */
		$p1 = sprintf( wp_kses( __( 'For security, use of Google\'s reCAPTCHA service is required which is subject to the Google <a href="%1$s">Privacy Policy</a> and <a href="%2$s">Terms of Use</a>.', 'it-l10n-ithemes-security-pro' ), array( 'a' => array( 'href' => array() ) ) ), 'https://policies.google.com/privacy', 'https://policies.google.com/terms' );
		$p2 = sprintf(
			esc_html__( '%1$sI agree to these terms%2$s.', 'it-l10n-ithemes-security-pro' ),
			'<a href="' . esc_url( $url ) . '" class="itsec-recaptcha-opt-in__agree">',
			'</a>'
		);

		$html = '<div class="itsec-recaptcha-opt-in">';
		$html .= '<p>' . $p1 . '</p>';
		$html .= '<p>' . $p2 . '</p>';
		$html .= '<script type="text-template" class="itsec-recaptcha-opt-in__template">' . $this->get_g_recaptcha_html( $args, false ) . '</script>';
		$html .= '</div>';

		return $html;
	}

	public function get_recaptcha( $args = array(), $margin_right = null, $margin_bottom = null, $margin_left = null ) {
		self::$captcha_count ++;

		if ( is_numeric( $args ) ) {
			_deprecated_argument( __METHOD__, '5.7.0', 'Use $args[margin_top] instead. Use ITSEC_Recaptcha_API for the stable interface.' );

			$args = array(
				'margin' => array(
					'top' => $args,
				),
			);
		}

		if ( $margin_right !== null ) {
			$args['margin']['right'] = $margin_right;
			_deprecated_argument( __METHOD__, '5.7.0', 'Use $args[margin_right] instead. Use ITSEC_Recaptcha_API for the stable interface.' );
		}

		if ( $margin_bottom !== null ) {
			$args['margin']['bottom'] = $margin_bottom;
			_deprecated_argument( __METHOD__, '5.7.0', 'Use $args[margin_bottom] instead. Use ITSEC_Recaptcha_API for the stable interface.' );
		}

		if ( $margin_left !== null ) {
			$args['margin']['left'] = $margin_left;
			_deprecated_argument( __METHOD__, '5.7.0', 'Use $args[margin_left] instead. Use ITSEC_Recaptcha_API for the stable interface.' );
		}

		$defaults = array(
			'margin' => array(
				'top'    => 0,
				'right'  => 0,
				'bottom' => 0,
				'left'   => 0,
			),
			'action' => '',
		);
		$args     = wp_parse_args( $args, $defaults );

		$args['margin'] = wp_parse_args( $args['margin'], $defaults['margin'] );

		if ( $html = $this->show_opt_in( $args ) ) {
			return $html;
		}

		$this->register_google_api_script();

		$recaptcha = $this->get_g_recaptcha_html( $args );
		$this->enqueue_itsec_script();

		return $recaptcha;
	}

	/**
	 * Get the g-recaptcha HTML.
	 *
	 * @param array $args
	 * @param bool  $include_fallback
	 *
	 * @return string
	 */
	private function get_g_recaptcha_html( $args, $include_fallback = true ) {

		if ( 'v3' === $this->settings['type'] ) {
			return '<input type="hidden" name="g-recaptcha-response" class="g-recaptcha" data-sitekey="' . esc_attr( $this->settings['site_key'] ) . '" data-action="' . esc_attr( $args['action'] ) . '">';
		}

		if ( 'invisible' === $this->settings['type'] ) {
			$html = '<div class="g-recaptcha" id="g-recaptcha-' . esc_attr( self::$captcha_count ) . '" data-sitekey="' . esc_attr( $this->settings['site_key'] ) . '" data-size="invisible" data-badge="' . esc_attr( $this->settings['invis_position'] ) . '"></div>';
		} else {
			$theme       = $this->settings['theme'] ? 'dark' : 'light';
			$style_value = sprintf( 'margin:%dpx %dpx %dpx %dpx', $args['margin']['top'], $args['margin']['right'], $args['margin']['bottom'], $args['margin']['left'] );
			$html        = '<div class="g-recaptcha" id="g-recaptcha-' . esc_attr( self::$captcha_count ) . '" data-sitekey="' . esc_attr( $this->settings['site_key'] ) . '" data-theme="' . esc_attr( $theme ) . '" style="' . esc_attr( $style_value ) . '"></div>';
		}

		if ( $include_fallback ) {
			$html .= '<noscript>
				<div>
					<div style="width: 302px; height: 422px; position: relative;">
						<div style="width: 302px; height: 422px; position: absolute;">
							<iframe src="https://www.google.com/recaptcha/api/fallback?k=' . esc_attr( $this->settings['site_key'] ) . '" frameborder="0" scrolling="no" style="width: 302px; height:422px; border-style: none;"></iframe>
						</div>
					</div>
					<div style="width: 300px; height: 60px; border-style: none; bottom: 12px; left: 25px; margin: 0px; padding: 0px; right: 25px; background: #f9f9f9; border: 1px solid #c1c1c1; border-radius: 3px;">
						<textarea id="g-recaptcha-response" name="g-recaptcha-response" class="g-recaptcha-response" style="width: 250px; height: 40px; border: 1px solid #c1c1c1; margin: 10px 25px; padding: 0px; resize: none;"></textarea>
					</div>
				</div>
			</noscript>';
		}

		return $html;
	}

	/**
	 * Build the JS script we use to control the reCAPTCHA API.
	 *
	 * @return string
	 */
	private function build_itsec_script() {
		if ( 'invisible' === $this->settings['type'] ) {
			return plugin_dir_url( __FILE__ ) . 'js/invisible-recaptcha.js';
		}

		if ( 'v3' === $this->settings['type'] ) {
			return plugin_dir_url( __FILE__ ) . 'js/recaptcha-v3.js';
		}

		return plugin_dir_url( __FILE__ ) . 'js/recaptcha-v2.js';
	}

	/**
	 * Enqueue the JS script we use to control the reCAPTCHA API.
	 */
	private function enqueue_itsec_script() {
		$script = $this->build_itsec_script();

		if ( 'v3' === $this->settings['type'] ) {
			wp_enqueue_script( 'itsec-recaptcha-script', $script, array( 'jquery', 'itsec-recaptcha-api' ), ITSEC_Core::get_plugin_build() );
		} elseif ( 'invisible' === $this->settings['type'] ) {
			wp_enqueue_script( 'itsec-recaptcha-script', $script, array( 'jquery', 'itsec-recaptcha-api' ), ITSEC_Core::get_plugin_build() );
		} else {
			wp_enqueue_script( 'itsec-recaptcha-script', $script, array( 'itsec-recaptcha-api' ), ITSEC_Core::get_plugin_build() );
		}
	}

	/**
	 * Build the Google API script js.
	 *
	 * @param bool $include_onload
	 *
	 * @return string
	 */
	private function build_google_api_script( $include_onload = true ) {
		$script = 'https://www.google.com/recaptcha/api.js';

		$query_args = array(
			'render' => 'explicit'
		);

		if ( ! empty( $this->settings['language'] ) ) {
			$query_args['hl'] = $this->settings['language'];
		}

		switch ( $this->settings['type'] ) {
			case 'invisible':
				$query_args['onload'] = 'itsecInvisibleRecaptchaLoad';
				break;
			case 'v3':
				$query_args['render'] = $this->settings['site_key'];
				$query_args['onload'] = 'itsecRecaptchav3Load';
				break;
			case 'v2':
			default:
				$query_args['onload'] = 'itsecRecaptchav2Load';
				break;
		}

		if ( ! $include_onload ) {
			unset( $query_args['onload'] );
		}

		if ( ! empty( $query_args ) ) {
			$script .= '?' . http_build_query( $query_args, '', '&' );
		}

		return $script;
	}

	/**
	 * Register the Google reCAPTCHA api.js script as 'itsec-recaptcha-api'.
	 */
	private function register_google_api_script() {
		wp_register_script( 'itsec-recaptcha-api', $this->build_google_api_script() );
	}

	/**
	 * Validates the captcha code
	 *
	 * This function is used both internally in iThemes Security and externally in other projects, such as iThemes
	 * Exchange.
	 *
	 * @since 1.13
	 *
	 * @param array $args
	 *
	 * @return bool|WP_Error Returns true or a WP_Error object on error.
	 */
	public function validate_captcha( $args = array() ) {
		if ( isset( $GLOBALS['__itsec_recaptcha_cached_result'] ) ) {
			return $GLOBALS['__itsec_recaptcha_cached_result'];
		}

		if ( empty( $_POST['g-recaptcha-response'] ) ) {
			if ( ! $this->settings['validated'] ) {
				ITSEC_Modules::set_setting( 'recaptcha', 'last_error', esc_html__( 'The Site Key may be invalid or unrecognized. Verify that you input the Site Key and Private Key correctly.', 'it-l10n-ithemes-security-pro' ) );

				$GLOBALS['__itsec_recaptcha_cached_result'] = true;

				return $GLOBALS['__itsec_recaptcha_cached_result'];
			}

			$GLOBALS['__itsec_recaptcha_cached_result'] = new WP_Error( 'itsec-recaptcha-form-not-submitted', esc_html__( 'You must submit the reCAPTCHA to proceed. Please try again.', 'it-l10n-ithemes-security-pro' ) );

			$this->log_failed_validation( $GLOBALS['__itsec_recaptcha_cached_result'] );

			return $GLOBALS['__itsec_recaptcha_cached_result'];
		}


		$url = add_query_arg(
			array(
				'secret'   => $this->settings['secret_key'],
				'response' => esc_attr( $_POST['g-recaptcha-response'] ),
				'remoteip' => ITSEC_Lib::get_ip(),
			),
			'https://www.google.com/recaptcha/api/siteverify'
		);

		$response = wp_remote_get( $url );

		if ( is_wp_error( $response ) ) {
			// Don't lock people out when reCAPTCHA servers cannot be contacted.
			$GLOBALS['__itsec_recaptcha_cached_result'] = true;

			return $GLOBALS['__itsec_recaptcha_cached_result'];
		}


		$status = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! $this->is_valid_response_format( $status ) ) {
			// Unrecognized response. Do not prevent access.
			$GLOBALS['__itsec_recaptcha_cached_result'] = true;

			return $GLOBALS['__itsec_recaptcha_cached_result'];
		}

		$validation_error = $this->validate_response( $status, $args );

		if ( ! is_wp_error( $validation_error ) ) {
			if ( ! $this->settings['validated'] ) {
				ITSEC_Modules::set_setting( 'recaptcha', 'validated', true );
			}

			if ( ! empty( $this->settings['last_error'] ) ) {
				ITSEC_Modules::set_setting( 'recaptcha', 'last_error', '' );
			}

			$GLOBALS['__itsec_recaptcha_cached_result'] = true;

			return $GLOBALS['__itsec_recaptcha_cached_result'];
		}

		if ( ! $this->settings['validated'] ) {
			if ( ! empty( $status['error-codes'] ) ) {
				if ( array( 'invalid-input-secret' ) === $status['error-codes'] ) {
					ITSEC_Modules::set_setting( 'recaptcha', 'last_error', esc_html__( 'The Secret Key is invalid or unrecognized.', 'it-l10n-ithemes-security-pro' ) );
				} elseif ( 1 === count( $status['error-codes'] ) ) {
					$code = current( $status['error-codes'] );

					ITSEC_Modules::set_setting( 'recaptcha', 'last_error', sprintf( esc_html__( 'The reCAPTCHA server reported the following error: <code>%1$s</code>.', 'it-l10n-ithemes-security-pro' ), $code ) );
				} else {
					ITSEC_Modules::set_setting( 'recaptcha', 'last_error', sprintf( esc_html__( 'The reCAPTCHA server reported the following errors: <code>%1$s</code>.', 'it-l10n-ithemes-security-pro' ), implode( ', ', $status['error-codes'] ) ) );
				}
			}

			$GLOBALS['__itsec_recaptcha_cached_result'] = true;

			return $GLOBALS['__itsec_recaptcha_cached_result'];
		}

		$GLOBALS['__itsec_recaptcha_cached_result'] = $validation_error;

		$this->log_failed_validation( $GLOBALS['__itsec_recaptcha_cached_result'] );

		return $GLOBALS['__itsec_recaptcha_cached_result'];
	}

	/**
	 * Is the reCAPTCHA response from Google valid.
	 *
	 * @param array $response
	 *
	 * @return bool
	 */
	private function is_valid_response_format( $response ) {

		if ( ! is_array( $response ) ) {
			return false;
		}

		if ( ! isset( $response['success'] ) ) {
			return false;
		}

		if ( 'v3' === $this->settings['type'] && ! isset( $response['score'], $response['action'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Validate the response.
	 *
	 * @param array $response The response from Google.
	 * @param array $args     The args passed by the user.
	 *
	 * @return WP_Error|null
	 */
	private function validate_response( $response, $args ) {

		ITSEC_Log::add_debug( 'recaptcha', 'validate-response', compact( 'response', 'args' ) );

		$error = new WP_Error( 'itsec-recaptcha-incorrect', esc_html__( 'The captcha response you submitted does not appear to be valid. Please try again.', 'it-l10n-ithemes-security-pro' ) );

		if ( ! $response['success'] ) {
			$error->add_data( array( 'validate_error' => 'invalid-token' ) );

			return $error;
		}

		if ( ! $this->validate_host( $response ) ) {
			$error->add_data( array( 'validate_error' => 'host-mismatch' ) );

			return $error;
		}

		if ( ! $this->validate_action( $response, $args ) ) {
			$error->add_data( array( 'validate_error' => 'action-mismatch' ) );

			return $error;
		}

		if ( ! $this->validate_score( $response ) ) {
			$error->add_data( array( 'validate_error' => 'insufficient_score' ) );

			return $error;
		}

		return null;
	}

	/**
	 * Validate the hostname the Recaptcha was filled on.
	 *
	 * This allows the user to disable "Domain Name Validation" on large multisite installations because Google
	 * limits the number of sites a recaptcha key can be used on.
	 *
	 * @since 4.2.0
	 *
	 * @param array $status
	 *
	 * @return bool
	 */
	private function validate_host( $status ) {

		if ( ! apply_filters( 'itsec_recaptcha_validate_host', false ) ) {
			return true;
		}

		if ( ! isset( $status['hostname'] ) ) {
			return true;
		}

		$site_parsed = parse_url( site_url() );

		if ( ! is_array( $site_parsed ) || ! isset( $site_parsed['host'] ) ) {
			return true;
		}

		return $site_parsed['host'] === $status['hostname'];
	}

	/**
	 * Validate that the action matches and the score is above the threshold..
	 *
	 * @param array $status Response from Google.
	 * @param array $args   Validation args.
	 *
	 * @return bool
	 */
	private function validate_action( $status, $args ) {

		if ( 'v3' !== $this->settings['type'] ) {
			return true;
		}

		return empty( $args['action'] ) || $status['action'] === $args['action'];
	}

	/**
	 * Validate that the action matches and the score is above the threshold..
	 *
	 * @param array $status Response from Google.
	 *
	 * @return bool
	 */
	private function validate_score( $status ) {

		if ( 'v3' !== $this->settings['type'] ) {
			return true;
		}

		return $status['score'] >= $this->settings['v3_threshold'];
	}

	/**
	 * Log when Recaptcha fails to validate.
	 *
	 * @param WP_Error $data
	 */
	private function log_failed_validation( $data ) {
		/** @var ITSEC_Lockout $itsec_lockout */
		global $itsec_lockout;

		ITSEC_Log::add_notice( 'recaptcha', 'failed-validation', $data );

		$itsec_lockout->do_lockout( 'recaptcha' );
	}

	/**
	 * Set the login error if captcha wasn't validated
	 *
	 * @since 1.13
	 *
	 * @param null|WP_User|WP_Error $user     WP_User if the user is authenticated.
	 *                                        WP_Error or null otherwise.
	 *
	 * @return null|WP_User|WP_Error $user     WP_User if the user is authenticated.
	 *                                         WP_Error or null otherwise.
	 */
	public function filter_authenticate( $user ) {
		if ( empty( $_POST ) || ITSEC_Core::is_api_request() ) {
			return $user;
		}

		$result = $this->validate_captcha( array( 'action' => self::A_LOGIN ) );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $user;

	}

	/**
	 * Register recaptcha for lockout
	 *
	 * @since 1.13
	 *
	 * @param  array $lockout_modules array of lockout modules
	 *
	 * @return array                   array of lockout modules
	 */
	public function register_lockout_module( $lockout_modules ) {

		$lockout_modules['recaptcha'] = array(
			'type'   => 'recaptcha',
			'reason' => __( 'too many failed captcha submissions.', 'it-l10n-ithemes-security-pro' ),
			'host'   => isset( $this->settings['error_threshold'] ) ? absint( $this->settings['error_threshold'] ) : 7,
			'period' => isset( $this->settings['check_period'] ) ? absint( $this->settings['check_period'] ) : 5,
		);

		return $lockout_modules;

	}
}
