<?php

/**
 * Class ITSEC_Geolocator_GeoPlugin
 */
final class ITSEC_Geolocator_GeoPlugin implements ITSEC_Geolocator {

	const HOST = 'http://www.geoplugin.net/json.gp?ip=';

	/**
	 * @inheritDoc
	 */
	public function geolocate( $ip ) {

		$response = wp_remote_get( self::HOST . $ip );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );

		if ( ! $body || null === ( $data = json_decode( $body, true ) ) || empty( $data['geoplugin_latitude'] ) || empty( $data['geoplugin_longitude'] ) ) {
			return new WP_Error( 'itsec-geolocate-geoplugin-invalid-response', __( 'Invalid Geolocation response from geoPlugin', 'it-l10n-ithemes-security-pro' ), compact( 'body', 'ip' ) );
		}

		$label = $data['geoplugin_countryName'];

		if ( ! empty( $data['geoplugin_city'] ) ) {
			/* translators: 1. City Name, 2. Country Name */
			$label = sprintf( _x( '%1$s, %2$s', 'Location', 'it-l10n-ithemes-security-pro' ), $data['geoplugin_city'], $label );
		}

		return array(
			'lat'    => (float) $data['geoplugin_latitude'],
			'long'   => (float) $data['geoplugin_longitude'],
			'label'  => esc_html( $label ),
			'credit' => wp_kses( $data['geoplugin_credit'], array( 'a' => array( 'href' ) ) ),
		);
	}

	/**
	 * @inheritDoc
	 */
	public function is_available() {
		return true;
	}
}