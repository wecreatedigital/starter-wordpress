<?php

/**
 * Online File Scan Execution
 *
 * Handles all online file scan execution once the feature has been
 * enabled by the user.
 *
 * @since   1.10.0
 *
 * @package iThemes_Security
 */
class ITSEC_Online_Files {
	function run() {
		add_action( 'itsec-file-change-settings-form', array( $this, 'render_settings' ) );

		add_filter( 'itsec-file-change-sanitize-settings', array( $this, 'sanitize_settings' ) );
		add_action( 'itsec_scheduled_confirm-valid-wporg-plugin', array( $this, 'confirm_valid_wporg_plugin' ) );
		add_action( 'itsec_scheduled_preload-core-hashes', array( $this, 'preload_core_hashes' ) );
		add_action( 'itsec_scheduled_preload-plugin-hashes', array( $this, 'preload_plugin_hashes' ) );
		add_action( 'itsec_scheduled_preload-ithemes-hashes', array( $this, 'preload_ithemes_hashes' ) );

		if ( ITSEC_Modules::get_setting( 'online-files', 'compare_file_hashes' ) ) {
			add_action( 'activated_plugin', array( $this, 'on_plugin_activate' ) );
			add_action( 'delete_plugin', array( $this, 'clear_hashes_on_delete' ) );
			add_filter( 'upgrader_post_install', array( $this, 'preload_hashes_on_upgrade' ), 100, 3 );
			add_action( 'itsec_load_file_change_scanner', array( $this, 'load_scanner' ) );
			add_filter( 'itsec_file_change_comparators', array( $this, 'register_comparators' ) );
			add_filter( 'itsec_file_change_package', array( $this, 'handle_ithemes_packages' ) );
			add_filter( 'itsec_file_change_package', array( $this, 'handle_wporg_plugins' ) );
		}
	}

	public function render_settings( $form ) {
		require_once( dirname( __FILE__ ) . '/custom-settings.php' );

		ITSEC_Online_Files_Custom_Settings::render_settings( $form );
	}

	public function sanitize_settings( $settings ) {
		require_once( dirname( __FILE__ ) . '/custom-settings.php' );

		return ITSEC_Online_Files_Custom_Settings::sanitize_settings( $settings );
	}

	/**
	 * When a plugin is activated, clear the flag specifying whether it is
	 * a WordPress.org plugin or not. Then, try to preload the plugin's hashes.
	 *
	 * This allows for later installing a .org plugin or non-repo plugin after
	 * having used the previous one.
	 *
	 * @param string $file
	 */
	public function on_plugin_activate( $file ) {

		if ( $package = $this->get_ithemes_package( $file ) ) {
			ITSEC_Online_Files_Utility::clear_ithemes_hashes( $package );
			ITSEC_Core::get_scheduler()->schedule_soon( 'preload-ithemes-hashes', array(
				'package' => $package,
				'version' => $this->get_plugin_version( $file ),
			) );
		} else {
			ITSEC_Online_Files_Utility::clear_wporg_plugin_hashes( dirname( $file ) );
			ITSEC_Core::get_scheduler()->schedule_soon( 'preload-plugin-hashes', compact( 'file' ) );
		}
	}

	/**
	 * After a package has been installed, schedule an event to fetch the hashes.
	 *
	 * @param bool  $success
	 * @param array $data
	 *
	 * @return bool
	 */
	public function preload_hashes_on_upgrade( $success, $data ) {

		if ( ! $success || is_wp_error( $success ) ) {
			return $success;
		}

		if ( empty( $data['plugin'] ) ) {
			return $success;
		}

		if ( $package = $this->get_ithemes_package( $data['plugin'] ) ) {
			ITSEC_Core::get_scheduler()->schedule_soon( 'preload-ithemes-hashes', array(
				'package' => $package,
				'version' => $this->get_plugin_version( $data['plugin'] ),
			) );
		} else {
			ITSEC_Core::get_scheduler()->schedule_soon( 'preload-plugin-hashes', array( 'file' => $data['plugin'] ) );
		}

		return $success;
	}

	/**
	 * When a plugin is uninstalled, remove its hashes from storage.
	 *
	 * @param string $file
	 */
	public function clear_hashes_on_delete( $file ) {
		if ( $package = $this->get_ithemes_package( $file ) ) {
			ITSEC_Online_Files_Utility::clear_ithemes_hashes( $package );
		} else {
			ITSEC_Online_Files_Utility::clear_wporg_plugin_hashes( dirname( $file ) );
		}
	}

	/**
	 * When downloading plugin hashes during a file scan, we might come across
	 * a 404. Instead of wasting execution time during the lengthy file scan,
	 * we schedule an event later to confirm whether the 404 was due to the plugin
	 * not existing, or just the version being non-existent or a temporary error.
	 *
	 * @param ITSEC_Job $job
	 */
	public function confirm_valid_wporg_plugin( $job ) {

		if ( ! function_exists( 'plugins_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		}

		if ( ! function_exists( 'plugins_api' ) ) {
			return;
		}

		$data = $job->get_data();
		$slug = $data['slug'];

		ITSEC_Online_Files_Utility::query_is_wporg_plugin( $slug );
	}

	/**
	 * Preload the hashes of a plugin after upgrade or install.
	 *
	 * @param ITSEC_Job $job
	 */
	public function preload_plugin_hashes( $job ) {

		$data = $job->get_data();

		if ( ! isset( $data['version'] ) ) {
			if ( ! isset( $data['file'] ) ) {
				return;
			}

			$version = $this->get_plugin_version( $data['file'] );
		} else {
			$version = $data['version'];
		}

		if ( isset( $data['slug'] ) ) {
			$slug = $data['slug'];
		} elseif ( isset( $data['file'] ) ) {
			$slug = dirname( $data['file'] );
		} else {
			return;
		}

		ITSEC_Online_Files_Utility::load_wporg_plugin_hashes( $slug, $version );
	}

	/**
	 * Preload core hashes for the requested version and locale.
	 *
	 * @param ITSEC_Job $job
	 */
	public function preload_core_hashes( $job ) {

		$data = $job->get_data();

		ITSEC_Online_Files_Utility::load_core_hashes( $data['version'], $data['locale'] );
	}

	/**
	 * Preload iThemes hashes for the requested package and version.
	 *
	 * @param ITSEC_Job $job
	 */
	public function preload_ithemes_hashes( $job ) {

		$data = $job->get_data();

		ITSEC_Online_Files_Utility::load_ithemes_hashes( $data['package'], $data['version'] );
	}

	/**
	 * Get the iThemes package identifier from a plugin file.
	 *
	 * @param string $file
	 *
	 * @return bool
	 */
	private function get_ithemes_package( $file ) {

		$data = @get_file_data( trailingslashit( WP_PLUGIN_DIR ) . $file, array(
			'package' => 'iThemes Package',
		) );

		if ( empty( $data['package'] ) ) {
			return false;
		}

		return $data['package'];
	}

	/**
	 * Get the version header from a plugin file.
	 *
	 * @param string $file
	 *
	 * @return string
	 */
	private function get_plugin_version( $file ) {
		$data = @get_file_data( trailingslashit( WP_PLUGIN_DIR ) . $file, array(
			'version' => 'Version',
		) );

		return $data['version'];
	}

	/**
	 * Fires when the scanner is loaded.
	 */
	public function load_scanner() {
		require_once( dirname( __FILE__ ) . '/comparator-core.php' );
		require_once( dirname( __FILE__ ) . '/comparator-ithemes.php' );
		require_once( dirname( __FILE__ ) . '/comparator-wporg.php' );
		require_once( dirname( __FILE__ ) . '/package-ithemes.php' );
		require_once( dirname( __FILE__ ) . '/package-wporg.php' );
	}

	/**
	 * Register WP.org and iThemes comparator.
	 *
	 * @param ITSEC_File_Change_Hash_Comparator[] $comparators
	 *
	 * @return array
	 */
	public function register_comparators( $comparators ) {

		array_unshift( $comparators, new ITSEC_File_Change_Hash_Comparator_Core() );
		array_unshift( $comparators, new ITSEC_File_Change_Hash_Comparator_iThemes() );
		array_unshift( $comparators, new ITSEC_File_Change_Hash_Comparator_WPOrg_Plugin() );

		return $comparators;
	}

	/**
	 * Return iThemes packages for iThemes Plugins and Themes.
	 *
	 * @param ITSEC_File_Change_Package|null $package
	 *
	 * @return ITSEC_File_Change_Package|null
	 */
	public function handle_ithemes_packages( $package ) {

		if ( $package instanceof ITSEC_File_Change_Package_Plugin && $id = $package->get_plugin_header( 'iThemes Package' ) ) {
			return new ITSEC_File_Change_Package_iThemes( $package, $id );
		}

		if ( $package instanceof ITSEC_File_Change_Package_Theme && $id = $package->get_theme_header( 'iThemes Package' ) ) {
			return new ITSEC_File_Change_Package_iThemes( $package, $id );
		}

		return $package;
	}

	/**
	 * Return a WordPress.org Plugin package for plugins that reside on WordPress.org.
	 *
	 * @param ITSEC_File_Change_Package|null $package
	 *
	 * @return ITSEC_File_Change_Package|null
	 */
	public function handle_wporg_plugins( $package ) {

		if ( $package instanceof ITSEC_File_Change_Package_Plugin && ITSEC_Online_Files_Utility::is_likely_wporg_plugin( dirname( $package->get_identifier() ) ) ) {
			$package = ITSEC_File_Change_Package_WPOrg_Plugin::from_plugin( $package );
		}

		return $package;
	}
}
