<?php

final class ITSEC_Import_Export_Importer {
	public static function import_from_form( $form_name ) {
		require_once( ITSEC_Core::get_core_dir() . '/lib/class-itsec-lib-file.php' );


		$result = self::validate_uploaded_file( $form_name );

		if ( is_wp_error( $result ) ) {
			/* translators: 1: original error message */
			return new WP_Error( $result->get_error_code(), sprintf( __( 'Unable to properly read the import file. %1$s', 'it-l10n-ithemes-security-pro' ), $result->get_error_message() ) );
		}


		$type = isset( $_FILES[$form_name]['type'] ) ? $_FILES[$form_name]['type'] : '';
		$data = self::get_data_from_file( $_FILES[$form_name]['tmp_name'], $type );
		ITSEC_Lib_File::remove( $_FILES[$form_name]['tmp_name'] );

		if ( is_wp_error( $data ) ) {
			/* translators: 1: original error message */
			return new WP_Error( $data->get_error_code(), sprintf( __( 'Unable to properly read the settings from the import file. %1$s', 'it-l10n-ithemes-security-pro' ), $data->get_error_message() ) );
		}


		return self::import( $data );
	}

	public static function import_from_file_path( $path, $type ) {
		$data = self::get_data_from_file( $path, $type );

		if ( is_wp_error( $data ) ) {
			/* translators: 1: original error message */
			return new WP_Error( $data->get_error_code(), sprintf( __( 'Unable to properly read the settings from the import file. %1$s', 'it-l10n-ithemes-security-pro' ), $data->get_error_message() ) );
		}

		return self::import( $data );
	}

	public static function import( $data ) {
		if ( ! is_array( $data ) || ! isset( $data['options'] ) || ! is_array( $data['options'] ) ) {
			return new WP_Error( 'itsec-import-export-importer-import-invalid-data', esc_html__( 'The format of the data to be imported is invalid. The data cannot be imported.', 'it-l10n-ithemes-security-pro' ) );
		}

		if ( empty( $data['plugin_build'] ) ) {
			$data['plugin_build'] = self::get_plugin_build( $data['options'] );
		}

		ITSEC_Storage::save();

		foreach ( $data['options'] as $option ) {
			if ( ! empty( $data['abspath'] ) && ABSPATH !== $data['abspath'] ) {
				if ( 'itsec-storage' === $option['name'] ) {
					$abspath = trailingslashit( ABSPATH );

					$path_settings = array(
						array(
							'module'  => 'global',
							'setting' => 'log_location',
							'label'   => __( 'Path to Log Files', 'it-l10n-ithemes-security-pro' ),
							'type'    => 'dir',
						),
						array(
							'module'  => 'global',
							'setting' => 'nginx_file',
							'label'   => __( 'NGINX Conf File', 'it-l10n-ithemes-security-pro' ),
							'type'    => 'file',
						),
						array(
							'module'  => 'backup',
							'setting' => 'location',
							'label'   => __( 'Backup Location', 'it-l10n-ithemes-security-pro' ),
							'type'    => 'dir',
						)
					);

					foreach ( $path_settings as $setting ) {
						if ( empty( $option['value'][ $setting['module'] ][ $setting['setting'] ] ) ) {
							continue;
						}

						$replaced_path = preg_replace( '/^' . preg_quote( $data['abspath'], '/' ) . '/', $abspath, $option['value'][ $setting['module'] ][ $setting['setting'] ] );
						$option['value'][ $setting['module'] ][ $setting['setting'] ] = $replaced_path;

						$error = false;

						if ( $setting['type'] === 'dir' ) {
							$error = self::validate_directory( $replaced_path, $setting['label'], $setting['module'], "itsec-{$setting['module']}-{$setting['setting']}" );
						} elseif ( $setting['type'] === 'file' ) {
							$error = self::validate_file( $replaced_path, $setting['label'], $setting['module'], "itsec-{$setting['module']}-{$setting['setting']}" );
						}

						if ( is_wp_error( $error ) ) {
							ITSEC_Response::add_warning( $error->get_error_message() );
						}
					}
				}
			}

			switch ( $option['name'] ) {
				case 'itsec_active_modules':
					foreach ( ITSEC_Modules::get_available_modules() as $module ) {
						if ( ITSEC_Modules::is_always_active( $module ) ) {
							continue;
						}

						$is_active   = ITSEC_Modules::is_active( $module );
						$make_active = ! empty( $option['value'][ $module ] );

						if ( $make_active === $is_active ) {
							continue; 
						}

						if ( $make_active ) {
							ITSEC_Modules::activate( $module );
						} else {
							ITSEC_Modules::deactivate( $module );
						}
					}
					break;
				default:
				 self::import_option( $option );
				 break;
			}
		}


		if ( version_compare( $data['plugin_build'], ITSEC_Core::get_plugin_build(), '<' ) ) {
			$core = ITSEC_Core::get_instance();
			$core->handle_upgrade( $data['plugin_build'] );
		}

		ITSEC_Storage::reload();
		ITSEC_Response::regenerate_server_config();
		ITSEC_Response::regenerate_wp_config();

		return true;
	}

	/**
	 * Default option import.
	 *
	 * @param array $option Option to import.
	 */
	private static function import_option( $option ) {
			if ( is_multisite() ) {
				delete_site_option( $option['name'] );
				add_site_option( $option['name'], $option['value'] );
			} else {
				delete_option( $option['name'] );
				add_option( $option['name'], $option['value'], null, $option['auto'] );
			}
	}

	/**
	 * Attempts to determine the build version of the supplied options.
	 *
	 * @static
	 *
	 * @param string $options The options to inspect to find the build version.
	 * @return int Build version of the supplied options.
	 */
	private static function get_plugin_build( $raw_options ) {
		$options = array();

		foreach ( $raw_options as $raw_option ) {
			$options[$raw_option['name']] = $raw_option['value'];
		}


		if ( isset( $options['itsec_two_factor'] ) && isset( $options['itsec_two_factor']['enabled-providers'] ) ) {
			return 4038;
		}

		if ( isset( $options['itsec_malware_scheduling'] ) && isset( $options['itsec_malware_scheduling']['email_notifications'] ) ) {
			return 4037;
		}

		return 4031;
	}

	/**
	 * Returns the data contained in the supplied file path.
	 *
	 * The supplied file can be a zip file or a JSON file.
	 *
	 * @static
	 *
	 * @uses ITSEC_Import_Export_Importer::get_data_from_json_file() to parse the JSON file.
	 *
	 * @param string $file File path for the file to pull iThemes Security settings from.
	 * @return array|WP_Error Returns an array of options settings on success, or a WP_Error object on failure.
	 */
	private static function get_data_from_file( $file, $type ) {
		$temp_dir = self::get_temp_dir();

		if ( ! is_wp_error( $temp_dir ) ) {
			WP_Filesystem();

			$unzip_result = unzip_file( $file, $temp_dir );

			if ( true === $unzip_result ) {
				$files = ITSEC_Lib_Directory::read( $temp_dir );

				if ( is_wp_error( $files ) ) {
					ITSEC_Lib_Directory::remove( $temp_dir );

					return new WP_Error( $files->get_error_code(), sprintf( __( 'A server issue is preventing the zip file data from being read. Please unzip the export file and try importing the contained JSON file. The specific error that prevented the zip file data from being read is as follows: %s', 'it-l10n-ithemes-security-pro' ), $files->get_error_message() ) );
				}

				foreach ( $files as $file ) {
					if ( ! ITSEC_Lib_File::is_file( $file ) ) {
						continue;
					}

					$result = self::get_data_from_json_file( $file );

					if ( is_wp_error( $result ) ) {
						$error = $result;
					} else if ( isset( $settings ) ) {
						ITSEC_Lib_Directory::remove( $temp_dir );

						return new WP_Error( 'multiple_settings_files_found', __( 'The supplied zip file contained more than one JSON file with valid iThemes Security settings. Only zip files with one JSON file of valid settings are permitted. Please ensure that a valid export file is supplied.', 'it-l10n-ithemes-security-pro' ) );
					} else {
						$settings = $result;
					}
				}

				ITSEC_Lib_Directory::remove( $temp_dir );

				if ( isset( $settings ) ) {
					return $settings;
				} else if ( isset( $error ) ) {
					return $error;
				} else {
					return new WP_Error( 'valid_json_settings_file_not_found', __( 'The supplied zip file did not contain a JSON file with valid iThemes Security settings. Please ensure that a valid export file is supplied.', 'it-l10n-ithemes-security-pro' ) );
				}
			}
		}

		if ( ! is_wp_error( $temp_dir ) ) {
			ITSEC_Lib_Directory::remove( $temp_dir );
		}


		$settings = self::get_data_from_json_file( $file );

		if ( ! is_wp_error( $settings ) ) {
			return $settings;
		}


		if ( ( '.zip' === substr( $file, -4 ) ) || ( false !== strpos( $type, 'zip' ) ) ) {
			if ( is_wp_error( $temp_dir ) ) {
				$error = $temp_dir;
			}
			if ( is_wp_error( $unzip_result ) ) {
				$error = $unzip_result;
			}

			if ( isset( $error ) ) {
				return new WP_Error( $error->get_error_code(), sprintf( __( 'The unzip utility built into WordPress reported the following error when trying to unzip the supplied file: %s', 'it-l10n-ithemes-security-pro' ), $error->get_error_message() ) );
			}
		}

		return $settings;
	}

	/**
	 * Returns validated iThemes Security settings the supplied JSON file.
	 *
	 * @static
	 *
	 * @param string $file File path to the JSON file to pull the settings from.
	 * @return array|WP_Error Returns an array of valid iThemes Security settings, or a WP_Error object otherwise.
	 */
	private static function get_data_from_json_file( $file ) {
		$file_contents = ITSEC_Lib_File::read( $file );

		if ( is_wp_error( $file_contents ) ) {
			/* translators: 1: original error message */
			return new WP_Error( $file_contents->get_error_code(), sprintf( __( 'The settings file cannot be read. %1$s', 'it-l10n-ithemes-security-pro' ), $file_contents->get_error_message() ) );
		}


		$data = json_decode( $file_contents, true );

		if ( is_null( $data ) && ( 'null' !== $file_contents ) ) {

			if ( strpos( $file_contents, 'CREATE TABLE IF NOT EXISTS' ) !== false ) {
				return new WP_Error( 'unable_to_decode_json_data', __( 'The uploaded file appears to be a database backup. Please ensure that you are supplying a valid export file from the Settings Import and Export module.', 'it-l10n-ithemes-security-pro' ) );
			}

			return new WP_Error( 'unable_to_decode_json_data', __( 'The settings file is invalid or corrupt. The JSON data was unable to be read. Please ensure that you are supplying a valid export file in either a zip or JSON format.', 'it-l10n-ithemes-security-pro' ) );
		}

		if ( ! is_array( $data ) ) {
			return new WP_Error( 'found_non_array_json_data', __( 'The settings file contains invalid data. The data is expected to be in a JSON array format, but a different format was found. Please ensure that you are supplying a valid export file.', 'it-l10n-ithemes-security-pro' ) );
		}


		if ( ! isset( $data['exporter_version'] ) || ! isset( $data['options'] ) ) {
			$data = array(
				'exporter_version' => 0,
				'options'          => $data,
			);
		}


		foreach ( $data['options'] as $index => $option ) {
			if ( ! isset( $option['name'] ) || ! isset( $option['value'] ) || ! isset( $option['auto'] ) ) {
				return new WP_Error( 'invalid_data_format', __( 'The settings file contains invalid data. Valid exported settings are a series of options table entries. The supplied data did not match this format. Please ensure that you are supplying a valid export file.', 'it-l10n-ithemes-security-pro' ) );
			}

			if ( 'itsec' !== substr( $option['name'], 0, 5 ) ) {
				return new WP_Error( 'non_security_settings_found', __( 'The settings file contains settings that are not for iThemes Security. These settings will not be imported. Please supply an export file from iThemes Security.', 'it-l10n-ithemes-security-pro' ) );
			}


			if ( is_bool( $option['auto'] ) ) {
				$data['options'][$index]['auto'] = ( $option['auto'] ) ? 'yes' : 'no';
			}
		}

		return $data;
	}

	/**
	 * Ensure that a specific entry in $_FILES is present and valid.
	 *
	 * @static
	 *
	 * @param string $form_name The name of the $_FILES index to check.
	 * @return bool|WP_Error Returns true if the requested entry is present and valid, or a WP_Error object containing an error message otherwise.
	 */
	private static function validate_uploaded_file( $form_name ) {
		if ( ! isset( $_FILES[$form_name] ) ) {
			return new WP_Error( 'file_upload_field_missing', __( 'The form field used to upload the file is missing. This could indicate a browser or plugin compatibility issue. Please contact support.', 'it-l10n-ithemes-security-pro' ) );
		}


		$file = $_FILES[$form_name];

		if ( isset( $file['error'] ) && ( UPLOAD_ERR_OK !== $file['error'] ) ) {
			$messages = array(
				UPLOAD_ERR_INI_SIZE   => __( 'The uploaded file exceeds the upload_max_filesize directive in php.ini.' ),
				UPLOAD_ERR_FORM_SIZE  => __( 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.' ),
				UPLOAD_ERR_PARTIAL    => __( 'The uploaded file was only partially uploaded.' ),
				UPLOAD_ERR_NO_FILE    => __( 'No file was uploaded.' ),
				UPLOAD_ERR_NO_TMP_DIR => __( 'Missing a temporary folder.' ),
				UPLOAD_ERR_CANT_WRITE => __( 'Failed to write file to disk.' ),
				UPLOAD_ERR_EXTENSION  => __( 'File upload stopped by extension.' ),
			);

			if ( isset( $messages[$file['error']] ) ) {
				$message = $messages[$file['error']];
			} else {
				$message = sprintf( __( 'Unknown upload error (code "%s")', 'it-l10n-ithemes-security-pro' ), $file['error'] );
			}

			return new WP_Error( 'file_upload_error', $message );
		}

		if ( ! isset( $file['tmp_name'] ) ) {
			return new WP_Error( 'file_upload_php_error', __( 'The uploaded file was unable to be read due to a PHP error. The "tmp_name" field for the file upload is missing. Please contact support.', 'it-l10n-ithemes-security-pro' ) );
		}


		return true;
	}

	/**
	 * Get a writable temporary directory.
	 *
	 * The directory has a randomized name to make it hard for snooping people/bots to find the location. Multiple
	 * directories to house the temporary directory are checked in order to ensure that a usable directory can be
	 * created on as many platforms as possible.
	 *
	 * @static
	 *
	 * @uses ITSEC_Settings_Admin::get_writable_subdir() to get the generated random directory.
	 *
	 * @return string|WP_Error Returns the path to the temporary directory
	 */
	private static function get_temp_dir() {
		if ( false !== ( $dir = self::get_writable_subdir( ITSEC_Core::get_storage_dir() ) ) ) {
			return $dir;
		}


		$wp_upload_dir = ITSEC_Core::get_wp_upload_dir();

		if ( false !== ( $dir = self::get_writable_subdir( $wp_upload_dir['basedir'] ) ) ) {
			return $dir;
		}
		if ( false !== ( $dir = self::get_writable_subdir( $wp_upload_dir['path'] ) ) ) {
			return $dir;
		}
		if ( false !== ( $dir = self::get_writable_subdir( ABSPATH ) ) ) {
			return $dir;
		}
		if ( ITSEC_Lib_Utility::is_callable_function( 'sys_get_temp_dir' ) ) {
			if ( false !== ( $dir = self::get_writable_subdir( @sys_get_temp_dir() ) ) ) {
				return $dir;
			}
		} else {
			if ( false !== ( $dir = self::get_writable_subdir( getenv( 'TMP' ) ) ) ) {
				return $dir;
			}
			if ( false !== ( $dir = self::get_writable_subdir( getenv( 'TEMP' ) ) ) ) {
				return $dir;
			}
			if ( false !== ( $dir = self::get_writable_subdir( getenv( 'TMPDIR' ) ) ) ) {
				return $dir;
			}
		}
		if ( false !== ( $dir = self::get_writable_subdir( dirname( __FILE__ ) ) ) ) {
			return $dir;
		}

		return new WP_Error( 'cannot_create_temp_dir', __( 'Unable to create a temporary directory. This indicates a file permissions issue where the web server user cannot create files or directories. Please correct the file permission issue or contact your host for assistance and then try again.', 'it-l10n-ithemes-security-pro' ) );
	}

	/**
	 * Returns a writable, randomized directory if one can be created in the supplied directory
	 *
	 * @static
	 *
	 * @param string $dir Directory path to create the randomized directory in.
	 * @return string|bool Returns the path to the writable directory, or false if it cannot be created.
	 */
	private static function get_writable_subdir( $dir ) {
		if ( empty( $dir ) ) {
			return false;
		}
		if ( ! is_dir( $dir ) ) {
			return false;
		}

		$test_file = @tempnam( $dir, 'itsec-temp-' );

		if ( false === $test_file ) {
			return false;
		}
		if ( false === @unlink( $test_file ) ) {
			return false;
		}

		$subdir = $test_file;

		if ( false === @mkdir( $subdir, 0700 ) ) {
			return false;
		}
		if ( ! is_writable( $subdir ) ) {
			@rmdir( $subdir );
			return false;
		}

		return $subdir;
	}

	private static function validate_directory( $directory, $label, $module, $setting_id ) {

		require_once( ITSEC_Core::get_core_dir() . 'lib/class-itsec-lib-directory.php' );

		$sanitized = rtrim( $directory, DIRECTORY_SEPARATOR );

		$url   = network_admin_url( "admin.php?page=itsec&module={$module}" );
		$a_tag = "<a href=\"{$url}\" data-module-link=\"{$module}\" data-highlight-setting-id='{$setting_id}'>";

		if ( ! ITSEC_Lib_Directory::is_dir( $sanitized ) ) {
			$result = ITSEC_Lib_Directory::create( $sanitized );

			if ( is_wp_error( $result ) ) {
				/* translators: %1$s is the input name. %2$s is the error message. %3$s is opening link tag. %4$s is closing link tag. */
				$error = sprintf( __( 'The directory supplied in "%1$s" cannot be used as a valid directory. %2$s %3$sPlease select a valid directory%4$s.', 'it-l10n-ithemes-security-pro' ), $label, $result->get_error_message(), $a_tag, '</a>' );
			}
		}

		if ( empty( $error ) && ! ITSEC_Lib_Directory::is_writable( $sanitized ) ) {
			/* translators: %1$s is opening link tag. %2$s is closing link tag. */
			$error = sprintf( __( 'The directory supplied in %1$s is not writable. Please %1$sselect a directory%2$s that can be written to.', 'it-l10n-ithemes-security-pro' ), $label, $a_tag, '</a>' );
		}

		if ( empty( $error ) ) {
			ITSEC_Lib_Directory::add_file_listing_protection( $sanitized );

			return $sanitized;
		}

		return new WP_Error( 'itsec-import-invalid-directory', $error );
	}

	private static function validate_file( $file, $label, $module, $setting_id ) {

		$url   = network_admin_url( "admin.php?page=itsec&module={$module}" );
		$a_tag = "<a href=\"{$url}\" data-module-link=\"{$module}\" data-highlight-setting-id='{$setting_id}'>";

		if ( ! ITSEC_Lib_File::is_file( $file ) && ITSEC_Lib_File::exists( $file ) ) {
			$error = sprintf( __( 'The file path supplied in "%1$s" cannot be used as it already exists but is not a file. %2$sPlease supply a valid file path%3$s.', 'it-l10n-ithemes-security-pro' ), $label, $a_tag, '</a>' );
		} else {
			$result = ITSEC_Lib_Directory::create( dirname( $file ) );

			if ( is_wp_error( $result ) ) {
				/* translators: %1$s is the input name. %2$s is the error message. %3$s is opening link tag. %4$s is closing link tag. */
				$error = sprintf( __( 'The file path supplied in "%1$s" cannot be used as the parent directory cannot be created. %2$s %3$sPlease supply a valid file path%4$s.', 'it-l10n-ithemes-security-pro' ), $label, $result->get_error_message(), $a_tag, '</a>' );
			} elseif ( ! ITSEC_Lib_File::exists( $file ) ) {
				$result = ITSEC_Lib_File::write( $file, '' );

				if ( is_wp_error( $result ) ) {
					$error = sprintf( __( 'The file path supplied in "%1$s" could not be created. %2$sPlease supply a file path that can be written to%3$s.', 'it-l10n-ithemes-security-pro' ), $label, $a_tag, '</a>' );
				} elseif ( ! is_writable( $file ) ) {
					$error = sprintf( __( 'The file path supplied in "%1$s" was successfully created, but it cannot be updated. %2$sPlease supply a file path that can be written to%3$s.', 'it-l10n-ithemes-security-pro' ), $label, $a_tag, '</a>' );
				}
			} elseif ( ! is_writable( $file ) ) {
				$error = sprintf( __( 'The file path supplied in "%1$s" is not writable. %2$sPlease supply a file path that can be written to%3$s.', 'it-l10n-ithemes-security-pro' ), $label, $a_tag, '</a>' );
			}
		}

		if ( ! empty( $error ) ) {
			return new WP_Error( 'itsec-import-invalid-file', $error );
		}
	}
}
