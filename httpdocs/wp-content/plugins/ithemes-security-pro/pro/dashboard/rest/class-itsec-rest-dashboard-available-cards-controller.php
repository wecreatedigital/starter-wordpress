<?php

class ITSEC_REST_Dashboard_Available_Cards_Controller extends ITSEC_REST_Dashboard_Controller {

	public function __construct() {
		$this->namespace = 'ithemes-security/v1';
		$this->rest_base = 'dashboard-available-cards';
	}

	public function register_routes() {
		register_rest_route( $this->namespace, $this->rest_base, array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_items' ),
			'permission_callback' => array( $this, 'get_items_permissions_check' ),
			'schema'              => array( $this, 'get_public_item_schema' ),
		) );

		register_rest_route( $this->namespace, "{$this->rest_base}/(?P<card>[\\w_-]+)", array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_item' ),
			'permission_callback' => array( $this, 'get_item_permissions_check' ),
			'schema'              => array( $this, 'get_public_item_schema' ),
		) );
	}

	/**
	 * @inheritDoc
	 */
	public function get_items_permissions_check( $request ) {
		return current_user_can( 'itsec_create_dashboards' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_items( $request ) {

		$data = array();

		foreach ( ITSEC_Dashboard_Util::get_registered_cards() as $card ) {
			$data[] = $this->prepare_response_for_collection( $this->prepare_item_for_response( $card, $request ) );
		}

		return new WP_REST_Response( $data );
	}

	/**
	 * @inheritDoc
	 */
	public function get_item_permissions_check( $request ) {
		if ( current_user_can( 'itsec_create_dashboards' ) ) {
			return true;
		}

		return ITSEC_Dashboard_Util::can_access_card( $request['card'] ) ? true : ITSEC_Dashboard_REST::not_found_error();
	}

	/**
	 * @inheritDoc
	 */
	public function get_item( $request ) {

		$cards = ITSEC_Dashboard_Util::get_registered_cards();

		$slug = $request['card'];

		foreach ( $cards as $maybe_card ) {
			if ( $maybe_card->get_slug() === $slug ) {
				return $this->prepare_item_for_response( $maybe_card, $request );
			}
		}

		return ITSEC_Dashboard_REST::not_found_error( new WP_Error( 'rest_not_found', esc_html__( 'Card Not Found', 'it-l10n-ithemes-security-pro' ), array( 'status' => WP_Http::NOT_FOUND ) ) );
	}

	/**
	 * @inheritDoc
	 *
	 * @param ITSEC_Dashboard_Card $item
	 */
	public function prepare_item_for_response( $item, $request ) {
		$size = $item->get_size();

		$response = new WP_REST_Response( array(
			'slug'       => $item->get_slug(),
			'label'      => $item->get_label(),
			'type'       => $item->get_type(),
			'size'       => array(
				'minW'     => $size['minW'],
				'minH'     => $size['minH'],
				'maxW'     => $size['maxW'],
				'maxH'     => $size['maxH'],
				'defaultW' => $size['defaultW'],
				'defaultH' => $size['defaultH'],
			),
			'max'        => $item->get_max(),
			'settings'   => $item->get_settings_schema(),
			'query_args' => $item->get_query_args(),
		) );
		$response->add_link( 'self', rest_url( "{$this->namespace}/{$this->rest_base}/{$item->get_slug()}" ) );

		return $response;
	}

	/**
	 * @inheritDoc
	 */
	public function get_item_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'ithemes-security-dashboard-available-card',
			'type'       => 'object',
			'properties' => array(
				'slug'       => array(
					'type' => 'string',
				),
				'label'      => array(
					'type' => 'string',
				),
				'type'       => array(
					'type' => 'string',
					'enum' => array( 'line', 'doughnut', 'custom' )
				),
				'size'       => array(
					'type'       => 'object',
					'properties' => array(
						'minW'     => array(
							'type' => 'integer',
						),
						'minH'     => array(
							'type' => 'integer',
						),
						'maxW'     => array(
							'type' => 'integer',
						),
						'maxH'     => array(
							'type' => 'integer',
						),
						'defaultW' => array(
							'type' => 'integer',
						),
						'defaultH' => array(
							'type' => 'integer',
						),
					)
				),
				'max'        => array(
					'type'    => 'integer',
					'minimum' => 1,
				),
				'settings'   => array(
					'type' => 'object',
				),
				'query_args' => array(
					'type' => 'object',
				),
			)
		);
	}
}
