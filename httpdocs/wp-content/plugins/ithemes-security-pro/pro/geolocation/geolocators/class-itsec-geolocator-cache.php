<?php

/**
 * Class ITSEC_Geolocator_Cache
 */
final class ITSEC_Geolocator_Cache implements ITSEC_Geolocator {

	/** @var ITSEC_Geolocator */
	private $geolocator;

	/**
	 * ITSEC_Geolocator_Cache constructor.
	 *
	 * @param ITSEC_Geolocator $geolocator
	 */
	public function __construct( ITSEC_Geolocator $geolocator ) { $this->geolocator = $geolocator; }

	/**
	 * @inheritDoc
	 */
	public function geolocate( $ip ) {

		global $wpdb;

		$id   = 0;
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->base_prefix}itsec_geolocation_cache WHERE `location_host` = %s LIMIT 1", $ip ) );

		if ( $rows ) {
			$r = $rows[0];

			if ( ! empty( $r->location_lat ) && ! empty( $r->location_long ) && strtotime( $r->location_time ) >= ITSEC_Core::get_current_time_gmt() - 2 * WEEK_IN_SECONDS ) {
				return array(
					'lat'    => (float) $r->location_lat,
					'long'   => (float) $r->location_long,
					'label'  => $r->location_label,
					'credit' => $r->location_credit,
				);
			}

			$id = $r->location_id;
		}

		$data = $this->geolocator->geolocate( $ip );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$for_db = array(
			'location_host'   => $ip,
			'location_lat'    => $data['lat'],
			'location_long'   => $data['long'],
			'location_label'  => $data['label'],
			'location_credit' => $data['credit'],
			'location_time'   => date( 'Y-m-d H:i:s', ITSEC_Core::get_current_time_gmt() ),
		);

		if ( $id ) {
			unset( $for_db['location_host'] );

			$wpdb->update( $wpdb->base_prefix . 'itsec_geolocation_cache', $for_db, array( 'location_id' => $id ) );
		} else {
			$wpdb->insert( $wpdb->base_prefix . 'itsec_geolocation_cache', $for_db );
		}

		return $data;
	}

	/**
	 * @inheritDoc
	 */
	public function is_available() {
		return $this->geolocator->is_available();
	}
}