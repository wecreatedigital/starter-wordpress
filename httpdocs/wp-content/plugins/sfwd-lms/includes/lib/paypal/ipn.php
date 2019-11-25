<?php
/**
 *  PHP-PayPal-IPN Handler
 */

/*NOTE: the IPN call is asynchronous and can arrive later than the browser is redirected to the success url by paypal
You cannot rely on setting up some details here and then using them in your success page.
 */

global $ld_lms_processing_id;
$ld_lms_processing_id = time();

global $ipn_log_filename;
$ipn_log_filename = '';

$wp_upload_dir = wp_upload_dir();
$ld_ipn_logs_dir = trailingslashit( $wp_upload_dir['basedir'] ) . 'learndash/paypal_ipn/';
$ipn_log_filename = trailingslashit( $ld_ipn_logs_dir ) . $ld_lms_processing_id .'.log';
if ( !file_exists( $ld_ipn_logs_dir ) ) {
	if ( wp_mkdir_p( $ld_ipn_logs_dir ) == false ) {
		$ipn_log_filename = '';
	} 
} 
@file_put_contents( trailingslashit( $ld_ipn_logs_dir ) .'index.php', '// nothing to see here');


if ( ! function_exists( 'ld_ipn_debug' ) ) {
	function ld_ipn_debug( $msg ) {
		global $ld_lms_processing_id, $ipn_log_filename;

		if (( isset( $_REQUEST['debug'] ) ) && ( !empty( $ipn_log_filename ) )) {
			//error_log( "[$ld_lms_processing_id] " . $msg ."\r\n", 3, $ipn_log_filename );
			
			file_put_contents( $ipn_log_filename, learndash_adjust_date_time_display( time(), 'Y-m-d H:i:s'). " [". $ld_lms_processing_id ."] " . $msg ."\r\n", FILE_APPEND );
		}
	}
}

/*
if ( ! function_exists( 'ld_ipn_debug' ) ) {
	function ld_ipn_debug( $msg) {
		global $ld_lms_processing_id;

		if ( isset( $_GET['debug'] ) ) {
			error_log( "[$ld_lms_processing_id] " . $msg ."\r\n", 3, ABSPATH .'ld_ipn_debug.log' );
		}
	}
}
*/

ld_ipn_debug( print_r( $_REQUEST, true ) );

ld_ipn_debug( 'IPN Listener Loading...' );
include __DIR__ . '/ipnlistener.php';
$listener = new IpnListener();

/**
 * Action for initial IpnListener to allow override of public attributes. 
 *
 * @since 2.2.1.2
 *
 * @param Object  $listener Instance of IpnListener Class.
 */
do_action_ref_array( 'leandash_ipnlistener_init', array( &$listener ) );


ld_ipn_debug( 'IPN Listener Loaded' );

/*While testing your IPN script you should be using a PayPal "Sandbox" (get an account at: https://developer.paypal.com )
When you are ready to go live change use_sandbox to false.*/

//$courses_options = array();

//if ( ! empty( $this->post_types) ) {
//	$sfwd_courses    = $this->post_types['sfwd-courses'];
//	$courses_prefix  = $sfwd_courses->get_prefix();
//	$prefix_len      = strlen( $courses_prefix );
//	$courses_options = $sfwd_courses->get_current_options();
//	ld_ipn_debug( 'courses_options<pre>'. print_r($courses_options, true) .'</pre>' );
//	
//	
//	foreach ( $courses_options as $k => $v ) {
//		if ( strpos( $k, $courses_prefix ) === 0 ) {
//			$courses_options[substr( $k, $prefix_len )] = $v;
//			unset( $courses_options[ $k ] );
//		}
//	}
//}

//extract( $courses_options );

$paypal_settings = LearnDash_Settings_Section::get_section_settings_all( 'LearnDash_Settings_Section_PayPal' );
$paypal_settings['paypal_sandbox'] = $paypal_settings['paypal_sandbox'] == 'yes' ? 1 : 0;
ld_ipn_debug( 'DEBUG: paypal_settings<pre>'. print_r($paypal_settings, true) .'</pre>' );

ld_ipn_debug( 'Course Settings Loaded.' );

$listener->use_sandbox = false;

if ( ! empty( $paypal_settings['paypal_sandbox'] ) ) {
	$listener->use_sandbox = true;
	ld_ipn_debug( 'Sandbox Enabled.' );
}

try {
	ld_ipn_debug( 'Checking Post Method.' );
	$listener->requirePostMethod();
	$verified = $listener->processIpn();
	ld_ipn_debug( 'Post method check completed.' );
} catch ( Exception $e ) {
	ld_ipn_debug( 'Post method error: <pre>'. print_r($e->getMessage(), true) .'</pre>' );
	ld_ipn_debug( 'Found Exception. Ending Script.' );
	exit(0);
}

if ( ( isset( $_REQUEST['item_number'] ) ) && ( !empty( $_REQUEST['item_number'] ) ) ) {
	$course_id = $_REQUEST['item_number'];
	$meta = get_post_meta( $course_id, '_sfwd-courses', true );
	//ld_ipn_debug('course meta:<pre>'. print_r($meta, true) .'</pre>');
	
	if ( isset( $_REQUEST['mc_gross'] ) ) {
		if ( ( isset( $meta['sfwd-courses_course_price_type'] ) ) && ( $meta['sfwd-courses_course_price_type'] == 'paynow' ) ) {
			if ( ( isset( $meta['sfwd-courses_course_price'] ) ) && ( !empty( $meta['sfwd-courses_course_price'] ) ) ) {
				$server_course_price = preg_replace("/[^0-9.]/", '', $meta['sfwd-courses_course_price'] );
				$server_course_price = number_format( floatval( $server_course_price ), 2, '.', '' );
				
				$ipn_course_price = preg_replace("/[^0-9.]/", '', $_REQUEST['mc_gross'] );
				$ipn_course_price = floatval( $ipn_course_price );
				ld_ipn_debug( "DEBUG: IPN GrossTax [". $ipn_course_price ."]" );
				
				if (isset( $_REQUEST['tax'] ) ) {
					$ipn_tax_price = preg_replace("/[^0-9.]/", '', $_REQUEST['tax'] );
				} else {
					$ipn_tax_price = 0;
				}
				$ipn_tax_price = floatval( $ipn_tax_price );
				ld_ipn_debug( "DEBUG: IPN Tax [". $ipn_tax_price ."]" );
				
				$ipn_course_price = $ipn_course_price - $ipn_tax_price;
				$ipn_course_price = number_format( floatval( $ipn_course_price ), 2, '.', '' );
				ld_ipn_debug( "DEBUG: IPN Gross - Tax (result) [". $ipn_course_price ."]" );
				
				if ( $server_course_price == $ipn_course_price ) {
					ld_ipn_debug( "IPN Price match: IPN Price [". $ipn_course_price ."] Course Price [". $server_course_price ."]" );
				} else {
					ld_ipn_debug( "Error: IPN Price mismatch: IPN Price [". $ipn_course_price ."] Course Price [". $server_course_price ."]" );
					$verified = false;
				} 
			}
		}
	} else {
		ld_ipn_debug( "Error: Missing 'mc_gross' in IPN data" );
		$verified = false;
	}
} else {
	ld_ipn_debug( "Error: Missing 'item_number' in IPN data" );
	$verified = false;
}



$YOUR_NOTIFICATION_EMAIL_ADDRESS = get_option( 'admin_email' );
$seller_email = $paypal_settings['paypal_email'];

ld_ipn_debug( 'Loaded Email IDs. Notification Email: ' . $YOUR_NOTIFICATION_EMAIL_ADDRESS . ' Seller Email: ' . $seller_email );
$notify_on_valid_ipn = 1;

ld_ipn_debug( 'Payment Verified? : ' . ( ( $verified ) ? 'YES' : 'NO' ) );
/*The processIpn() method returned true if the IPN was "VERIFIED" and false if it was "INVALID".*/

if ( $verified ) {
	ld_ipn_debug( 'Sure, Verfied! Moving Ahead.' );
	/*	Once you have a verified IPN you need to do a few more checks on the POST
	fields--typically against data you stored in your database during when the
	end user made a purchase (such as in the "success" page on a web payments
	standard button). The fields PayPal recommends checking are:
	1. Check the $_POST['payment_status'] is "Completed"
	2. Check that $_POST['txn_id'] has not been previously processed
	3. Check that $_POST['receiver_email'] is get_option('EVI_Paypal_Seller_email')
	4. Check that $_POST['payment_amount'] and $_POST['payment_currency']
	are correct
	 */

	//note: This is just notification for us. Paypal has already made up its mind and the payment has been processed
	//  (you can't cancel that here)
	ld_ipn_debug( 'Receiver Email: ' . $_POST['receiver_email'] . ' Valid Receiver Email? :' . (( $_POST['receiver_email'] == $seller_email) ? 'YES' : 'NO') );

	if ( $_POST['receiver_email'] != $seller_email ) {

		if ( $YOUR_NOTIFICATION_EMAIL_ADDRESS != '' ) {
			//mail( $YOUR_NOTIFICATION_EMAIL_ADDRESS, 'Warning: IPN with invalid receiver email!', $listener->getTextReport() );
			ld_ipn_debug( 'Warning! IPN with invalid receiver email!' );
		} else {
			//error_log( 'notification email not set' );
		}

	}

	ld_ipn_debug( 'Payment Status: ' . $_POST['payment_status'] . ' Completed? :' . ( ( $_POST['payment_status'] == 'Completed') ? 'YES' : 'NO' ) );

	if ( $_POST['payment_status'] == 'Completed' ) {
		ld_ipn_debug( 'Sure, Completed! Moving Ahead.' );
		//a customer has purchased from this website
		//add him to database for customer support

		// get / add user

		$email = $_REQUEST['payer_email'];

		ld_ipn_debug( 'Payment Email: ' . $email );

		if ( ! empty( $_REQUEST['custom'] ) && is_numeric( $_REQUEST['custom'] ) ) {
			$user = get_user_by( 'id', $_REQUEST['custom'] );
			ld_ipn_debug( 'User ID [' . $_REQUEST['custom'] . '] passed back by Paypal. Checking if user exists. User Found: ' . ( ! empty( $user->ID ) ? 'Yes' : 'No' ) );
		}

		if ( ! empty( $user->ID ) ) {

			$user_id = $user->ID;
			ld_ipn_debug( 'User found. Passed back by Paypal. User ID: ' . $user_id );

		} else if ( is_user_logged_in() ) {

			ld_ipn_debug( 'User is logged in.' );
			$user    = wp_get_current_user();
			$user_id = $user->ID;
			ld_ipn_debug( 'User is logged in. User Id: ' . $user_id );

		} else {

			ld_ipn_debug( 'User not logged in.' );

			if ( $user_id = email_exists( $email ) ) {

				ld_ipn_debug( 'User email exists. User Found. User Id: ' . $user_id );
				$user = get_user_by( 'id', $user_id );

			} else {

				ld_ipn_debug( 'User email does not exists. Checking available username...' );
				$username = $email;

				if ( username_exists( $email ) ) {

					ld_ipn_debug( 'Username matching email found, cannot use. Looking further with $count_$email.' );
					$count = 1;

					do {
						$new_username = $count . '_' . $email;
						$count++;
					} while ( username_exists( $new_username ) );

					$username = $new_username;
					ld_ipn_debug( 'Accepting user with $username as :' . $new_username );
				}

				$random_password = wp_generate_password( 12, false );
				ld_ipn_debug( 'Creating User with username:' . $username . ' password: ' . $random_password, ' email: ' . $email );
				$user_id = wp_create_user( $username, $random_password, $email );
				ld_ipn_debug( 'User created with user_id: ' . $user_id );
				$user = get_user_by( 'id', $user_id );
				// Handle all three versions of WP wp_new_user_notification
				global $wp_version;
				if (version_compare($wp_version, '4.3.0', '<')) {
				    wp_new_user_notification( $user_id, $user_pass );
				} else if (version_compare($wp_version, '4.3.0', '==')) {
				    wp_new_user_notification( $user_id, 'both' );						
				} else if (version_compare($wp_version, '4.3.1', '>=')) {
				    wp_new_user_notification( $user_id, null, 'both' );
				}				
				ld_ipn_debug( 'Notification Sent.' );

			}

		}

		// record in course
		ld_ipn_debug( 'Starting to give course access...' );

		$course_id = $_REQUEST['item_number'];
		
		/*$meta = get_post_meta( $course_id, '_sfwd-courses', true );
		$access_list = $meta['sfwd-courses_course_access_list'];
		ld_ipn_debug('Current Access List for Course ID:'.$course_id. ' Access List:'. $access_list);

		if ( empty( $access_list ) )
		$access_list = $user_id;
		else
		$access_list .= ",$user_id";

		$meta['sfwd-courses_course_access_list'] = $access_list;
		update_post_meta( $course_id, '_sfwd-courses', $meta );*/
		$meta = ld_update_course_access( $user_id, $course_id );

		//if ( isset( $meta['sfwd-courses_course_access_list'] ) ) {
		//	ld_ipn_debug( 'Updated Course Access List: ' . print_r( $meta['sfwd-courses_course_access_list'], true ) );
		//} else {
		//	ld_ipn_debug( 'Error: Updated but empty Course Access List for Course ID:' . $course_id );
		//}

		$usermeta = get_user_meta( $user_id, '_sfwd-courses', true );
		ld_ipn_debug( 'Fetched User Meta:' . $usermeta );

		if ( empty( $usermeta) ) {
			$usermeta = $course_id;
		} else {
			$usermeta .= ",$course_id";
		}

		update_user_meta( $user_id, '_sfwd-courses', $usermeta );
		ld_ipn_debug( 'Updated user meta:' . $usermeta );

		// log transaction
		ld_ipn_debug( 'Starting Transaction Creation.' );
		$transaction = $_REQUEST;
		$transaction['user_id'] = $user_id;
		$transaction['course_id'] = $course_id;
		$transaction['log_file'] = basename($ipn_log_filename);
		
		$course_title = '';
		$course       = get_post( $course_id );

		if ( ! empty( $course) ) {
			$course_title = $course->post_title;
		}

		ld_ipn_debug( 'Course Title: ' . $course_title );

		$post_id = wp_insert_post( array('post_title' => "Course {$course_title} Purchased By {$email}", 'post_type' => 'sfwd-transactions', 'post_status' => 'publish', 'post_author' => $user_id) );
		ld_ipn_debug( 'Created Transaction. Post Id: ' . $post_id );

		foreach ( $transaction as $k => $v ) {
			update_post_meta( $post_id, $k, $v );
		}
	} /* else if(!empty( $_REQUEST['txn_type'] ) && ( $_REQUEST['txn_type'] == "subscr_cancel" || $_REQUEST['txn_type'] == "subscr_eot" ) ) {
	$subscr_id = $_REQUEST['subscr_id'];
	$transaction = get_posts("post_type=sfwd-transactions&meta_key=subscr_id&meta_value=".$subscr_id);
	if(!empty( $transaction[0]->ID)) {
	$user_id = get_post_meta( $transaction[0]->ID, "user_id", true);
	$course_id = get_post_meta( $transaction[0]->ID, "course_id", true);
	ld_ipn_debug('User ID: '.$user_id.' Course ID:'.$course_id);

	if(!empty( $course_id)  && !empty( $user_id)) {
	$course = get_post( $course_id);
	$user = get_user_by("id", $user_id);
	$end = learndash_get_setting( $course, "course_remove_access_on_subscription_end");
	ld_ipn_debug('End Subscription: '.$end);
	if(!empty( $end)) {
	$meta = ld_update_course_access( $user_id, $course_id, true);
	ld_ipn_debug('Meta after removal of access: '.print_r( $meta, true));
	}

	$post_id = wp_insert_post( array( 'post_title' => "Course {$course->post_title} Subscription Ended for {$user->user_email}", 'post_type' => 'sfwd-transactions', 'post_status' => 'publish', 'post_author' => $user_id ) );
	ld_ipn_debug('Created Transaction. Post Id: '  .$post_id);
	$post = $_REQUEST;
	$post["user_id"]  = $user_id;
	$post["course_id"]  = $course_id;
	foreach( $post as $k => $v )
	update_post_meta( $post_id, $k, $v );
	}
	}
	else
	ld_ipn_debug('Matching transaction not found : [post_type=sfwd-transactions&meta_key=subscr_id&meta_value='.$subscr_id.'] :'.print_r( $transaction, true));

	} */
	ld_ipn_debug( 'IPN Processing Completed Successfully.' );
	$notifyOnValid = $notify_on_valid_ipn != '' ? $notify_on_valid_ipn : '0';

	//if ( $notifyOnValid == '1' ) {
	//	mail( $YOUR_NOTIFICATION_EMAIL_ADDRESS, 'Verified IPN', $listener->getTextReport() );
	//}

} else {

	/*An Invalid IPN *may* be caused by a fraudulent transaction attempt. It's a good idea to have a developer or sys admin
	manually investigate any invalid IPN.*/
	ld_ipn_debug( 'Invalid IPN. Shutting Down Processing.' );
	//mail( $YOUR_NOTIFICATION_EMAIL_ADDRESS, 'Invalid IPN', $listener->getTextReport() );

}

//we're done here
