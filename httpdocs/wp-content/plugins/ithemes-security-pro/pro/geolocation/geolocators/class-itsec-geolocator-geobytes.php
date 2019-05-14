<?php

/**
 * Class ITSEC_Geolocator_Geobytes
 */
final class ITSEC_Geolocator_Geobytes implements ITSEC_Geolocator {

	const HOST = 'http://gd.geobytes.com/GetCityDetails?fqcn=';

	/**
	 * @inheritDoc
	 */
	public function geolocate( $ip ) {

		$response = wp_remote_get( self::HOST . $ip );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );

		if ( ! $body || null === ( $data = json_decode( $body, true ) ) || empty( $data['geobyteslatitude'] ) || empty( $data['geobyteslongitude'] ) ) {
			return new WP_Error( 'itsec-geolocate-geobytes-invalid-response', __( 'Invalid Geolocation response from Geobytes', 'it-l10n-ithemes-security-pro' ), compact( 'body', 'ip' ) );
		}

		$label = $data['geobytescountry'];

		if ( ! empty( $data['geobytesregion'] ) ) {
			/* translators: 1. City Name, 2. Country Name */
			$label = sprintf( _x( '%1$s, %2$s', 'Location', 'it-l10n-ithemes-security-pro' ), $data['geobytesregion'], $label );
		}

		return array(
			'lat'    => (float) $data['geobyteslatitude'],
			'long'   => (float) $data['geobyteslongitude'],
			'label'  => esc_html( $label ),
			'credit' => esc_html__( 'Location data provided by Geobytes', 'it-l10n-ithemes-security-pro' ),
		);
	}

	/**
	 * @inheritDoc
	 */
	public function is_available() {
		return true;
	}
}