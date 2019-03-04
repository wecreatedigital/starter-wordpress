<?php

/**
 * Interface ITSEC_Fingerprint_Source
 */
interface ITSEC_Fingerprint_Source {

	/**
	 * Calculate the source value from global state.
	 *
	 * @return ITSEC_Fingerprint_Value
	 */
	public function calculate_value_from_global_state();

	/**
	 * Compare two source values.
	 *
	 * @param ITSEC_Fingerprint_Value $known
	 * @param ITSEC_Fingerprint_Value $unknown
	 *
	 * @return int A percentage, 100 being a perfect match.
	 */
	public function compare( ITSEC_Fingerprint_Value $known, ITSEC_Fingerprint_Value $unknown );

	/**
	 * How should the source be weighted.
	 *
	 * @param ITSEC_Fingerprint_Value $value
	 *
	 * @return int
	 */
	public function get_weight( ITSEC_Fingerprint_Value $value );

	/**
	 * Get the unique slug identifying this fingerprint source.
	 *
	 * @return string
	 */
	public function get_slug();
}