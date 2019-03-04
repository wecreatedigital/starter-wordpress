<?php

interface ITSEC_File_Change_Hash_Comparator_Loadable extends ITSEC_File_Change_Hash_Comparator {

	const CACHED = 1;
	const LOCAL = 2;
	const EXTERNAL = 3;

	/**
	 * Load the hashes for a package.
	 *
	 * @param ITSEC_File_Change_Package $package
	 *
	 * @return void
	 *
	 * @throws ITSEC_File_Change_Hash_Loading_Failed_Exception
	 */
	public function load( ITSEC_File_Change_Package $package );

	/**
	 * Get the cost to load the hashes.
	 *
	 * A higher number is slower.
	 *
	 * @param ITSEC_File_Change_Package $package
	 *
	 * @return int
	 */
	public function get_load_cost( ITSEC_File_Change_Package $package );
}