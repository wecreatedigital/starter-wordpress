<?php

/**
 * Class ITSEC_File_Change_Hash_Comparator_WPOrg_Plugin
 */
class ITSEC_File_Change_Hash_Comparator_WPOrg_Plugin implements ITSEC_File_Change_Hash_Comparator_Loadable {

	/** @var array */
	private $hashes = array();

	/**
	 * @inheritdoc
	 */
	public function supports_package( ITSEC_File_Change_Package $package ) {
		return $package instanceof ITSEC_File_Change_Package_WPOrg_Plugin;
	}

	/**
	 * @inheritdoc
	 */
	public function has_hash( $relative_path, ITSEC_File_Change_Package $package ) {
		return isset( $this->hashes[ $package->get_identifier() ][ $relative_path ] );
	}

	/**
	 * @inheritdoc
	 */
	public function hash_matches( $actual_hash, $relative_path, ITSEC_File_Change_Package $package ) {

		$hashes = (array) $this->hashes[ $package->get_identifier() ][ $relative_path ];

		foreach ( $hashes as $hash ) {
			if ( $hash === $actual_hash ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @inheritdoc
	 */
	public function load( ITSEC_File_Change_Package $package ) {
		if ( ! $hashes = ITSEC_Online_Files_Utility::get_wporg_plugin_hashes( $package->get_identifier(), $package->get_version() ) ) {
			throw ITSEC_File_Change_Hash_Loading_Failed_Exception::create_for( $package, $this );
		}

		$this->hashes[ $package->get_identifier() ] = $hashes;
	}

	/**
	 * @inheritdoc
	 */
	public function get_load_cost( ITSEC_File_Change_Package $package ) {
		if ( false !== ITSEC_Online_Files_Utility::get_cached_wporg_plugin_hashes( $package->get_identifier(), $package->get_version() ) ) {
			return self::CACHED;
		}

		return self::EXTERNAL;
	}
}