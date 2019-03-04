<?php

/**
 * Class ITSEC_Fingerprint_Value
 */
final class ITSEC_Fingerprint_Value {

	/** @var ITSEC_Fingerprint_Source */
	private $source;

	/** @var mixed */
	private $value;

	/**
	 * ITSEC_Fingerprint_Source_Value constructor.
	 *
	 * @param ITSEC_Fingerprint_Source $source
	 * @param mixed                    $value
	 */
	public function __construct( ITSEC_Fingerprint_Source $source, $value ) {
		$this->source = $source;
		$this->value  = $value;
	}

	/**
	 * Get the source type.
	 *
	 * @return ITSEC_Fingerprint_Source
	 */
	public function get_source() {
		return $this->source;
	}

	/**
	 * Get the value.
	 *
	 * @return mixed
	 */
	public function get_value() {
		return $this->value;
	}
}