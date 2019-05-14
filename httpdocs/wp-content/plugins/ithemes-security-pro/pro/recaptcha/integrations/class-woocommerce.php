<?php
/**
 * WooCommerce Recaptcha Integration.
 *
 * @since   4.1.0
 * @license GPLv2+
 */

/**
 * Class ITSEC_Recaptcha_Integration_WooCommerce
 */
final class ITSEC_Recaptcha_Integration_WooCommerce {

	/**
	 * @var ITSEC_Recaptcha
	 */
	private $recaptcha;

	/** @var array */
	private $settings;

	/**
	 * ITSEC_Recaptcha_Integration_WooCommerce constructor.
	 *
	 * @param ITSEC_Recaptcha $recaptcha
	 */
	public function __construct( ITSEC_Recaptcha $recaptcha ) {
		$this->recaptcha = $recaptcha;
		$this->settings  = ITSEC_Modules::get_settings( 'recaptcha' );
	}

	public function run() {
		add_action( 'init', array( $this, 'setup' ) );
	}

	/**
	 * Setup hooks to enable Recaptchas in WooCommerce login and register forms.
	 */
	public function setup() {

		if ( is_user_logged_in() ) {
			return;
		}

		if ( empty( $this->settings['site_key'] ) || empty( $this->settings['secret_key'] ) ) {
			return;
		}

		if ( $this->settings['login'] ) {
			add_action( 'woocommerce_login_form', array( $this, 'add_to_login_form' ) );
		}

		if ( $this->settings['register'] ) {
			add_action( 'woocommerce_register_form', array( $this, 'add_to_register_form' ) );
			add_action( 'woocommerce_after_checkout_registration_form', array( $this, 'add_to_register_form' ) );
			add_filter( 'woocommerce_process_registration_errors', array( $this, 'validate_register_form' ) );
			add_filter( 'woocommerce_registration_errors', array( $this, 'validate_register_form' ) );
		}
	}

	/**
	 * Display the recaptcha on the login form on both the account page and during checkout.
	 */
	public function add_to_login_form() {
		$this->recaptcha->show_recaptcha( array( 'action' => ITSEC_Recaptcha::A_LOGIN ) );
	}

	/**
	 * Display the recaptcha on the registration form on both the account page and during checkout.
	 */
	public function add_to_register_form() {
		$this->recaptcha->show_recaptcha( array( 'action' => ITSEC_Recaptcha::A_REGISTER ) );
	}

	/**
	 * Validate the registration form recaptcha.
	 *
	 * @since 4.1.0
	 *
	 * @param WP_Error $error
	 *
	 * @return \WP_Error
	 */
	public function validate_register_form( $error ) {

		$result = $this->recaptcha->validate_captcha( array( 'action' => ITSEC_Recaptcha::A_REGISTER ) );

		if ( is_wp_error( $result ) ) {
			$error->add( $result->get_error_code(), $result->get_error_message() );
		}

		return $error;
	}
}
