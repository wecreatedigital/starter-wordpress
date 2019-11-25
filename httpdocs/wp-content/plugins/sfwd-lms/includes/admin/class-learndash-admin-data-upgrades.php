<?php
/**
 * This class handles the data upgrade from the user meta arrays into a DB structure to
 * allow on the floy reporting. Plus to not bloat the user meta table.
 *
 * @package LearnDash
 * @subpackage Data Upgrades
 */

if ( ! class_exists( 'Learndash_Admin_Data_Upgrades' ) ) {
	/**
	 * Class to create the Data Upgrade for Courses.
	 */
	class Learndash_Admin_Data_Upgrades {

		/**
		 * Static instance of class.
		 *
		 * @var object $instance.
		 */
		protected static $instance;

		/**
		 * Static array of section instances.
		 *
		 * @var array $_instances
		 */
		protected static $_instances = array();

		/**
		 * Upgrade Actions array
		 *
		 * @var array $upgrade_actions
		 */
		protected static $upgrade_actions = array();

		/**
		 * Private flag for when admin notices have been
		 * show. This prevent multiple admin notices.
		 *
		 * @var boolean $admin_notice_shown
		 */
		private static $admin_notice_shown = false;

		/**
		 * Variable to contain the current processing times.
		 *
		 * @var array $process_times
		 */
		protected $process_times = array();

		/**
		 * Data Slug used to itentify each instance.
		 *
		 * @var string $data_slug
		 */
		protected $data_slug;

		/**
		 * Meta Key used to itentify each instance.
		 *
		 * @var string $meta_key
		 */
		protected $meta_key;

		/**
		 * Transient Prefix
		 *
		 * @var string $transient_prefix
		 */
		protected $transient_prefix = 'ld-upgraded-';

		/**
		 * Transient Key
		 *
		 * @var string $transient_key
		 */
		protected $transient_key = '';

		/**
		 * Transient Data
		 *
		 * @var array $transient_data
		 */
		protected $transient_data = array();

		/**
		 * Data Settings Loadded
		 *
		 * @var boolean $data_settings_loaded
		 */
		protected $data_settings_loaded = false;

		/**
		 * Data Settings array
		 *
		 * @var array $data_settings
		 */
		protected $data_settings = array();

		/**
		 * Public constructor for class
		 */
		protected function __construct() {
			$this->meta_key = $this->transient_prefix . $this->data_slug;

			add_action( 'admin_init', array( $this, 'admin_init' ) );

			if ( ! defined( 'LEARNDASH_PROCESS_TIME_PERCENT' ) ) {
				define( 'LEARNDASH_PROCESS_TIME_PERCENT', apply_filters( 'learndash_process_time_percent', 80 ) );
			}

			if ( ! defined( 'LEARNDASH_PROCESS_TIME_SECONDS' ) ) {
				define( 'LEARNDASH_PROCESS_TIME_SECONDS', apply_filters( 'learndash_process_time_seconds', 10 ) );
			}
		}

		/**
		 * Get the current instance of this class or new.
		 *
		 * @since 2.3
		 *
		 * @param string $instance_key Unique identifier for instance.
		 * @return object instance of class.
		 */
		public static function get_instance( $instance_key = '' ) {
			if ( ! empty( $instance_key ) ) {
				if ( isset( self::$_instances[ $instance_key ] ) ) {
					return self::$_instances[ $instance_key ];
				}
			} else {
				if ( null === self::$instance ) {
					self::$instance = new static();
				}

				return self::$instance;
			}
		}

		/**
		 * Add instance to static tracking array
		 *
		 * @since 2.4.0
		 */
		final public static function add_instance() {
			$section = get_called_class();

			if ( ! isset( self::$_instances[ $section ] ) ) {
				self::$_instances[ $section ] = new $section();
			}
		}

		/**
		 * Register the data upgrade action.
		 *
		 * @since 2.3
		 */
		public function register_upgrade_action() {
			// Add ourselved to the upgrade actions.
			if ( ! isset( self::$upgrade_actions[ $this->data_slug ] ) ) {
				self::$upgrade_actions[ $this->data_slug ] = array(
					'class'    => get_called_class(),
					'instance' => $this,
					'slug'     => $this->data_slug,
				);
			}
		}

		/**
		 * Initialize the LearnDash Settings array
		 *
		 * @since 2.3
		 *
		 * @param bool $force_reload optional to force reload from database.
		 * @return void.
		 */
		private function init_data_settings( $force_reload = false ) {

			if ( ( true !== $this->data_settings_loaded ) || ( true === $force_reload ) ) {
				$this->data_settings_loaded = true;

				$this->data_settings = get_option( 'learndash_data_settings', array() );

				if ( ! isset( $this->data_settings['db_version'] ) ) {
					$this->data_settings['db_version'] = 0;
				}
			}
		}

		/**
		 * Get the LearnDash Settings array
		 *
		 * @since 2.3
		 *
		 * @param string $key optional to return only specifc key value.
		 * @return mixed.
		 */
		public function get_data_settings( $key = '' ) {
			$this->init_data_settings( true );

			if ( ! empty( $key ) ) {
				if ( isset( $this->data_settings[ $key ] ) ) {
					return $this->data_settings[ $key ];
				}
			} else {
				return $this->data_settings;
			}
		}

		/**
		 * Set data upgrade option for instance.
		 *
		 * @since 2.3
		 *
		 * @param string $key Key to data upgrade instance.
		 * @param string $value Value for key iinstance.
		 */
		public function set_data_settings( $key = '', $value = '' ) {
			if ( empty( $key ) ) {
				return;
			}

			$this->init_data_settings( true );
			$this->data_settings[ $key ] = $value;

			return update_option( 'learndash_data_settings', $this->data_settings );
		}

		/**
		 * General admin_init hook function to check admin notices.
		 *
		 * @since 2.3
		 */
		public function admin_init() {

			$this->init_data_settings();

			if ( true === $this->check_upgrade_admin_notice() ) {
				add_action( 'admin_notices', array( $this, 'show_upgrade_admin_notice' ) );
			}
		}

		/**
		 * Shows Data Upgrade admin notice.
		 *
		 * @version 2.3
		 */
		public function show_upgrade_admin_notice() {
			if ( true !== self::$admin_notice_shown ) {
				self::$admin_notice_shown = true;

				$admin_notice_message = sprintf(
					// translators: placeholder: link to LearnDash Data Upgrade admin page.
					esc_html_x( 'LearnDash Notice: Please perform a %s. This is a required step to ensure accurate reporting.', 'placeholder: link to LearnDash Data Upgrade admin page', 'learndash' ),
					'<a href="' . admin_url( 'admin.php?page=learndash_data_upgrades' ) . '">' . esc_html__( 'LearnDash Data Upgrade', 'learndash' ) . '</a>'
				);
				?>
				<div id="ld-data-upgrade-notice-error" class="notice notice-info is-dismissible">
					<p><?php echo $admin_notice_message; ?></p>
				</div>
				<?php
			}
		}

		/**
		 * Trigger admin notice if Data Upgrades need to be performed.
		 *
		 * @since 2.3
		 */
		public function check_upgrade_admin_notice() {
			$show_admin_notice = false;

			if ( ( isset( $this->data_settings['user-meta-courses']['version'] ) ) && ( $this->data_settings['user-meta-courses']['version'] < LEARNDASH_SETTINGS_TRIGGER_UPGRADE_VERSION ) ) {
				$show_admin_notice = true;
			}

			if ( ( isset( $this->data_settings['user-meta-quizzes']['version'] ) ) && ( $this->data_settings['user-meta-quizzes']['version'] < LEARNDASH_SETTINGS_TRIGGER_UPGRADE_VERSION ) ) {
				$show_admin_notice = true;
			}

			return $show_admin_notice;
		}

		/**
		 * Show the admin page content.
		 *
		 * @since 2.3
		 */
		public function admin_page() {
			?>

			<table id="learndash-data-upgrades" class="wc_status_table widefat" cellspacing="0">
			<?php
			foreach ( self::$upgrade_actions as $upgrade_action_slug => $upgrade_action ) {
				$upgrade_action['instance']->show_upgrade_action();
			}
			?>
			</table>
			<?php
		}

		/**
		 * Placeholder function. This function is called when displaying the admin page.
		 */
		public function show_upgrade_action() {
			// Does nothing.
		}

		/**
		 * Placeholder function. This function is called when processing the upgrade action.
		 */
		public function process_upgrade_action() {
			// Does nothing.
		}

		/**
		 * Set the last run completed data upgrade for instance.
		 *
		 * @since 2.3
		 *
		 * @param array $data Last run data array.
		 */
		public function set_last_run_info( $data = array() ) {
			$data_settings = array_merge(
				array(
					'last_run' => time(),
					'user_id'  => get_current_user_id(),
					'version'  => LEARNDASH_SETTINGS_TRIGGER_UPGRADE_VERSION,
				),
				$data
			);

			$data_settings = array_diff_key(
				$data_settings,
				array(
					'nonce'            => '',
					'slug'             => '',
					'continue'         => '',
					'progress_label'   => '',
					'result_count'     => '',
					'progress_percent' => '',
				)
			);

			$this->set_data_settings( $this->data_slug, $data_settings );
		}

		/**
		 * Return the last run details for the last completed data upgrade for the instance.
		 *
		 * @since 2.3
		 */
		public function get_last_run_info() {
			$last_run_info = '';

			$data_settings = $this->get_data_settings( $this->data_slug );

			$last_run_info = esc_html__( 'Last run: none', 'learndash' );
			if ( ! empty( $data_settings ) ) {
				if ( isset( $data_settings['user_id'] ) ) {
					$user = get_user_by( 'id', $data_settings['user_id'] );
					if ( ( $user ) && ( is_a( $user, 'WP_User' ) ) ) {
						$last_run_info = sprintf(
							// translators: placeholders: date/time, user name.
							_x( 'Last run: %1$s by %2$s', 'placeholders: date/time, user name', 'learndash' ),
							learndash_adjust_date_time_display( $data_settings['last_run'] ),
							$user->display_name
						);
					}
				}
			}

			return $last_run_info;
		}

		/**
		 * Entry point to perform data upgrade for instance.
		 *
		 * @since 2.3
		 *
		 * @param array $post_data Array of post dats sent via AJAX.
		 * @param array $reply_data Array of return data returned to browser.
		 *
		 * @return array $reply_data.
		 */
		public function do_data_upgrades( $post_data = array(), $reply_data = array() ) {

			if ( ( isset( $post_data['slug'] ) ) && ( ! empty( $post_data['slug'] ) ) ) {
				$post_data_slug = esc_attr( $post_data['slug'] );

				if ( isset( self::$upgrade_actions[ $post_data_slug ] ) ) {
					if ( isset( $post_data['data'] ) ) {
						$data = $post_data['data'];
					} else {
						$data = array();
					}

					$reply_data = self::$upgrade_actions[ $post_data_slug ]['instance']->process_upgrade_action( $post_data );
				}
			}
			return $reply_data;
		}

		/**
		 * Initialize the processing timer.
		 *
		 * @since 2.3
		 */
		protected function init_process_times() {
			$this->process_times['started'] = time();
			$this->process_times['limit']   = intval( ini_get( 'max_execution_time' ) );
			if ( empty( $this->process_times['limit'] ) ) {
				$this->process_times['limit'] = 60;
			}
		}

		/**
		 * Check if the process timer is out of time.
		 *
		 * @since 2.3
		 */
		protected function out_of_timer() {
			$this->process_times['current_time'] = time();

			$this->process_times['ticks']   = $this->process_times['current_time'] - $this->process_times['started'];
			$this->process_times['percent'] = ( $this->process_times['ticks'] / $this->process_times['limit'] ) * 100;

			// If we are over 80% of the allowed processing time or over 10 seconds then finish up and return.
			if ( ( $this->process_times['percent'] >= LEARNDASH_PROCESS_TIME_PERCENT ) || ( $this->process_times['ticks'] > LEARNDASH_PROCESS_TIME_SECONDS ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Remove the processing transient for instance.
		 *
		 * @since 2.3
		 *
		 * @param string $transient_key Transient key to identify transient.
		 */
		protected function remove_transient( $transient_key = '' ) {
			if ( ! empty( $transient_key ) ) {
				$options_key = $this->transient_prefix . $transient_key;
				$options_key = str_replace( '-', '_', $options_key );
				return delete_option( $options_key );
			}
		}

		/**
		 * Get the processing transient for instance.
		 *
		 * @since 2.3
		 *
		 * @param string $transient_key Transient key to identify transient.
		 * @return mixed transient data.
		 */
		protected function get_transient( $transient_key = '' ) {
			if ( ! empty( $transient_key ) ) {
				$options_key = $this->transient_prefix . $transient_key;
				$options_key = str_replace( '-', '_', $options_key );
				return get_option( $options_key );
			}
		}

		/**
		 * Set the processing transient for instance.
		 *
		 * @since 2.3
		 *
		 * @param string $transient_key Transient key to identify transient.
		 * @param array  $transient_data Array for transient data.
		 */
		protected function set_option_cache( $transient_key = '', $transient_data = '' ) {
			if ( ! empty( $transient_key ) ) {
				$options_key = $this->transient_prefix . $transient_key;
				$options_key = str_replace( '-', '_', $options_key );

				if ( ! empty( $transient_data ) ) {
					update_option( $options_key, $transient_data );
				} else {
					delete_option( $options_key );
				}
			}
		}

		// End of functions.
	}
}

// Go ahead and inlcude out User Meta Courses upgrade class.
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-data-uprades-actions/class-learndash-admin-data-upgrades-translations.php';
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-data-uprades-actions/class-learndash-admin-data-upgrades-group-leader-role.php';
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-data-uprades-actions/class-learndash-admin-data-upgrades-user-activity-db-table.php';
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-data-uprades-actions/class-learndash-admin-data-upgrades-user-meta-courses.php';
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-data-uprades-actions/class-learndash-admin-data-upgrades-user-meta-quizzes.php';
//require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-data-uprades-actions/class-learndash-admin-data-upgrades-course-access-list.php';
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-data-uprades-actions/class-learndash-admin-data-upgrades-quiz-questions.php';
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-data-uprades-actions/class-learndash-admin-data-upgrades-course-access-list-convert.php';
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-data-uprades-actions/class-learndash-admin-data-upgrades-rename_wpproquiz-tables.php';

/**
 * Action to let other.
 *
 * @since 2.6.0
 */
do_action( 'learndash_data_upgrades_init' );

/**
 * AJAX function to handle calls from browser on Data Upgrade cycles.
 */
function learndash_data_upgrades_ajax() {

	$reply_data = array( 'status' => false );
	if ( isset( $_POST['data'] ) ) {
		$post_data = $_POST['data'];
	} else {
		$post_data = array();
	}

	if ( learndash_is_admin_user() ) {
		$ld_admin_data_upgrades = Learndash_Admin_Data_Upgrades::get_instance();
		$reply_data['data']     = $ld_admin_data_upgrades->do_data_upgrades( $post_data, $reply_data );

		if ( ! empty( $reply_data ) ) {
			echo json_encode( $reply_data );
		}
	}
	wp_die();
}

add_action( 'wp_ajax_learndash-data-upgrades', 'learndash_data_upgrades_ajax' );


/**
 * Utility function to check if the data upgrade for Quiz Questions has been run.
 *
 * @since 2.6.0
 * @return boolean true if has been run.
 */
function is_data_upgrade_quiz_questions_updated() {

	$element                      = Learndash_Admin_Data_Upgrades::get_instance();
	$data_settings_quiz_questions = $element->get_data_settings( 'pro-quiz-questions' );
	if ( ( isset( $data_settings_quiz_questions['last_run'] ) ) && ( ! empty( $data_settings_quiz_questions['last_run'] ) ) ) {
		return true;
	}
}
