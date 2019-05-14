<?php

abstract class ITSEC_Grading_System_Section {
	abstract public function get_id();
	abstract public function get_name();
	abstract public function get_description();
	abstract public function get_weights();
	abstract public function get_criteria();

	public function before_resolve_issues( $issues ) { }
	abstract public function resolve_issue( $id );
	public function after_resolve_issues( $issues ) { }

	public function get_report( $weight ) {
		$weights = $this->get_weights();

		$report = array(
			'id'             => $this->get_id(),
			'name'           => $this->get_name(),
			'description'    => $this->get_description(),
			'weight'         => $weight,
			'issues'         => 0,
			'fixable_issues' => 0,
			'grade'          => array(
				'current'   => 'F',
				'potential' => 'F',
			),
			'score'          => array(
				'current'   => 0,
				'potential' => 0,
				'max'       => 0,
			),
			'caps'           => array(),
			'cap'            => 100,
			'potential_cap'  => 100,
			'criteria'       => $this->get_criteria(),
		);

		$to_hash = array();

		foreach ( $report['criteria'] as $id => &$criterion ) {
			if ( isset( $weights[$id] ) ) {
				$weight = $weights[$id];
			} else {
				$weight = $weights[substr( $id, 0, strpos( $id, ':' ) )];
			}

			$criterion['max'] = $weight;
			$criterion['score'] = intval( $weight * $criterion['percent'] / 100 );

			if ( $criterion['fixable'] ) {
				$criterion['potential'] = $weight;
			} else {
				$criterion['potential'] = $criterion['score'];
			}

			$criterion['grade'] = ITSEC_Grading_System::get_grade( $criterion['percent'], 100 );

			if ( is_wp_error( $criterion['details'] ) ) {
				$criterion['details'] = $criterion['details']->get_error_message();
			}

			$report['score']['current'] += $criterion['score'];
			$report['score']['potential'] += $criterion['potential'];
			$report['score']['max'] += $weight;

			if ( isset( $criterion['cap'] ) ) {
				$report['caps'][$id] = $criterion['cap'];

				if ( $criterion['cap'] < $report['cap'] ) {
					$report['cap'] = $criterion['cap'];
				}

				if ( ! $criterion['fixable'] && $criterion['cap'] < $report['potential_cap'] ) {
					$report['potential_cap'] = $criterion['cap'];
				}
			}

			if ( $criterion['issue'] ) {
				$report['issues']++;

				if ( $criterion['fixable'] ) {
					$report['fixable_issues']++;
				}
			}

			$to_hash[ $id ] = $criterion['score'];
		}

		ksort( $to_hash );
		$report['hash'] = md5( serialize( $to_hash ) );

		if ( $report['cap'] < 100 ) {
			if ( $report['cap'] < 10 && $report['score']['current'] >= 10 ) {
				$report['cap'] = 10;
			}

			$capped_score = $report['score']['max'] * $report['cap'] / 100;

			if ( $capped_score < $report['score']['current'] ) {
				$report['score']['current'] = $capped_score;
			}
		}

		if ( $report['potential_cap'] < 100 ) {
			if ( $report['potential_cap'] < 10 && $report['score']['potential'] >= 10 ) {
				$report['potential_cap'] = 10;
			}

			$capped_score = $report['score']['max'] * $report['potential_cap'] / 100;

			if ( $capped_score < $report['score']['potential'] ) {
				$report['score']['potential'] = $capped_score;
			}
		}

		uasort( $report['criteria'], array( $this, 'sort_criteria' ) );

		$report['grade']['current'] = ITSEC_Grading_System::get_grade( $report['score']['current'], $report['score']['max'] );
		$report['grade']['potential'] = ITSEC_Grading_System::get_grade( $report['score']['potential'], $report['score']['max'] );

		return $report;
	}

	protected function sort_criteria( $a, $b ) {
		$a_loss = $a['max'] - $a['score'];
		$b_loss = $b['max'] - $b['score'];

		if ( $a_loss > $b_loss ) {
			return -1;
		} else if ( $a_loss < $b_loss ) {
			return 1;
		}

		if ( $a['max'] > $b['max'] ) {
			return -1;
		} else if ( $a['max'] < $b['max'] ) {
			return 1;
		}

		return strnatcasecmp( $a['name'], $b['name'] );
	}
}
