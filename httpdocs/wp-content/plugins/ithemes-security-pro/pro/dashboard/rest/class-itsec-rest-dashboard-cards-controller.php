<?php

/**
 * Class ITSEC_REST_Dashboard_Cards_Controller
 */
class ITSEC_REST_Dashboard_Cards_Controller extends ITSEC_REST_Dashboard_Controller {

	/** @var string */
	private $parent_base;

	/** @var ITSEC_REST_Dashboard_Card_Controller[] */
	private $card_controllers = array();

	/** @var ITSEC_Dashboard_Card[] */
	private $cards = array();

	/** @var ITSEC_REST_Dashboard_Unknown_Card_Controller */
	private $unknown;

	/** @var array */
	private $params = array();

	/**
	 * ITSEC_REST_Dashboard_Cards_Controller constructor.
	 */
	public function __construct() {
		$this->namespace   = 'ithemes-security/v1';
		$this->rest_base   = 'cards';
		$this->parent_base = 'dashboards';
	}

	public function register_routes() {
		register_rest_route( $this->namespace, "{$this->parent_base}/(?P<dashboard>[\\d]+)/{$this->rest_base}", array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_items' ),
			'permission_callback' => array( $this, 'get_items_permissions_check' ),
			'args'                => self::apply_validation_callbacks( $this->get_collection_params() ),
			'schema'              => array( $this, 'get_public_item_schema' ),
			'show_in_index'       => false,
		) );

		foreach ( ITSEC_Dashboard_Util::get_registered_cards() as $card ) {
			$this->cards[ $card->get_slug() ] = $card;

			$this->card_controllers[ $card->get_slug() ] = new ITSEC_REST_Dashboard_Card_Controller( $card );
			$this->card_controllers[ $card->get_slug() ]->register_routes();
		}

		$this->unknown = new ITSEC_REST_Dashboard_Unknown_Card_Controller();
		$this->unknown->register_routes();
	}

	/**
	 * @inheritDoc
	 */
	public function get_items_permissions_check( $request ) {

		$id = (int) $request['dashboard'];

		if ( ITSEC_Dashboard::CPT_DASHBOARD !== get_post_type( $id ) ) {
			return new WP_Error( 'rest_not_found', esc_html__( 'Not Found', 'it-l10n-ithemes-security-pro' ), array( 'status' => 404 ) );
		}

		if ( 'edit' === $request['context'] && ! current_user_can( 'itsec_edit_dashboard', $id ) ) {
			return new WP_Error( 'rest_forbidden_context', esc_html__( 'Sorry, you are not allowed to edit this dashboard.' ), array( 'status' => rest_authorization_required_code() ) );
		}

		if ( current_user_can( 'itsec_view_dashboard', $id ) ) {
			return true;
		}

		return new WP_Error( 'rest_cannot_view', esc_html__( 'Sorry, you do not have permission to view this dashboard.', array( 'status' => rest_authorization_required_code() ) ) );
	}

	/**
	 * @inheritDoc
	 */
	public function get_items( $request ) {

		$items = array();

		foreach ( ITSEC_Dashboard_Util::get_dashboard_cards( $request['dashboard'] ) as $post ) {
			if ( ! $type = get_post_meta( $post->ID, ITSEC_Dashboard::META_CARD, true ) ) {
				wp_delete_post( $post->ID, true );

				continue;
			}

			if ( isset( $this->card_controllers[ $type ] ) ) {
				$response = $this->card_controllers[ $type ]->prepare_item_for_response( $post, $request );

				if ( ! is_wp_error( $response ) ) {
					$items[] = $this->card_controllers[ $type ]->prepare_response_for_collection( $response );
				}
			} else {
				$response = $this->unknown->prepare_item_for_response( $post, $request );

				if ( ! is_wp_error( $response ) ) {
					$items[] = $this->unknown->prepare_response_for_collection( $response );
				}
			}
		}

		return new WP_REST_Response( $items );
	}

	/**
	 * @inheritDoc
	 */
	public function get_collection_params() {

		if ( $this->params ) {
			return $this->params;
		}

		$params = array(
			'context' => $this->get_context_param( array( 'default' => 'view' ) ),
			'cards'   => array(),
		);

		foreach ( $this->card_controllers as $slug => $card_controller ) {
			$params['cards'][ $slug ] = array(
				'type'                 => 'object',
				'additionalProperties' => array(
					'type'       => 'object',
					'properties' => $this->cards[ $slug ]->get_query_args(),
				),
			);
		}

		return $this->params = $params;
	}
}
