<?php

/**
 * Class ITSEC_Geolocator_Page_Cache
 */
class ITSEC_Geolocator_Page_Cache implements ITSEC_Geolocator {

	/** @var ITSEC_Geolocator */
	private $geolocator;

	/** @var array */
	private $cache = array();

	/**
	 * ITSEC_Geolocator_Cache constructor.
	 *
	 * @param ITSEC_Geolocator $geolocator
	 */
	public function __construct( ITSEC_Geolocator $geolocator ) { $this->geolocator = $geolocator; }

	/**
	 * @inheritDoc
	 */
	public function geolocate( $ip ) {
		if ( ! isset( $this->cache[ $ip ] ) ) {
			$this->cache[ $ip ] = $this->geolocator->geolocate( $ip );
		}

		return $this->cache[ $ip ];
	}

	/**
	 * @inheritDoc
	 */
	public function is_available() {
		return $this->geolocator->is_available();
	}
}