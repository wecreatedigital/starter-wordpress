<?php

/**
 * Class ITSEC_File_Change_Hash_Comparator_Core
 */
class ITSEC_File_Change_Hash_Comparator_Core implements ITSEC_File_Change_Hash_Comparator_Loadable {

	/** @var array */
	private $hashes = array();

	/**
	 * @inheritdoc
	 */
	public function supports_package( ITSEC_File_Change_Package $package ) {
		return $package instanceof ITSEC_File_Change_Package_Core;
	}

	/**
	 * @inheritdoc
	 */
	public function has_hash( $relative_path, ITSEC_File_Change_Package $package ) {
		return isset( $this->hashes[ $package->get_version() ][ $this->expand_path( $package, $relative_path ) ] );
	}

	/**
	 * @inheritdoc
	 */
	public function hash_matches( $actual_hash, $relative_path, ITSEC_File_Change_Package $package ) {
		return $this->hashes[ $package->get_version() ][ $this->expand_path( $package, $relative_path ) ] === $actual_hash;
	}

	/**
	 * Expand a path to include the beginning wp-admin or wp-includes.
	 *
	 * For performance reasons, the core package is split up into 'wp-admin',
	 * 'wp-includes' and root level files like wp-blog-header.php. We need to expand
	 * the relative paths, which might be relative to wp-admin to be relative to the base
	 * path.
	 *
	 * @param ITSEC_File_Change_Package $package
	 * @param string                    $relative
	 *
	 * @return string
	 */
	private function expand_path( ITSEC_File_Change_Package $package, $relative ) {
		$path = $package->get_root_path() . $relative;
		$path = substr( $path, strlen( ABSPATH ) );

		return $path;
	}

	/**
	 * @inheritdoc
	 */
	public function load( ITSEC_File_Change_Package $package ) {

		if ( ! $hashes = ITSEC_Online_Files_Utility::get_core_hashes( $package->get_version(), get_locale() ) ) {
			throw ITSEC_File_Change_Hash_Loading_Failed_Exception::create_for( $package, $this );
		}

		$this->hashes[ $package->get_version() ] = $hashes;
	}

	/**
	 * @inheritdoc
	 */
	public function get_load_cost( ITSEC_File_Change_Package $package ) {
		if ( false !== ITSEC_Online_Files_Utility::get_cached_core_hashes( $package->get_version(), get_locale() ) ) {
			return self::CACHED;
		}

		return self::EXTERNAL;
	}
}