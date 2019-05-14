<?php

/**
 * Class ITSEC_File_Change_Package_Core
 */
class ITSEC_File_Change_Package_Core implements ITSEC_File_Change_Package {

	/** @var string */
	private $root;

	/**
	 * ITSEC_File_Change_Package_Core constructor.
	 *
	 * @param string $root
	 */
	public function __construct( $root ) { $this->root = $root; }

	/**
	 * @inheritdoc
	 */
	public function get_root_path() {
		return $this->root;
	}

	/**
	 * @inheritdoc
	 */
	public function get_version() {
		return $GLOBALS['wp_version'];
	}

	/**
	 * @inheritdoc
	 */
	public function get_type() {
		return 'core';
	}

	/**
	 * @inheritdoc
	 */
	public function get_identifier() {
		return 'core';
	}

	/**
	 * @inheritdoc
	 */
	public function __toString() {
		return sprintf( __( 'WordPress Core %s', 'it-l10n-ithemes-security-pro' ), 'v' . $this->get_version() );
	}
}