<?php

/**
 * Class ITSEC_File_Change_Package_iThemes
 */
class ITSEC_File_Change_Package_iThemes implements ITSEC_File_Change_Package {

	/** @var ITSEC_File_Change_Package */
	private $package;

	/** @var string */
	private $identifier;

	/**
	 * ITSEC_File_Change_Package_iThemes constructor.
	 *
	 * @param ITSEC_File_Change_Package $package
	 * @param string                    $identifier
	 */
	public function __construct( ITSEC_File_Change_Package $package, $identifier ) {
		$this->package    = $package;
		$this->identifier = $identifier;
	}

	/**
	 * @inheritdoc
	 */
	public function get_root_path() {
		return $this->package->get_root_path();
	}

	/**
	 * @inheritdoc
	 */
	public function get_version() {
		return $this->package->get_version();
	}

	/**
	 * @inheritdoc
	 */
	public function get_type() {
		return 'ithemes';
	}

	/**
	 * @inheritdoc
	 */
	public function get_identifier() {
		return $this->identifier;
	}

	/**
	 * @inheritDoc
	 */
	public function __toString() {
		return sprintf( __( '%s by iThemes', 'it-l10n-ithemes-security-pro' ), $this->package );
	}
}