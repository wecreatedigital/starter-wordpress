<?php

$settings = ITSEC_Modules::get_settings( 'backup' );

if ( $settings['enabled'] && $settings['interval'] > 0 ) {
	ITSEC_Core::get_scheduler()->schedule( 'backup', 'backup' );
}