<?php

/**
 * Class ITSEC_File_Change_Package_Factory
 */
class ITSEC_File_Change_Package_Factory {

	private $search_paths;

	/**
	 * ITSEC_File_Change_Package_Factory constructor.
	 */
	public function __construct() {

		global $wp_theme_directories;

		$sp = array(
			WP_PLUGIN_DIR . '/'   => 'plugin',
			ABSPATH . WPINC . '/' => 'core',
			ABSPATH . 'wp-admin/' => 'core',
		);


		if ( empty( $wp_theme_directories ) ) {
			$sp[ WP_CONTENT_DIR . '/themes/' ] = 'theme';
		} else {
			foreach ( $wp_theme_directories as $theme_directory ) {
				$sp[ trailingslashit( $theme_directory ) ] = 'theme';
			}
		}

		$core_files        = '@' . preg_quote( ABSPATH, '@' ) . '[\w\-_]+\.@';
		$sp[ $core_files ] = 'core';

		uksort( $sp, array( $this, '_sort' ) );

		foreach ( $this->get_system_files() as $file ) {
			$sp[ $file ] = 'system-files';
		}

		$this->search_paths = array_reverse( $sp );
	}

	/**
	 * Sort a list of paths to that the most precise paths are first.
	 *
	 * @param string $a
	 * @param string $b
	 *
	 * @return int
	 */
	private function _sort( $a, $b ) {
		return substr_count( $a, '/' ) - substr_count( $b, '/' );
	}

	/**
	 * Get the system files to track.
	 *
	 * @return array
	 */
	private function get_system_files() {

		$files = array(
			ITSEC_Lib::get_htaccess(),
			ITSEC_Lib::get_config(),
		);

		/**
		 * The list of files that iThemes Security manages.
		 *
		 * @param string[] $files
		 */
		return apply_filters( 'itsec_managed_files', $files );
	}

	/**
	 * Find all packages from the set of files added or changed.
	 *
	 * @param iterable $files
	 *
	 * @return array[]
	 */
	public function find_packages_for_files( $files ) {

		$packages = array();
		$append   = array();

		$skipped_files = array();

		foreach ( $files as $file => $attr ) {
			$found = false;

			foreach ( $this->search_paths as $search_path => $type ) {

				if ( '@' === $search_path[0] ) {
					if ( ! preg_match( $search_path, $file ) ) {
						continue;
					}
				} elseif ( 0 !== strpos( $file, $search_path ) ) {
					continue;
				}

				if ( isset( $packages[ $search_path ] ) ) {
					$package = $packages[ $search_path ]['package'];
				} elseif ( ! $package = $this->make( $file, $search_path, $packages ) ) {
					break;
				}

				// Ugly specific exemption so that single-file plugins don't end up getting matched
				// for all further plugins because their root path is the plugins directory.
				if ( 'plugin' === $type && $package->get_root_path() === $search_path ) {
					$append[] = array(
						'package' => $package,
						'files'   => array( $this->make_relative( $file, $package->get_root_path() ) => $attr ),
					);
					$found    = true;
					break;
				}

				if ( isset( $packages[ $package->get_root_path() ] ) ) {
					$packages[ $package->get_root_path() ]['files'][ $this->make_relative( $file, $package->get_root_path() ) ] = $attr;
				} else {
					$packages[ $package->get_root_path() ] = array(
						'package' => $package,
						'files'   => array( $this->make_relative( $file, $package->get_root_path() ) => $attr ),
					);
				}

				$found = true;
				break;
			}

			if ( ! $found ) {
				$skipped_files[ $file ] = $attr;
			}
		}

		if ( $skipped_files ) {
			$unknown = array();

			foreach ( $skipped_files as $file => $attr ) {
				$unknown[ $this->make_relative( $file, '/' ) ] = $attr;
			}

			$packages['/'] = array( 'package' => new ITSEC_File_Change_Package_Unknown(), 'files' => $unknown );
		}

		return array_merge( array_values( $packages ), $append );
	}

	/**
	 * Make an absolute path relative.
	 *
	 * @param string $absolute Absolute path.
	 * @param string $to       Path to make relative to.
	 *
	 * @return string
	 */
	private function make_relative( $absolute, $to ) {
		return ltrim( substr( $absolute, strlen( trailingslashit( $to ) ) ), '/' );
	}

	/**
	 * Get all installed plugins.
	 *
	 * @return array
	 */
	private function get_plugins() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( ! function_exists( 'get_plugins' ) ) {
			return array();
		}

		// WordPress caches this internally.
		return get_plugins();
	}

	/**
	 * Find a plugin file by a slug.
	 *
	 * @param string $slug
	 *
	 * @return array Tuple of the file path and file headers.
	 */
	private function find_plugin_by_slug( $slug ) {

		$plugins = $this->get_plugins();

		foreach ( $plugins as $file => $data ) {
			// Comparison is faster, but vast majority of plugins installed are not single file plugins.
			if ( 0 === strpos( $file, $slug . '/' ) || $file === $slug ) {
				return array( $file, $data );
			}
		}

		return array( '', '' );
	}

	/**
	 * Filter the package.
	 *
	 * @param ITSEC_File_Change_Package|null $package
	 * @param string                         $file
	 * @param string                         $search_path
	 *
	 * @return ITSEC_File_Change_Package|null
	 */
	private function filter( ITSEC_File_Change_Package $package = null, $file, $search_path ) {

		/**
		 * Filter the corresponding package for a file.
		 *
		 * @param ITSEC_File_Change_Package|null $package
		 * @param string                         $file        The absolute path to the file.
		 * @param string                         $search_path The search path this file was found in.
		 */
		$filtered = apply_filters( 'itsec_file_change_package', $package, $file, $search_path );

		if ( null === $filtered || $filtered instanceof ITSEC_File_Change_Package ) {
			return $filtered;
		}

		return $package;
	}

	/**
	 * Make a package for a file.
	 *
	 * @param string $file        The absolute path to the file.
	 * @param string $search_path The search path this file was found in.
	 * @param array  $packages    Packages that have already been found. Keyed by the theme root.
	 *
	 * @return ITSEC_File_Change_Package|null
	 */
	private function make( $file, $search_path, array $packages ) {

		$package = null;

		switch ( $this->search_paths[ $search_path ] ) {
			case 'plugin':
				if ( ! $directory = $this->get_first_directory( $file, $search_path ) ) {
					break;
				}

				if ( isset( $packages[ $root_path = $search_path . $directory . '/' ] ) ) {
					return $packages[ $root_path ]['package']; // Don't filter multiple times if we already have the correct package.
				}

				list( $plugin_file, $plugin_data ) = $this->find_plugin_by_slug( $directory );

				if ( $plugin_file ) {
					$package = new ITSEC_File_Change_Package_Plugin( $plugin_file, $plugin_data );
				}
				break;
			case 'theme':
				if ( ! $directory = $this->get_first_directory( $file, $search_path ) ) {
					break;
				}

				if ( isset( $packages[ $root_path = $search_path . $directory . '/' ] ) ) {
					return $packages[ $root_path ]['package'];
				}

				if ( ( ! $theme = wp_get_theme( $directory, untrailingslashit( $search_path ) ) ) || ! $theme->exists() ) {
					break;
				}

				$package = new ITSEC_File_Change_Package_Theme( $theme );
				break;
			case 'core':
				$package = new ITSEC_File_Change_Package_Core( $search_path[0] === '@' ? ABSPATH : $search_path );
				break;
			case 'system-files':
				$package = new ITSEC_File_Change_Package_System();
				break;
		}

		return $this->filter( $package, $file, $search_path );
	}

	/**
	 * Get the first directory after a search path from a file path.
	 *
	 * For example, given the following parameters:
	 *
	 * /app/public/wp-content/plugins/ithemes-security-pro/core/lib/log.php
	 * /app/public/wp-content/plugins/
	 *
	 * 'ithemes-security-pro' will be returned.
	 *
	 * @param string $file
	 * @param string $search_path
	 *
	 * @return string
	 */
	private function get_first_directory( $file, $search_path ) {
		$relative = wp_normalize_path( substr( $file, strlen( $search_path ) ) );
		$parts    = explode( '/', $relative, 2 );

		if ( empty( $parts ) ) {
			return '';
		}

		return $parts[0];
	}
}
