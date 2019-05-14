<?php

require_once( dirname( __FILE__ ) . '/class-magic-links.php' );
$magic_links = new ITSEC_Magic_Links();
$magic_links->run();