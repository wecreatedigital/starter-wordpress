<?php

final class ITSEC_Magic_Links_Settings extends ITSEC_Settings {
	public function get_id() {
		return 'magic-links';
	}

	public function get_defaults() {
		return array();
	}
}

ITSEC_Modules::register_settings( new ITSEC_Magic_Links_Settings() );
