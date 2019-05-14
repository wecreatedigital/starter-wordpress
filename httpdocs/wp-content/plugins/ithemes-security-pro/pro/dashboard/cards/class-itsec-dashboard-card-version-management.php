<?php

/**
 * Class ITSEC_Dashboard_Card_Version_Management
 */
class ITSEC_Dashboard_Card_Version_Management extends ITSEC_Dashboard_Card {
	/**
	 * @inheritDoc
	 */
	public function get_slug() {
		return 'version-management';
	}

	/**
	 * @inheritDoc
	 */
	public function get_label() {
		return __( 'Updates Summary', 'it-l10n-ithemes-security-pro' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_size() {
		return array(
			'minW'     => 1,
			'minH'     => 2,
			'maxW'     => 2,
			'maxH'     => 2,
			'defaultW' => 2,
			'defaultH' => 2,
		);
	}

	public function get_query_args() {
		$args = parent::get_query_args();

		$args['period'] = ITSEC_Dashboard_REST::get_period_arg();

		return $args;
	}

	/**
	 * @inheritDoc
	 */
	public function query_for_data( array $query_args, array $settings ) {

		if ( isset( $query_args['period'] ) ) {
			$period = $query_args['period'];
		} else {
			$qa_schema = $this->get_query_args();
			$period    = $qa_schema['period']['default'];
		}

		$events = ITSEC_Dashboard_Util::count_events( array( 'vm-update-core', 'vm-update-theme', 'vm-update-plugin' ), $period );

		if ( is_wp_error( $events ) ) {
			return $events;
		}

		$data = array(
			'counts' => array(),
			'all'    => 0,
		);

		foreach ( $events as $event => $count ) {
			$data['all'] += $count;

			$data['counts'][ str_replace( 'vm-update-', '', $event ) ] = $count;
		}

		return $data;
	}

	/**
	 * @inheritdoc
	 */
	public function get_links() {
		return array(
			array(
				'rel'   => ITSEC_Dashboard_REST::LINK_REL . 'logs',
				'href'  => network_admin_url( 'update-core.php' ),
				'title' => __( 'Manage Updates', 'it-l10n-ithemes-security-pro' ),
				'media' => 'text/html',
				'cap'   => 'update_core',
			),
		);
	}
}
