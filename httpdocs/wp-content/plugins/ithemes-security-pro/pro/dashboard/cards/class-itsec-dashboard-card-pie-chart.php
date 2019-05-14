<?php

/**
 * Class ITSEC_Dashboard_Card_Pie_Chart
 */
class ITSEC_Dashboard_Card_Pie_Chart extends ITSEC_Dashboard_Card {

	/** @var string */
	private $slug;

	/** @var string */
	private $label;

	/** @var array */
	private $data_config;

	/** @var array */
	private $size;

	/** @var array */
	private $options;

	/**
	 * ITSEC_Dashboard_Card_Line_Graph constructor.
	 *
	 * @param string $slug
	 * @param string $label
	 * @param array  $data_config
	 * @param array  $options
	 */
	public function __construct( $slug, $label, array $data_config, array $options = array() ) {
		$this->slug        = $slug;
		$this->label       = $label;
		$this->data_config = $data_config;

		$options = $this->options = wp_parse_args( $options, array(
			'size'            => array(),
			'dated'           => false,
			'circle_callback' => null,
			'circle_label'    => __( 'Total', 'it-l10n-ithemes-security-pro' ),
		) );

		$this->size = wp_parse_args( $options['size'], array(
			'minW'     => 1,
			'minH'     => 2,
			'maxW'     => 2,
			'maxH'     => 2,
			'defaultW' => 1,
			'defaultH' => 2,
		) );
	}

	/**
	 * @inheritDoc
	 */
	public function query_for_data( array $query_args, array $settings ) {

		if ( ! $this->options['dated'] ) {
			$period = false;
		} elseif ( isset( $query_args['period'] ) ) {
			$period = $query_args['period'];
		} else {
			$qa_schema = $this->get_query_args();
			$period    = $qa_schema['period']['default'];
		}

		$events = ITSEC_Dashboard_Util::query_events( ITSEC_Lib::flatten( wp_list_pluck( $this->data_config, 'events' ) ), $period );

		if ( is_wp_error( $events ) ) {
			return $events;
		}

		$data = array(
			'data'         => array(),
			'circle_sum'   => 0,
			'circle_label' => $this->options['circle_label'],
		);

		foreach ( $this->data_config as $config ) {
			$key = implode( '--', (array) $config['events'] );

			$data['data'][ $key ] = array(
				'data'  => array(),
				'sum'   => 0,
				'label' => $config['label'],
			);

			foreach ( (array) $config['events'] as $event_name ) {
				foreach ( $events[ $event_name ] as $event ) {
					$date = ITSEC_Lib::to_rest_date( $event['time'] );

					$data['circle_sum']          += $event['count'];
					$data['data'][ $key ]['sum'] += $event['count'];

					if ( isset( $data[ $key ]['data'][ $date ] ) ) {
						$data[ $key ]['data'][ $date ]['y'] += $event['count'];
					} else {
						$data[ $key ]['data'][ $date ] = array(
							'x' => $date,
							'y' => $event['count'],
						);
					}
				}
			}

			$data[ $key ]['data'] = array_values( $data[ $key ]['data'] );
		}

		if ( $this->options['circle_callback'] ) {
			$data['circle_sum'] = call_user_func( $this->options['circle_callback'], $query_args, $settings );
		}

		return $data;
	}

	public function get_query_args() {
		$args = parent::get_query_args();

		if ( $this->options['dated'] ) {
			$args['period'] = ITSEC_Dashboard_REST::get_period_arg();
		}

		return $args;
	}

	/**
	 * @inheritDoc
	 */
	public function get_type() {
		return 'pie';
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
