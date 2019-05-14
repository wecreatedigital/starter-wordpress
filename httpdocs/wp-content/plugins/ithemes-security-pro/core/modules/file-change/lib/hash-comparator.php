<?php

/**
 * Interface ITSEC_File_Change_Hash_Comparator
 */
interface ITSEC_File_Change_Hash_Comparator {

	/**
	 * Does this comparator support hashes for a given package.
	 *
	 * For example, a comparator might only support iThemes Packages.
	 *
	 * @param ITSEC_File_Change_Package $package
	 *
	 * @return bool
	 */
	public function supports_package( ITSEC_File_Change_Package $package );

	/**
	 * Check if this comparator has an expected hash for the given file.
	 *
	 * @param string                    $relative_path Path relative to the root of the package.
	 * @param ITSEC_File_Change_Package $package
	 *
	 * @return bool
	 */
	public function has_hash( $relative_path, ITSEC_File_Change_Package $package );

	/**
	 * Check if the file's actual hash matches the expected hash.
	 *
	 * @param string                    $actual_hash   The hash to compare against.
	 * @param string                    $relative_path Path relative to the root of the package.
	 * @param ITSEC_File_Change_Package $package
	 *
	 * @return bool
	 */
	public function hash_matches( $actual_hash, $relative_path, ITSEC_File_Change_Package $package );
}