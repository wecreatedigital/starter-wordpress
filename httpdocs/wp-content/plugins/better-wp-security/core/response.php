<?php

final class ITSEC_Response {
	private static $instance = false;

	private $response;
	private $errors;
	private $warnings;
	private $messages;
	private $infos;
	private $success;
	private $js_function_calls;
	private $show_default_success_message;
	private $show_default_error_message;
	private $force_logout;
	private $redirect;
	private $close_modal;
	private $regenerate_wp_config;
	private $regenerate_server_config;
	private $has_new_notifications = false;

	private function __construct() {
		$this->reset_to_defaults();

		add_action( 'shutdown', array( $this, 'shutdown' ) );
	}

	public static function set_success( $success ) {
		$self = self::get_instance();

		$old_success = $self->success;
		$self->success = (bool) $success;

		return $old_success;
	}

	public static function is_success() {
		$self = self::get_instance();

		return $self->success;
	}

	public static function set_response( $response ) {
		$self = self::get_instance();

		$old_response = $self->response;
		$self->response = $response;

		return $old_response;
	}

	public static function get_response() {
		$self = self::get_instance();

		return $self->response;
	}

	public static function add_errors( $errors ) {
		foreach ( $errors as $error ) {
			self::add_error( $error );
		}
	}

	public static function add_error( $error ) {
		$self = self::get_instance();

		$self->errors[] = $error;
	}

	public static function get_errors() {
		$self = self::get_instance();

		return $self->errors;
	}

	public static function add_warnings( $warnings ) {
		foreach ( $warnings as $warning ) {
			self::add_warning( $warning );
		}
	}

	public static function add_warning( $warning ) {
		$self = self::get_instance();

		$self->warnings[] = $warning;
	}

	public static function get_warnings() {
		$self = self::get_instance();

		return $self->warnings;
	}

	public static function get_error_count() {
		$self = self::get_instance();

		return count( $self->errors );
	}

	public static function add_messages( $messages ) {
		foreach ( $messages as $message ) {
			self::add_message( $message );
		}
	}

	public static function add_message( $message ) {
		$self = self::get_instance();

		$self->messages[] = $message;
	}

	public static function get_messages() {
		$self = self::get_instance();

		return $self->messages;
	}

	public static function add_infos( $messages ) {
		foreach ( $messages as $message ) {
			self::add_info( $message );
		}
	}

	public static function add_info( $message ) {
		$self = self::get_instance();

		$self->infos[] = $message;
	}

	public static function get_infos() {
		$self = self::get_instance();

		return $self->infos;
	}

	public static function add_js_function_call( $js_function, $args = null ) {
		$self = self::get_instance();

		if ( is_null( $args ) ) {
			$call = array( $js_function );
		} else {
			$call = array( $js_function, $args );
		}

		if ( ! in_array( $call, $self->js_function_calls ) ) {
			$self->js_function_calls[] = $call;
		}
	}

	public static function get_js_function_calls() {
		$self = self::get_instance();

		return $self->js_function_calls;
	}

	public static function set_show_default_success_message( $show_default_success_message ) {
		$self = self::get_instance();

		$old_show_default_success_message = $self->show_default_success_message;
		$self->show_default_success_message = $show_default_success_message;

		return $old_show_default_success_message;
	}

	public static function get_show_default_success_message() {
		$self = self::get_instance();

		return $self->show_default_success_message;
	}

	public static function set_show_default_error_message( $show_default_error_message ) {
		$self = self::get_instance();

		$old_show_default_error_message = $self->show_default_error_message;
		$self->show_default_error_message = $show_default_error_message;

		return $old_show_default_error_message;
	}

	public static function get_show_default_error_message() {
		$self = self::get_instance();

		return $self->show_default_error_message;
	}

	public static function prevent_modal_close() {
		$self = self::get_instance();

		$self->close_modal = false;
	}

	public static function reload_module( $module ) {
		$self = self::get_instance();

		$self->add_js_function_call( 'reloadModule', $module );
	}

	public static function reload_all_modules() {
		self::get_instance()->add_js_function_call( 'reloadAllModules' );
	}

	public static function refresh_page() {
		self::get_instance()->add_js_function_call( 'refreshPage' );
	}

	public static function regenerate_wp_config() {
		$self = self::get_instance();

		$self->regenerate_wp_config = true;

		self::reload_module( 'wp-config-rules' );
	}

	public static function regenerate_server_config() {
		$self = self::get_instance();

		$self->regenerate_server_config = true;

		self::reload_module( 'server-config-rules' );
	}

	public static function force_logout() {
		$self = self::get_instance();

		if ( $self->force_logout ) {
			return;
		}

		$self->force_logout = true;
		self::redirect( add_query_arg( 'loggedout', 'true', wp_login_url() ) );
	}

	public static function redirect( $redirect ) {
		$self = self::get_instance();

		$self->redirect = $redirect;
	}


	public static function maybe_regenerate_wp_config() {
		$self = self::get_instance();

		if ( $self->regenerate_wp_config ) {
			ITSEC_Files::regenerate_wp_config();
			$self->regenerate_wp_config = false;
		}
	}

	public static function maybe_regenerate_server_config() {
		$self = self::get_instance();

		if ( $self->regenerate_server_config ) {
			ITSEC_Files::regenerate_server_config();
			$self->regenerate_server_config = false;
		}
	}

	public static function maybe_do_force_logout() {
		$self = self::get_instance();

		if ( $self->force_logout ) {
			@wp_clear_auth_cookie();
			$self->force_logout = false;
		}
	}

	public static function maybe_do_redirect() {
		$self = self::get_instance();

		if ( $self->redirect ) {
			wp_safe_redirect( $self->redirect );
			exit();
		}
	}

	public static function maybe_flag_new_notifications_available() {
		$nc = ITSEC_Core::get_notification_center();

		$current = array_keys( $nc->get_notifications() );
		$nc->clear_notifications_cache();
		$new = array_keys( $nc->get_notifications() );

		$added = array_diff( $new, $current );

		if ( $added ) {
			self::flag_new_notifications_available();
		}
	}

	public static function flag_new_notifications_available() {
		static $run_count = 0;

		if ( $run_count++ > 0 ) {
			return;
		}

		self::reload_module( 'notification-center' );
		self::get_instance()->has_new_notifications = true;
		self::get_instance()->add_info( sprintf(
			esc_html__( 'New notifications available in the %1$sNotification Center%2$s.', 'better-wp-security' ),
			'<a href="#" data-module-link="notification-center">',
			'</a>'
		) );
	}

	public static function get_raw_data() {
		$self = self::get_instance();

		self::maybe_regenerate_wp_config();
		self::maybe_regenerate_server_config();
		self::maybe_do_force_logout();

		if ( is_wp_error( $self->response ) ) {
			$self->add_error( $self->response );
			$self->set_response( null );
		}


		$data = array(
			'source'           => 'ITSEC_Response',
			'success'          => $self->success,
			'response'         => $self->response,
			'errors'           => self::get_error_strings( $self->errors ),
			'warnings'         => self::get_error_strings( $self->warnings ),
			'messages'         => $self->messages,
			'infos'            => $self->infos,
			'functionCalls'    => self::parse_js_function_calls_for_module_reloads(),
			'redirect'         => $self->redirect,
			'closeModal'       => $self->close_modal,
			'newNotifications' => $self->has_new_notifications,
		);

		return $data;
	}

	public static function send_json() {
		$data = self::get_raw_data();

		wp_send_json( $data );
	}

	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}


	public function reset_to_defaults() {
		$this->response = null;
		$this->errors = array();
		$this->warnings = array();
		$this->messages = array();
		$this->infos = array();
		$this->success = true;
		$this->js_function_calls = array();
		$this->show_default_success_message = true;
		$this->show_default_error_message = true;
		$this->force_logout = false;
		$this->redirect = false;
		$this->close_modal = true;
		$this->regenerate_wp_config = false;
		$this->regenerate_server_config = false;
	}

	public static function get_error_strings( $error ) {
		if ( is_string( $error ) ) {
			return array( $error );
		} else if ( is_a( $error, 'WP_Error' ) ) {
			/* translators: 1: error message, 2: error code */
			$format = __( '%1$s <span class="itsec-error-code">(%2$s)</span>', 'better-wp-security' );
			$errors = array();

			foreach ( $error->get_error_codes() as $code ) {
				$message = implode( ' ', (array) $error->get_error_messages( $code ) );
				$errors[] = sprintf( $format, $message, $code ) . ' ';
			}

			return $errors;
		} else if ( is_array( $error ) ) {
			$errors = array();

			foreach ( $error as $error_item ) {
				$new_errors = self::get_error_strings( $error_item );
				$errors = array_merge( $errors, $new_errors );
			}

			return $errors;
		}

		/* translators: 1: variable type */
		return array( sprintf( __( 'Unknown error type received: %1$s.', 'better-wp-security' ), gettype( $error ) ) );
	}

	private static function parse_js_function_calls_for_module_reloads() {

		$has_reload_all = false;

		$function_calls = self::get_instance()->js_function_calls;

		foreach ( $function_calls as $function_call ) {
			if ( $function_call[0] === 'reloadAllModules' ) {
				$has_reload_all = true;
				break;
			}
		}

		if ( ! $has_reload_all ) {
			return $function_calls;
		}

		foreach ( $function_calls as $i => $function_call ) {
			if ( $function_call[0] === 'reloadModule' ) {
				unset( $function_calls[ $i ] );
			}
		}

		return array_values( $function_calls );
	}

	public function shutdown() {
		self::maybe_regenerate_wp_config();
		self::maybe_regenerate_server_config();
		self::maybe_do_force_logout();
	}
}
