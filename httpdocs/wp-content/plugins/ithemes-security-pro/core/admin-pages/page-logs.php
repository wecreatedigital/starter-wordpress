<?php


final class ITSEC_Logs_Page {
	private $self_url = '';
	private $modules = array();
	private $widgets = array();
	private $translations = array();


	public function __construct() {
		add_action( 'itsec-logs-page-register-widget', array( $this, 'register_widget' ) );

		add_action( 'itsec-page-show', array( $this, 'handle_page_load' ) );
		add_action( 'itsec-page-ajax', array( $this, 'handle_ajax_request' ) );
		add_action( 'admin_print_scripts', array( $this, 'add_scripts' ) );
		add_action( 'admin_print_styles', array( $this, 'add_styles' ) );

		add_filter( 'screen_settings', array( $this, 'filter_screen_settings' ) );

		$this->set_translation_strings();


		require( dirname( __FILE__ ) . '/module-settings.php' );
		require( dirname( __FILE__ ) . '/sidebar-widget.php' );

		require_once( ITSEC_Core::get_core_dir() . '/lib/form.php' );


		do_action( 'itsec-logs-page-init' );
		do_action( 'itsec-logs-page-register-widgets' );


		if ( ! empty( $_POST ) && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
			$this->handle_post();
		}
	}

	public function add_scripts() {
		ITSEC_Lib::enqueue_util();

		foreach ( $this->modules as $id => $module ) {
			$module->enqueue_scripts_and_styles();
		}

		foreach ( $this->widgets as $id => $widget ) {
			$widget->enqueue_scripts_and_styles();
		}

		$vars = array(
			'ajax_action'   => 'itsec_logs_page',
			'ajax_nonce'    => wp_create_nonce( 'itsec-logs-nonce' ),
			'logs_page_url' => ITSEC_Core::get_logs_page_url(),
			'translations'  => $this->translations,
		);

		wp_enqueue_script( 'itsec-logs-page-script', plugins_url( 'js/logs.js', __FILE__ ), array( 'jquery-ui-dialog' ), ITSEC_Core::get_plugin_build(), true );
		wp_localize_script( 'itsec-logs-page-script', 'itsec_page', $vars );
	}

	public function add_styles() {
		wp_enqueue_style( 'itsec-settings-page-style', plugins_url( 'css/style.css', __FILE__ ), array(), ITSEC_Core::get_plugin_build() );
		wp_enqueue_style( 'wp-jquery-ui-dialog' );
	}

	private function set_translation_strings() {
		$this->translations = array(
			'show_raw_details'           => esc_html__( 'Show Raw Details', 'it-l10n-ithemes-security-pro' ),
			'hide_raw_details'           => esc_html__( 'Hide Raw Details', 'it-l10n-ithemes-security-pro' ),
			'loading'                    => esc_html__( 'Loading...', 'it-l10n-ithemes-security-pro' ),
			/* translators: 1: loading gif image */
			'log_migration_started'      => esc_html__( '%1$s Migrating log entries from an older format. This message will update when the migration is complete.', 'it-l10n-ithemes-security-pro' ),
			'log_migration_failed'       => esc_html__( 'The log entry migration failed. Reload the page to try again.', 'it-l10n-ithemes-security-pro' ),
			'log_migration_loading_url'  => admin_url( 'images/loading.gif' ),

			'ajax_invalid'            => new WP_Error( 'itsec-settings-page-invalid-ajax-response', __( 'An "invalid format" error prevented the request from completing as expected. The format of data returned could not be recognized. This could be due to a plugin/theme conflict or a server configuration issue.', 'it-l10n-ithemes-security-pro' ) ),

			'ajax_forbidden'          => new WP_Error( 'itsec-settings-page-forbidden-ajax-response: %1$s "%2$s"',  __( 'A "request forbidden" error prevented the request from completing as expected. The server returned a 403 status code, indicating that the server configuration is prohibiting this request. This could be due to a plugin/theme conflict or a server configuration issue. Please try refreshing the page and trying again. If the request continues to fail, you may have to alter plugin settings or server configuration that could account for this AJAX request being blocked.', 'it-l10n-ithemes-security-pro' ) ),

			'ajax_not_found'          => new WP_Error( 'itsec-settings-page-not-found-ajax-response: %1$s "%2$s"', __( 'A "not found" error prevented the request from completing as expected. The server returned a 404 status code, indicating that the server was unable to find the requested admin-ajax.php file. This could be due to a plugin/theme conflict, a server configuration issue, or an incomplete WordPress installation. Please try refreshing the page and trying again. If the request continues to fail, you may have to alter plugin settings, alter server configurations, or reinstall WordPress.', 'it-l10n-ithemes-security-pro' ) ),

			'ajax_server_error'       => new WP_Error( 'itsec-settings-page-server-error-ajax-response: %1$s "%2$s"', __( 'A "internal server" error prevented the request from completing as expected. The server returned a 500 status code, indicating that the server was unable to complete the request due to a fatal PHP error or a server problem. This could be due to a plugin/theme conflict, a server configuration issue, a temporary hosting issue, or invalid custom PHP modifications. Please check your server\'s error logs for details about the source of the error and contact your hosting company for assistance if required.', 'it-l10n-ithemes-security-pro' ) ),

			'ajax_unknown'            => new WP_Error( 'itsec-settings-page-ajax-error-unknown: %1$s "%2$s"', __( 'An unknown error prevented the request from completing as expected. This could be due to a plugin/theme conflict or a server configuration issue.', 'it-l10n-ithemes-security-pro' ) ),

			'ajax_timeout'            => new WP_Error( 'itsec-settings-page-ajax-error-timeout: %1$s "%2$s"', __( 'A timeout error prevented the request from completing as expected. The site took too long to respond. This could be due to a plugin/theme conflict or a server configuration issue.', 'it-l10n-ithemes-security-pro' ) ),

			'ajax_parsererror'        => new WP_Error( 'itsec-settings-page-ajax-error-parsererror: %1$s "%2$s"', __( 'A parser error prevented the request from completing as expected. The site sent a response that jQuery could not process. This could be due to a plugin/theme conflict or a server configuration issue.', 'it-l10n-ithemes-security-pro' ) ),
		);

		foreach ( $this->translations as $key => $message ) {
			if ( is_wp_error( $message ) ) {
				$messages = ITSEC_Response::get_error_strings( $message );
				$this->translations[$key] = $messages[0];
			}
		}
	}

	private function handle_post() {
		if ( ITSEC_Core::is_ajax_request() ) {
			return;
		}

		if ( ! empty( $_POST['screenoptionnonce'] ) ) {
			check_admin_referer( 'screen-options-nonce', 'screenoptionnonce' );

			if ( isset( $_POST['apply'] ) ) {
				require_once( ITSEC_Core::get_core_dir() . '/lib/log-util.php' );

				$options = ITSEC_Form::get_post_data( array_keys( ITSEC_Log_Util::get_logs_page_screen_options() ) );
				ITSEC_Log_Util::set_logs_page_screen_options( $options );

				ITSEC_Response::add_message( __( 'Your screen options saved successfully.', 'it-l10n-ithemes-security-pro' ) );
			} else if ( isset( $_POST['mark_all_seen'] ) ) {
				require_once( ITSEC_Core::get_core_dir() . '/lib/log-util.php' );

				$options['last_seen'] = $_POST['current_time_gmt'];
				ITSEC_Log_Util::set_logs_page_screen_options( $options );

				ITSEC_Response::add_message( __( 'Log entries hidden.', 'it-l10n-ithemes-security-pro' ) );
			} else if ( isset( $_POST['mark_all_unseen'] ) ) {
				require_once( ITSEC_Core::get_core_dir() . '/lib/log-util.php' );

				$options['last_seen'] = 0;
				ITSEC_Log_Util::set_logs_page_screen_options( $options );

				ITSEC_Response::add_message( __( 'Log entries shown.', 'it-l10n-ithemes-security-pro' ) );
			}
		} else if ( ! empty( $_POST['itsec_clear_logs'] ) && 'clear_logs' === $_POST['itsec_clear_logs'] ) {
			if ( ! wp_verify_nonce( $_POST['wp_nonce'], 'itsec_clear_logs' ) ) {
				die( __( 'Security error!', 'it-l10n-ithemes-security-pro' ) );
			}

			global $itsec_logger;

			$itsec_logger->purge_logs( true );
		} else {
			$post_data = ITSEC_Form::get_post_data();
			$saved = true;
			$js_function_calls = array();

			if ( ! empty( $_POST['widget-id'] ) ) {
				$id = $_POST['widget-id'];

				if ( isset( $post_data[$id] ) && isset( $this->widgets[$id] ) ) {
					$widget = $this->widgets[$id];

					$widget->handle_form_post( $post_data[$id] );
				}
			} else {
				if ( ! empty( $_POST['module'] ) ) {
					if ( isset( $this->modules[$_POST['module']] ) ) {
						$modules = array( $_POST['module'] => $this->modules[$_POST['module']] );
					} else {
						ITSEC_Response::add_error( new WP_Error( 'itsec-settings-save-unrecognized-module', sprintf( __( 'The supplied module (%s) is not recognized. The module settings could not be saved.', 'it-l10n-ithemes-security-pro' ), $_POST['module'] ) ) );
						$modules = array();
					}
				} else {
					$modules = $this->modules;
				}

				foreach ( $modules as $id => $module ) {
					if ( isset( $post_data[$id] ) ) {
						$results = $module->handle_form_post( $post_data[$id] );
					}
				}

				if ( ITSEC_Response::is_success() ) {
					if ( ITSEC_Response::get_show_default_success_message() ) {
						ITSEC_Response::add_message( __( 'The settings saved successfully.', 'it-l10n-ithemes-security-pro' ) );
					}
				} else {
					if ( ITSEC_Response::get_show_default_error_message() ) {
						$error_count = ITSEC_Response::get_error_count();

						if ( $error_count > 0 ) {
							ITSEC_Response::add_error( new WP_Error( 'itsec-settings-data-not-saved', _n( 'The settings could not be saved. Please correct the error above and try again.', 'The settings could not be saved. Please correct the errors above and try again.', $error_count, 'it-l10n-ithemes-security-pro' ) ) );
						} else {
							ITSEC_Response::add_error( new WP_Error( 'itsec-settings-data-not-saved-missing-error', __( 'The settings could not be saved. Due to an unknown error. Please try refreshing the page and trying again.', 'it-l10n-ithemes-security-pro' ) ) );
						}
					}
				}
			}


			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				return;
			}

			ITSEC_Response::maybe_regenerate_wp_config();
			ITSEC_Response::maybe_regenerate_server_config();
			ITSEC_Response::maybe_do_force_logout();
			ITSEC_Response::maybe_do_redirect();
		}
	}

	public function handle_ajax_request() {
		if ( WP_DEBUG ) {
			ini_set( 'display_errors', 1 );
		}

		if ( ! empty( $_POST['method'] ) && 'handle_logs_migration' === $_POST['method'] ) {
			require_once( ITSEC_Core::get_core_dir() . '/lib/log-util.php' );

			$complete = ITSEC_Log_Util::migrate_old_log_entries();

			if ( $complete ) {
				$message  = '<p>' . esc_html__( 'Migration complete. Please refresh the page to see all log entries.', 'it-l10n-ithemes-security-pro' ) . '</p>';
				$message .= '<a href="' . esc_url( $this->self_url ) . '" class="button-secondary">' . esc_html__( 'Refresh Page', 'it-l10n-ithemes-security-pro' ) . '</a>';

				ITSEC_Response::set_response( $message );
			} else {
				ITSEC_Response::set_response( 'incomplete' );
			}
		} else {
			ITSEC_Core::set_interactive( true );

			$id = ( isset( $_POST['id'] ) && is_string( $_POST['id'] ) ) ? $_POST['id'] : '';

			if ( empty( $GLOBALS['hook_suffix'] ) ) {
				$GLOBALS['hook_suffix'] = 'toplevel_page_itsec';
			}


			if ( false === check_ajax_referer( 'itsec-logs-nonce', 'nonce', false ) ) {
				ITSEC_Response::add_error( new WP_Error( 'itsec-logs-page-failed-nonce', __( 'A nonce security check failed, preventing the request from completing as expected. Please try reloading the page and trying again.', 'it-l10n-ithemes-security-pro' ) ) );
			} else if ( ! ITSEC_Core::current_user_can_manage() ) {
				ITSEC_Response::add_error( new WP_Error( 'itsec-logs-page-insufficient-privileges', __( 'A permissions security check failed, preventing the request from completing as expected. The currently logged in user does not have sufficient permissions to make this request. Please try reloading the page and trying again.', 'it-l10n-ithemes-security-pro' ) ) );
			} else if ( empty( $id ) ) {
				ITSEC_Response::add_error( new WP_Error( 'itsec-logs-page-missing-method', __( 'The server did not receive a valid request. The required "id" argument is missing. Please try again.', 'it-l10n-ithemes-security-pro' ) ) );
			} else {
				ITSEC_Modules::load_module_file( 'logs.php' );

				$entry = ITSEC_Log::get_entry( $id );

				if ( false === strpos( $entry['code'], '::' ) ) {
					$code = $entry['code'];
					$code_data = array();
				} else {
					list( $code, $code_data ) = explode( '::', $entry['code'], 2 );
					$code_data = explode( ',', $code_data );
				}


				$timestamp = strtotime( $entry['timestamp'] );
				$datetime = date( 'Y-m-d H:i:s', $timestamp + ITSEC_Core::get_time_offset() );
				$types = ITSEC_Log::get_types_for_display();

				if ( isset( $types[$entry['type']] ) ) {
					$type = $types[$entry['type']];
				} else {
					$type = esc_html( $entry['type'] );
				}

				$user = get_user_by( 'id', $entry['user_id'] );

				if ( $user ) {
					$username = $user->user_login;
				} else {
					$username = '';
				}

				if ( 'wp-cli' === $entry['url'] ) {
					$url = esc_html__( 'WP-CLI Command', 'it-l10n-ithemes-security-pro' );
				} else if ( 'wp-cron' === $entry['url'] ) {
					$url = esc_html__( 'WP-Cron Scheduled Task', 'it-l10n-ithemes-security-pro' );
				} else if ( 'unknown' === $entry['url'] ) {
					$url = esc_html__( 'Unknown', 'it-l10n-ithemes-security-pro' );
				} else {
					$url = esc_html( $entry['url'] );
				}

				$details = array(
					'module'      => array(
						'header'  => esc_html__( 'Module', 'it-l10n-ithemes-security-pro' ),
						'content' => esc_html( $entry['module'] ),
						'order'   => 0,
					),
					'type'        => array(
						'header'  => esc_html__( 'Type', 'it-l10n-ithemes-security-pro' ),
						'content' => $type,
						'order'   => 10,
					),
					'description' => array(
						'header'  => esc_html__( 'Description', 'it-l10n-ithemes-security-pro' ),
						'content' => esc_html( $code ),
						'order'   => 20,
					),
					'timestamp'   => array(
						'header'  => esc_html__( 'Timestamp', 'it-l10n-ithemes-security-pro' ),
						'content' => esc_html( $datetime ),
						'order'   => 30,
					),
					'host'        => array(
						'header'  => esc_html__( 'Host', 'it-l10n-ithemes-security-pro' ),
						'content' => '<code>' . esc_html( $entry['remote_ip'] ) . '</code>',
						'order'   => 40,
					),
					'user'        => array(
						'header'  => esc_html__( 'User', 'it-l10n-ithemes-security-pro' ),
						'content' => esc_html( $username ),
						'order'   => 50,
					),
					'url'         => array(
						'header'  => esc_html__( 'URL', 'it-l10n-ithemes-security-pro' ),
						'content' => '<code>' . $url . '</code>',
						'order'   => 60,
					),
					'raw-details'    => array(
						'header'  => esc_html__( 'Raw Details', 'it-l10n-ithemes-security-pro' ),
						'content' => true,
						'order'   => PHP_INT_MAX,
					),
				);


				$details = apply_filters( "itsec_logs_prepare_{$entry['module']}_entry_for_details_display", $details, $entry, $code, $code_data );

				if ( isset( $details['raw-details'] ) ) {
					if ( true === $details['raw-details']['content'] ) {
						// Ensure that Raw Details is listed last.
						$raw_details = $details['raw-details'];
						unset( $details['raw-details'] );

						if ( empty( $entry['parent_id'] ) ) {
							unset( $entry['parent_id'] );
						}

						if ( strlen( serialize( $entry['data'] ) ) > 1048576 ) {
							// Don't run the risk of crashing the process when trying to display a large data set.
							$entry['data'] = '[' . esc_html__( 'Too large to display', 'it-l10n-ithemes-security-pro' ) . ']';
						}

						$raw_details['content'] = '<pre>' . preg_replace( '/^    /m', '', substr( ITSEC_Lib::get_print_r( $entry ), 23 ) ) . '</pre>';
						$details['raw-details'] = $raw_details;
					}

					$details['raw-details']['content'] = '<p><a class="itsec-log-raw-details-toggle" href="#">' . $this->translations['show_raw_details'] . '</a></p><div class="itsec-log-raw-details">' . $details['raw-details']['content'] . '</div>';
				}

				$i = 1;

				foreach ( $details as $column => $detail ) {
					if ( ! isset( $detail['order'] ) ) {
						$details[ $column ]['order'] = PHP_INT_MAX - 10 * $i;
						$i ++;
					}
				}

				$details = wp_list_sort( $details, 'order', 'ASC', true );
				$messages = ITSEC_Lib_Remote_Messages::get_messages_for_placement( array( 'logs' => array( 'module' => $entry['module'], 'code' => $entry['code'] ) ) );
			}

			ob_start();

?>
	<?php if ( $messages ) : ?>
		<div class="itsec-logs-service-status">
			<?php foreach ( $messages as $message ): ?>
				<div class="notice notice-alt notice-<?php echo esc_attr( $message['type'] ); ?> below-h2">
					<p><?php echo $message['message']; ?></p>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
	<table class="form-table">
		<?php foreach ( $details as $row ) : ?>
			<tr>
				<th scope="row"><?php echo $row['header']; ?></th>
				<td><?php echo $row['content'] ?></td>
			</tr>
		<?php endforeach; ?>
	</table>
<?php

			ITSEC_Response::set_response( ob_get_clean() );
		}


		ITSEC_Response::send_json();
	}

	public function filter_screen_settings( $settings ) {
		require_once( ITSEC_Core::get_core_dir() . '/lib/log-util.php' );
		$options = ITSEC_Log_Util::get_logs_page_screen_options();

		$form = new ITSEC_Form( $options );

		ob_start();

?>
	<fieldset class="screen-options">
		<legend><?php esc_html_e( 'Pagination' ); ?></legend>
		<label for="itsec_logs_page_entries_per_page"><?php esc_html_e( 'Number of items per page:' ); ?></label>
		<?php $form->add_number( 'per_page', array( 'step' => 1, 'min' => 1, 'max' => 999, 'maxlength' => 3 ) ); ?>
	</fieldset>

	<fieldset>
		<legend><?php esc_html_e( 'View Mode' ); ?></legend>

		<label for="itsec-default_view-important">
			<?php $form->add_radio( 'default_view', 'important' ); ?>
			<?php esc_html_e( 'Important Events', 'it-l10n-ithemes-security-pro' ); ?>
		</label>
		<label for="itsec-default_view-all">
			<?php $form->add_radio( 'default_view', 'all' ); ?>
			<?php esc_html_e( 'All Events', 'it-l10n-ithemes-security-pro' ); ?>
		</label>
		<label for="itsec-default_view-critical-issue">
			<?php $form->add_radio( 'default_view', 'critical-issue' ); ?>
			<?php esc_html_e( 'Critical Issues', 'it-l10n-ithemes-security-pro' ); ?>
		</label>
	</fieldset>

	<fieldset>
	<legend><?php esc_html_e( 'Colors', 'it-l10n-ithemes-security-pro' ); ?></legend>
		<label for="itsec-color">
			<?php $form->add_checkbox( 'color' ); ?>
			<?php esc_html_e( 'Use colors to indicate the severity of each entry.', 'it-l10n-ithemes-security-pro' ); ?>
		</label>
	</fieldset>

	<fieldset>
		<legend><?php esc_html_e( 'Advanced Entries for Support and Developers', 'it-l10n-ithemes-security-pro' ); ?></legend>
		<label for="itsec-show_debug">
			<?php $form->add_checkbox( 'show_debug' ); ?>
			<?php esc_html_e( 'Show Debug entries.', 'it-l10n-ithemes-security-pro' ); ?>
		</label>
		<br />
		<label for="itsec-show_process">
			<?php $form->add_checkbox( 'show_process' ); ?>
			<?php esc_html_e( 'Show Process entries.', 'it-l10n-ithemes-security-pro' ); ?>
		</label>
	</fieldset>

	<p class="submit">
		<?php $form->add_submit( 'apply', __( 'Apply' ) ); ?>
	</p>

	<p class="submit">
		<?php $form->add_submit( 'mark_all_seen', array( 'class' => 'button-secondary', 'value' => esc_html__( 'Hide Current Entries', 'it-l10n-ithemes-security-pro' ), 'title' => esc_html__( 'Hide existing entries from view without deleting them.', 'it-l10n-ithemes-security-pro' ) ) ); ?>
		&nbsp;
		<?php $form->add_submit( 'mark_all_unseen', array( 'class' => 'button-secondary', 'value' => esc_html__( 'Show All Entries', 'it-l10n-ithemes-security-pro' ), 'title' => esc_html__( 'Show all entries, including ones that were previously hidden.', 'it-l10n-ithemes-security-pro' ) ) ); ?>
		<?php $form->add_hidden( 'current_time_gmt', ITSEC_Core::get_current_time_gmt() ); ?>
	</p>
<?php

		return ob_get_clean();
	}

	public function handle_page_load( $self_url ) {
		$this->self_url = $self_url;

		$this->show_settings_page();
	}

	private function show_settings_page() {
		require_once( ITSEC_Core::get_core_dir() . '/lib/class-itsec-wp-list-table.php' );
		require_once( ITSEC_Core::get_core_dir() . '/admin-pages/logs-list-table.php' );


		$form = new ITSEC_Form();

?>
	<div class="wrap">
		<h1>
			<?php _e( 'iThemes Security', 'it-l10n-ithemes-security-pro' ); ?>
			<a href="<?php echo esc_url( ITSEC_Core::get_settings_page_url() ); ?>" class="page-title-action"><?php _e( 'Manage Settings', 'it-l10n-ithemes-security-pro' ); ?></a>
			<a href="<?php echo esc_url( apply_filters( 'itsec_support_url', 'https://wordpress.org/support/plugin/better-wp-security' ) ); ?>" class="page-title-action"><?php _e( 'Support', 'it-l10n-ithemes-security-pro' ); ?></a>
		</h1>

		<div id="itsec-settings-messages-container">
			<?php
				foreach ( ITSEC_Response::get_errors() as $error ) {
					ITSEC_Lib::show_error_message( $error );
				}

				foreach ( ITSEC_Response::get_messages() as $message ) {
					ITSEC_Lib::show_status_message( $message );
				}
			?>
		</div>

		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-2 hide-if-no-js">
				<div id="postbox-container-2" class="postbox-container">
					<?php $this->show_old_logs_migration(); ?>

					<?php if ( 'file' === ITSEC_Modules::get_setting( 'global', 'log_type' ) ) : ?>
						<p><?php _e( 'To view logs within the plugin you must enable database logging in the Global Settings. File logging is not available for access within the plugin itself.', 'it-l10n-ithemes-security-pro' ); ?></p>
						<p><?php printf( wp_kses( __( 'The log file can be found at: <code>%s</code>', 'it-l10n-ithemes-security-pro' ), array( 'code' => array() ) ), ITSEC_Log::get_log_file_path() ); ?></p>
					<?php else : ?>
						<div class="itsec-module-cards-container list">
							<?php
								$list = new ITSEC_Logs_List_Table();

								$list->prepare_items();
								$list->views();
								$form->start_form( array( 'method' => 'GET' ) );
								$form->add_hidden( 'page', 'itsec-logs' );
								$list->display();
								$form->end_form();
							?>
						</div>
					<?php endif; ?>
				</div>
				<div class="itsec-modal-background"></div>
				<div id="itsec-log-details-container" class="grid">
					<div class="itsec-module-settings-container">
						<div class="itsec-modal-navigation">
							<button class="dashicons itsec-close-modal"></button>
						</div>
						<div class="itsec-module-settings-content-container">
							<div class="itsec-module-settings-content">
								<div class="itsec-module-messages-container"></div>
								<div class="itsec-module-settings-content-main"></div>
							</div>
						</div>
					</div>
				</div>

				<div id="postbox-container-1" class="postbox-container">
					<?php foreach ( $this->widgets as $id => $widget ) : ?>
						<?php $form->start_form( "itsec-sidebar-widget-form-$id" ); ?>
							<?php $form->add_nonce( 'itsec-logs-page' ); ?>
							<?php $form->add_hidden( 'widget-id', $id ); ?>
							<div id="itsec-sidebar-widget-<?php echo $id; ?>" class="postbox itsec-sidebar-widget">
								<h3 class="hndle ui-sortable-handle"><span><?php echo esc_html( $widget->title ); ?></span></h3>
								<div class="inside">
									<?php $this->get_widget_settings( $id, $form, true ); ?>
								</div>
							</div>
						<?php $form->end_form(); ?>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="hide-if-js">
				<p class="itsec-warning-message"><?php _e( 'iThemes Security requires Javascript in order for the settings to be modified. Please enable Javascript to configure the settings.', 'it-l10n-ithemes-security-pro' ); ?></p>
			</div>

			<div class="hidden" id="itsec-logs-cache">
			</div>
		</div>
	</div>
<?php

	}

	public function register_widget( $widget ) {
		if ( ! is_object( $widget ) || ! is_a( $widget, 'ITSEC_Settings_Page_Sidebar_Widget' ) ) {
			trigger_error( 'An invalid widget was registered.', E_USER_ERROR );
			return;
		}

		if ( isset( $this->modules[$widget->id] ) ) {
			trigger_error( "A widget with the id of {$widget->id} is registered. Widget id's must be unique from any other module or widget." );
			return;
		}

		if ( isset( $this->widgets[$widget->id] ) ) {
			trigger_error( "A widget with the id of {$widget->id} is already registered. Widget id's must be unique from any other module or widget." );
			return;
		}


		$this->widgets[$widget->id] = $widget;
	}

	private function get_widget_settings( $id, $form = false, $echo = false ) {
		if ( ! isset( $this->widgets[$id] ) ) {
			$error = new WP_Error( 'itsec-settings-page-get-widget-settings-invalid-id', sprintf( __( 'The requested widget (%s) does not exist. Logs for it cannot be rendered.', 'it-l10n-ithemes-security-pro' ), $id ) );

			if ( $echo ) {
				ITSEC_Lib::show_error_message( $error );
			} else {
				return $error;
			}
		}

		if ( false === $form ) {
			$form = new ITSEC_Form();
		}

		$widget = $this->widgets[$id];

		$form->add_input_group( $id );
		$form->set_defaults( $widget->get_defaults() );

		if ( ! $echo ) {
			ob_start();
		}

		$widget->render( $form );

		$form->remove_all_input_groups();

		if ( ! $echo ) {
			return ob_get_clean();
		}
	}

	private function show_old_logs_migration() {
		require_once( ITSEC_Core::get_core_dir() . '/lib/log-util.php' );

		if ( ITSEC_Log_Util::has_old_log_entries() ) {
			echo '<div id="old-logs-migration-status"></div>';
		}
	}
}

new ITSEC_Logs_Page();
