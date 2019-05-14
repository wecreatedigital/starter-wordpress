<?php

/**
 * Class ITSEC_Dashboard
 */
class ITSEC_Dashboard {

	const CPT_DASHBOARD = 'itsec-dashboard';
	const META_SHARE_USER = '_itsec_dashboard_share_user';
	const META_SHARE_ROLE = '_itsec_dashboard_share_role';

	const CPT_CARD = 'itsec-dash-card';
	const META_CARD = '_itsec_dashboard_card';
	const META_CARD_SETTINGS = '_itsec_dashboard_card_settings';
	const META_CARD_POSITION = '_itsec_dashboard_card_position';
	const META_CARD_SIZE = '_itsec_dashboard_card_size';

	const META_PRIMARY = '_itsec_primary_dashboard';

	/**
	 * Run the dashboard module.
	 */
	public function run() {

		if ( ! version_compare( $GLOBALS['wp_version'], '4.9.8', '>=' ) ) {
			return;
		}

		add_action( 'init', array( $this, 'register_data_storage' ) );
		add_action( 'itsec_scheduler_register_events', array( $this, 'register_events' ) );
		add_action( 'itsec_scheduled_dashboard-consolidate-events', array( $this, 'run_consolidate_events' ) );
		add_action( 'after_delete_post', array( $this, 'after_delete_post' ) );
		add_filter( 'map_meta_cap', array( $this, 'map_meta_cap' ), 10, 4 );
		add_action( 'itsec_log_add', array( $this, 'log_add' ) );
		add_action( 'itsec_four_oh_four_whitelisted', array( $this, 'record_four_oh_four_whitelist' ) );
		add_action( 'itsec_login_with_fingerprint', array( $this, 'record_fingerprint_login' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'current_screen', array( $this, 'render_help_tabs' ) );

		if ( ! ITSEC_Modules::get_setting( 'global', 'hide_admin_bar' ) ) {
			add_action( 'admin_bar_menu', array( $this, 'modify_admin_bar' ), 100 );
		}

		if ( is_multisite() ) {
			add_action( 'network_admin_menu', array( $this, 'register_menu' ) );
		} else {
			add_action( 'admin_menu', array( $this, 'register_menu' ) );
		}

		require_once( dirname( __FILE__ ) . '/class-itsec-dashboard-rest.php' );
		$rest = new ITSEC_Dashboard_REST();
		$rest->run();
	}

	/**
	 * Register the Custom Post Types and Metadata.
	 */
	public function register_data_storage() {
		register_post_type( self::CPT_DASHBOARD, array(
			'public'       => false,
			'hierarchical' => true,
			'supports'     => array( 'title' ),
		) );

		register_post_meta( self::CPT_DASHBOARD, self::META_SHARE_USER, array(
			'type'              => 'integer',
			'single'            => false,
			'sanitize_callback' => 'absint'
		) );

		register_post_meta( self::CPT_DASHBOARD, self::META_SHARE_ROLE, array(
			'type'              => 'string',
			'single'            => false,
			'sanitize_callback' => array( __CLASS__, '_sanitize_role' )
		) );

		register_post_type( self::CPT_CARD, array(
			'public'   => false,
			'supports' => array(),
		) );

		register_post_meta( self::CPT_CARD, self::META_CARD, array(
			'type'              => 'string',
			'single'            => true,
			'sanitize_callback' => array( __CLASS__, '_sanitize_card' ),
		) );

		register_post_meta( self::CPT_CARD, self::META_CARD_SETTINGS, array(
			'type'              => 'object',
			'single'            => true,
			'sanitize_callback' => array( __CLASS__, '_sanitize_settings' ),
		) );

		register_post_meta( self::CPT_CARD, self::META_CARD_POSITION, array(
			'type'              => 'object',
			'single'            => true,
			'sanitize_callback' => array( __CLASS__, '_sanitize_position' ),
		) );

		register_post_meta( self::CPT_CARD, self::META_CARD_SIZE, array(
			'type'              => 'object',
			'single'            => true,
			'sanitize_callback' => array( __CLASS__, '_sanitize_size' ),
		) );

		register_meta( 'user', self::META_PRIMARY, array(
			'type'              => 'integer',
			'single'            => true,
			'sanitize_callback' => 'absint',
			'auth_callback'     => array( __CLASS__, '_auth_primary' ),
			'show_in_rest'      => array(
				'schema' => array(
					'type'    => 'integer',
					'context' => array( 'edit' ),
				)
			),
		) );
	}

	/**
	 * Delete all cards when a dashboard is deleted.
	 *
	 * @param int $post_id
	 */
	public function after_delete_post( $post_id ) {
		if ( get_post_type( $post_id ) !== self::CPT_DASHBOARD ) {
			return;
		}

		foreach ( ITSEC_Dashboard_Util::get_dashboard_cards( $post_id ) as $post ) {
			wp_delete_post( $post->ID );
		}
	}

	/**
	 * Sanitize the "role" metadata.
	 *
	 * @param string $role
	 *
	 * @return string
	 */
	public static function _sanitize_role( $role ) {
		return array_key_exists( $role, wp_roles()->roles ) ? $role : '';
	}

	/**
	 * Sanitize the "card" metadata.
	 *
	 * @param string $card
	 *
	 * @return string
	 */
	public static function _sanitize_card( $card ) {
		return (string) preg_replace( '/[^\w_-]/', '', $card );
	}

	/**
	 * Sanitize the "settings" metadata.
	 *
	 * @param mixed $settings
	 *
	 * @return array
	 */
	public static function _sanitize_settings( $settings ) {
		return is_array( $settings ) ? $settings : array();
	}

	/**
	 * Sanitize the "position" metadata.
	 *
	 * @param mixed $position
	 *
	 * @return array
	 */
	public static function _sanitize_position( $position ) {

		$sanitized = array();

		if ( ! is_array( $position ) ) {
			return $sanitized;
		}

		require_once( dirname( __FILE__ ) . '/class-itsec-dashboard-util.php' );

		foreach ( $position as $breakpoint => $entry ) {
			if ( ! in_array( $breakpoint, ITSEC_Dashboard_Util::$breakpoints, true ) ) {
				continue;
			}

			$sanitized[ $breakpoint ] = self::_sanitize_position_entry( $entry );
		}

		return $sanitized;
	}

	/**
	 * Sanitize a single position value for a breakpoint.
	 *
	 * @param array|mixed $position
	 *
	 * @return array
	 */
	private static function _sanitize_position_entry( $position ) {
		if ( ! is_array( $position ) || ! isset( $position['x'], $position['y'] ) ) {
			return array();
		}

		return array(
			'x' => absint( $position['x'] ),
			'y' => absint( $position['y'] ),
		);
	}

	/**
	 * Sanitize the "size" metadata.
	 *
	 * @param mixed $size
	 *
	 * @return array
	 */
	public static function _sanitize_size( $size ) {

		$sanitized = array();

		if ( ! is_array( $size ) ) {
			return $sanitized;
		}

		require_once( dirname( __FILE__ ) . '/class-itsec-dashboard-util.php' );

		foreach ( $size as $breakpoint => $entry ) {
			if ( ! in_array( $breakpoint, ITSEC_Dashboard_Util::$breakpoints, true ) ) {
				continue;
			}

			$sanitized[ $breakpoint ] = self::_sanitize_size_entry( $entry );
		}

		return $sanitized;
	}

	/**
	 * Sanitize a single size value for a breakpoint.
	 *
	 * @param array|mixed $size
	 *
	 * @return array
	 */
	private static function _sanitize_size_entry( $size ) {
		if ( ! is_array( $size ) || ! isset( $size['w'], $size['h'] ) ) {
			return array();
		}

		return array(
			'w' => absint( $size['w'] ),
			'h' => absint( $size['h'] ),
		);
	}

	/**
	 * Authorization callback to check if a user can set the primary dashboard meta key.
	 *
	 * @param bool   $allowed
	 * @param string $meta_key
	 * @param int    $user_id
	 *
	 * @return bool
	 */
	public static function _auth_primary( $allowed, $meta_key, $user_id ) {
		return current_user_can( 'edit_user', $user_id );
	}

	/**
	 * Register the consolidate events event.
	 *
	 * @param ITSEC_Scheduler $scheduler
	 */
	public function register_events( $scheduler ) {
		$scheduler->schedule( ITSEC_Scheduler::S_DAILY, 'dashboard-consolidate-events' );
	}

	/**
	 * Consolidate events on a daily schedule.
	 */
	public function run_consolidate_events() {
		require_once( dirname( __FILE__ ) . '/class-itsec-dashboard-util.php' );
		ITSEC_Dashboard_Util::consolidate_events();
	}

	/**
	 * Handle custom capabilities for the dashboard.
	 *
	 * @param array  $caps
	 * @param string $cap
	 * @param int    $user_id
	 * @param array  $args
	 *
	 * @return array
	 */
	public function map_meta_cap( $caps, $cap, $user_id, $args ) {

		$user_id = (int) $user_id;

		switch ( $cap ) {
			case 'itsec_dashboard_menu':
				if ( user_can( $user_id, 'itsec_create_dashboards' ) ) {
					return array();
				}

				require_once( dirname( __FILE__ ) . '/class-itsec-dashboard-util.php' );

				if ( ITSEC_Dashboard_Util::get_shared_dashboards( $user_id, 'ids' ) ) {
					return array();
				}

				return array( 'do_not_allow' );
			case 'itsec_view_dashboard':
				if ( empty( $args[0] ) || ! ( $post = get_post( $args[0] ) ) || self::CPT_DASHBOARD !== $post->post_type ) {
					return array( 'do_not_allow' );
				}

				if ( $user_id === (int) $post->post_author ) {
					return array( ITSEC_Core::get_required_cap() );
				}

				$uids = get_post_meta( $post->ID, '_itsec_dashboard_share_user' );

				if ( in_array( $user_id, $uids, false ) ) {
					return array();
				}

				$user = get_userdata( $user_id );

				foreach ( get_post_meta( $post->ID, '_itsec_dashboard_share_role' ) as $role ) {
					if ( in_array( $role, $user->roles, true ) ) {
						return array();
					}
				}

				return array( 'do_not_allow' );
			case 'itsec_edit_dashboard':
				if ( empty( $args[0] ) || ! ( $post = get_post( $args[0] ) ) || self::CPT_DASHBOARD !== $post->post_type ) {
					return array( 'do_not_allow' );
				}

				if ( $user_id === (int) $post->post_author ) {
					return array( ITSEC_Core::get_required_cap() );
				}

				return array( 'do_not_allow' );
			case 'itsec_create_dashboards':
				$disabled = ITSEC_Modules::get_setting( 'dashboard', 'disabled_users' );

				if ( in_array( $user_id, $disabled, false ) ) {
					return array( 'do_not_allow' );
				}

				return array( ITSEC_Core::get_required_cap() );
		}

		return $caps;
	}

	/**
	 * Add a link to the dashboard in the Admin Bar Security submenu.
	 *
	 * @param WP_Admin_Bar $wp_admin_bar
	 */
	public function modify_admin_bar( $wp_admin_bar ) {
		$wp_admin_bar->add_node(
			array(
				'parent' => 'itsec_admin_bar_menu',
				'title'  => __( 'Dashboard', 'it-l10n-ithemes-security-pro' ),
				'href'   => network_admin_url( 'index.php?page=itsec-dashboard' ),
				'id'     => 'itsec_admin_bar_dashboard',
			)
		);
	}

	/**
	 * Register the admin menu for the Dashboard.
	 *
	 * This includes an alias in the Security Menu to be more discoverable.
	 */
	public function register_menu() {
		add_dashboard_page( __( 'Security Dashboard', 'it-l10n-ithemes-security-pro' ), __( 'Security Dashboard', 'it-l10n-ithemes-security-pro' ), 'itsec_dashboard_menu', 'itsec-dashboard', array( $this, 'render_page' ) );

		if ( ! ITSEC_Core::current_user_can_manage() ) {
			return;
		}

		global $submenu;

		$alias = array(
			__( 'Dashboard', 'it-l10n-ithemes-security-pro' ),
			'itsec_dashboard_menu',
			network_admin_url( 'index.php?page=itsec-dashboard' ),
			__( 'Dashboard', 'it-l10n-ithemes-security-pro' ),
			'',
		);

		$added_alias = false;
		$menus       = array();

		foreach ( $submenu['itsec'] as $definition ) {
			if ( 'itsec-logs' === $definition[2] ) {
				$menus[]     = $alias;
				$added_alias = true;
			}

			$menus[] = $definition;
		}

		if ( ! $added_alias ) {
			$menus[] = $alias;
		}

		$submenu['itsec'] = $menus;
	}

	public function enqueue( $hook ) {
		if ( 'dashboard_page_itsec-dashboard' !== $hook && 'index_page_itsec-dashboard' !== $hook ) {
			return;
		}

		require_once( dirname( __FILE__ ) . '/class-itsec-dashboard-util.php' );

		$uid = get_current_user_id();

		$primary_id = ITSEC_Dashboard_Util::get_primary_dashboard_id();

		$preload_requests = array(
			'/ithemes-security/v1/dashboard-static',
			'/ithemes-security/v1/dashboards?_embed=1'       => array(
				'route' => '/ithemes-security/v1/dashboards',
				'embed' => true,
			),
			'/ithemes-security/v1/dashboard-available-cards' => array(
				'route' => '/ithemes-security/v1/dashboard-available-cards',
			),
			'/wp/v2/users/me?context=edit'                   => array(
				'route' => '/wp/v2/users/me',
				'query' => array( 'context' => 'edit' ),
			),
		);

		if ( $primary_id ) {
			$key   = "/ithemes-security/v1/dashboards/{$primary_id}?_embed=1";
			$query = array();

			if ( current_user_can( 'itsec_edit_dashboard', $primary_id ) ) {
				$key .= '&context=edit';

				$query['context'] = 'edit';
			}

			$preload_requests[ $key ] = array(
				'route' => "/ithemes-security/v1/dashboards/{$primary_id}",
				'embed' => true,
				'query' => $query,
			);

			$preload_requests["/ithemes-security/v1/dashboards/{$primary_id}/cards?_embed=1"] = array(
				'route' => "/ithemes-security/v1/dashboards/{$primary_id}/cards",
				'embed' => true,
			);

			$preload_requests[] = "/ithemes-security/v1/dashboards/{$primary_id}/layout";
		}

		$preload = ITSEC_Lib::preload_rest_requests( $preload_requests );

		$roles = array();

		foreach ( wp_roles()->roles as $role => $config ) {
			$roles[] = array(
				'slug' => $role,
				'name' => $config['name'],
			);
		}

		wp_enqueue_style( 'itsec-dashboard-dashboard' );
		wp_enqueue_script( 'itsec-dashboard-dashboard' );
		wp_localize_script( 'itsec-dashboard-dashboard', 'iThemesSecurityDashboard', array(
			'rootURL'           => rest_url(),
			'nonce'             => wp_create_nonce( 'wp_rest' ),
			'user'              => array(
				'id'         => $uid,
				'name'       => wp_get_current_user()->display_name,
				'avatar'     => get_avatar_url( get_current_user_id(), array( 'size' => 128 ) ),
				'can_manage' => ITSEC_Core::current_user_can_manage(),
			),
			'site_url'          => network_site_url(),
			'site_url_pretty'   => self::get_pretty_url(),
			'roles'             => $roles,
			'preload'           => $preload,
			'primary_dashboard' => $primary_id,
			'db_logs'           => ITSEC_Modules::get_setting( 'global', 'log_type' ) !== 'file',
			'logs_nonce'        => wp_create_nonce( 'itsec-logs-nonce' ),
			'ajaxurl'           => admin_url( 'admin-ajax.php' ),
		) );
	}

	private static function get_pretty_url() {
		$url    = network_site_url();
		$parsed = parse_url( $url );

		$display = $parsed['host'];

		if ( ! empty( $parsed['path'] ) ) {
			$display .= $parsed['path'];
		}

		return $display;
	}

	/**
	 * Render the help tabs in the Screen Options area.
	 *
	 * @param WP_Screen $screen
	 */
	public function render_help_tabs( $screen ) {
		if ( $screen->id !== 'dashboard_page_itsec-dashboard' && $screen->id !== 'index_page_itsec-dashboard' ) {
			return;
		}

		$screen->set_help_sidebar( '<p>' . sprintf(
				esc_html__( 'More questions about the Security Dashboard? Visit our %1$sHelp Center%3$s or submit a %2$sSupport Ticket%3$s.', 'it-l10n-ithemes-security-pro' ),
				'<a href="https://ithemeshelp.zendesk.com/hc/en-us/articles/360015429214-Security-Dashboard">',
				'<a href="https://members.ithemes.com/panel/helpdesk.php">',
				'</a>'
			) . '</p>' );

		$screen->add_help_tab( array(
			'title'    => esc_html__( 'Top Bar', 'it-l10n-ithemes-security-pro' ),
			'id'       => 'static-bar',
			'callback' => array( $this, 'render_top_bar' ),
		) );
	}

	public function render_top_bar() {
		$help = array(
			'events'     => sprintf( esc_html__( '%1$sEvents Tracked%2$s: Total logged events across all of iThemes Security.', 'it-l10n-ithemes-security-pro' ), '<strong>', '</strong>' ),
			'suspicious' => sprintf( esc_html__( '%1$sSuspicious Activities%2$s: Total activity that iThemes Security deems suspicious such as 404s, invalid login attempts, missing reCAPTCHA, or unrecognized Trusted Devices.', 'it-l10n-ithemes-security-pro' ), '<strong>', '</strong>' ),
			'blocked'    => sprintf( esc_html__( '%1$sActivities Blocked%2$s: The number of times iThemes Security takes action to block a suspicious user. Includes lockouts, banned IPs and protected hijacked sessions.', 'it-l10n-ithemes-security-pro' ), '<strong>', '</strong>' ),
			'ips'        => sprintf( esc_html__( '%1$sIPs Monitored%2$s: Total number of IPs being tracked by iThemes Security.', 'it-l10n-ithemes-security-pro' ), '<strong>', '</strong>' ),
		);

		$html = '<p>' . esc_html__( 'The bar at the top of the screen is a summary of the number of Security related events that occurred on your website in the last 30 days.', 'it-l10n-ithemes-security-pro' ) . '</p>';
		$html .= '<ul>';
		foreach ( $help as $message ) {
			$html .= '<li>' . $message . '</li>';
		}
		$html .= '</ul>';

		echo $html;
	}

	public function render_page() {
		echo '<div id="itsec-dashboard-root"></div>';
	}

	/**
	 * Create an event for certain log items.
	 *
	 * @param array $data
	 */
	public function log_add( $data ) {

		list( $code, $code_data ) = array_pad( explode( '::', $data['code'] ), 2, '' );
		$code_data = wp_parse_slug_list( $code_data );

		require_once( dirname( __FILE__ ) . '/class-itsec-dashboard-util.php' );

		switch ( $data['module'] ) {
			case 'brute_force':
				switch ( $code ) {
					case 'auto-ban-admin-username':
					case 'invalid-login':
						ITSEC_Dashboard_Util::record_event( 'local-brute-force' );
						break;
				}
				break;
			case 'ipcheck':
				switch ( $code ) {
					case 'failed-login-by-blocked-ip':
					case 'successful-login-by-blocked-ip':
						ITSEC_Dashboard_Util::record_event( 'network-brute-force' );
						break;
				}
				break;
			case 'lockout':
				switch ( $code ) {
					case 'host-triggered-blacklist':
						// blacklist-four_oh_four, blacklist-brute_force, blacklist-brute_force_admin_user, blacklist-recaptcha
						ITSEC_Dashboard_Util::record_event( 'blacklist-' . $data['data']['module'] );
						break;
					case 'host-lockout':
						ITSEC_Dashboard_Util::record_event( 'lockout-host' );
						break;
					case 'user-lockout':
						ITSEC_Dashboard_Util::record_event( 'lockout-user' );
						break;
					case 'username-lockout':
						ITSEC_Dashboard_Util::record_event( 'lockout-username' );
						break;
				}

				break;
			case 'version_management':
				switch ( $code ) {
					case 'update':
						ITSEC_Dashboard_Util::record_event( "vm-update-{$code_data[0]}" );
						break;
					case 'update-core':
						ITSEC_Dashboard_Util::record_event( 'vm-update-core' );
						break;
				}
				break;
			case 'four_oh_four':
				if ( is_user_logged_in() ) {
					ITSEC_Dashboard_Util::record_event( 'four-oh-four-logged-in' );
					break;
				}

				if ( isset( $data['server']['HTTP_USER_AGENT'] ) ) {
					require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-lib-browser.php' );
					$browser = new ITSEC_Lib_Browser( $data['server']['HTTP_USER_AGENT'] );

					if ( $browser->isRobot() ) {
						ITSEC_Dashboard_Util::record_event( 'four-oh-four-bot' );
						break;
					}
				}

				ITSEC_Dashboard_Util::record_event( 'four-oh-four' );
				break;
			case 'recaptcha':
				if ( is_wp_error( $data['data'] ) ) { // Safety Check
					if ( 'itsec-recaptcha-form-not-submitted' === $data['data']->get_error_code() ) {
						ITSEC_Dashboard_Util::record_event( 'recaptcha-empty' );
					} else {
						ITSEC_Dashboard_Util::record_event( 'recaptcha-invalid' );
					}
				}
				break;
			case 'fingerprinting':
				switch ( $code ) {
					case 'denied_fingerprint_blocked':
						ITSEC_Dashboard_Util::record_event( 'fingerprint-login-blocked' );
						break;
					case 'status':
						switch ( $code_data[0] ) {
							case ITSEC_Fingerprint::S_APPROVED:
							case ITSEC_Fingerprint::S_AUTO_APPROVED:
							case ITSEC_Fingerprint::S_DENIED:
								ITSEC_Dashboard_Util::record_event( 'fingerprint-status-' . $code_data[0] );
								break;
						}
						break;
					case 'session_destroyed':
						ITSEC_Dashboard_Util::record_event( 'fingerprint-session-destroyed' );
						break;
					case 'session_switched_unknown':
						ITSEC_Dashboard_Util::record_event( 'fingerprint-session-switched-unknown' );
						break;
					case 'session_switched_known':
						ITSEC_Dashboard_Util::record_event( 'fingerprint-session-switched-known' );
						break;
				}
				break;
		}
	}

	/**
	 * Record when a whitelisted 404 is encountered.
	 */
	public function record_four_oh_four_whitelist() {
		require_once( dirname( __FILE__ ) . '/class-itsec-dashboard-util.php' );
		ITSEC_Dashboard_Util::record_event( 'four-oh-four-whitelisted' );
	}

	/**
	 * Record the kind of fingerprint used when logging in.
	 *
	 * @param ITSEC_Fingerprint $fingerprint
	 */
	public function record_fingerprint_login( $fingerprint ) {
		require_once( dirname( __FILE__ ) . '/class-itsec-dashboard-util.php' );

		if ( $fingerprint->is_approved() ) {
			ITSEC_Dashboard_Util::record_event( 'fingerprint-login-known' );
		} elseif ( $fingerprint->is_pending_auto_approval() ) {
			ITSEC_Dashboard_Util::record_event( 'fingerprint-login-unknown-auto-approved' );
		} elseif ( $fingerprint->is_pending() ) {
			ITSEC_Dashboard_Util::record_event( 'fingerprint-login-unknown' );
		}
	}
}
