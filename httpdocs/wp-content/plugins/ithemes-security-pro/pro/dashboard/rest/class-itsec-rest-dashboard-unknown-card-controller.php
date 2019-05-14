<?php

class ITSEC_REST_Dashboard_Unknown_Card_Controller extends ITSEC_REST_Dashboard_Controller {

	/** @var string */
	private $parent_base;

	/**
	 * ITSEC_REST_Dashboard_Unknown_Card_Controller constructor.
	 */
	public function __construct() {
		$this->namespace   = 'ithemes-security/v1';
		$this->rest_base   = 'cards';
		$this->parent_base = 'dashboards';
	}

	/**
	 * @inheritDoc
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, "{$this->parent_base}/(?P<dashboard>[\\d]+)/{$this->rest_base}/unknown/(?P<id>[\\d]+)", array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'                => array(
					'context' => $this->get_context_param( array( 'default' => 'view' ) )
				),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_item' ),
				'permission_callback' => array( $this, 'delete_item_permissions_check' ),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
	}

	/**
	 * @inheritDoc
	 */
	public function get_item_permissions_check( $request ) {

		$dashboard_id = (int) $request['dashboard'];

		if ( ITSEC_Dashboard::CPT_DASHBOARD !== get_post_type( $dashboard_id ) ) {
			return new WP_Error( 'rest_not_found', esc_html__( 'Dashboard Not Found', 'it-l10n-ithemes-security-pro' ), array( 'status' => 404 ) );
		}

		if ( ! current_user_can( 'itsec_view_dashboard', $dashboard_id ) ) {
			return false;
		}

		$id = (int) $request['id'];

		if (
			ITSEC_Dashboard::CPT_CARD !== get_post_type( $id ) ||
			wp_get_post_parent_id( $id ) !== (int) $request['dashboard'] ||
			ITSEC_Dashboard_Util::get_card( get_post_meta( $id, ITSEC_Dashboard::META_CARD, true ) )
		) {
			return new WP_Error( 'rest_not_found', esc_html__( 'Not Found', 'it-l10n-ithemes-security-pro' ), array( 'status' => 404 ) );
		}

		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function get_item( $request ) {
		return $this->prepare_item_for_response( get_post( $request['id'] ), $request );
	}

	/**
	 * @inheritDoc
	 */
	public function delete_item_permissions_check( $request ) {
		if ( true !== ( $error = $this->get_item_permissions_check( $request ) ) ) {
			return $error;
		}

		return current_user_can( 'itsec_edit_dashboard', $request['dashboard'] );
	}

	/**
	 * @inheritDoc
	 */
	public function delete_item( $request ) {
		if ( ! wp_delete_post( (int) $request['id'], true ) ) {
			return new WP_Error( 'rest_cannot_delete', __( 'The dashboard card cannot be deleted.', 'it-l10n-ithemes-security-pro' ), array( 'status' => 500 ) );
		}

		return new WP_REST_Response( null, WP_Http::NO_CONTENT );
	}

	/**
	 * @inheritDoc
	 *
	 * @param WP_Post $item
	 */
	public function prepare_item_for_response( $item, $request ) {

		if ( ITSEC_Dashboard_Util::get_card( get_post_meta( $item->ID, ITSEC_Dashboard::META_CARD, true ) ) ) {
			return new WP_Error( 'rest_not_found', esc_html__( 'Not Found', 'it-l10n-ithemes-security-pro' ), array( 'status' => 404 ) );
		}

		$prepared = array(
			'id'        => $item->ID,
			'card'      => 'unknown',
			'original'  => get_post_meta( $item->ID, ITSEC_Dashboard::META_CARD, true ),
			'dashboard' => (int) $item->post_parent,
		);

		$self     = "/{$this->namespace}/{$this->parent_base}/{$request['dashboard']}/cards/unknown/{$item->ID}";
		$response = new WP_REST_Response( $prepared );
		$response->add_link( 'self', rest_url( $self ), array(
			'targetHints' => array(
				'allow' => $this->build_allow_target_hints( $self, array( 'dashboard' => $request['dashboard'], 'id' => $item->ID ) ),
			),
		) );

		return $response;
	}

	/**
	 * @inheritDoc
	 */
	public function get_item_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'ithemes-security-dashboard-card-unknown',
			'type'       => 'object',
			'properties' => array(
				'id'        => array(
					'type'     => 'integer',
					'readonly' => true,
					'context'  => array( 'view', 'edit', 'embed' ),
				),
				'card'      => array(
					'type'     => 'string',
					'readonly' => true,
					'context'  => array( 'view', 'edit', 'embed' ),
					'enum'     => array( 'unknown' ),
				),
				'original'  => array(
					'type'     => 'string',
					'readonly' => true,
					'context'  => array( 'view', 'edit', 'embed' ),
				),
				'dashboard' => array(
					'type'     => 'integer',
					'readonly' => true,
					'context'  => array( 'view', 'edit', 'embed' ),
				),
			)
		);
	}
}
