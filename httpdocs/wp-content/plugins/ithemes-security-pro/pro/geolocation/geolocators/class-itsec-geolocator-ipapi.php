<?php

/**
 * Class ITSEC_Geolocator_IPAPI
 */
final class ITSEC_Geolocator_IPAPI implements ITSEC_Geolocator {

	const HOST = 'http://ip-api.com/json/';

	/**
	 * @inheritDoc
	 */
	public function geolocate( $ip ) {

		$response = wp_remote_get( self::HOST . $ip );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );

		if ( ! $body || null === ( $data = json_decode( $body, true ) ) || empty( $data['lat'] ) || empty( $data['lon'] ) ) {
			return new WP_Error( 'itsec-geolocate-ipapi-invalid-response', __( 'Invalid Geolocation response from ip-api', 'it-l10n-ithemes-security-pro' ), compact( 'body', 'ip' ) );
		}

		$label = $data['country'];

		if ( ! empty( $data['city'] ) ) {
			/* translators: 1. City Name, 2. Country Name */
			$label = sprintf( _x( '%1$s, %2$s', 'Location', 'it-l10n-ithemes-security-pro' ), $data['city'], $label );
		}

		return array(
			'lat'    => (float) $data['lat'],
			'long'   => (float) $data['lon'],
			'label'  => esc_html( $label ),
			'credit' => esc_html__( 'Location data provided by IP API', 'it-l10n-ithemes-security-pro' ),
		);
	}

	/**
	 * @inheritDoc
	 */
	public function is_available() {
		return true;
	}
}