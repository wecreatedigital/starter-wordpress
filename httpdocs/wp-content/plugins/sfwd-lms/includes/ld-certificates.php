<?php
/**
 * Certificate functions
 * 
 * @since 2.1.0
 * 
 * @package LearnDash\Certificates
 */



/**
 * Get certificate details
 *
 * Return a link to certificate and certificate threshold
 *
 * @since 2.1.0
 * 
 * @param  int  	$post_id
 * @param  int  	$user_id
 * @return array    certificate details
 */
function learndash_certificate_details( $post_id, $cert_user_id = null ) {
	$cert_details = array();

	$cert_user_id = ! empty( $cert_user_id ) ? intval( $cert_user_id ) : get_current_user_id();
	
	if ( ( learndash_is_admin_user() ) || ( learndash_is_group_leader_user() ) )
		$view_user_id = get_current_user_id();
	else
		$view_user_id = $cert_user_id;

	$certificateLink = '';
	$post = get_post( $post_id );
	
	if ( ( $post instanceof WP_Post ) && ( $post->post_type == 'sfwd-quiz' ) ) {

		$meta = get_post_meta( $post_id, '_sfwd-quiz', true );		
		if ( is_array( $meta ) && ! empty( $meta ) ) {

			if ( ( isset( $meta['sfwd-quiz_threshold'] ) ) && ( '' !== $meta['sfwd-quiz_threshold'] ) ) {
				$certificate_threshold = $meta['sfwd-quiz_threshold'];
			} else {
				$certificate_threshold = '0.8';
			}

			if ( (isset( $meta['sfwd-quiz_certificate'] )) && ( ! empty( $meta['sfwd-quiz_certificate'] ) ) ) {
				$certificate_post = intval($meta['sfwd-quiz_certificate']);
				$certificateLink = get_permalink( $certificate_post );

				if ( ! empty( $certificateLink ) ) {

					$cert_query_args = array(
						"quiz"	=>	$post->ID,
					);

					//$course_id = learndash_get_course_id();
					//if ( !empty( $course_id ) ) {
					//	$cert_query_args['course_id'] = $course_id;
					//}

					// We add the user query string key/value if the viewing user is an admin. This 
					// allows the admin to view other user's certificated
					if ( ( $cert_user_id != $view_user_id ) && ( ( learndash_is_admin_user() ) || ( learndash_is_group_leader_user() ) ) ) {
						$cert_query_args['user'] = $cert_user_id;
					} 
					$cert_query_args['cert-nonce'] = wp_create_nonce( $post->ID . $cert_user_id . $view_user_id );

					//error_log('cert_query_args<pre>'. print_r($cert_query_args, true) .'</pre>');

					$certificateLink = add_query_arg( $cert_query_args, $certificateLink );
				}
				$certificateLink = apply_filters('learndash_certificate_details_link', $certificateLink, $certificate_post, $post->ID, $cert_user_id);

				$cert_details = array( 'certificateLink' => $certificateLink, 'certificate_threshold' => $certificate_threshold );
			}
		}
	}
	
	return $cert_details;
}



/**
 * Shortcode to output course certificate link
 *
 * @since 2.1.0
 * 
 * @param  array 	$atts 	shortcode attributes
 * @return string       	output of shortcode
 */
function ld_course_certificate_shortcode( $atts ) {
	global $learndash_shortcode_used;
	$learndash_shortcode_used = true;
	
	$course_id = @$atts['course_id'];

	if ( empty( $course_id ) ) {
		$course_id = learndash_get_course_id();
	}

	$user_id = get_current_user_id();
	$link = learndash_get_course_certificate_link( $course_id, $user_id );

	if ( empty( $link ) ) {
		return '';
	}

	/**
	 * Filter output of shortcode
	 * 
	 * @since 2.1.0
	 *
	 * @param  string  markout of course certificate shortcode
	 */
	return apply_filters( 'ld_course_certificate', "<div id='learndash_course_certificate'><a href='".$link."' class='btn-blue' target='_blank'>". apply_filters('ld_certificate_link_label', esc_html__( 'PRINT YOUR CERTIFICATE', 'learndash' ), $user_id, $course_id ) . '</a></div>', $link, $course_id, $user_id );
}

add_shortcode( 'ld_course_certificate', 'ld_course_certificate_shortcode' );



/**
 * Get course certificate link for user
 *
 * @since 2.1.0
 * 
 * @param  int 		 $course_id
 * @param  int 		 $user_id
 * @return string
 */
function learndash_get_course_certificate_link( $course_id, $cert_user_id = null ) {
	$cert_user_id = ! empty( $cert_user_id ) ? intval( $cert_user_id ) : get_current_user_id();

	//if ( ( empty( $course_id ) ) || ( empty( $cert_user_id ) ) || ( !sfwd_lms_has_access( $course_id, $cert_user_id ) ) ) {
	if ( ( empty( $course_id ) ) || ( empty( $cert_user_id ) ) ) {
		return '';
	}

	$certificate_id = learndash_get_setting( $course_id, 'certificate' );
	if ( empty( $certificate_id ) ) {
		return '';
	}

	if ( ( learndash_get_post_type_slug( 'certificate' ) !== get_post_type( $certificate_id ) ) ) {
		return '';
	}

	if ( ( learndash_get_post_type_slug( 'course' ) !== get_post_type( $course_id ) ) ) {
		return '';
	}


	$course_status = learndash_course_status( $course_id, $cert_user_id );
	if ( $course_status != esc_html__( 'Completed', 'learndash' ) ) {
		return '';
	}

	if ( ( learndash_is_admin_user() ) || ( learndash_is_group_leader_user() ) )
		$view_user_id = get_current_user_id();
	else
		$view_user_id = $cert_user_id;
	
	$cert_query_args = array(
		"course_id"	=>	$course_id,
	);

	// We add the user query string key/value if the viewing user is an admin. This 
	// allows the admin to view other user's certificated
	if ( ( $cert_user_id != $view_user_id ) && ( ( learndash_is_admin_user() ) || ( learndash_is_group_leader_user() ) ) ) {
		$cert_query_args['user'] = $cert_user_id;
	} 
	$cert_query_args['cert-nonce'] = wp_create_nonce( $course_id . $cert_user_id . $view_user_id );
	
	$url = add_query_arg( $cert_query_args, get_permalink( $certificate_id ) );
	return apply_filters('learndash_course_certificate_link', $url, $course_id, $cert_user_id);
}



/**
 * Get certificate link if certificate exists and quizzes are completed
 *
 * @todo  consider for deprecation, not being used in plugin
 *
 * @since 2.1.0
 * 
 * @param  int 		 $quiz_id
 * @param  int 		 $user_id
 * @return string
 */
function learndash_get_certificate_link( $quiz_id, $user_id = null ) {
	if ( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	if ( empty( $user_id ) || empty( $quiz_id ) ) {
		return '';
	}

	$c = learndash_certificate_details( $quiz_id, $user_id );

	if ( empty( $c['certificateLink'] ) ) {
		return '';
	}

	$usermeta = get_user_meta( $user_id, '_sfwd-quizzes', true );
	$usermeta = maybe_unserialize( $usermeta );

	if ( ! is_array( $usermeta ) ) { 
		$usermeta = array();
	}

	foreach ( $usermeta as $quizdata ) {
		if ( ! empty( $quizdata['quiz'] ) && $quizdata['quiz'] == $quiz_id ) {
			if ( $c['certificate_threshold'] <= $quizdata['percentage'] / 100 ) {
				return '<a target="_blank" href="'.$c['certificateLink'].'">'. apply_filters('ld_certificate_link_label', esc_html__( 'PRINT YOUR CERTIFICATE', 'learndash' ), $user_id, $quiz_id ) .'</a>';
			}
		}
	}

	return '';
}



/**
 * Show text tab by default on certificate edit screen
 * User should not be able to use visual editor tab
 *
 * @since 2.1.0
 * 
 * @param  array $return 	An array of editors. Accepts 'tinymce', 'html', 'test'.
 * @return array $return 	html
 */
function learndash_disable_editor_on_certificate( $return ) {
	global $post;

	if ( is_admin() && ! empty( $post->post_type ) && $post->post_type == 'sfwd-certificates' ) {
		return 'html';
	}

	return $return;
}

add_filter( 'wp_default_editor', 'learndash_disable_editor_on_certificate', 1, 1 );



/**
 * Disable being able to click the visual editor on certificates
 * User should not be able to use visual editor tab
 *
 * @since 2.1.0
 */
function learndash_disable_editor_on_certificate_js() {
	global $post;
	if ( is_admin() && ! empty( $post->post_type) && $post->post_type == 'sfwd-certificates' ) {
		?>
			<style type="text/css">
			a#content-tmce, a#content-tmce:hover, #qt_content_fullscreen, #insert-media-button{
				display:none;
			}
			</style>
			<script type="text/javascript">
			jQuery(document).ready(function(){
				jQuery("#content-tmce").attr("onclick", null);
			});
			</script>
		<?php
	}
}

add_filter( 'admin_footer', 'learndash_disable_editor_on_certificate_js', 99 );


function learndash_certificates_add_meta_box( $post ) {
	add_meta_box(
		'learndash_certificate_options',
		esc_html__( 'LearnDash Certificate Options', 'learndash' ),
		'learndash_certificate_options_metabox',
		'sfwd-certificates',
		'advanced',
		'high'
	);
}
add_action( 'add_meta_boxes', 	'learndash_certificates_add_meta_box' );


function learndash_certificate_options_metabox( $certificate ) {

	$learndash_certificate_options_selected = get_post_meta( $certificate->ID, 'learndash_certificate_options', true);

	if ( !is_array( $learndash_certificate_options_selected ) ) {
		if ( !empty( $learndash_certificate_options_selected ) )
			$learndash_certificate_options_selected = array($learndash_certificate_options_selected);
		else
			$learndash_certificate_options_selected = array();
	}
	
	if ( !isset( $learndash_certificate_options_selected['pdf_page_format'] ) )
		$learndash_certificate_options_selected['pdf_page_format'] = 'LETTER';

	if ( !isset( $learndash_certificate_options_selected['pdf_page_orientation'] ) )
		$learndash_certificate_options_selected['pdf_page_orientation'] = 'L';
	
	wp_nonce_field( plugin_basename( __FILE__ ), 'learndash_certificates_nonce' );

	$learndash_certificate_options['pdf_page_format'] = array(
		"LETTER"	=>	esc_html__('Letter / USLetter (default)', 'learndash'),
		"A4"		=>	esc_html__('A4', 'learndash')
	);
	$learndash_certificate_options['pdf_page_format'] = apply_filters('learndash_certificate_pdf_page_formats', $learndash_certificate_options['pdf_page_format']);

	$learndash_certificate_options['pdf_page_orientation'] = array(
		"L"		=>	esc_html__('Landscape (default)', 'learndash'),
		"P"		=>	esc_html__('Portrait', 'learndash')
	);
	$learndash_certificate_options['pdf_page_orientation'] = apply_filters('learndash_certificate_pdf_page_orientations', $learndash_certificate_options['pdf_page_orientation']);	

	if ( ( is_array( $learndash_certificate_options['pdf_page_format'] ) ) && ( !empty( $learndash_certificate_options['pdf_page_format'] ) ) ) {
		?>
		<p><label for="learndash_certificate_options_pdf_page_format"><?php esc_html_e('PDF Page Size', 'learndash') ?></label>
			<select id="learndash_certificate_options_pdf_page_format" name="learndash_certificate_options[pdf_page_format]">
			<?php
				foreach( $learndash_certificate_options['pdf_page_format'] as $key => $label ) {
					?><option <?php selected($key, $learndash_certificate_options_selected['pdf_page_format']) ?> value="<?php echo $key ?>"><?php echo $label ?></option><?php
				}
			?>
			</select>
		</p>
		<?php
	}

	if ( ( is_array( $learndash_certificate_options['pdf_page_orientation'] ) ) && ( !empty( $learndash_certificate_options['pdf_page_orientation'] ) ) ) {
				
		?>
		<p><label for="learndash_certificate_options_pdf_page_orientation"><?php esc_html_e('PDF Page Orientation', 'learndash') ?></label>
			<select id="learndash_certificate_options_pdf_page_orientation" name="learndash_certificate_options[pdf_page_orientation]">
			<?php
				foreach( $learndash_certificate_options['pdf_page_orientation'] as $key => $label ) {
					?><option <?php selected( $key, $learndash_certificate_options_selected['pdf_page_orientation'] ) ?> value="<?php echo $key ?>"><?php echo $label ?></option><?php
				}
			?>
			</select>
		</p>
		<?php
	}
}


function learndash_certificates_save_meta_box( $post_id ) {
	// verify if this is an auto save routine.
	// If it is our form has not been submitted, so we dont want to do anything
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// verify this came from the our screen and with proper authorization,
	// because save_post can be triggered at other times
	if ( ! isset( $_POST['learndash_certificates_nonce'] ) || ! wp_verify_nonce( $_POST['learndash_certificates_nonce'], plugin_basename( __FILE__ ) ) ) {
		return;
	}

	if ( 'sfwd-certificates' != $_POST['post_type'] ) {
		return;
	}

	// Check permissions
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$learndash_certificate_options = array();

	if ( ( isset( $_POST['learndash_certificate_options']['pdf_page_format'] ) ) && (!empty( $_POST['learndash_certificate_options']['pdf_page_format'] ) ) ) {
		$learndash_certificate_options['pdf_page_format'] = esc_attr( $_POST['learndash_certificate_options']['pdf_page_format'] );
	} else {
		$learndash_certificate_options['pdf_page_format'] = 'LETTER';
	}

	if ( ( isset( $_POST['learndash_certificate_options']['pdf_page_orientation'] ) ) && (!empty( $_POST['learndash_certificate_options']['pdf_page_orientation'] ) ) ) {
		$learndash_certificate_options['pdf_page_orientation'] = esc_attr( $_POST['learndash_certificate_options']['pdf_page_orientation'] );
	} else {
		$learndash_certificate_options['pdf_page_orientation'] = PDF_PAGE_ORIENTATION;
	}
	
	update_post_meta( $post_id, 'learndash_certificate_options', $learndash_certificate_options );
}
add_action( 'save_post', 'learndash_certificates_save_meta_box' );


/**
 * Certificate published/updated notice to replace the default notice that contains a link to a non-existent resource.
 * 
 * @since 3.0
 * @return array Array of published/updated notice messages.
 */
function learndash_certificates_post_updated_messages( $messages ) {

	$post             = get_post();
	$post_type        = get_post_type( $post );
	$post_type_object = get_post_type_object( $post_type );

	$published_message = wp_kses_post( __( 'Certificate published. <br /><br />
	To view the certificate, you must assign it to a quiz or course. <br />
	Once you complete the assigned milestone, you can generate the certificate. <br /><br />
	Click here to read more about this topic: <a href="https://www.learndash.com/support/docs/core/certificates/create-certificate/#previewing_certificates" target="_blank">Previewing Certificates</a>.', 'learndash' ) );

	$updated_message = wp_kses_post( __( 'Certificate updated. <br /><br />
	To view the certificate, you must assign it to a quiz or course. <br />
	Once you complete the assigned milestone, you can generate the certificate.<br /><br />	
	Click here to read more about this topic: <a href="https://www.learndash.com/support/docs/core/certificates/create-certificate/#previewing_certificates" target="_blank">Previewing Certificates</a>.', 'learndash' ) );

	$messages[ 'sfwd-certificates' ] = array(
		0  => '', // Unused. Messages start at index 1.
		1  => $updated_message,
		2  => esc_html__( 'Custom field updated.', 'learndash' ),
		3  => esc_html__( 'Custom field deleted.', 'learndash' ),
		4  => $updated_message,
		/* translators: %s: date and time of the revision */
		5  => isset( $_GET['revision'] ) ? sprintf( esc_html__( 'Certificate restored to revision from %s', 'learndash' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6  => $published_message,
		7  => esc_html__( 'Certificate saved.', 'learndash' ),
		8  => esc_html__( 'Certificate submitted.', 'learndash' ),
		9  => sprintf(
			esc_html__( 'Certificate scheduled for: <strong>%1$s</strong>.', 'learndash' ),
			// translators: Publish box date format, see http://php.net/date
			date_i18n( __( 'M j, Y @ H:i', 'default' ), strtotime( $post->post_date ) )
		),
		10 => esc_html__( 'Certificate draft updated.', 'learndash' )
	);
	
	return $messages;
}
add_filter( 'post_updated_messages', 'learndash_certificates_post_updated_messages' );
