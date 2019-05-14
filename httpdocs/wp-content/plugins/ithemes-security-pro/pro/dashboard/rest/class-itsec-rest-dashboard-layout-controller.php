<?php

/**
 * Class ITSEC_REST_Dashboard_Layout_Controller
 */
class ITSEC_REST_Dashboard_Layout_Controller extends ITSEC_REST_Dashboard_Controller {

	/** @var string */
	private $parent_base;

	/** @var array */
	private $schema;

	public function __construct() {
		$this->namespace   = 'ithemes-security/v1';
		$this->rest_base   = 'layout';
		$this->parent_base = 'dashboards';
	}

	public function register_routes() {
		register_rest_route( $this->namespace, "{$this->parent_base}/(?P<id>[\\d]+)/{$this->rest_base}", array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
			),
			array(
				'methods'             => 'PUT',
				'callback'            => array( $this, 'update_item' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),

				// Treat this as a creatable so the required properties get validated.
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
			),
			'schema'        => array( $this, 'get_public_item_schema' ),
			'show_in_index' => false,
		) );
	}

	/**
	 * @inheritDoc
	 */
	public function get_item_permissions_check( $request ) {

		$id = (int) $request['id'];

		if ( ITSEC_Dashboard::CPT_DASHBOARD !== get_post_type( $id ) ) {
			return new WP_Error( 'rest_not_found', esc_html__( 'Not Found', 'it-l10n-ithemes-security-pro' ), array( 'status' => 404 ) );
		}

		return current_user_can( 'itsec_view_dashboard', $id );
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
	public function update_item_permissions_check( $request ) {

		if ( true !== ( $error = $this->get_item_permissions_check( $request ) ) ) {
			return $error;
		}

		return current_user_can( 'itsec_edit_dashboard', $request['id'] );
	}

	/**
	 * @inheritDoc
	 */
	public function update_item( $request ) {

		$updates = array();
		$invalid = array();

		foreach ( $request['cards'] as $card ) {
			$id   = $card['id'];
			$slug = get_post_meta( $id, ITSEC_Dashboard::META_CARD, true );

			if ( ITSEC_Dashboard_Util::get_card( $slug ) ) {
				if (
					ITSEC_Dashboard::CPT_CARD !== get_post_type( $id ) ||
					wp_get_post_parent_id( $id ) !== (int) $request['id'] ||
					get_post_meta( $id, ITSEC_Dashboard::META_CARD, true ) !== $card['card']
				) {
					$invalid[] = $id;
				} else {
					$updates[ $id ] = array(
						ITSEC_Dashboard::META_CARD_POSITION => $card['position'],
						ITSEC_Dashboard::META_CARD_SIZE     => $card['size'],
					);
				}
			} else {
				$updates[ $id ] = array(
					ITSEC_Dashboard::META_CARD_POSITION => $card['position'],
				);
			}
		}

		if ( $invalid ) {
			return new WP_Error( 'itsec-dashboard-layout-invalid-cards', esc_html__( 'Some Cards are Invalid', 'it-l10n-ithemes-security-pro' ), array( 'status' => 400, 'cards' => $invalid ) );
		}

		$errors = $success = 0;

		foreach ( $updates as $id => $update ) {
			foreach ( $update as $key => $value ) {
				$old = get_post_meta( $id, $key, true );

				if ( sanitize_meta( $key, $value, 'post', ITSEC_Dashboard::CPT_CARD ) === $old ) {
					continue;
				}

				if ( update_post_meta( $id, $key, ITSEC_Lib::slash( $value ) ) ) {
					$success ++;
				} else {
					$errors ++;
				}
			}
		}

		if ( $errors && ! $success ) {
			return new WP_Error( 'itsec-dashboard-layout-save-failed', esc_html__( 'Failed to save new layout.', 'it-l10n-ithemes-security-pro' ), array( 'status' => 500, ) );
		}

		if ( $errors ) {
			return new WP_Error( 'itsec-dashboard-layout-partial-save-failed', esc_html__( 'Failed to save layout for some items.', 'it-l10n-ithemes-security-pro' ), array( 'status' => 500 ) );
		}

		return $this->prepare_item_for_response( get_post( $request['id'] ), $request );
	}

	public function prepare_item_for_response( $item, $request ) {

		$items = array();

		foreach ( ITSEC_Dashboard_Util::get_dashboard_cards( $request['id'] ) as $post ) {
			$type = get_post_meta( $post->ID, ITSEC_Dashboard::META_CARD, true );

			if ( $type ) {
				$size     = get_post_meta( $post->ID, ITSEC_Dashboard::META_CARD_SIZE, true );
				$position = get_post_meta( $post->ID, ITSEC_Dashboard::META_CARD_POSITION, true );

				$items[] = array(
					'id'       => $post->ID,
					'card'     => ITSEC_Dashboard_Util::get_card( $type ) ? $type : 'unknown',
					'size'     => is_array( $size ) ? $size : array(),
					'position' => is_array( $position ) ? $position : array(),
				);
			}
		}

		return new WP_REST_Response( array(
			'cards' => $items,
		) );
	}

	/**
	 * @inheritDoc
	 */
	public function get_item_schema() {

		if ( $this->schema ) {
			return $this->schema;
		}

		$oneOfs = array(
			array(
				'type'       => 'object',
				'properties' => array(
					'id'       => array(
						'type'        => 'integer',
						'required'    => true,
						'arg_options' => array(
							'validation_callback' => array( __CLASS__, '_validate_card_id' ),
						),
					),
					'card'     => array(
						'type'     => 'string',
						'enum'     => array( 'unknown' ),
						'required' => true,
					),
					'size'     => array(
						'type'                 => 'object',
						'required'             => true,
						'properties'           => array(),
						'additionalProperties' => false,
					),
					'position' => array(
						'type'                 => 'object',
						'required'             => true,
						'properties'           => array(),
						'additionalProperties' => false,
					),
				)
			)
		);

		foreach ( ITSEC_Dashboard_Util::$breakpoints as $breakpoint ) {
			$oneOfs[0]['properties']['size']['properties'][ $breakpoint ] = array(
				'type'       => 'object',
				'properties' => array(
					'w' => array(
						'type'     => 'integer',
						'required' => true,
						'minimum'  => 1,
						'default'  => 1,
					),
					'h' => array(
						'type'     => 'integer',
						'required' => true,
						'minimum'  => 1,
						'default'  => 1,
					),
				),
			);

			$oneOfs[0]['properties']['position']['properties'][ $breakpoint ] = array(
				'type'       => 'object',
				'properties' => array(
					'x' => array(
						'type'     => 'integer',
						'required' => true,
						'minimum'  => 0,
					),
					'y' => array(
						'type'     => 'integer',
						'required' => true,
						'minimum'  => 0,
					),
				),
			);
		}

		foreach ( ITSEC_Dashboard_Util::get_registered_cards() as $card ) {
			$size = $card->get_size();

			$oneOf = array(
				'type'       => 'object',
				'properties' => array(
					'id'       => array(
						'type'        => 'integer',
						'required'    => true,
						'arg_options' => array(
							'validation_callback' => array( __CLASS__, '_validate_card_id' ),
						),
					),
					'card'     => array(
						'type'     => 'string',
						'enum'     => array( $card->get_slug() ),
						'required' => true,
					),
					'size'     => array(
						'type'                 => 'object',
						'required'             => true,
						'properties'           => array(),
						'additionalProperties' => false,
					),
					'position' => array(
						'type'                 => 'object',
						'required'             => true,
						'properties'           => array(),
						'additionalProperties' => false,
					),
				)
			);

			foreach ( ITSEC_Dashboard_Util::$breakpoints as $breakpoint ) {
				$oneOf['properties']['size']['properties'][ $breakpoint ] = array(
					'type'       => 'object',
					'properties' => array(
						'w' => array(
							'type'     => 'integer',
							'required' => true,
							#'minimum'  => $size['minW'],
							#'maximum'  => $size['maxW'],
							'default'  => $size['defaultW'],
						),
						'h' => array(
							'type'     => 'integer',
							'required' => true,
							#'minimum'  => $size['minH'],
							#'maximum'  => $size['maxH'],
							'default'  => $size['defaultH'],
						),
					),
				);

				$oneOf['properties']['position']['properties'][ $breakpoint ] = array(
					'type'       => 'object',
					'properties' => array(
						'x' => array(
							'type'     => 'integer',
							'required' => true,
							'minimum'  => 0,
						),
						'y' => array(
							'type'     => 'integer',
							'required' => true,
							'minimum'  => 0,
						),
					),
				);
			}

			$oneOfs[] = $oneOf;
		}

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'ithemes-security-dashboard-layout',
			'type'       => 'object',
			'properties' => array(
				'cards' => array(
					'type'     => 'array',
					'items'    => array(
						'oneOf' => $oneOfs,
					),
					'required' => true,
				)
			)
		);

		return $this->schema = $schema;
	}

	public static function _validate_card_id( $id, $request ) {
		return get_post_type( $id ) === ITSEC_Dashboard::CPT_CARD && wp_get_post_parent_id( $id ) === (int) $request['id'];
	}
}
