<?php

require_once( dirname( __FILE__ ) . '/geolocation/interface-itsec-geolocator.php' );
require_once( dirname( __FILE__ ) . '/geolocation/class-itsec-geolocator-chain.php' );
require_once( dirname( __FILE__ ) . '/geolocation/class-itsec-geolocator-page-cache.php' );

class ITSEC_Lib_Geolocation {

	/** @var ITSEC_Geolocator */
	private static $geolocator;

	/**
	 * Geolocate an IP address.
	 *
	 * @param string $ip
	 *
	 * @return array|WP_Error With 'lat', 'long', 'label' and 'credit' fields. Label and credit ARE safe, but may contain limited HTML like <a> tags.
	 */
	public static function geolocate( $ip ) {

		require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-lib-ip-tools.php' );

		if ( ! ITSEC_Lib_IP_Tools::validate( $ip ) ) {
			return new WP_Error( 'itsec_geolocate_invalid_ip', esc_html__( 'Tried to geolocate an invalid IP address.', 'better-wp-security' ) );
		}

		if ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE ) ) {
			return new WP_Error( 'itsec_geolocate_private_ip', esc_html__( 'Tried to geolocate a private IP address.', 'better-wp-security' ) );
		}

		return self::get_geolocator()->geolocate( $ip );
	}

	/**
	 * Get the geolocator.
	 *
	 * @return ITSEC_Geolocator
	 */
	private static function get_geolocator() {
		if ( null === self::$geolocator ) {
			$geolocator = new ITSEC_Geolocator_Chain( self::get_geolocator_apis() );

			/**
			 * Filter the Geolocator uses to geolocate IPs.
			 *
			 * @param ITSEC_Geolocator $geolocator
			 */
			$geolocator = apply_filters( 'itsec_geolocator', $geolocator );

			self::$geolocator = new ITSEC_Geolocator_Page_Cache( $geolocator );
		}

		return self::$geolocator;
	}

	/**
	 * Get a list of geolocators.
	 *
	 * @return ITSEC_Geolocator[]
	 */
	private static function get_geolocator_apis() {
		/**
		 * Get all API powered Geolocators.
		 *
		 * @param ITSEC_Geolocator[] $geolocaators
		 */
		return apply_filters( 'itsec_geolocator_apis', array() );
	}
}