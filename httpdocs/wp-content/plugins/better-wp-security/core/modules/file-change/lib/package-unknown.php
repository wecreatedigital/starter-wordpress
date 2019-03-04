<?php

/**
 * Class ITSEC_File_Change_Package_Unknown
 */
class ITSEC_File_Change_Package_Unknown implements ITSEC_File_Change_Package {

	/**
	 * @inheritDoc
	 */
	public function get_root_path() {
		return '/';
	}

	/**
	 * @inheritDoc
	 */
	public function get_version() {
		return '0.0';
	}

	/**
	 * @inheritDoc
	 */
	public function get_type() {
		return 'unknown';
	}

	/**
	 * @inheritDoc
	 */
	public function get_identifier() {
		return 'unknown';
	}

	/**
	 * @inheritDoc
	 */
	public function __toString() {
		return __( 'Unknown', 'better-wp-security' );
	}
}