<?php

class ITSEC_File_Change_Hash_Loading_Failed_Exception extends Exception {

	/** @var ITSEC_File_Change_Package */
	private $package;

	/** @var ITSEC_File_Change_Hash_Comparator_Loadable */
	private $comparator;

	/**
	 * Create for a given package and loader.
	 *
	 * @param ITSEC_File_Change_Package                  $package
	 * @param ITSEC_File_Change_Hash_Comparator_Loadable $comparator
	 *
	 * @return ITSEC_File_Change_Hash_Loading_Failed_Exception
	 */
	public static function create_for( ITSEC_File_Change_Package $package, ITSEC_File_Change_Hash_Comparator_Loadable $comparator ) {
		$e = new self( sprintf(
		/* translators: 1. The name of the comparator. 2. The name of the package, for example "iThemes Security Pro v4.5.0". */
			__( 'The %1$s comparator failed to load hashes for %2$s.', 'it-l10n-ithemes-security-pro' ),
			get_class( $comparator ),
			$package
		) );

		$e->package    = $package;
		$e->comparator = $comparator;

		return $e;
	}

	/**
	 * Get the package whose hashes were loaded.
	 *
	 * @return ITSEC_File_Change_Package
	 */
	public function get_package() {
		return $this->package;
	}

	/**
	 * Get the hash comparator that could not load the hashes.
	 *
	 * @return ITSEC_File_Change_Hash_Comparator_Loadable
	 */
	public function get_comparator() {
		return $this->comparator;
	}
}
