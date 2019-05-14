<?php

/**
 * Interface ITSEC_File_Change_Package
 */
interface ITSEC_File_Change_Package {

	/**
	 * Get the path to the root directory of the package.
	 *
	 * This contains a trailingslash.
	 *
	 * @return string
	 */
	public function get_root_path();

	/**
	 * Get the version of the package.
	 *
	 * @return string
	 */
	public function get_version();

	/**
	 * Get the type of the package.
	 *
	 * For example, 'core' or 'theme' or 'ithemes-plugin'.
	 *
	 * @return string
	 */
	public function get_type();

	/**
	 * Get an identifier for the package.
	 *
	 * This identifier must be globally unique amongst packages of the same type.
	 * For example 'akismet/akismet.php' or 'ithemes-security-pro'.
	 *
	 * @return string
	 */
	public function get_identifier();

	/**
	 * Return a human readable label of the package.
	 *
	 * @return string
	 */
	public function __toString();
}