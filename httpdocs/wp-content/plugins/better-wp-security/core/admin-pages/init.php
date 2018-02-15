<?php


final class ITSEC_Admin_Page_Loader {
	private $version = 2.0;

	private $page_refs = array();
	private $page_id;
	private $translations = array();


	public function __construct() {
		if ( is_multisite() ) {
			add_action( 'network_admin_menu', array( $this, 'add_admin_pages' ) );
		} else {
			add_action( 'admin_menu', array( $this, 'add_admin_pages' ) );
		}

		add_action( 'wp_ajax_itsec_settings_page', array( $this, 'handle_ajax_request' ) );
		add_action( 'wp_ajax_itsec_logs_page', array( $this, 'handle_ajax_request' ) );
		add_action( 'wp_ajax_itsec_help_page', array( $this, 'handle_ajax_request' ) );
		add_action( 'wp_ajax_itsec-set-user-setting', array( $this, 'handle_user_setting' ) );

		// Filters for validating user settings
		add_filter( 'itsec-user-setting-valid-itsec-settings-view', array( $this, 'validate_view' ), null, 2 );
	}

	public function add_scripts() {
		$this->set_translation_strings();

		$vars = array(
			'ajax_action'  => 'itsec_settings_page',
			'ajax_nonce'   => wp_create_nonce( 'itsec-settings-nonce' ),
			'translations' => $this->translations,
		);

		wp_enqueue_script( 'itsec-util-script', plugins_url( 'js/util.js', __FILE__ ), array(), $this->version, true );
		wp_localize_script( 'itsec-util-script', 'itsec_util', $vars );
	}

	public function add_styles() {
		wp_enqueue_style( 'itsec-settings-page-style', plugins_url( 'css/style.css', __FILE__ ), array(), $this->version );
	}

	private function set_translation_strings() {
		$this->translations = array(
			'ajax_invalid'      => new WP_Error( 'itsec-settings-page-invalid-ajax-response', __( 'An "invalid format" error prevented the request from completing as expected. The format of data returned could not be recognized. This could be due to a plugin/theme conflict or a server configuration issue.', 'better-wp-security' ) ),

			'ajax_forbidden'    => new WP_Error( 'itsec-settings-page-forbidden-ajax-response: %1$s "%2$s"',  __( 'A "request forbidden" error prevented the request from completing as expected. The server returned a 403 status code, indicating that the server configuration is prohibiting this request. This could be due to a plugin/theme conflict or a server configuration issue. Please try refreshing the page and trying again. If the request continues to fail, you may have to alter plugin settings or server configuration that could account for this AJAX request being blocked.', 'better-wp-security' ) ),

			'ajax_not_found'    => new WP_Error( 'itsec-settings-page-not-found-ajax-response: %1$s "%2$s"', __( 'A "not found" error prevented the request from completing as expected. The server returned a 404 status code, indicating that the server was unable to find the requested admin-ajax.php file. This could be due to a plugin/theme conflict, a server configuration issue, or an incomplete WordPress installation. Please try refreshing the page and trying again. If the request continues to fail, you may have to alter plugin settings, alter server configurations, or reinstall WordPress.', 'better-wp-security' ) ),

			'ajax_server_error' => new WP_Error( 'itsec-settings-page-server-error-ajax-response: %1$s "%2$s"', __( 'A "internal server" error prevented the request from completing as expected. The server returned a 500 status code, indicating that the server was unable to complete the request due to a fatal PHP error or a server problem. This could be due to a plugin/theme conflict, a server configuration issue, a temporary hosting issue, or invalid custom PHP modifications. Please check your server\'s error logs for details about the source of the error and contact your hosting company for assistance if required.', 'better-wp-security' ) ),

			'ajax_unknown'      => new WP_Error( 'itsec-settings-page-ajax-error-unknown: %1$s "%2$s"', __( 'An unknown error prevented the request from completing as expected. This could be due to a plugin/theme conflict or a server configuration issue.', 'better-wp-security' ) ),

			'ajax_timeout'      => new WP_Error( 'itsec-settings-page-ajax-error-timeout: %1$s "%2$s"', __( 'A timeout error prevented the request from completing as expected. The site took too long to respond. This could be due to a plugin/theme conflict or a server configuration issue.', 'better-wp-security' ) ),

			'ajax_parsererror'  => new WP_Error( 'itsec-settings-page-ajax-error-parsererror: %1$s "%2$s"', __( 'A parser error prevented the request from completing as expected. The site sent a response that jQuery could not process. This could be due to a plugin/theme conflict or a server configuration issue.', 'better-wp-security' ) ),
		);

		foreach ( $this->translations as $key => $message ) {
			if ( is_wp_error( $message ) ) {
				$messages = ITSEC_Response::get_error_strings( $message );
				$this->translations[$key] = $messages[0];
			}
		}
	}

	public function add_admin_pages() {
		$capability = ITSEC_Core::get_required_cap();
		$page_refs = array();

		add_menu_page( __( 'Settings', 'better-wp-security' ), __( 'Security', 'better-wp-security' ), $capability, 'itsec', array( $this, 'show_page' ) );
		$page_refs[] = add_submenu_page( 'itsec', __( 'iThemes Security Settings', 'better-wp-security' ), __( 'Settings', 'better-wp-security' ), $capability, 'itsec', array( $this, 'show_page' ) );
		$page_refs[] = add_submenu_page( 'itsec', '', __( 'Security Check', 'better-wp-security' ), $capability, 'itsec-security-check', array( $this, 'show_page' ) );
		$page_refs[] = add_submenu_page( 'itsec', __( 'iThemes Security Logs', 'better-wp-security' ), __( 'Logs', 'better-wp-security' ), $capability, 'itsec-logs', array( $this, 'show_page' ) );

		if ( ! ITSEC_Core::is_pro() ) {
			$page_refs[] = add_submenu_page( 'itsec', '', '<span style="color:#2EA2CC">' . __( 'Go Pro', 'better-wp-security' ) . '</span>', $capability, 'itsec-go-pro', array( $this, 'show_page' ) );
		}

		foreach ( $page_refs as $page_ref ) {
			add_action( "load-$page_ref", array( $this, 'load' ) );
		}
	}

	private function get_page_id() {
		global $plugin_page;

		if ( isset( $this->page_id ) ) {
			return $this->page_id;
		}

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			if ( isset( $_REQUEST['action'] ) && preg_match( '/^itsec_(.+)_page$/', $_REQUEST['action'], $match ) ) {
				$this->page_id = $match[1];
			}
		} else if ( 'itsec-' === substr( $plugin_page, 0, 6 ) ) {
			$this->page_id = substr( $plugin_page, 6 );
		} else if ( 'itsec' === substr( $plugin_page, 0, 5 ) ) {
			$this->page_id = 'settings';
		}

		if ( ! isset( $this->page_id ) ) {
			$this->page_id = '';
		}

		return $this->page_id;
	}

	public function load() {
		add_action( 'admin_print_scripts', array( $this, 'add_scripts' ) );
		add_action( 'admin_print_styles', array( $this, 'add_styles' ) );

		$this->load_file( 'page-%s.php' );
	}

	public function show_page() {
		$page_id = $this->get_page_id();

		if ( 'settings' === $page_id ) {
			$url = network_admin_url( 'admin.php?page=itsec' );
		} else {
			$url = network_admin_url( 'admin.php?page=itsec-' . $this->get_page_id() );
		}

		do_action( 'itsec-page-show', $url );
	}

	public function handle_ajax_request() {
		$this->load_file( 'page-%s.php' );

		do_action( 'itsec-page-ajax' );
	}

	private function load_file( $file ) {
		$id = $this->get_page_id();

		if ( empty( $id ) ) {
			if ( isset( $GLOBALS['pagenow'] ) && 'admin.php' === $GLOBALS['pagenow'] && isset( $_GET['page'] ) && 'itsec-' === substr( $_GET['page'], 0, 6 ) ) {
				$id = substr( $_GET['page'], 6 );
			} else {
				return;
			}
		}

		$id = str_replace( '_', '-', $id );

		$file = dirname( __FILE__ ) . '/' . sprintf( $file, $id );

		if ( is_file( $file ) ) {
			require_once( $file );
		}
	}

	public function handle_user_setting() {
		$whitelist_settings = array(
			'itsec-settings-view'
		);

		if ( in_array( $_REQUEST['setting'], $whitelist_settings ) ) {
			$_REQUEST['setting'] = sanitize_title_with_dashes( $_REQUEST['setting'] );

			// Verify nonce is valid and for this setting, and allow a filter to
			if ( wp_verify_nonce( $_REQUEST['itsec-user-setting-nonce'], 'set-user-setting-' . $_REQUEST['setting'] ) &&
				apply_filters( 'itsec-user-setting-valid-' . $_REQUEST['setting'], true, $_REQUEST['value'] ) ) {

				if ( false !== update_user_meta( get_current_user_id(), $_REQUEST['setting'], $_REQUEST['value'] ) ) {
					wp_send_json_success();
				}

			}
		}
		wp_send_json_error();
	}

	public function validate_view( $valid, $view ) {
		return in_array( $view, array( 'grid', 'list' ) );
	}
}

new ITSEC_Admin_Page_Loader();
