<?php

require_once( dirname( __FILE__ ) . '/class-itsec-password-requirements.php' );

$requirements = new ITSEC_Password_Requirements();
$requirements->run();