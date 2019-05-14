<?php

class ITSEC_Grading_System_Active_Settings extends ITSEC_Settings {

	public function get_id() {
		return 'grade-report';
	}

	public function get_defaults() {
		return array(
			'disabled_users' => array(),
		);
	}
}

ITSEC_Modules::register_settings( new ITSEC_Grading_System_Active_Settings() );