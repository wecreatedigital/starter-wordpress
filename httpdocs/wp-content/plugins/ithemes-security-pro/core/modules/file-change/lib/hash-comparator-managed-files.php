<?php

/**
 * Class ITSEC_File_Change_Hash_Comparator_Managed_Files
 */
class ITSEC_File_Change_Hash_Comparator_Managed_Files implements ITSEC_File_Change_Hash_Comparator {

	/** @var array */
	private $hashes;

	/**
	 * ITSEC_File_Change_Hash_Comparator_Managed_Files constructor.
	 */
	public function __construct() {
		$this->hashes = ITSEC_Modules::get_setting( 'file-change', 'expected_hashes', array() );
	}

	/**
	 * @inheritDoc
	 */
	public function supports_package( ITSEC_File_Change_Package $package ) {
		return $package instanceof ITSEC_File_Change_Package_System;
	}

	/**
	 * @inheritDoc
	 */
	public function has_hash( $relative_path, ITSEC_File_Change_Package $package ) {
		return isset( $this->hashes[ $package->get_root_path() . $relative_path ] );
	}

	/**
	 * @inheritDoc
	 */
	public function hash_matches( $actual_hash, $relative_path, ITSEC_File_Change_Package $package ) {
		return $this->hashes[ $package->get_root_path() . $relative_path ] === $actual_hash;
	}
}