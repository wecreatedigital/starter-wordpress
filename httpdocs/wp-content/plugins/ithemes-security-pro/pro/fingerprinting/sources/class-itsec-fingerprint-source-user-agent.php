<?php

/**
 * Class ITSEC_Fingerprint_Source_User_Agent
 */
class ITSEC_Fingerprint_Source_User_Agent extends ITSEC_Fingerprint_Source_Header {

	/**
	 * @inheritDoc
	 */
	public function calculate_value_from_global_state() {
		return new ITSEC_Fingerprint_Value( $this, $this->retrieve_header() );
	}

	/**
	 * @inheritDoc
	 */
	public function compare( ITSEC_Fingerprint_Value $known, ITSEC_Fingerprint_Value $unknown ) {

		if ( $known->get_value() === $unknown->get_value() ) {
			return 100;
		}

		require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-lib-browser.php' );

		$b_known = new ITSEC_Lib_Browser( $known->get_value() );
		$b_unknown = new ITSEC_Lib_Browser( $unknown->get_value() );

		if ( $b_known->isRobot() || $b_unknown->isRobot() ) {
			return 0;
		}

		if ( $b_known->getPlatform() !== $b_unknown->getPlatform() ) {
			return 0;
		}

		if ( $b_known->getBrowser() === ITSEC_Lib_Browser::BROWSER_UNKNOWN || $b_unknown->getBrowser() === ITSEC_Lib_Browser::BROWSER_UNKNOWN ) {
			return 0;
		}

		if ( $b_known->getBrowser() !== $b_unknown->getBrowser() ) {
			return 20;
		}

		if ( version_compare( $b_known->getVersion(), $b_unknown->getVersion(), '=' ) ) {
			return 95;
		}

		// Allow for an unknown fingerprint that is an upgrade from the known fingerprint.
		if ( version_compare( $b_unknown->getVersion(), $b_known->getVersion(), '>' ) ) {
			return 90;
		}

		return 20;
	}

	/**
	 * @inheritDoc
	 */
	public function get_weight( ITSEC_Fingerprint_Value $value ) {

		require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-lib-browser.php' );

		$browser = new ITSEC_Lib_Browser( $value->get_value() );

		if ( $browser->getBrowser() === ITSEC_Lib_Browser::BROWSER_CHROME ) {
			return 35;
		}

		// Safari has a high market share, but that is because of mobile safari which doesn't use Apple as a platform.
		if ( $browser->getBrowser() === ITSEC_Lib_Browser::BROWSER_SAFARI && $browser->getPlatform() !== ITSEC_Lib_Browser::PLATFORM_APPLE ) {
			return 45;
		}

		return 50;
	}

	/**
	 * @inheritDoc
	 */
	protected function get_header_name() {
		return 'user-agent';
	}
}