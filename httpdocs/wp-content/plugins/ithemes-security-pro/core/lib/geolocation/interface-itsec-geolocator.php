<?php

/**
 * Interface ITSEC_Geolocator
 */
interface ITSEC_Geolocator {

	/**
	 * Geolocate an IP address.
	 *
	 * @param string $ip
	 *
	 * @return array|WP_Error With 'lat', 'long', 'label' and 'credit' fields. Label and credit ARE safe.
	 */
	public function geolocate( $ip );

	/**
	 * Is this geolocator available.
	 *
	 * @return bool
	 */
	public function is_available();
}