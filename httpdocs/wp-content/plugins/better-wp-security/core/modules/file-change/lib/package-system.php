<?php

/**
 * Class ITSEC_File_Change_Package_System
 */
class ITSEC_File_Change_Package_System implements ITSEC_File_Change_Package {

	/**
	 * @inheritDoc
	 */
	public function get_root_path() {
		return '/'; // System files might not necessarily be within the web root.
	}

	/**
	 * @inheritDoc
	 */
	public function get_version() {
		return '';
	}

	/**
	 * @inheritDoc
	 */
	public function get_type() {
		return 'system-files';
	}

	/**
	 * @inheritDoc
	 */
	public function get_identifier() {
		return 'system-files';
	}

	/**
	 * @inheritDoc
	 */
	public function __toString() {
		return __( 'System Files', 'better-wp-security' );
	}
}