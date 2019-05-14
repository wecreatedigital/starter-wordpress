<?php

/**
 * Class ITSEC_Geolocator_MaxMind_API
 */
final class ITSEC_Geolocator_MaxMind_API implements ITSEC_Geolocator {

	const URL = 'https://geoip.maxmind.com/geoip/v2.1/city/';

	/**
	 * @inheritDoc
	 */
	public function geolocate( $ip ) {

		$user = ITSEC_Modules::get_setting( 'fingerprinting', 'maxmind_api_user' );
		$key  = ITSEC_Modules::get_setting( 'fingerprinting', 'maxmind_api_key' );

		$response = wp_remote_get( self::URL . $ip, array(
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( "{$user}:{$key}" ),
				'Accept'        => 'application/vnd.maxmind.com-city+json; charset=UTF-8; version=2.1',
			)
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );

		if ( ! $body || null === ( $data = json_decode( $body, true ) ) || empty( $data['location'] ) ) {
			return new WP_Error( 'itsec-geolocate-maxmind-invalid-response', __( 'Invalid Geolocation response from MaxMind', 'it-l10n-ithemes-security-pro' ), compact( 'body', 'ip' ) );
		}

		$label = $data['country']['names']['en'];

		if ( ! empty( $data['city']['names']['en'] ) ) {
			/* translators: 1. City Name, 2. Country Name */
			$label = sprintf( _x( '%1$s, %2$s', 'Location', 'it-l10n-ithemes-security-pro' ), $data['city']['names']['en'], $label );
		}

		return array(
			'lat'    => (float) $data['location']['latitude'],
			'long'   => (float) $data['location']['longitude'],
			'label'  => wp_strip_all_tags( $label, true ),
			'credit' => ''
		);
	}

	/**
	 * @inheritDoc
	 */
	public function is_available() {
		return ITSEC_Modules::get_setting( 'fingerprinting', 'maxmind_api_user' ) && ITSEC_Modules::get_setting( 'fingerprinting', 'maxmind_api_key' );
	}
}