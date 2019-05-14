<?php

/**
 * Class ITSEC_Geolocator_Chain
 */
final class ITSEC_Geolocator_Chain implements ITSEC_Geolocator {

	/** @var ITSEC_Geolocator[] */
	private $chain;

	/**
	 * ITSEC_Geolocator_Chain constructor.
	 *
	 * @param ITSEC_Geolocator[] $chain
	 */
	public function __construct( $chain ) { $this->chain = $chain; }

	/**
	 * @inheritDoc
	 */
	public function geolocate( $ip ) {
		foreach ( $this->chain as $geolocator ) {
			if ( ! is_wp_error( $location = $geolocator->geolocate( $ip ) ) ) {
				return $location;
			}
		}

		return new WP_Error( 'itsec_geolocation_not_found', __( 'No geolocator found a valid location.', 'it-l10n-ithemes-security-pro' ) );
	}

	/**
	 * @inheritDoc
	 */
	public function is_available() {
		return count( $this->chain ) > 0;
	}
}