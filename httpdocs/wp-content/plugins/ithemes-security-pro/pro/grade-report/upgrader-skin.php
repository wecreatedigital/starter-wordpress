<?php

final class ITSEC_Upgrader_Skin extends WP_Upgrader_Skin {
	function header() {}
	function footer() {}
	function bulk_header() {}
	function bulk_footer() {}
	function before( $title = '' ) {}
	function after( $title = '' ) {}
	function error( $errors ) {
		if ( ! empty( $this->options['add_to_response'] ) ) {
			if ( ! empty( $this->plugin_info ) ) {
				ITSEC_Response::add_error(
					new WP_Error( 'itsec-grading-system-plugin-update-failed', sprintf(
						__( 'Unable to update the %1$s plugin. %2$s', 'it-l10n-ithemes-security-pro' ),
						$this->plugin_info['Name'],
						wp_sprintf('%l', ITSEC_Response::get_error_strings( $errors ) )
					) )
				);
			}
		}

	}
	function feedback( $string ) {}
}
