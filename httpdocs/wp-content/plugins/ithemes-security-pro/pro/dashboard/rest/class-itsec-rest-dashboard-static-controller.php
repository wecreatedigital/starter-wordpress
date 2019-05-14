<?php

/**
 * Class ITSEC_REST_Dashboard_Static_Controller
 */
class ITSEC_REST_Dashboard_Static_Controller extends ITSEC_REST_Dashboard_Controller {

	/**
	 * ITSEC_REST_Dashboard_Static_Controller constructor.
	 */
	public function __construct() {
		$this->namespace = 'ithemes-security/v1';
		$this->rest_base = 'dashboard-static';
	}

	public function register_routes() {
		register_rest_route( $this->namespace, $this->rest_base, array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'get_item' ),
			'permission_callback' => array( $this, 'get_item_permissions_check' ),
			'args'                => $this->get_collection_params(),
			'schema'              => array( $this, 'get_public_item_schema' ),
		) );
	}

	public function get_item_permissions_check( $request ) {

		if ( current_user_can( 'itsec_create_dashboards' ) ) {
			return true;
		}

		return count( ITSEC_Dashboard_Util::get_shared_dashboards( false, 'ids' ) ) > 0;
	}

	public function get_item( $request ) {

		$ips    = ITSEC_Dashboard_Util::total_ips( $request['period'] );
		$events = ITSEC_Dashboard_Util::total_events( $request['period'] );

		$suspicious = ITSEC_Dashboard_Util::count_events( array(
			'four-oh-four',
			'local-brute-force',
			'network-brute-force',
			'recaptcha-empty',
			'recaptcha-invalid',
			'fingerprint-login-unknown',
			'fingerprint-session-switched-unknown'
		), $request['period'] );
		$suspicious = is_wp_error( $suspicious ) ? 0 : array_sum( $suspicious );

		$blocked = ITSEC_Dashboard_Util::count_events( array(
			'blacklist-four_oh_four',
			'blacklist-brute_force',
			'blacklist-brute_force_admin_user',
			'blacklist-recaptcha',
			'lockout-user',
			'lockout-username',
			'lockout-host',
			'fingerprint-login-blocked',
			'recaptcha-empty',
			'recaptcha-invalid',
			'fingerprint-session-destroyed'
		), $request['period'] );
		$blocked = is_wp_error( $blocked ) ? 0 : array_sum( $blocked );

		return new WP_REST_Response( compact( 'events', 'ips', 'suspicious', 'blocked' ) );
	}

	public function get_collection_params() {
		return array(
			'period' => ITSEC_Dashboard_REST::get_period_arg(),
		);
	}
}
