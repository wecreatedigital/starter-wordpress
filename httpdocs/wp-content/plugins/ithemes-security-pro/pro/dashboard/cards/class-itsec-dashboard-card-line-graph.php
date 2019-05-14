<?php

/**
 * Class ITSEC_Dashboard_Card_Line_Graph
 */
class ITSEC_Dashboard_Card_Line_Graph extends ITSEC_Dashboard_Card {

	/** @var string */
	private $slug;

	/** @var string */
	private $label;

	/** @var array */
	private $size;

	/** @var array */
	private $data_config;

	/**
	 * ITSEC_Dashboard_Card_Line_Graph constructor.
	 *
	 * @param string $slug
	 * @param string $label
	 * @param array  $size
	 * @param array  $data_config
	 */
	public function __construct( $slug, $label, array $data_config, array $size = array() ) {
		$this->slug        = $slug;
		$this->label       = $label;
		$this->data_config = $data_config;
		$this->size        = wp_parse_args( $size, array(
			'minW'     => 2,
			'minH'     => 2,
			'maxW'     => 3,
			'maxH'     => 3,
			'defaultW' => 2,
			'defaultH' => 2,
		) );
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

		$events = ITSEC_Dashboard_Util::query_events( ITSEC_Lib::flatten( wp_list_pluck( $this->data_config, 'events' ) ), $period );

		if ( is_wp_error( $events ) ) {
			return $events;
		}

		$data = array();

		foreach ( $this->data_config as $config ) {
			$key = implode( '--', (array) $config['events'] );

			$data[ $key ] = array(
				'data'  => array(),
				'label' => $config['label'],
			);

			foreach ( (array) $config['events'] as $event_name ) {
				foreach ( $events[ $event_name ] as $event ) {
					$date = ITSEC_Lib::to_rest_date( $event['time'] );

					if ( isset( $data[ $key ]['data'][ $date ] ) ) {
						$data[ $key ]['data'][ $date ]['y'] += $event['count'];
					} else {
						$data[ $key ]['data'][ $date ] = array(
							't' => $date,
							'y' => $event['count'],
						);
					}
				}
			}

			$data[ $key ]['data'] = array_values( $data[ $key ]['data'] );
		}

		return $data;
	}

	public function get_query_args() {
		$args = parent::get_query_args();

		$args['period'] = ITSEC_Dashboard_REST::get_period_arg();

		return $args;
	}

	/**
	 * @inheritDoc
	 */
	public function get_type() {
		return 'line';
	}

	/**
	 * @inheritDoc
	 */
	public function get_slug() {
		return $this->slug;
	}

	/**
	 * @inheritDoc
	 */
	public function get_label() {
		return $this->label;
	}

	/**
	 * @inheritDoc
	 */
	public function get_size() {
		return $this->size;
	}
}
