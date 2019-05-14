<?php

/**
 * Class ITSEC_Static_Map_API_MapQuest
 */
class ITSEC_Static_Map_API_MapQuest implements ITSEC_Static_Map_API {

	// https://www.mapquestapi.com/staticmap/v5/map?key=KEY&center=Boston,MA&size=600,400@2x
	const HOST = 'https://www.mapquestapi.com/staticmap/v5/map';

	/**
	 * @inheritDoc
	 */
	public function get_map( array $config ) {
		$size = '';
		$size .= isset( $config['width'] ) ? $config['width'] : '1000';
		$size .= ',';
		$size .= isset( $config['height'] ) ? $config['height'] : '1000';
		$size .= '@2x';

		return add_query_arg( array(
			'center' => "{$config['lat']},{$config['long']}",
			'size'   => $size,
			'zoom'   => 14,
			'format' => 'png',
			'key'    => $this->get_api_key(),
		), self::HOST );
	}

	/**
	 * @inheritDoc
	 */
	public function is_available() {
		return (bool) $this->get_api_key();
	}

	/**
	 * Get the API key to use.
	 *
	 * @return string
	 */
	private function get_api_key() {
		return ITSEC_Modules::get_setting( 'fingerprinting', 'mapquest_api_key' );
	}
}