<?php

class ITSEC_Four_Oh_Four {

	private $settings;

	function run() {

		$this->settings = ITSEC_Modules::get_settings( '404-detection' );

		add_filter( 'itsec_lockout_modules', array( $this, 'register_lockout' ) );

		add_action( 'template_redirect', array( $this, 'check_404' ), 9999 );
	}

	/**
	 * If the page is a WordPress 404 error log it and register for lockout
	 *
	 * @return void
	 */
	public function check_404() {

		/** @var ITSEC_Lockout $itsec_lockout */
		global $itsec_lockout;

		if ( ! is_404() ) {
			return;
		}

		$uri = explode( '?', $_SERVER['REQUEST_URI'] );

		if (
			! in_array( '/' . ITSEC_Lib::get_request_path(), $this->settings['white_list'], true ) &&
			! in_array( '.' . pathinfo( $uri[0], PATHINFO_EXTENSION ), $this->settings['types'], true )
		) {
			ITSEC_Log::add_notice( 'four_oh_four', 'found_404', array( 'SERVER' => $_SERVER ) );
			$itsec_lockout->do_lockout( 'four_oh_four' );
		} else {
			do_action( 'itsec_four_oh_four_whitelisted', $uri );
		}
	}

	/**
	 * Register 404 detection for lockout
	 *
	 * @param  array $lockout_modules array of lockout modules
	 *
	 * @return array                   array of lockout modules
	 */
	public function register_lockout( $lockout_modules ) {

		$lockout_modules['four_oh_four'] = array(
			'type'   => 'four_oh_four',
			'reason' => __( 'too many attempts to access a file that does not exist', 'better-wp-security' ),
			'host'   => $this->settings['error_threshold'],
			'period' => $this->settings['check_period']
		);

		return $lockout_modules;

	}

}
