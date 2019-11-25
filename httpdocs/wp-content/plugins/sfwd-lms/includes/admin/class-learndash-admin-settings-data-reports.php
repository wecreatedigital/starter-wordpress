<?php

if ( ! class_exists( 'Learndash_Admin_Settings_Data_Reports' ) ) {
	class Learndash_Admin_Settings_Data_Reports {

		protected $process_times = array();
		private $report_actions  = array();

		public function __construct() {

			$this->parent_menu_page_url  = 'admin.php?page=learndash-lms-reports';
			$this->menu_page_capability  = LEARNDASH_ADMIN_CAPABILITY_CHECK;
			$this->settings_page_id      = 'learndash-lms-reports';
			$this->settings_page_title   = esc_html_x( 'Reports', 'Learndash Report Menu Label', 'learndash' );
			$this->settings_tab_title    = $this->settings_page_title;
			$this->settings_tab_priority = 0;

			add_action( 'init', array( $this, 'init_check_for_download_request' ) );
			add_action( 'admin_menu', array( $this, 'admin_menu' ) );

			if ( ! defined( 'LEARNDASH_PROCESS_TIME_PERCENT' ) ) {
				define( 'LEARNDASH_PROCESS_TIME_PERCENT', apply_filters( 'learndash_process_time_percent', 80 ) );
			}

			if ( ! defined( 'LEARNDASH_PROCESS_TIME_SECONDS' ) ) {
				define( 'LEARNDASH_PROCESS_TIME_SECONDS', apply_filters( 'learndash_process_time_seconds', 10 ) );
			}

		}

		public function init_check_for_download_request() {
			if ( isset( $_GET['ld-report-download'] ) ) {

				if ( ( isset( $_GET['data-nonce'] ) ) && ( ! empty( $_GET['data-nonce'] ) ) && ( isset( $_GET['data-slug'] ) ) && ( ! empty( $_GET['data-slug'] ) ) ) {

					if ( wp_verify_nonce( esc_attr( $_GET['data-nonce'] ), 'learndash-data-reports-' . esc_attr( $_GET['data-slug'] ) . '-' . get_current_user_id() ) ) {
						$transient_key = esc_attr( $_GET['data-slug'] ) . '_' . esc_attr( $_GET['data-nonce'] );

						$transient_data = $this->get_transient( $transient_key );
						if ( ( isset( $transient_data['report_filename'] ) ) && ( ! empty( $transient_data['report_filename'] ) ) ) {
							//$report_filename = ABSPATH . $transient_data['report_filename'];
							$report_filename = $transient_data['report_filename'];
							if ( ( file_exists( $report_filename ) ) && ( is_readable( $report_filename ) ) ) {
								$http_headers = array(
									'Content-Encoding: ' . DB_CHARSET,
									'Content-type: text/csv; charset=' . DB_CHARSET,
									'Content-Disposition: attachment; filename=' . basename( $report_filename ),
									'Pragma: no-cache',
									'Expires: 0',
								);
								$http_headers = apply_filters( 'learndash_csv_download_headers', $http_headers, $transient_data, esc_attr( $_GET['data-slug'] ) );
								if ( ! empty( $http_headers ) ) {
									foreach ( $http_headers as $http_header ) {
										header( $http_header );
									}
								}
								do_action( 'learndash_csv_download_after_headers' );

								echo file_get_contents( $report_filename );
							}
						}
					}
				}
				die();
			}
		}

		/**
		 * Register settings page
		 */
		public function admin_menu() {

			$element               = Learndash_Admin_Data_Upgrades::get_instance();
			$data_settings_courses = $element->get_data_settings( 'user-meta-courses' );
			$data_settings_quizzes = $element->get_data_settings( 'user-meta-quizzes' );

			if ( ( ! empty( $data_settings_courses ) ) && ( ! empty( $data_settings_quizzes ) ) ) {
				$this->settings_page_id = add_submenu_page(
					'learndash-lms',
					$this->settings_page_title,
					$this->settings_page_title,
					$this->menu_page_capability,
					$this->settings_page_id,
					array( $this, 'admin_page' )
				);
				add_action( 'load-' . $this->settings_page_id, array( $this, 'on_load_panel' ) );

			} else {
				// If the data upgreades have not been performed then we call the old Reports page output in ld-admin.php
				$this->settings_page_id = add_submenu_page(
					'learndash-lms',
					$this->settings_page_title,
					$this->settings_page_title,
					LEARNDASH_ADMIN_CAPABILITY_CHECK,
					'learndash-lms-reports',
					'learndash_lms_reports_page'
				);
			}
		}

		public function admin_tabs( $admin_menu_section, $ld_admin_tabs ) {
			if ( $admin_menu_section == $this->parent_menu_page_url ) {

				$ld_admin_tabs->add_admin_tab_item(
					$admin_menu_section,
					array(
						'id'   => $this->settings_page_id,
						'link' => add_query_arg( array( 'page' => $this->settings_page_id ), 'admin.php' ),
						'name' => $this->settings_tab_title,
					),
					$this->settings_tab_priority
				);
			}
		}


		public function on_load_panel() {

			wp_enqueue_style(
				'learndash_style',
				LEARNDASH_LMS_PLUGIN_URL . 'assets/css/style' . leardash_min_asset() . '.css',
				array(),
				LEARNDASH_SCRIPT_VERSION_TOKEN
			);
			wp_style_add_data( 'learndash_style', 'rtl', 'replace' );
			$learndash_assets_loaded['styles']['learndash_style'] = __FUNCTION__;

			wp_enqueue_style(
				'sfwd-module-style',
				LEARNDASH_LMS_PLUGIN_URL . 'assets/css/sfwd_module' . leardash_min_asset() . '.css',
				array(),
				LEARNDASH_SCRIPT_VERSION_TOKEN
			);
			wp_style_add_data( 'sfwd-module-style', 'rtl', 'replace' );
			$learndash_assets_loaded['styles']['sfwd-module-style'] = __FUNCTION__;

			wp_enqueue_script(
				'learndash-admin-settings-data-reports-script',
				LEARNDASH_LMS_PLUGIN_URL . 'assets/js/learndash-admin-settings-data-reports' . leardash_min_asset() . '.js',
				array( 'jquery' ),
				LEARNDASH_SCRIPT_VERSION_TOKEN,
				true
			);
			$learndash_assets_loaded['scripts']['learndash-admin-settings-data-reports-script'] = __FUNCTION__;

			$this->init_report_actions();

		}

		public function init_report_actions() {

			$this->report_actions = apply_filters( 'learndash_admin_report_register_actions', $this->report_actions );
		}

		public function admin_page() {

			do_action( 'learndash_settings_page_before_content' );
			?>
			<div id="learndash-settings" class="wrap">
				<h1><?php esc_html_e( 'User Reports', 'learndash' ); ?></h1>
				<form method="post" action="options.php">
					<div class="sfwd_options_wrapper sfwd_settings_left">
						<div id="advanced-sortables" class="meta-box-sortables">
							<div id="sfwd-courses_metabox" class="postbox learndash-settings-postbox">
								<div class="handlediv" title="<?php esc_html_e( 'Click to toggle', 'learndash' ); ?>"><br></div>
								<h3 class="hndle"><span><?php esc_html_e( 'User Reports', 'learndash' ); ?></span></h3>
								<div class="inside">
									<div class="sfwd sfwd_options sfwd-courses_settings">

										<table id="learndash-data-reports" class="wc_status_table widefat" cellspacing="0">
										<?php
											//error_log('report_actions<pre>'. print_r($this->report_actions, true) .'</pre>');
										foreach ( $this->report_actions as $report_action_slug => $report_action ) {
											$report_action['instance']->show_report_action();
										}
										?>
										</table>
									</div>
								</div>
							</div>
						</div>
					</div>
				</form>
			</div>
			<?php
		}

		public function do_data_reports( $post_data = array(), $reply_data = array() ) {

			$this->init_report_actions();

			if ( ( isset( $post_data['slug'] ) ) && ( ! empty( $post_data['slug'] ) ) ) {
				$post_data_slug = esc_attr( $post_data['slug'] );

				if ( isset( $this->report_actions[ $post_data_slug ] ) ) {
					$reply_data = $this->report_actions[ $post_data_slug ]['instance']->process_report_action( $post_data );
				}
			}
			return $reply_data;
		}


		public function init_process_times() {
			$this->process_times['started'] = time();
			$this->process_times['limit']   = ini_get( 'max_execution_time' );
			$this->process_times['limit']   = intval( $this->process_times['limit'] );
			if ( empty( $this->process_times['limit'] ) ) {
				$this->process_times['limit'] = 30;
			}
		}

		public function out_of_timer() {
			$this->process_times['current_time'] = time();

			$this->process_times['ticks']   = $this->process_times['current_time'] - $this->process_times['started'];
			$this->process_times['percent'] = ( $this->process_times['ticks'] / $this->process_times['limit'] ) * 100;

			// If we are over 80% of the allowed processing time or over 10 seconds then finish up and return
			if ( ( $this->process_times['percent'] >= LEARNDASH_PROCESS_TIME_PERCENT ) || ( $this->process_times['ticks'] > LEARNDASH_PROCESS_TIME_SECONDS ) ) {
				return true;
			}

			return false;
		}

		public function get_transient( $transient_key = '' ) {
			if ( ! empty( $transient_key ) ) {
				$options_key = 'learndash_reports_' . $transient_key;
				$options_key = str_replace( '-', '_', $options_key );
				return get_option( $options_key );
			}
		}

		public function set_option_cache( $transient_key = '', $transient_data = '' ) {

			if ( ! empty( $transient_key ) ) {
				$options_key = 'learndash_reports_' . $transient_key;
				$options_key = str_replace( '-', '_', $options_key );

				if ( ! empty( $transient_data ) ) {
					update_option( $options_key, $transient_data );
				} else {
					delete_option( $options_key );
				}
			}
		}

		// End of functions
	}
}

// Go ahead and inlcude out User Meta Courses upgrade class
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-data-reports-actions/class-learndash-admin-data-reports-user-courses.php';
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-data-reports-actions/class-learndash-admin-data-reports-user-quizzes.php';

add_action(
	'plugins_loaded',
	function() {
		new Learndash_Admin_Data_Reports_Courses();
		new Learndash_Admin_Data_Reports_Quizzes();
	}
);


function learndash_data_reports_ajax() {
	$reply_data = array( 'status' => false );

	if ( current_user_can( 'read' ) ) {
		if ( isset( $_POST['data'] ) ) {
			$post_data = $_POST['data'];
		} else {
			$post_data = array();
		}

		$ld_admin_settings_data_reports = new Learndash_Admin_Settings_Data_Reports();
		$reply_data['data']             = $ld_admin_settings_data_reports->do_data_reports( $post_data, $reply_data );
	}

	if ( ! empty( $reply_data ) ) {
		echo json_encode( $reply_data );
	}

	wp_die(); // this is required to terminate immediately and return a proper response
}

add_action( 'wp_ajax_learndash-data-reports', 'learndash_data_reports_ajax' );
