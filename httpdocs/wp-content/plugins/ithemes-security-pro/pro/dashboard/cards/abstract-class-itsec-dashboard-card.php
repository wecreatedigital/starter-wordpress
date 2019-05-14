<?php

/**
 * Class ITSEC_Dashboard_Card
 */
abstract class ITSEC_Dashboard_Card {

	/**
	 * Get the slug for this card.
	 *
	 * @return string
	 */
	abstract public function get_slug();

	/**
	 * Get the label for this card.
	 *
	 * @return string
	 */
	abstract public function get_label();

	/**
	 * Get the size constraints.
	 *
	 * - minW
	 * - minH
	 * - maxW
	 * - maxH
	 * - defaultW
	 * - defaultH
	 *
	 * @return array
	 */
	abstract public function get_size();

	/**
	 * Respond to the query for this card.
	 *
	 * @param array $query_args Any query arguments. Will need to be registered with {@see get_query_args()}
	 * @param array $settings   Settings for the instance. Will need to be registered with {@see get_settings_schema()}
	 *
	 * @return array
	 */
	abstract public function query_for_data( array $query_args, array $settings );

	/**
	 * Get the card type.
	 *
	 * @return string
	 */
	public function get_type() {
		return 'custom';
	}

	/**
	 * Get the maximum instances of this card allowed in the dashboard.
	 *
	 * @return int|null Number of instances, or null for unlimited.
	 */
	public function get_max() {
		return 1;
	}

	/**
	 * Get supported query args.
	 *
	 * Format of query parameter names => schema configurations.
	 *
	 * @return array
	 */
	public function get_query_args() {
		return array();
	}

	/**
	 * Get the schema for the settings.
	 *
	 * @return array
	 */
	public function get_settings_schema() {
		return array();
	}

	/**
	 * Get links to include on the response.
	 *
	 * @return array
	 */
	public function get_links() {
		return array();
	}
}
