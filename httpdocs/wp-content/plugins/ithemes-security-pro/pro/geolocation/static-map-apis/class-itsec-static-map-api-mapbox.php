<?php

/**
 * Class ITSEC_Static_Map_API_MapBox
 */
class ITSEC_Static_Map_API_Mapbox implements ITSEC_Static_Map_API {

	const URL = 'https://api.mapbox.com/styles/v1/{username}/{style_id}/static/{lon},{lat},{zoom},{bearing},{pitch}/{width}x{height}{@2x}';

	/**
	 * @inheritDoc
	 */
	public function get_map( array $config ) {

		$replace = array(
			'{username}' => 'mapbox',
			'{style_id}' => 'light-v9',
			'{lon}'      => $config['long'],
			'{lat}'      => $config['lat'],
			'{zoom}'     => 12,
			'{bearing}'  => 0,
			'{pitch}'    => 0,
			'{width}'    => isset( $config['width'] ) ? $config['width'] : 1000,
			'{height}'   => isset( $config['height'] ) ? $config['height'] : 1000,
			'{@2x}'      => '@2x',
		);

		/**
		 * Filter the configuration for the MapBox API request.
		 *
		 * @param array $replace
		 */
		$replace = apply_filters( 'itsec_static_map_api_mapbox_config', $replace );

		return add_query_arg( 'access_token', $this->get_access_token(), str_replace( array_keys( $replace ), $replace, self::URL ) );
	}

	/**
	 * @inheritDoc
	 */
	public function is_available() {
		return (bool) $this->get_access_token();
	}

	/**
	 * Get the API key to use.
	 *
	 * @return string
	 */
	private function get_access_token() {
		return ITSEC_Modules::get_setting( 'fingerprinting', 'mapbox_access_token' );
	}
}