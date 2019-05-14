<?php

class ITSEC_REST_Dashboard_Card_Controller extends ITSEC_REST_Dashboard_Controller {

	/** @var ITSEC_Dashboard_Card */
	private $card;

	/** @var string */
	private $parent_base;

	/**
	 * ITSEC_REST_Dashboard_Card_Controller constructor.
	 *
	 * @param ITSEC_Dashboard_Card $card
	 */
	public function __construct( ITSEC_Dashboard_Card $card ) {
		$this->card        = $card;
		$this->namespace   = 'ithemes-security/v1';
		$this->rest_base   = 'cards';
		$this->parent_base = 'dashboards';
	}

	/**
	 * @inheritDoc
	 */
	public function register_routes() {
		register_rest_route( $this->namespace, "{$this->parent_base}/(?P<dashboard>[\\d]+)/{$this->rest_base}/(?P<card>{$this->card->get_slug()})", array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'create_item' ),
			'permission_callback' => array( $this, 'create_item_permissions_check' ),
			'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
			'schema'              => array( $this, 'get_public_item_schema' ),
			'show_in_index'       => false,
		) );

		register_rest_route( $this->namespace, "{$this->parent_base}/(?P<dashboard>[\\d]+)/{$this->rest_base}/(?P<card>{$this->card->get_slug()})/(?P<id>[\\d]+)", array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'                => array_merge(
					array( 'context' => $this->get_context_param( array( 'default' => 'view' ) ) ),
					$this->card->get_query_args()
				),
			),
			array(
				'methods'             => 'PUT',
				'callback'            => array( $this, 'update_item' ),
				'permission_callback' => array( $this, 'update_item_permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_item' ),
				'permission_callback' => array( $this, 'delete_item_permissions_check' ),
			),
			'schema'        => array( $this, 'get_public_item_schema' ),
			'show_in_index' => false,
		) );

		foreach ( $this->card->get_links() as $i => $link ) {
			if ( ! isset( $link['endpoint'] ) && ! isset( $link['route'] ) ) {
				continue;
			}

			$route = "{$this->parent_base}/(?P<dashboard>[\\d]+)/{$this->rest_base}/(?P<card>{$this->card->get_slug()})/(?P<id>[\\d]+)/";

			if ( isset( $link['route'] ) ) {
				$route .= $link['route'];
			} else {
				$route .= $link['endpoint'];
			}

			$methods = isset( $link['methods'] ) ? $link['methods'] : WP_REST_Server::CREATABLE;

			register_rest_route( $this->namespace, $route, array(
				'methods'             => $methods,
				'callback'            => array( $this, 'card_request' ),
				'permission_callback' => array( $this, 'card_request_permissions_check' ),
				'show_in_index'       => false,
				'itsec_id'            => $i,
			) );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function create_item_permissions_check( $request ) {

		$id = (int) $request['dashboard'];

		if ( ITSEC_Dashboard::CPT_DASHBOARD !== get_post_type( $id ) ) {
			return ITSEC_Dashboard_REST::not_found_error(
				new WP_Error( 'rest_not_found', esc_html__( 'Dashboard Not Found', 'it-l10n-ithemes-security-pro' ), array( 'status' => 404 ) )
			);
		}

		if ( current_user_can( 'itsec_edit_dashboard', $id ) ) {
			return true;
		}

		return ITSEC_Dashboard_REST::not_found_error(
			new WP_Error( 'rest_cannot_create', esc_html__( 'Sorry, you are not allowed to add cards to this dashboard.', 'it-l10n-ithemes-security-pro' ) )
		);
	}

	/**
	 * @inheritDoc
	 */
	public function create_item( $request ) {

		if ( $max = $this->card->get_max() ) {
			$query = new WP_Query( array(
				'post_type'      => ITSEC_Dashboard::CPT_CARD,
				'post_parent'    => $request['dashboard'],
				'fields'         => 'ids',
				'posts_per_page' => 1,
				'meta_query'     => array(
					array(
						'key'   => ITSEC_Dashboard::META_CARD,
						'value' => $request['card'],
					)
				)
			) );

			if ( $query->found_posts >= $max ) {
				return new WP_Error( 'itsec_dashboard_maximum_cards', esc_html__( 'This card cannot be added to the dashboard another time.', 'it-l10n-ithemes-security-pro' ), array( 'status' => 400 ) );
			}
		}

		$data = $this->prepare_item_for_database( $request );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$post_id = wp_insert_post( ITSEC_Lib::slash( $data ), true );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$request->set_param( 'context', 'edit' );

		$response = $this->prepare_item_for_response( get_post( $post_id ), $request );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$links = $response->get_links();

		$response->set_status( WP_Http::CREATED );
		$response->header( 'Location', $links['self'][0]['href'] );

		return $response;
	}

	/**
	 * @inheritDoc
	 */
	public function get_item_permissions_check( $request ) {

		$dashboard_id = (int) $request['dashboard'];

		if ( ITSEC_Dashboard::CPT_DASHBOARD !== get_post_type( $dashboard_id ) ) {
			return ITSEC_Dashboard_REST::not_found_error(
				new WP_Error( 'rest_not_found', esc_html__( 'Dashboard Not Found', 'it-l10n-ithemes-security-pro' ), array( 'status' => 404 ) )
			);
		}

		if ( ! current_user_can( 'itsec_view_dashboard', $dashboard_id ) ) {
			return ITSEC_Dashboard_REST::not_found_error(
				new WP_Error( 'rest_cannot_view', esc_html__( 'Sorry, you do not have permission to view this dashboard.', 'it-l10n-ithemes-security-pro' ), array( 'status' => rest_authorization_required_code() ) )
			);
		}

		$id = (int) $request['id'];

		if (
			ITSEC_Dashboard::CPT_CARD !== get_post_type( $id ) ||
			wp_get_post_parent_id( $id ) !== (int) $request['dashboard'] ||
			get_post_meta( $id, ITSEC_Dashboard::META_CARD, true ) !== $this->card->get_slug()
		) {
			return ITSEC_Dashboard_REST::not_found_error(
				new WP_Error( 'rest_not_found', esc_html__( 'Dashboard Card Not Found', 'it-l10n-ithemes-security-pro' ), array( 'status' => 404 ) )
			);
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
	public function update_item_permissions_check( $request ) {
		if ( true !== ( $error = $this->get_item_permissions_check( $request ) ) ) {
			return $error;
		}

		if ( current_user_can( 'itsec_edit_dashboard', $request['dashboard'] ) ) {
			return true;
		}

		return ITSEC_Dashboard_REST::not_found_error(
			new WP_Error( 'rest_cannot_edit', esc_html__( 'Sorry, you do not have permission to edit this dashboard card.', 'it-l10n-ithemes-security-pro' ), array( 'status' => rest_authorization_required_code() ) )
		);
	}

	/**
	 * @inheritDoc
	 */
	public function update_item( $request ) {

		$data = $this->prepare_item_for_database( $request );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$post_id = wp_update_post( array_merge( array( 'ID' => (int) $request['id'] ), ITSEC_Lib::slash( $data ) ), true );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$request->set_param( 'context', 'edit' );

		return $this->prepare_item_for_response( get_post( $post_id ), $request );
	}

	/**
	 * @inheritDoc
	 */
	public function delete_item_permissions_check( $request ) {
		if ( true !== ( $error = $this->get_item_permissions_check( $request ) ) ) {
			return $error;
		}

		if ( current_user_can( 'itsec_edit_dashboard', $request['dashboard'] ) ) {
			return true;
		}

		return ITSEC_Dashboard_REST::not_found_error(
			new WP_Error( 'rest_cannot_edit', esc_html__( 'Sorry, you do not have permission to delete this dashboard card.', 'it-l10n-ithemes-security-pro' ), array( 'status' => rest_authorization_required_code() ) )
		);
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
	 * @inheritdoc
	 */
	protected function prepare_item_for_database( $request ) {

		$data = array(
			'post_type'   => ITSEC_Dashboard::CPT_CARD,
			'post_parent' => $request['dashboard'],
			'post_status' => 'publish',
			'post_author' => get_current_user_id(),
			'meta_input'  => array(
				ITSEC_Dashboard::META_CARD => $this->card->get_slug(),
			),
		);

		if ( isset( $request['settings'] ) && $this->card->get_settings_schema() ) {
			$data['meta_input'][ ITSEC_Dashboard::META_CARD_SETTINGS ] = $request['settings'];
		}

		return $data;
	}

	/**
	 * @inheritDoc
	 *
	 * @param WP_Post $item
	 */
	public function prepare_item_for_response( $item, $request ) {

		if ( $this->card->get_slug() !== get_post_meta( $item->ID, ITSEC_Dashboard::META_CARD, true ) ) {
			return new WP_Error( 'rest_not_found', esc_html__( 'Not Found', 'it-l10n-ithemes-security-pro' ), array( 'status' => 404 ) );
		}

		$prepared = array(
			'id'        => $item->ID,
			'card'      => $this->card->get_slug(),
			'dashboard' => (int) $item->post_parent,
			'settings'  => get_post_meta( $item->ID, ITSEC_Dashboard::META_CARD_SETTINGS, true ),
		);

		if ( ! is_array( $prepared['settings'] ) ) {
			$prepared['settings'] = array();
		}

		if ( in_array( 'data', $this->get_fields_for_response( $request ), true ) ) {
			if ( array_key_exists( 'dashboard', $request->get_url_params() ) ) {
				$params = wp_parse_args( $request->get_query_params(), $request->get_default_params() );
			} else {
				$defaults = array();

				foreach ( $this->card->get_query_args() as $param => $query_arg ) {
					$defaults[ $param ] = isset( $query_arg['default'] ) ? $query_arg['default'] : null;
				}

				$params = isset( $request['cards'][ $this->card->get_slug() ][ $item->ID ] ) ? $request['cards'][ $this->card->get_slug() ][ $item->ID ] : array();
				$params = wp_parse_args( $params, $defaults );
			}

			$data = $this->card->query_for_data( $params, $prepared['settings'] );

			$prepared['data'] = is_wp_error( $data ) ? array() : $data;
		}

		if ( ! $this->card->get_settings_schema() ) {
			unset( $prepared['settings'] );
		}

		$prepared = $this->filter_response_by_context( $prepared, $request['context'] );

		$self     = "/{$this->namespace}/{$this->parent_base}/{$request['dashboard']}/cards/{$this->card->get_slug()}/{$item->ID}";
		$response = new WP_REST_Response( $prepared );
		$response->add_link( 'self', rest_url( $self ), array(
			'targetHints' => array(
				'allow' => $this->build_allow_target_hints( $self, array( 'dashboard' => $request['dashboard'], 'id' => $item->ID ) ),
			),
		) );
		$response->add_link( 'create-form', rest_url( "/{$this->namespace}/{$this->parent_base}/{$request['dashboard']}/cards/{$this->card->get_slug()}" ) );
		$response->add_link(
			'about',
			rest_url( "{$this->namespace}/dashboard-available-cards/{$this->card->get_slug()}" ),
			array( 'embeddable' => true )
		);

		foreach ( $this->card->get_links() as $link_id => $link ) {
			if ( ! $this->check_card_request_permission( $request, $link, $link_id ) ) {
				continue;
			}

			if ( isset( $link['href'] ) ) {
				$href = $link['href'];
			} elseif ( isset( $link['endpoint'] ) ) {
				$href = rest_url( $self . '/' . $link['endpoint'] );
			} elseif ( isset( $link['route'] ) ) {
				$href = rest_url( $self . '/' . preg_replace( '/\(.[^<*]<(\w+)>[^<.]*\)/', '{$1}', $link['route'] ) );
			} else {
				continue;
			}

			$attr = $link;
			unset( $attr['rel'], $attr['href'], $attr['endpoint'], $attr['cap'], $attr['callback'], $attr['permission_callback'], $attr['require_write'], $attr['route'] );

			$response->add_link( $link['rel'], $href, $attr );
		}

		return $response;
	}

	private function check_card_request_permission( WP_REST_Request $request, array $link, $link_id ) {

		if ( isset( $link['require_write'] ) && ! $this->update_item_permissions_check( $request ) ) {
			return false;
		}

		if ( isset( $link['cap'] ) && ! current_user_can( $link['cap'] ) ) {
			return false;
		}

		if ( ! isset( $link['permission_callback'] ) ) {
			return true;
		}

		if ( isset( $link['route'] ) ) {
			return true; // We can't verify dynamic routes.
		}

		if ( isset( $link['methods'] ) ) {
			$method = is_array( $link['methods'] ) ? $link['methods'][0] : $link['methods'];
		} else {
			$method = WP_REST_Server::CREATABLE;
		}

		$route = trailingslashit( $request->get_route() ) . $link['endpoint'];

		$permission_request = new WP_REST_Request( $method, $route, array_merge( $request->get_attributes(), array(
			'itsec_id' => $link_id,
		) ) );

		return true === call_user_func( $link['permission_callback'], $permission_request );
	}

	/**
	 * Perform a card action like initiating a backup or updating a plugin.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function card_request( $request ) {

		if ( ! ( $found = $this->get_action_link( $request ) ) || ! isset( $found['callback'] ) ) {
			return new WP_Error( 'rest_not_found', esc_html__( 'Card route not found.', 'it-l10n-ithemes-security-pro' ), array( 'status' => WP_Http::NOT_FOUND ) );
		}

		$callback = $found['callback'];
		$settings = get_post_meta( $request['id'], ITSEC_Dashboard::META_CARD_SETTINGS, true );

		$retval = call_user_func( $callback, $request, $settings );

		if ( is_wp_error( $retval ) ) {
			return $retval;
		}

		if ( null === $retval ) {
			return new WP_REST_Response( null, WP_Http::NO_CONTENT );
		}

		return new WP_REST_Response( $retval );
	}

	/**
	 * Perform a permissions check.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return bool|WP_Error
	 */
	public function card_request_permissions_check( $request ) {

		if ( ! $found = $this->get_action_link( $request ) ) {
			return ITSEC_Dashboard_REST::not_found_error(
				new WP_Error( 'rest_not_found', esc_html__( 'Action not found.', 'it-l10n-ithemes-security-pro' ), array( 'status' => WP_Http::NOT_FOUND ) )
			);
		}

		if ( true !== ( $error = $this->get_item_permissions_check( $request ) ) ) {
			return $error;
		}

		if ( isset( $found['require_write'] ) && true !== ( $error = $this->update_item_permissions_check( $request ) ) ) {
			return $error;
		}

		$forbidden = new WP_Error( 'rest_cannot_perform_action', esc_html__( 'Sorry, you do not have permission to perform this action.', 'it-l10n-ithemes-security-pro' ), array( 'status' => rest_authorization_required_code() ) );

		if ( isset( $found['cap'] ) && ! current_user_can( $found['cap'] ) ) {
			return ITSEC_Dashboard_REST::not_found_error( $forbidden );
		}

		if ( isset( $found['permission_callback'] ) ) {
			$allowed = call_user_func( $found['permission_callback'], $request );

			if ( is_wp_error( $allowed ) ) {
				return ITSEC_Dashboard_REST::not_found_error( $allowed );
			}

			if ( true !== $allowed ) {
				return ITSEC_Dashboard_REST::not_found_error( $forbidden );
			}
		}

		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => "ithemes-security-dashboard-card-{$this->card->get_slug()}",
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
				),
				'dashboard' => array(
					'type'     => 'integer',
					'readonly' => true,
					'context'  => array( 'view', 'edit', 'embed' ),
				),
				'data'      => array(
					'context'  => array( 'view', 'edit', 'embed' ),
					'readonly' => true,
				),
			)
		);

		if ( $settings = $this->card->get_settings_schema() ) {
			$schema['properties']['settings'] = array_merge( $settings, array(
				'context' => array( 'edit' ),
			) );
		}

		return $schema;
	}

	/**
	 * Get the configured action link for a request.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return array|null
	 */
	private function get_action_link( $request ) {

		$handler = $request->get_attributes();
		$links   = $this->card->get_links();

		if ( ! isset( $handler['itsec_id'], $links[ $handler['itsec_id'] ] ) ) {
			return null;
		}

		return $links[ $handler['itsec_id'] ];
	}
}
