<?php

/**
 * Class ITSEC_Fingerprint_Source_Accept_Language
 */
class ITSEC_Fingerprint_Source_Accept_Language extends ITSEC_Fingerprint_Source_Header {

	/**
	 * @inheritDoc
	 */
	public function calculate_value_from_global_state() {

		$value = $this->retrieve_header();

		if ( $value && '*' !== $value ) {
			$value = ITSEC_Lib::parse_header_with_attributes( $value );
		}

		return new ITSEC_Fingerprint_Value( $this, $value );
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

		$raw = $value->get_value();

		// Browsers don't typically send a header like this.
		if ( '*' === $raw ) {
			return 40;
		}

		// Browsers almost always submit some value for the header.
		if ( ! $raw ) {
			return 50;
		}

		$weight = 0;

		foreach ( $raw as $lang => $attrs ) {
			$lang = strtolower( $lang );

			if ( 'en' !== $lang && 'en-us' !== $lang ) {
				$weight += 20;
			} else {
				$weight += 10;
			}

			if ( isset( $attrs['q'] ) && 1 !== (int) $attrs['q'] ) {
				$weight += 5;
			}
		}

		return $weight;
	}

	/**
	 * @inheritDoc
	 */
	protected function get_header_name() {
		return 'Accept-Language';
	}
}