<?php
/**
 * Utility class to contain all transient related functions within LearnDash.
 *
 * @since 3.1
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LDLMS_Transients' ) ) {
	/**
	 * Class to create the instance.
	 */
	class LDLMS_Transients {

		/**
		 * Public constructor for class
		 *
		 * @since 3.1
		 */
		public function __construct() {
		}

		/**
		 * Get a Transient
		 *
		 * @since 3.1
		 *
		 * @param string $transient_key The transient key to retreive.
		  * @return mixed $transient_data the retreived transient data or false if expired.
		 */
		public static function get( $transient_key = '' ) {
			$transient_data = false;

			if ( ( ! empty( $transient_key ) ) && ( ! apply_filters( 'learndash_transients_disabled', LEARNDASH_TRANSIENTS_DISABLED, $transient_key ) ) ) {
				$transient_data = get_transient( $transient_key );
			}

			return $transient_data;
		}

		/**
		 * Utility function to interface with WP set_transient function. This function allow for
		 * filtering if to actually write the transient.
		 *
		 * @since 3.1
		 * @param string  $transient_key The transient key.
		 * @param mixed   $transient_data Data to store in transient.
		 * @param integer $transient_expire Expiration time for transient.
		 */
		public static function set( $transient_key = '', $transient_data = '', $transient_expire = MINUTE_IN_SECONDS ) {
			if ( ( ! empty( $transient_key ) ) && ( ! apply_filters( 'learndash_transients_disabled', LEARNDASH_TRANSIENTS_DISABLED, $transient_key ) ) ) {
				return set_transient( $transient_key, $transient_data, $transient_expire );
			} 
		}

		public static function purge_all() {
			if ( ! apply_filters( 'learndash_transients_disabled', LEARNDASH_TRANSIENTS_DISABLED, 'learndash_all_purge' ) ) {
				global $wpdb;

				$sql_str = 'DELETE FROM ' . $wpdb->options . " WHERE option_name LIKE '_transient_learndash_%' OR option_name LIKE '_transient_timeout_learndash_%'";
				$wpdb->query( $sql_str );
			}
		}

		// End of functions.
	}
}
