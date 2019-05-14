<?php

final class ITSEC_Grading_System {
	private static $sections = array();
	
	private static function get_sections() {
		if ( ! self::$sections ) {
			require_once( dirname( __FILE__ ) . '/section.php' );
			require_once( dirname( __FILE__ ) . '/section-software.php' );
			require_once( dirname( __FILE__ ) . '/section-security-settings.php' );

			self::$sections = array(
				'software' =>          new ITSEC_Grading_System_Section_Software(),
				'security_settings' => new ITSEC_Grading_System_Section_Security_Settings(),
			);
		}

		return self::$sections;
	}

	public static function get_report() {
		$sections = self::get_sections();

		$section_reports = array(
			$sections['software']->get_report( 40 ),
			$sections['security_settings']->get_report( 60 ),
		);

		$report = array(
			'timestamp'      => time(),
			'grade'          => array(
				'real'             => 'F',
				'current'          => 'F',
				'potential'        => 'F',
				'capped'           => 'A',
				'potential_capped' => 'A',
			),
			'score'         => array(
				'real'      => 0,
				'current'   => 0,
				'potential' => 0,
				'max'       => 0,
				'capped'    => 100,
			),
			'issues'         => 0,
			'fixable_issues' => 0,
			'caps'           => array(),
			'cap'            => 100,
			'potential_cap'  => 100,
			'sections'       => $section_reports,
		);


		$total_weight = 0;
		$hash = '';

		foreach ( $report['sections'] as $section ) {
			if ( $section['cap'] < $report['cap'] ) {
				$report['cap'] = $section['cap'];
			}

			if ( $section['potential_cap'] < $report['potential_cap'] ) {
				$report['potential_cap'] = $section['potential_cap'];
			}

			$hash .= $section['hash'];
		}

		$report['hash'] = md5( $hash );

		if ( $report['cap'] < 100 ) {
			if ( $report['cap'] < 60 ) {
				// This prevents the large range of F grades from skewing the ratios too far.
				$cap = max( $report['cap'], 10 );
			} else {
				$cap = self::get_max_percent_for_grade( $report['cap'] );
			}

			$section_1_grade = self::get_grade( $report['sections'][0]['score']['current'], $report['sections'][0]['score']['max'] );
			$section_2_grade = self::get_grade( $report['sections'][1]['score']['current'], $report['sections'][1]['score']['max'] );
			$section_1_percent = self::get_min_percent_for_grade( $section_1_grade );
			$section_2_percent = self::get_min_percent_for_grade( $section_2_grade );

			if ( $section_1_percent + $section_2_percent > $cap * 2 ) {
				$section_1_weight = round( 100 * ( $cap - $section_2_percent ) / ( $section_1_percent - $section_2_percent ) );
				$section_2_weight = 100 - $section_1_weight;

				$report['sections'][0]['weight'] = $section_1_weight;
				$report['sections'][1]['weight'] = $section_2_weight;
			}
		}

		foreach ( $report['sections'] as $section ) {
			$report['score']['current']   += intval( $section['score']['current'] * $section['weight'] / 100 );
			$report['score']['potential'] += intval( $section['score']['potential'] * $section['weight'] / 100 );
			$report['score']['max']       += intval( $section['score']['max'] * $section['weight'] / 100 );

			$total_weight += $section['weight'];
			$report['issues'] += $section['issues'];
			$report['fixable_issues'] += $section['fixable_issues'];
		}

		foreach ( $report['sections'] as &$section ) {
			$section['weight_percent'] = $section['weight'] / $total_weight * 100;
		}

		$report['score']['capped'] = intval( $report['cap'] * $report['score']['max'] / 100 );
		$report['score']['potential_capped'] = intval( $report['potential_cap'] * $report['score']['max'] / 100 );

		$report['score']['real'] = min( $report['score']['current'], $report['score']['capped'] );
		$report['score']['potential'] = min( $report['score']['potential'], $report['score']['potential_capped'] );

		$report['grade']['real'] = self::get_grade( $report['score']['real'], $report['score']['max'] );
		$report['grade']['current'] = self::get_grade( $report['score']['current'], $report['score']['max'] );
		$report['grade']['potential'] = self::get_grade( $report['score']['potential'], $report['score']['max'] );
		$report['grade']['capped'] = self::get_grade( $report['score']['capped'], $report['score']['max'] );

		return $report;
	}

	public static function get_grade( $score, $max = 100 ) {
		if ( 0 == $max ) {
			return 'F';
		}

		$percent = $score / $max * 100;

		if ( $percent >= 100 ) {
			return 'A+';
		} else if ( $percent >= 90 ) {
			$grade = 'A';
		} else if ( $percent >= 80 ) {
			$grade = 'B';
		} else if ( $percent >= 70 ) {
			$grade = 'C';
		} else if ( $percent >= 60 ) {
			$grade = 'D';
		} else {
			return 'F';
		}

		if ( ( $percent % 10 ) > 6 ) {
			$grade .= '+';
		} else if ( ( $percent % 10 ) < 3 ) {
			$grade .= '-';
		}

		return $grade;
	}

	public static function get_min_percent_for_grade( $grade ) {
		if ( is_int( $grade ) ) {
			$grade = self::get_grade( $grade );
		}

		if ( 'A+' === $grade ) {
			return 97;
		} else if ( 'F' === $grade ) {
			return 0;
		}

		if ( 2 === strlen( $grade ) ) {
			list( $grade, $modifier ) = str_split( $grade );
		} else {
			$modifier = '';
		}

		if ( 'A' === $grade ) {
			$percent = 93;
		} else if ( 'B' === $grade ) {
			$percent = 83;
		} else if ( 'C' === $grade ) {
			$percent = 73;
		} else if ( 'D' === $grade ) {
			$percent = 63;
		}

		if ( '+' === $modifier ) {
			$percent += 4;
		} else if ( '-' === $modifier ) {
			$percent -= 3;
		}

		return $percent;
	}

	public static function get_max_percent_for_grade( $grade ) {
		if ( is_int( $grade ) || preg_match( '/^\d+$/', $grade ) ) {
			$grade = self::get_grade( $grade );
		}

		if ( 'A+' === $grade ) {
			return 100;
		} else if ( 'F' === $grade ) {
			return 59;
		}

		if ( 2 === strlen( $grade ) ) {
			list( $grade, $modifier ) = str_split( $grade );
		} else {
			$modifier = '';
		}

		if ( 'A' === $grade ) {
			$percent = 96;
		} else if ( 'B' === $grade ) {
			$percent = 86;
		} else if ( 'C' === $grade ) {
			$percent = 76;
		} else if ( 'D' === $grade ) {
			$percent = 66;
		}

		if ( '+' === $modifier ) {
			$percent += 3;
		} else if ( '-' === $modifier ) {
			$percent -= 4;
		}

		return $percent;
	}

	public static function resolve_issues( $ids ) {
		$sections         = self::get_sections();
		$invalid_sections = array();

		$to_resolve = array();

		foreach ( $ids as $id ) {
			list( $section, $criterion_id ) = explode( '::', $id, 2 );

			if ( isset( $sections[ $section ] ) ) {
				$to_resolve[ $section ][] = $criterion_id;
			} else {
				$invalid_sections[] = $section;
			}
		}

		foreach ( $to_resolve as $section => $criteria ) {
			$sections[ $section ]->before_resolve_issues( $criteria );

			foreach ( $criteria as $criterion ) {
				$sections[ $section ]->resolve_issue( $criterion );
			}

			$sections[ $section ]->after_resolve_issues( $criteria );
		}

		if ( ! empty( $invalid_sections ) ) {
			$invalid_sections = array_unique( $invalid_sections, SORT_STRING );

			ITSEC_Response::add_error( new WP_Error( 'itsec-grading-system-invalid-sections', sprintf( _n( 'Received a request to resolve issues for a section that does not exist: %s', 'Received a request to resolve issues for sections that do not exist: %s', count( $invalid_sections ), 'it-l10n-ithemes-security-pro' ), implode( ', ', $invalid_sections ) ) ) );
		}
	}
}
