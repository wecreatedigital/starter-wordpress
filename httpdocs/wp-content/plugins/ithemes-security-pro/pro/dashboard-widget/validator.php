<?php

class ITSEC_Dashboard_Widget_Validator extends ITSEC_Validator {
	public function get_id() {
		return 'dashboard-widget';
	}

	protected function sanitize_settings() {
		$this->set_previous_if_empty( array_keys( $this->defaults ) );
		$this->vars_to_skip_validate_matching_fields = array_keys( $this->defaults );
		$this->vars_to_skip_validate_matching_types  = array_keys( $this->defaults );

		$this->sanitize_setting( array( 1, 2 ), 'version', esc_html__( 'Widget Version', 'it-l10n-ithemes-security-pro' ) );
		$this->sanitize_setting( 'positive-int', 'nag_dismissed', esc_html__( 'Last Nag', 'it-l10n-ithemes-security-pro' ) );
	}
}

ITSEC_Modules::register_validator( new ITSEC_Dashboard_Widget_Validator() );
