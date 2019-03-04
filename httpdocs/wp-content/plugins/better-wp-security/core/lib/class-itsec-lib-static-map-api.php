<?php

require_once( dirname( __FILE__ ) . '/static-map-api/interface-itsec-static-map-api.php' );

/**
 * Class ITSEC_Lib_Static_Map_API
 */
class ITSEC_Lib_Static_Map_API {

	/** @var ITSEC_Static_Map_API */
	private static $api;

	/**
	 * Get the map.
	 *
	 * Sizing: If only one dimension is specified, it will scale the image proportionally.
	 *
	 * @param array $config {
	 *      Configuration array.
	 *
	 *      @type float  $lat    Latitude.
	 *      @type float  $long   Longitude.
	 *      @type int    $width  Desired width of the image.
	 *      @type int    $height Desired height of the image.
	 * }
	 *
	 * @return string|WP_Error The URL to the file, or a WP Error object.
	 */
	public static function get_map( $config ) {

		if ( ! is_array( $config ) || ! isset( $config['lat'], $config['long'] ) || ! is_numeric( $config['lat'] ) || ! is_numeric( $config['long'] ) ) {
			return new WP_Error( 'itsec-static-map-api-invalid-config', __( 'Invalid configuration for retrieving a static map image.', 'better-wp-security' ) );
		}

		if ( ! self::get_api() ) {
			return new WP_Error( 'itsec-static-map-api-no-providers', __( 'No provider was found to generate a static map image.', 'better-wp-security' ) );
		}

		if ( is_wp_error( $file = self::get_cached_map_or_fetch( $config ) ) ) {
			return $file;
		}

		return ITSEC_Lib::get_url_from_file( $file );
	}

	/**
	 * Get the resized map file.
	 *
	 * @param string $file Path to the full size image.
	 * @param array  $config
	 *
	 * @return string|WP_Error Either the path to the resized file or a WP_Error.
	 */
	private static function get_resized_map( $file, array $config ) {

		$width  = isset( $config['width'] ) ? (int) $config['width'] : null;
		$height = isset( $config['height'] ) ? (int) $config['height'] : null;

		if ( $width > 1000 || $height > 1000 ) {
			return new WP_Error( 'itsec-static-map-api-invalid-resize-dimensions', __( 'Maximum map dimensions is 1000px.', 'better-wp-security' ) );
		}

		$f_info            = pathinfo( $file );
		$f_info['dirname'] = trailingslashit( $f_info['dirname'] );

		$filename_resized = "{$f_info['dirname']}{$f_info['filename']}-{$width}x{$height}.{$f_info['extension']}";

		if ( file_exists( $filename_resized ) ) {
			return $filename_resized;
		}

		$editor = wp_get_image_editor( $file );

		if ( is_wp_error( $editor ) ) {
			return $editor;
		}

		// We're treating everything as @2x.
		if ( is_wp_error( $resized = $editor->resize( $width ? $width * 2 : null, $height ? $height * 2 : null, true ) ) ) {
			return $resized;
		}

		$saved = $editor->save( $filename_resized );

		if ( is_wp_error( $saved ) ) {
			return $saved;
		}

		return $filename_resized;
	}

	/**
	 * Get a cached map image or fetch.
	 *
	 * @param array $config
	 *
	 * @return string|WP_Error
	 */
	private static function get_cached_map_or_fetch( array $config ) {

		$dir = trailingslashit( ITSEC_Core::get_storage_dir( 'static-map', true ) );
		$key = wp_hash( $config['lat'] . $config['long'] );

		if ( isset( $config['width'] ) || isset( $config['height'] ) ) {
			$key .= sprintf( '-%sx%s', isset( $config['width'] ) ? $config['width'] : 1000, isset( $config['height'] ) ? $config['height'] : 1000 );
		}

		$file_name = "{$key}.png";

		if ( ! file_exists( $dir . $file_name ) ) {
			$url = self::get_api()->get_map( $config );

			if ( ! function_exists( 'download_url' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/file.php' );
			}

			if ( ! function_exists( 'download_url' ) ) {
				return new WP_Error( 'itsec-static-map-api-file-cache-no-download-url', __( 'The download_url() function was not found.', 'better-wp-security' ) );
			}

			$temp = download_url( $url );

			if ( is_wp_error( $temp ) ) {
				return $temp;
			}

			$checked = wp_check_filetype_and_ext( $temp, $file_name, array( 'png' => 'image/png' ) );

			if ( 'image/png' !== $checked['type'] ) {
				return $url;
			}

			rename( $temp, $dir . $file_name );
		}

		return $dir . $file_name;
	}

	/**
	 * Get the static map API
	 *
	 * @return ITSEC_Static_Map_API
	 */
	private static function get_api() {
		if ( null === self::$api ) {
			foreach ( self::get_apis() as $api ) {
				if ( $api->is_available() ) {
					self::$api = $api;
					break;
				}
			}
		}

		return self::$api;
	}

	/**
	 * Get the static map API providers.
	 *
	 * @return ITSEC_Static_Map_API[]
	 */
	private static function get_apis() {

		/**
		 * Filter the static map API providers.
		 *
		 * @param $apis ITSEC_Static_Map_API[]
		 */
		return apply_filters( 'itsec_static_map_apis', array() );
	}
}