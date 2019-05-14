<?php

/**
 * Class ITSEC_Fingerprint_Source_IP
 */
class ITSEC_Fingerprint_Source_IP implements ITSEC_Fingerprint_Source {

	/**
	 * @inheritDoc
	 */
	public function calculate_value_from_global_state() {
		return new ITSEC_Fingerprint_Value( $this, ITSEC_Lib::get_ip() );
	}

	/**
	 * @inheritDoc
	 */
	public function compare( ITSEC_Fingerprint_Value $known, ITSEC_Fingerprint_Value $unknown ) {

		if ( $known->get_value() === $unknown->get_value() ) {
			return 100;
		}

		require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-lib-geolocation.php' );

		$known_location   = ITSEC_Lib_Geolocation::geolocate( $known->get_value() );
		$unknown_location = ITSEC_Lib_Geolocation::geolocate( $unknown->get_value() );

		if ( is_wp_error( $known_location ) || is_wp_error( $unknown_location ) ) {
			return 0;
		}

		$distance = $this->calculate_distance(
			$known_location['lat'], $known_location['long'],
			$unknown_location['lat'], $unknown_location['long']
		);

		return 85 / ( 1 + pow( $distance / 50, 1.7 ) );
	}

	/**
	 * @inheritDoc
	 */
	public function get_weight( ITSEC_Fingerprint_Value $value ) {
		return 100;
	}

	/**
	 * @inheritDoc
	 */
	public function get_slug() {
		return 'ip';
	}

	/**
	 * Distance between two points
	 *
	 * @param float $lat_1  Latitude 1
	 * @param float $long_1 Longitude 1
	 * @param float $lat_2  Latitude 2
	 * @param float $long_2 Longitude 2
	 *
	 * @return float distance in miles.
	 */
	private function calculate_distance( $lat_1, $long_1, $lat_2, $long_2 ) {
		$lat_deg  = deg2rad( $lat_2 - $lat_1 );
		$long_deg = deg2rad( $long_2 - $long_1 );

		$a = sin( $lat_deg / 2 ) * sin( $lat_deg / 2 ) +
		     cos( deg2rad( $lat_1 ) ) * cos( deg2rad( $lat_2 ) ) *
		     sin( $long_deg / 2 ) * sin( $long_deg / 2 );

		$c = 2 * atan2( sqrt( $a ), sqrt( 1 - $a ) );

		$radius = 3958.761;

		return round( $radius * $c, 3 );
	}
}