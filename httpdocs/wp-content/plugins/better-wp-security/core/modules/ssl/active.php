<?php

require_once( dirname( __FILE__ ) . '/class-itsec-ssl-admin.php' );
$itsec_ssl_admin = new ITSEC_SSL_Admin();
$itsec_ssl_admin->run();

require_once( dirname( __FILE__ ) . '/class-itsec-ssl.php' );
