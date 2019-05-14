<?php

/**
 * Class ITSEC_Fingerprint_Source_Header_Basic
 */
final class ITSEC_Fingerprint_Source_Header_Basic extends ITSEC_Fingerprint_Source_Header {

	/** @var string */
	private $header_name;

	/** @var int */
	private $weight;

	/**
	 * ITSEC_Fingerprint_Source_Header_Basic constructor.
	 *
	 * @param string $header_name
	 * @param int    $weight
	 */
	public function __construct( $header_name, $weight ) {
		$this->header_name = $header_name;
		$this->weight      = $weight;
	}

	/**
	 * @inheritDoc
	 */
	public function calculate_value_from_global_state() {
		return new ITSEC_Fingerprint_Value( $this, $this->retrieve_header() );
	}

	/**
	 * @inheritDoc
	 */
	public function compare( ITSEC_Fingerprint_Value $known, ITSEC_Fingerprint_Value $unknown ) {
		return $known->get_value() === $unknown->get_value() ? 100 : 0;
	}

	/**
	 * @inheritDoc
	 */
	public function get_weight( ITSEC_Fingerprint_Value $value ) {
		return $this->weight;
	}

	/**
	 * @inheritDoc
	 */
	protected function get_header_name() {
		return $this->header_name;
	}
}