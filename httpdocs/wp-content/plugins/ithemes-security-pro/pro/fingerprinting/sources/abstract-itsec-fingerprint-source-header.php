<?php

/**
 * Class ITSEC_Fingerprint_Source_Header
 */
abstract class ITSEC_Fingerprint_Source_Header implements ITSEC_Fingerprint_Source {

	/**
	 * Get the header name to use.
	 *
	 * @return string
	 */
	abstract protected function get_header_name();

	/**
	 * Retrieve the value of the HTTP header.
	 *
	 * @return string|null
	 */
	protected function retrieve_header() {
		$key = 'HTTP_' . str_replace( '-', '_', strtoupper( $this->get_header_name() ) );

		return isset( $_SERVER[ $key ] ) ? $_SERVER[ $key ] : null;
	}

	/**
	 * @inheritdoc
	 */
	public function get_slug() {
		return 'header-' . strtolower( $this->get_header_name() );
	}
}