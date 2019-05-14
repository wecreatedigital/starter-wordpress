<?php

class ITSEC_Core_Admin {

	function run() {
		add_filter( 'itsec_meta_links', array( $this, 'add_plugin_meta_links' ) );
		add_filter( 'itsec_support_url', array( $this, 'itsec_support_url' ) );

		add_action( 'itsec-settings-page-init', array( $this, 'init_settings_page' ) );
		add_action( 'itsec-logs-page-init', array( $this, 'init_settings_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts' ), 0 );
	}

	public function init_settings_page() {
		if ( ! class_exists( 'backupbuddy_api' ) ) {
			require_once( dirname( __FILE__ ) . '/sidebar-widget-backupbuddy-cross-promo.php' );
		}
		require_once( dirname( __FILE__ ) . '/sidebar-widget-support.php' );
	}

	/**
	 * Adds links to the plugin row meta
	 *
	 * @since 4.0
	 *
	 * @param array $meta Existing meta
	 *
	 * @return array
	 */
	public function add_plugin_meta_links( $meta ) {

		$meta[] = '<a href="http://ithemes.com/member/support.php" target="_blank" rel="noopener noreferrer">' . __( 'Get Support', 'it-l10n-ithemes-security-pro' ) . '</a>';

		return $meta;
	}

	public function itsec_support_url( $support_url ) {
		return 'http://ithemes.com/member/support.php';
	}

	public function register_scripts() {
		$dir          = ITSEC_Core::get_plugin_dir() . 'dist/';
		$manifest     = require $dir . 'manifest.php';
		$script_debug = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;

		foreach ( $manifest as $name => $config ) {
			foreach ( $config['files'] as $file ) {
				$handle = $this->name_to_handle( $name );

				if ( $script_debug && file_exists( $dir . $file ) ) {
					$path     = 'dist/' . $file;
					$is_debug = true;
				} else {
					$path     = 'dist/' . str_replace( '.', '.min.', $file );
					$is_debug = false;
				}

				$is_css = ITSEC_Lib::str_ends_with( $file, '.css' );
				$is_js  = ! $is_css;

				if ( $is_debug ) {
					$version = filemtime( $dir . $file );
				} elseif ( $is_js && isset( $config['contentHash']['javascript'] ) ) {
					$version = $config['contentHash']['javascript'];
				} elseif ( $is_css && isset( $config['contentHash']['css/mini-extract'] ) ) {
					$version = $config['contentHash']['css/mini-extract'];
				} else {
					$version = $config['hash'];
				}

				$deps = $is_js ? $config['dependencies'] : array();

				if ( $is_css && in_array( 'wp-components', $config['dependencies'], true ) ) {
					$deps[] = 'wp-components';
				}

				if ( ! $is_debug ) {
					foreach ( array_reverse( $config['vendors'] ) as $vendor ) {
						if ( ! isset( $manifest[ $vendor ] ) ) {
							continue;
						}

						if ( $is_js && $this->has_js( $manifest[ $vendor ]['files'] ) ) {
							$deps[] = $this->name_to_handle( $vendor );
						} elseif ( $is_css && $this->has_css( $manifest[ $vendor ]['files'] ) ) {
							$deps[] = $this->name_to_handle( $vendor );
						}
					}
				}

				if ( $is_css ) {
					wp_register_style(
						$handle,
						plugins_url( $path, ITSEC_Core::get_plugin_file() ),
						$deps,
						$version
					);
				} else {
					wp_register_script(
						$handle,
						plugins_url( $path, ITSEC_Core::get_plugin_file() ),
						$deps,
						$version
					);
				}

				if ( $is_js && ! empty( $config['runtime'] ) ) {
					$public_path = esc_js( trailingslashit( plugins_url( 'dist', ITSEC_Core::get_plugin_file() ) ) );
					wp_add_inline_script( $handle, "window.itsecWebpackPublicPath = window.itsecWebpackPublicPath || '{$public_path}';", 'before' );
				}
			}
		}
	}

	private function has_js( $files ) {
		foreach ( $files as $file ) {
			if ( ITSEC_Lib::str_ends_with( $file, '.js' ) ) {
				return true;
			}
		}

		return false;
	}

	private function has_css( $files ) {
		foreach ( $files as $file ) {
			if ( ITSEC_Lib::str_ends_with( $file, '.css' ) ) {
				return true;
			}
		}

		return false;
	}

	private function name_to_handle( $name ) {
		$name = str_replace( '/dist/', '/entry/', $name );

		return 'itsec-' . str_replace( '/', '-', $name );
	}
}
