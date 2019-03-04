<?php

/**
 * Interface ITSEC_Static_Map_API
 */
interface ITSEC_Static_Map_API {

	/**
	 * Get the map for a location.
	 *
	 * @param array $config
	 *
	 * @return string|WP_Error URL to the image.
	 */
	public function get_map( array $config );

	/**
	 * Is this static map API available to be used.
	 *
	 * @return bool
	 */
	public function is_available();
}