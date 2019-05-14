<?php

/**
 * Class ITSEC_Fingerprint_Source_Accept_Encoding
 */
class ITSEC_Fingerprint_Source_Accept_Encoding extends ITSEC_Fingerprint_Source_Header {
	/**
	 * @inheritDoc
	 */
	public function calculate_value_from_global_state() {
		return new ITSEC_Fingerprint_Value( $this, ITSEC_Lib::parse_header_with_attributes( $this->retrieve_header() ) );
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
		$weight = 0;

		foreach ( $value->get_value() as $encoding => $attr ) {
			$weight += 5;

			if ( isset( $attr['q'] ) && 1 !== (int) $attr['q'] ) {
				$weight += 2.5;
			}
		}

		return $weight;
	}

	/**
	 * @inheritDoc
	 */
	protected function get_header_name() {
		return 'Accept-Encoding';
	}
}