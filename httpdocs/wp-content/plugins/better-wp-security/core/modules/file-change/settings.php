<?php

final class ITSEC_File_Change_Settings extends ITSEC_Settings {
	public function get_id() {
		return 'file-change';
	}

	public function get_defaults() {
		return array(
			'file_list'       => array(),
			'types'           => array(
				'.log', '.mo', '.po',
				// Images
				'.bmp', '.gif', '.ico', '.jpe', '.jpeg', '.jpg', '.png', '.psd', '.raw', '.svg', '.tif', '.tiff',

				// Audio
				'.aif', '.flac', '.m4a', '.mp3', '.oga', '.ogg', '.ogg', '.ra', '.wav', '.wma',

				// Video
				'.asf', '.avi', '.mkv', '.mov', '.mp4', '.mpe', '.mpeg', '.mpg', '.ogv', '.qt', '.rm', '.vob', '.webm', '.wm', '.wmv',
			),
			'notify_admin'    => true,
			'show_warning'    => false,
			'expected_hashes' => array(),
			'last_scan'       => 0,
		);
	}
}

ITSEC_Modules::register_settings( new ITSEC_File_Change_Settings() );
