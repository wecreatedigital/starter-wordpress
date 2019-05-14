<?php

/**
 * Class ITSEC_Login_Interstitial_Config_Driven
 */
class ITSEC_Login_Interstitial_Config_Driven extends ITSEC_Login_Interstitial {

	/** @var array */
	private $config;

	/**
	 * ITSEC_Login_Interstitial_Config_Driven constructor.
	 *
	 * @param array $config
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( array $config ) {
		$this->config = wp_parse_args( $config, array(
			'force_completion' => true, // Will logout the user's session before displaying the interstitial.
			'show_to_user'     => true, // Boolean or callable.
			'wp_login_only'    => false, // Only show the interstitial if the login form is submitted from wp-login.php,
			'submit'           => false, // Callable called with user when submitting the form.
			'async_action'     => false, // Callable called when a user clicks a link to perform an interstitial action.
			'info_message'     => false,
			'after_submit'     => false,
			'ajax_handler'     => false,
			'priority'         => 5,
		) );

		if ( ! is_bool( $this->config['show_to_user'] ) && ! is_callable( $this->config['show_to_user'] ) ) {
			throw new InvalidArgumentException( 'Show to user is required.' );
		}

		if ( ! is_bool( $this->config['force_completion'] ) && ! is_callable( $this->config['force_completion'] ) ) {
			throw new InvalidArgumentException( 'Force completion is required.' );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function render( ITSEC_Login_Interstitial_Session $session, array $args ) {
		call_user_func( $this->config['render'], $session->get_user(), array_merge( compact( 'session' ), $args ) );
	}

	/**
	 * @inheritDoc
	 */
	public function show_to_user( WP_User $user, $is_requested ) {
		return $this->result( $this->config['show_to_user'], array( $user, $is_requested ) );
	}

	/**
	 * @inheritDoc
	 */
	public function show_on_wp_login_only( WP_User $user ) {
		return $this->result( $this->config['wp_login_only'], array( $user ) );
	}

	/**
	 * @inheritDoc
	 */
	public function is_completion_forced( ITSEC_Login_Interstitial_Session $session ) {
		return $this->result( $this->config['force_completion'], $session->get_user() );
	}

	/**
	 * @inheritdoc
	 */
	public function has_submit() {
		return (bool) $this->config['submit'];
	}

	/**
	 * @inheritDoc
	 */
	public function submit( ITSEC_Login_Interstitial_Session $session, array $data ) {
		return call_user_func( $this->config['submit'], $session->get_user(), $data );
	}

	/**
	 * @inheritDoc
	 */
	public function has_async_action() {
		return (bool) $this->config['async_action'];
	}

	/**
	 * @inheritDoc
	 */
	public function handle_async_action( ITSEC_Login_Interstitial_Session $session, $action, array $args ) {
		return call_user_func( $this->config['async_action'], $session, $action, $args );
	}

	/**
	 * @inheritDoc
	 */
	public function has_ajax_handlers() {
		return (bool) $this->config['ajax_handler'];
	}

	/**
	 * @inheritDoc
	 */
	public function handle_ajax( ITSEC_Login_Interstitial_Session $session, array $data ) {
		call_user_func( $this->config['ajax_handler'], $session->get_user(), $data );
	}

	/**
	 * @inheritDoc
	 */
	public function get_info_message( ITSEC_Login_Interstitial_Session $session ) {
		return $this->result( $this->config['info_message'], array( $session->get_user() ) );
	}

	/**
	 * @inheritDoc
	 */
	public function after_submit( ITSEC_Login_Interstitial_Session $session, array $data ) {
		if ( is_callable( $this->config['after_submit'] ) ) {
			call_user_func( $this->config['after_submit'], $session->get_user(), $data );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function get_priority() {
		return $this->config['priority'];
	}

	/**
	 * Try and get a value from the provider.
	 *
	 * If it is a function, will call the function with the provided args.
	 *
	 * @param bool|callable $provider
	 * @param array         $args
	 *
	 * @return bool|mixed
	 */
	private function result( $provider, $args = array() ) {
		if ( is_bool( $provider ) ) {
			return $provider;
		}

		if ( is_callable( $provider, true ) ) {
			return call_user_func_array( $provider, $args );
		}

		return $provider;
	}
}
