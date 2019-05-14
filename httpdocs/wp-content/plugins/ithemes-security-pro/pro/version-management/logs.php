<?php

/**
 * Class ITSEC_Version_Management_Logs
 */
class ITSEC_Version_Management_Logs {

	public function __construct() {
		add_filter( 'itsec_logs_prepare_version_management_entry_for_list_display', array( $this, 'filter_entry_for_list_display' ), 10, 3 );
		add_filter( 'itsec_logs_prepare_version_management_entry_for_details_display', array( $this, 'filter_entry_for_details_display' ), 10, 4 );
		add_filter( 'itsec_logs_prepare_version_management_filter_row_action_for_code', array( $this, 'code_row_action' ), 10, 4 );
	}

	public function filter_entry_for_list_display( $entry, $code, $data ) {
		$entry['module_display'] = esc_html__( 'Version Management', 'it-l10n-ithemes-security-pro' );


		if ( $description = $this->get_description( $entry, $code, $data ) ) {
			$entry['description'] = $description;
		}

		return $entry;
	}

	public function filter_entry_for_details_display( $details, $entry, $code, $code_data ) {
		$details['module']['content'] = esc_html__( 'Version Management', 'it-l10n-ithemes-security-pro' );

		if ( $description = $this->get_description( $entry, $code, $code_data ) ) {
			$details['description']['content'] = $description;
		}

		if ( 'auto-update' === $code && ( 'error' === $entry['type'] || 'warning' === $entry['type'] ) ) {
			$errors = array();

			foreach ( $entry['data'] as $type => $updates ) {
				foreach ( $updates as $update ) {
					if ( isset( $update->result ) && is_wp_error( $update->result ) ) {
						if ( 'rollback_was_required' === $update->result->get_error_code() ) {
							$errors[] = $update->result->get_error_data( 'update' );

							if ( is_wp_error( $update->result->get_error_data( 'rollback' ) ) ) {
								$errors[] = $update->result->get_error_data( 'rollback' );
							}
						} else {
							$errors[] = $update->result;
						}
					}
				}
			}

			if ( $errors ) {
				$error = wp_sprintf( '%l', ITSEC_Response::get_error_strings( $errors ) );
			} else {
				$error = esc_html__( 'Unknown Error. View "Raw Details" for more information.', 'it-l10n-ithemes-security-pro' );
			}

			$details['errors'] = array(
				'header'  => esc_html__( 'Error', 'it-l10n-ithemes-security-pro' ),
				'content' => $error,
				'order'   => 21,
			);
		}

		return $details;
	}

	public function code_row_action( $vars, $entry, $code, $data ) {

		switch ( $code ) {
			case 'install':
			case 'update':
				list( $type, $file ) = $data;

				return array( 'filters[10]' => "code|{$code}::{$type},{$file}%" );
			case 'update-core':
				return array( 'filters[10]' => 'code|update-core%' );
		}

		return $vars;
	}
	
	private function get_description( $entry, $code, $data ) {
		switch ( $code ) {
			case 'install':
				list( $type, $file, $version ) = $data;

				switch ( $type ) {
					case 'plugin':
						/* translators: 1. Plugin Name, 2. Version */
						return sprintf( esc_html__( 'Installed Plugin: %1$s %2$s', 'it-l10n-ithemes-security-pro' ), $this->get_plugin_name( $file ), $version );
					case 'theme':
						/* translators: 1. Theme Name, 2. Version */
						return sprintf( esc_html__( 'Installed Theme: %1$s %2$s', 'it-l10n-ithemes-security-pro' ), $this->get_theme_name( $file ), $version );
					default:
						return esc_html__( 'Unknown Update' );

				}
			case 'update':
				list( $type, $file, $version, $auto ) = $data;

				switch ( $type ) {
					case 'plugin':
						if ( 'auto' === $auto ) {
							/* translators: 1. Plugin Name, 2. Version */
							return sprintf( esc_html__( 'Automatically Updated %1$s Plugin to %2$s', 'it-l10n-ithemes-security-pro' ), $this->get_plugin_name( $file ), $version );
						}

						/* translators: 1. Plugin Name, 2. Version */
						return sprintf( esc_html__( 'Updated %1$s Plugin to %2$s', 'it-l10n-ithemes-security-pro' ), $this->get_plugin_name( $file ), $version );
					case 'theme':
						if ( 'auto' === $auto ) {
							/* translators: 1. Theme Name, 2. Version */
							return sprintf( esc_html__( 'Automatically Updated %1$s Theme to %2$s', 'it-l10n-ithemes-security-pro' ), $this->get_theme_name( $file ), $version );
						}

						/* translators: 1. Theme Name, 2. Version */
						return sprintf( esc_html__( 'Updated %1$s theme to %2$s', 'it-l10n-ithemes-security-pro' ), $this->get_theme_name( $file ), $version );
					default:
						return esc_html__( 'Unknown Update' );
				}
			case 'update-core':
				list( $new, $old, $auto ) = $data;

				if ( 'auto' === $auto ) {
					/* translators: 1. New Version, 2. Old Version */
					return sprintf( esc_html__( 'Automatically Updated WordPress to %1$s from %2$s', 'it-l10n-ithemes-security-pro' ), $new, $old );
				}

				/* translators: 1. New Version, 2. Old Version */
				return sprintf( esc_html__( 'Updated WordPress to %1$s from %2$s', 'it-l10n-ithemes-security-pro' ), $new, $old );
			case 'auto-update':
				if ( 'error' === $entry['type'] || 'warning' === $entry['type'] ) {
					return esc_html__( 'Automatic Updates Error', 'it-l10n-ithemes-security-pro' );
				}

				return esc_html__( 'Automatic Updates Complete', 'it-l10n-ithemes-security-pro' );
		}

		return null;
	}

	private function get_plugin_name( $file ) {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		if ( ! function_exists( 'get_plugin_data' ) ) {
			return "'{$file}'";
		}

		$path = WP_PLUGIN_DIR . '/' . $file;

		if ( ! file_exists( $path ) ) {
			return "'{$file}'";
		}

		$data = get_plugin_data( $path );

		return $data['Name'];
	}

	private function get_theme_name( $stylesheet ) {

		if ( ! ( $theme = wp_get_theme( $stylesheet ) ) || ! $theme->exists() ) {
			return "'{$stylesheet}'";
		}

		return $theme->get( 'Name' );
	}
}

new ITSEC_Version_Management_Logs();