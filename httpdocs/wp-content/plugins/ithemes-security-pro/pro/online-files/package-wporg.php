<?php

/**
 * Class ITSEC_File_Change_Package_WPOrg_Plugin
 */
class ITSEC_File_Change_Package_WPOrg_Plugin extends ITSEC_File_Change_Package_Plugin {

	/**
	 * Construct a WP Org plugin package from a regular plugin package.
	 *
	 * @param ITSEC_File_Change_Package_Plugin $package
	 *
	 * @return ITSEC_File_Change_Package_WPOrg_Plugin
	 */
	public static function from_plugin( ITSEC_File_Change_Package_Plugin $package ) {
		return new self( $package->file, $package->data );
	}

	public function get_identifier() {
		return dirname( parent::get_identifier() );
	}
}