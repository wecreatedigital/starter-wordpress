<?php
/**
 * LearnDash Settings Page Import/Export.
 *
 * @since 2.5.4
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Page' ) ) && ( ! class_exists( 'LearnDash_Settings_Page_Import_Export' ) ) ) {
	/**
	 * Class to create the settings page.
	 */
	class LearnDash_Settings_Page_Import_Export extends LearnDash_Settings_Page {

		/**
		 * Public constructor for class
		 */
		public function __construct() {
			$this->parent_menu_page_url  = 'admin.php?page=learndash_lms_import_export';
			$this->menu_page_capability  = LEARNDASH_ADMIN_CAPABILITY_CHECK;
			$this->settings_page_id      = 'learndash_lms_import_export';
			$this->settings_page_title   = esc_html__( 'LearnDash Import/Export', 'learndash' );
			$this->settings_tab_title    = esc_html__( 'Import/Export', 'learndash' );
			$this->settings_tab_priority = 0;

			add_filter( 'learndash_submenu_last', array( $this, 'submenu_item' ), 200 );

			add_filter( 'learndash_admin_tab_sets', array( $this, 'learndash_admin_tab_sets' ), 10, 3 );

			parent::__construct();
		}

		/**
		 * Control visibility of submenu items based on lisence status
		 *
		 * @since 2.5.5
		 *
		 * @param array $submenu Submenu item to check.
		 * @return array $submenu
		 */
		public function submenu_item( $submenu ) {
			$submenu[ $this->settings_page_id ] = array(
				'name' => $this->settings_tab_title,
				'cap'  => $this->menu_page_capability,
				'link' => $this->parent_menu_page_url,
			);

			return $submenu;
		}

		/**
		 * Filter for page title wrapper.
		 *
		 * @since 2.5.5
		 */
		public function get_admin_page_title() {
			return apply_filters( 'learndash_admin_page_title', '<h1>' . $this->settings_page_title . '</h1>' );
		}

		/**
		 * Action function called when Add-ons page is loaded.
		 *
		 * @since 2.5.5
		 */
		public function load_settings_page() {
			add_thickbox();

			if ( ! class_exists( 'Learndash_Admin_Import_Export' ) ) {
				require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/class-learndash-admin-import-export.php';
			}
			$this->import_export = new Learndash_Admin_Import_Export();
		}

		/**
		 * Hide the tab menu items if on add-one page.
		 *
		 * @since 2.5.5
		 *
		 * @param array  $tab_set Tab Set.
		 * @param string $tab_key Tab Key.
		 * @param string $current_page_id ID of shown page.
		 *
		 * @return array $tab_set
		 */
		public function learndash_admin_tab_sets( $tab_set = array(), $tab_key = '', $current_page_id = '' ) {
			if ( ( ! empty( $tab_set ) ) && ( ! empty( $tab_key ) ) && ( ! empty( $current_page_id ) ) ) {
				if ( 'admin_page_' . $this->settings_page_id === $current_page_id ) {
					?>
					<style> h1.nav-tab-wrapper { display: none; }</style>
					<?php
				}
			}
			return $tab_set;
		}

		/**
		 * Custom display function for page content.
		 *
		 * @since 2.5.5
		 */
		public function show_settings_page() {

			?>
			<div class="wrap learndash-settings-page-wrap">

				<?php settings_errors(); ?>

				<?php do_action( 'learndash_settings_page_before_title', $this->settings_screen_id ); ?>
				<?php echo $this->get_admin_page_title(); ?>
				<?php do_action( 'learndash_settings_page_after_title', $this->settings_screen_id ); ?>

				<?php do_action( 'learndash_settings_page_before_form', $this->settings_screen_id ); ?>

				<?php $this->import_export->show(); ?>

				<?php do_action( 'learndash_settings_page_after_form', $this->settings_screen_id ); ?>
			</div>
			<?php
			/**
			 * The following is needed to trigger the wp-admin/js/updates.js logic in
			 * wp.updates.updatePlugin() where is checks for specific pagenow values
			 * but doesn't leave any option for externals.
			 */
			?>
			<script type="text/javascript">
				//pagenow = 'plugin-install';
			</script>
			<?php
		}
	}
}
add_action(
	'learndash_settings_pages_init',
	function() {
		LearnDash_Settings_Page_Import_Export::add_page_instance();
	}
);
