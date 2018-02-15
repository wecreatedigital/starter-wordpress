<?php

$split    = ITSEC_Modules::get_setting( 'file-change', 'split', false );
$interval = $split ? ITSEC_Scheduler::S_FOUR_DAILY : ITSEC_Scheduler::S_DAILY;

ITSEC_Core::get_scheduler()->schedule( $interval, 'file-change' );