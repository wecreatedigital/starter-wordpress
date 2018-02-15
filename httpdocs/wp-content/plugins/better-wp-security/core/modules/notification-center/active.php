<?php

require_once( dirname( __FILE__ ) . '/class-notification-center.php' );

$center = new ITSEC_Notification_Center();
$center->run();
ITSEC_Core::set_notification_center( $center );