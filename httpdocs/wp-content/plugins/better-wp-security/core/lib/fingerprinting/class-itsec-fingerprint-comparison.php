<?php

/**
 * Class ITSEC_Fingerprint_Comparison
 */
final class ITSEC_Fingerprint_Comparison {

	/** @var ITSEC_Fingerprint */
	private $known;

	/** @var ITSEC_Fingerprint */
	private $unknown;

	/** @var int|float */
	private $match_percent;

	/** @var array */
	private $scores;

	/**
	 * ITSEC_Fingerprint_Comparison constructor.
	 *
	 * @param ITSEC_Fingerprint $known
	 * @param ITSEC_Fingerprint $unknown
	 * @param float|int         $match_percent
	 * @param array             $scores
	 */
	public function __construct( ITSEC_Fingerprint $known, ITSEC_Fingerprint $unknown, $match_percent, array $scores ) {
		$this->known         = $known;
		$this->unknown       = $unknown;
		$this->match_percent = $match_percent;
		$this->scores = $scores;
	}

	/**
	 * @return ITSEC_Fingerprint
	 */
	public function get_known() {
		return $this->known;
	}

	/**
	 * @return ITSEC_Fingerprint
	 */
	public function get_unknown() {
		return $this->unknown;
	}

	/**
	 * @return float|int
	 */
	public function get_match_percent() {
		return $this->match_percent;
	}

	/**
	 * Get the raw score evaluation
	 *
	 * @return array
	 */
	public function get_scores() {
		return $this->scores;
	}
}