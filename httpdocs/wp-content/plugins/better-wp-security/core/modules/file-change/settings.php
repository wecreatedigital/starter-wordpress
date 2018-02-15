<?php

final class ITSEC_File_Change_Settings extends ITSEC_Settings {
	public function get_id() {
		return 'file-change';
	}

	public function get_defaults() {
		return array(
			'split'          => false,
			'method'         => 'exclude',
			'file_list'      => array(),
			'types'          => array(
				'.jpg',
				'.jpeg',
				'.png',
				'.log',
				'.mo',
				'.po'
			),
			'notify_admin'   => true,
			'last_run'       => 0,
			'last_chunk'     => false,
			'show_warning'   => false,
			'latest_changes' => array(),
		);
	}

	protected function handle_settings_changes( $old_settings ) {
		$split = isset( $old_settings['split'] ) ? $old_settings['split'] : false;

		if ( $split !== $this->settings['split'] ) {
			ITSEC_Core::get_scheduler()->unschedule( 'file-change' );
			$interval = $this->settings['split'] ? ITSEC_Scheduler::S_FOUR_DAILY : ITSEC_Scheduler::S_DAILY;
			ITSEC_Core::get_scheduler()->schedule( $interval, 'file-change' );
		}
	}
}

ITSEC_Modules::register_settings( new ITSEC_File_Change_Settings() );
