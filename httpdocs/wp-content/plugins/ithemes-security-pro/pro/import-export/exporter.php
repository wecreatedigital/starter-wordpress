<?php

final class ITSEC_Import_Export_Exporter {
	public static function create( $email ) {
		require_once( ITSEC_Core::get_core_dir() . '/lib/class-itsec-lib-file.php' );
		require_once( ABSPATH . 'wp-admin/includes/class-pclzip.php' );


		$email = sanitize_text_field( trim( $email ) );

		if ( ! is_email( $email ) ) {
			/* translators: 1: email address */
			return new WP_Error( 'itsec-import-export-exporter-create-invalid-email', sprintf( __( 'The supplied email address (%1$s) is invalid. The settings were not exported. Please supply a valid email address and try again.', 'it-l10n-ithemes-security-pro' ), $email ) );
		}


		$content = self::get_export_content();
		$content = json_encode( $content );


		$base_dir = ITSEC_Core::get_storage_dir() . '/export-' . current_time( 'Ymd-His' ) . '-';
		$dir = $base_dir . wp_generate_password( 10, false );
		$count = 0;

		while ( ITSEC_Lib_Directory::is_dir( $dir ) ) {
			$dir = $base_dir . wp_generate_password( 10, false );

			if ( ++$count > 20 ) {
				return new WP_Error( 'itsec-import-export-exporter-create-cannot-find-unique-directory', __( 'Unable to find a unique, new directory to store the generated export file. The settings were not exported.', 'it-l10n-ithemes-security-pro' ) );
			}
		}

		$result = ITSEC_Lib_Directory::create( $dir );

		if ( is_wp_error( $result ) ) {
			/* translators: 1: original error message */
			return new WP_Error( $result->get_error_code(), sprintf( __( 'Unable to create the directory to hold the export file. %1$s', 'it-l10n-ithemes-security-pro' ), $result->get_error_message() ) );
		}


		$settings_file = "$dir/itsec_options.json";
		$zip_file      = "$dir/itsec_options.zip";

		$result = ITSEC_Lib_File::write( $settings_file, $content );

		if ( is_wp_error( $result ) ) {
			/* translators: 1: original error message */
			return new WP_Error( $result->get_error_code(), sprintf( __( 'Unable to create the export file. %1$s', 'it-l10n-ithemes-security-pro' ), $result->get_error_message() ) );
		}


		$zip = new PclZip( $zip_file );

		$result = $zip->create( $settings_file, PCLZIP_OPT_REMOVE_PATH, dirname( $settings_file ) );

		if ( 0 === $result ) {
			$export_file = $settings_file;
		} else {
			$export_file = $zip_file;
			@unlink( $settings_file );
		}

		$time = ITSEC_Core::get_current_time_gmt();

		$nc   = ITSEC_Core::get_notification_center();
		$mail = $nc->mail();

		$subject = $mail->prepend_site_url_to_subject( $nc->get_subject( 'import-export' ) );
		$subject = apply_filters( 'itsec_backup_email_subject', $subject );

		$mail->set_subject( $subject, false );
		$mail->set_recipients( array( $email ) );
		$mail->add_attachment( $export_file );

		$mail->add_header(
			esc_html__( 'Settings Export', 'it-l10n-ithemes-security-pro' ),
			sprintf(
				/* translators: 1. opening bold tag, 2. date, 3. time, 4. closing bold tag. */
				esc_html__( 'Settings Export created on %1$s %2$s at %3$s %4$s', 'it-l10n-ithemes-security-pro' ),
				'<b>',
				date_i18n( get_option( 'date_format' ), $time ),
				date_i18n( get_option( 'time_format' ), $time ),
				'</b>'
			)
		);

		$message = ITSEC_Lib::replace_tags( $nc->get_message( 'import-export' ), array(
			'date'       => date_i18n( get_option( 'date_format' ), $time ),
			'time'       => date_i18n( get_option( 'time_format' ), $time ),
			'site_url'   => $mail->get_display_url(),
			'site_title' => get_bloginfo( 'name', 'display' ),
		) );

		$mail->add_info_box( $message, 'attachment' );
		$mail->add_footer();

		$result = $nc->send( 'import-export', $mail );

		if ( false === $result ) {
			/* translators: 1: absolute path to export file */
			return new WP_Error( 'itsec-import-export-exporter-create-wp-mail-failed', sprintf( __( 'Sending the email message failed. The exported settings file can be found at <code>%1$s</code>.', 'it-l10n-ithemes-security-pro' ), $export_file ) );
		}


		ITSEC_Lib_Directory::remove( $dir );

		return true;
	}

	public static function get_export_content() {
		global $wpdb;


		$ignored_options = array(
			'itsec_data',
			'itsec_file_change_warning',
			'itsec_initials',
			'itsec_jquery_version',
			'itsec_local_file_list',
			'itsec_local_file_list_0',
			'itsec_local_file_list_1',
			'itsec_local_file_list_2',
			'itsec_local_file_list_3',
			'itsec_local_file_list_4',
			'itsec_local_file_list_5',
			'itsec_local_file_list_6',
			'itsec_message_queue',
			'itsec_rewrites_changed',
			'itsec_config_changed',
			'itsec_temp_whitelist_ip',
			'itsec_online_files_hashes',
			'itsec_file_change_scan_destroyed',
			'itsec_file_change_latest',
		);

		$is_multisite = is_multisite();

		if ( $is_multisite ) {
			$raw_options = $wpdb->get_results( $q = $wpdb->prepare(
				"SELECT * FROM `" . $wpdb->sitemeta . "` WHERE `meta_key` LIKE %s AND `site_id` = %d;", 'itsec%', $wpdb->siteid
			), ARRAY_A );
		} else {
			$raw_options = $wpdb->get_results( "SELECT * FROM `" . $wpdb->options . "` WHERE `option_name` LIKE 'itsec%';", ARRAY_A );
		}

		$options = array();

		foreach ( (array) $raw_options as $option ) {

			$name = $is_multisite ? $option['meta_key'] : $option['option_name'];

			if ( in_array( $name, $ignored_options ) ) {
				continue;
			}

			if ( strpos( $name, 'itsec-lock' ) === 0 ) {
				continue;
			}

			$options[] = array(
				'name'  => $name,
				'value' => maybe_unserialize( $is_multisite ? $option['meta_value'] : $option['option_value'] ),
				'auto'  => ( empty( $option['autoload'] ) || 'yes' === $option['autoload'] ) ? 'yes' : 'no',
			);
		}


		$content = array(
			'exporter_version' => 1,
			'plugin_build'     => ITSEC_Core::get_plugin_build(),
			'timestamp'        => ITSEC_Core::get_current_time_gmt(),
			'site'             => network_home_url(),
			'options'          => $options,
			'abspath'          => ABSPATH,
		);

		return $content;
	}
}
