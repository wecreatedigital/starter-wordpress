<?php

/**
 * Class ITSEC_Geolocator_IP_Info
 */
final class ITSEC_Geolocator_IP_Info implements ITSEC_Geolocator {

	const HOST = 'https://ipinfo.io/';

	/**
	 * @inheritDoc
	 */
	public function geolocate( $ip ) {

		$response = wp_remote_get( self::HOST . $ip );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );

		if ( ! $body || null === ( $data = json_decode( $body, true ) ) || empty( $data['loc'] ) ) {
			return new WP_Error( 'itsec-geolocate-ipinfo-invalid-response', __( 'Invalid Geolocation response from IP Info', 'it-l10n-ithemes-security-pro' ), compact( 'body', 'ip' ) );
		}

		$label = $data['country'];

		if ( ! empty( $data['city'] ) ) {
			/* translators: 1. City Name, 2. Country Name */
			$label = sprintf( _x( '%1$s, %2$s', 'Location', 'it-l10n-ithemes-security-pro' ), $data['city'], $label );
		}

		list( $lat, $long ) = explode( ',', $data['loc'] );

		return array(
			'lat'    => (float) $lat,
			'long'   => (float) $long,
			'label'  => esc_html( $label ),
			'credit' => esc_html__( 'Location data provided by IP Info', 'it-l10n-ithemes-security-pro' ),
		);
	}

	/**
	 * @inheritDoc
	 */
	public function is_available() {
		return true;
	}
}