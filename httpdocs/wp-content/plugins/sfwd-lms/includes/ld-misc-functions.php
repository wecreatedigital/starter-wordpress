<?php
/**
 * Misc functions
 * 
 * @since 2.1.0
 * 
 * @package LearnDash\Misc
 */



/**
 * Add post thumbnail theme support for customn post types
 *
 * @since 2.1.0
 */
function learndash_add_theme_support() {
	if ( ! current_theme_supports( 'post-thumbnails' ) ) {
		add_theme_support( 'post-thumbnails', array( 'sfwd-certificates', 'sfwd-courses', 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz', 'sfwd-assignment', 'sfwd-essays' ) );
	}
}

add_action( 'after_setup_theme', 'learndash_add_theme_support' );

/**
 * Get LearnDash setting for a post
 * 
 * @since 2.1.0
 * 
 * @param  id|obj $post    
 * @param  string $setting 
 * @return string value for requested setting
 */
function learndash_get_setting( $post, $setting = null ) {

	if ( is_numeric( $post ) ) {
		$post = get_post( $post );
	} else {
		if ( empty( $post ) || ! is_object( $post ) || empty( $post->ID ) ) {
			return null;
		}
	}
	
	if ($post instanceof WP_Post) {

		if ( $setting == 'lesson' ) {
			return learndash_get_lesson_id( $post->ID ); 
		}

		if ( $setting == 'course' ) {
			//return get_post_meta( $post->ID, 'course_id', true ); 
			return learndash_get_course_id( $post->ID ); 
		}

		$meta = get_post_meta( $post->ID, '_' . $post->post_type, true );
		if ( ( !empty( $meta ) ) && ( is_array( $meta ) ) ) {
			if ( empty( $setting ) ) {
				$settings = array();
				foreach ( $meta as $k => $v ) {
					$settings[ str_replace( $post->post_type.'_', '', $k ) ] = $v;
				}
				return $settings;
			} else {
				if ( isset( $meta[ $post->post_type.'_'.$setting ] ) ) {
					return $meta[ $post->post_type.'_'.$setting ]; 
				} else {
					return ''; 
				}
			}
		} else {
			return ''; 
		}
	}
}



/**
 * Get options for a particular post type and setting
 * 
 * @since 2.1.0
 * 
 * @param  string $post_type
 * @param  string $setting
 * @return array|string 	options requested
 */
function learndash_get_option( $post_type, $setting = '' ) {
	$return = array();
	
	$options = get_option( 'sfwd_cpt_options' );

	// In LD v2.4 we moved all the settings to the new Settings API. Because of this we need to merge the value(s)
	// into the legacy values but keep in mind other add-ons might be extending the $post_args sections
	if ( $post_type == 'sfwd-lessons' ) {
		if ( $options === false ) $options = array();
		if ( !isset( $options['modules'] ) ) $options['modules'] = array();
		if ( !isset( $options['modules'][ $post_type.'_options'] ) ) $options['modules'][ $post_type.'_options'] = array();
		
		$settings_fields = LearnDash_Settings_Section::get_section_settings_all('LearnDash_Settings_Section_Lessons_Display_Order');
		if ( ( !empty( $settings_fields ) ) && ( is_array( $settings_fields ) ) ) {
			foreach( $settings_fields as $key => $val ) {
				$options['modules'][ $post_type . '_options'][$post_type .'_'. $key ] = $val;
			}
		}
	}

	if ( ( empty( $setting ) )  && ( !empty( $options['modules'][ $post_type.'_options'] ) ) ) {
		foreach ( $options['modules'][ $post_type.'_options'] as $key => $val ) {
			$return[str_replace( $post_type.'_', '', $key )] = $val;
		}

		return $return;
	}

	if ( ! empty( $options['modules'][ $post_type.'_options'][ $post_type.'_'.$setting] ) ) {
		return $options['modules'][ $post_type.'_options'][ $post_type.'_'.$setting];
	} else {
		return '';
	}	
}



/**
 * Update LearnDash setting for a post
 *
 * @since 2.1.0
 * 
 * @param  id|obj $post    
 * @param  string $setting 
 * @param  string $value
 * @return bool   if update was successful         
 */
function learndash_update_setting( $post, $setting, $value ) {
	$return = false;

	if ( empty( $setting) ) {
		return $return;
	}

	// Were we sent a post ID?
	if ( is_numeric( $post ) ) {
		$post = get_post( $post );
	}

	// Ensure we have a post object or type WP_Post!
	if ( is_a( $post, 'WP_Post' ) ) {
		$meta = get_post_meta( $post->ID, '_' . $post->post_type, true );
		if ( ! is_array( $meta ) ) {
			$meta = array( $meta );
		}
		$meta[ $post->post_type . '_' . $setting ] = $value;

		if ( 'course' === $setting ) {
			$value = intval( $value );
			$meta[ $post->post_type.'_'.$setting] = $value;
			if ( !empty( $value ) ) 
				update_post_meta( $post->ID, 'course_id', $value );
			else
				delete_post_meta( $post->ID, 'course_id' );

		} elseif ( 'course_access_list' === $setting ) {
			$value = learndash_convert_course_access_list( $value );
			update_post_meta( $post->ID, 'course_access_list', $value );
			$meta[ $post->post_type . '_' . $setting ] = $value;

		} elseif ( 'course_points' === $setting ) {
			$course_points = learndash_format_course_points( $value );
			if ( ! empty( $course_points ) ) {
				update_post_meta( $post->ID, 'course_points', $course_points );
			} else {
				delete_post_meta( $post->ID, 'course_points' );
			}
		} elseif ( 'lesson' === $setting ) {
			$value = intval( $value );
			$meta[ $post->post_type . '_' . $setting ] = $value;
			if ( ! empty( $value ) ) {
				update_post_meta( $post->ID, 'lesson_id', $value );
			} else {
				delete_post_meta( $post->ID, 'lesson_id' );
			}
		} elseif ( 'quiz' === $setting ) {
			update_post_meta( $post->ID, 'quiz_id', absint( $value ) );
		} elseif ( 'quiz_pro' === $setting ) {
			$value = absint( $value );

			// Moved from includes/class-ld-semper-fi-module.php line1052
			$quiz_pro_id_new = $value;
			$quiz_pro_id_org = absint( get_post_meta( $post->ID, 'quiz_pro_id', true ) );

			if ( ( ! empty( $quiz_pro_id_new ) ) && ( $quiz_pro_id_org !== $quiz_pro_id_new ) ) {
				/**
				 * If this quiz was the primary for all shared settings. We need to
				 * delete the primary marker then move the primary marker to another
				 * quiz using the same shared settngs.
				 */
				$quiz_id_primary_org = absint( learndash_get_quiz_primary_shared( $quiz_pro_id_org, false ) );
				if ( $quiz_id_primary_org === $post->ID ) {
					delete_post_meta( $post->ID, 'quiz_pro_primary_' . $quiz_pro_id_org );
					$quiz_post_ids = learndash_get_quiz_post_ids( $quiz_pro_id_org );
					if ( ! empty( $quiz_post_ids ) ) {
						foreach ( $quiz_post_ids as $quiz_post_id ) {
							if ( $quiz_post_id !== $post->ID ) {
								update_post_meta( $quiz_post_id, 'quiz_pro_primary_' . $quiz_pro_id_org, $quiz_pro_id_org );

								/**
								 * After we move the primary marker we also need to move the questions.
								 */
								$ld_quiz_questions_object = LDLMS_Factory_Post::quiz_questions( intval( $post->ID ) );
								if ( $ld_quiz_questions_object ) {
									$questions = $ld_quiz_questions_object->get_questions( 'post_ids' );

									$questions = get_post_meta( $post->ID, 'ld_quiz_questions', true );
									update_post_meta( $quiz_post_id, 'ld_quiz_questions', $questions );
								}
								break;
							}
						}
					}
				}

				$quiz_id_primary_new = absint( learndash_get_quiz_primary_shared( $quiz_pro_id_new, false ) );
				if ( empty( $quiz_id_primary_new ) ) {
					update_post_meta( $post->ID, 'quiz_pro_primary_' . $quiz_pro_id_new, $quiz_pro_id_new );
					// trigger to cause reloading of the questions.
					delete_post_meta( $post->ID, 'ld_quiz_questions' );
				}

				global $wpdb;
				$sql_str = "DELETE FROM " . $wpdb->postmeta . " WHERE post_id=" . $post->ID . " AND meta_key like 'quiz_pro_id_%'";
				$quiz_query_results = $wpdb->query( $sql_str );

				update_post_meta( $post->ID, 'quiz_pro_id', $quiz_pro_id_new );
				update_post_meta( $post->ID, 'quiz_pro_id_' . $quiz_pro_id_new, $quiz_pro_id_new );
			}
		} elseif ( 'viewProfileStatistics' === $setting ) {
			update_post_meta( $post->ID, '_viewProfileStatistics', $value );
		} elseif ( 'timeLimitCookie' === $setting ) {
			update_post_meta( $post->ID, '_timeLimitCookie', absint( $value ) );
		}

		$return = update_post_meta( $post->ID, '_' . $post->post_type, $meta );
	}

	return $return;
}



if ( ! function_exists( 'sfwd_lms_get_post_options' ) ) {

	/**
	 * Set up wp query args for the post type that are saved in options
	 * 
	 * @param  string $post_type
	 * @return array  wp query arguments
	 */
	function sfwd_lms_get_post_options( $post_type ) {
		global $sfwd_lms;
	
		// Set our default options

		$ret = array( 
			'order' 			=> 	'ASC', 
			'orderby' 			=> 	'date', 
			'posts_per_page' 	=> 	get_option('posts_per_page')
		);

		if ( ( !empty( $post_type ) ) && ( isset( $sfwd_lms->post_types[ $post_type ] ) ) ) {
			$cpt = $sfwd_lms->post_types[ $post_type ];
			if ( ( $cpt ) && ( $cpt instanceof SFWD_CPT_Instance ) ) {
				$prefix = $cpt->get_prefix();
				$options = $cpt->get_current_options();

				if ((!empty($prefix)) && (!empty($options))) {
					foreach ( $ret as $k => $v ) {
						if ( ! empty( $options["{$prefix}{$k}"] ) ) {
							$ret[ $k ] = $options["{$prefix}{$k}"];
						}
					}
				}
				
				if ( $post_type == 'sfwd-lessons' ) {
					$settings_fields = LearnDash_Settings_Section::get_section_settings_all('LearnDash_Settings_Section_Lessons_Display_Order');
					if ( ( !empty( $settings_fields ) ) && ( is_array( $settings_fields ) ) ) {
						$ret = wp_parse_args( $settings_fields, $ret );
					}
				}
				
			}
		}

		return $ret;
	}
}



/**
 * Output LearnDash Payment buttons
 * 
 * @since 2.1.0
 *
 * @uses learndash_get_function()
 * @uses sfwd_lms_has_access()
 * 
 * @param  id|obj 	$course course id or WP_Post course object
 * @return string   output of payment buttons
 */
function learndash_payment_buttons( $course ) {

	if ( is_numeric( $course ) ) {
		$course_id = $course;
		$course = get_post( $course_id );
	} else if ( ! empty( $course->ID ) ) {
		$course_id = $course->ID;
	} else {
		return '';
	}

	$user_id = get_current_user_id();

	if ( ( ! $course ) || ( ! is_a( $course, 'WP_Post' ) ) || ( $course->post_type != 'sfwd-courses' ) ) {
		return '';
	}

	$meta = get_post_meta( $course_id, '_sfwd-courses', true );
	$course_price_type = @$meta['sfwd-courses_course_price_type'];
	$course_price = @$meta['sfwd-courses_course_price'];
	$course_no_of_cycles = @$meta['sfwd-courses_course_no_of_cycles'];
	$course_price = @$meta['sfwd-courses_course_price'];
	$custom_button_url = @$meta['sfwd-courses_custom_button_url'];
	$custom_button_label = @$meta['sfwd-courses_custom_button_label'];

	// format the Course price to be proper XXX.YY no leading dollar signs or other values. 
	if (( $course_price_type == 'paynow' ) || ( $course_price_type == 'subscribe' )) {
		if ( $course_price != '' ) {
			$course_price = preg_replace( "/[^0-9.]/", '', $course_price );
			$course_price = number_format( floatval( $course_price ), 2, '.', '' );
		}
	}

	//$courses_options = learndash_get_option( 'sfwd-courses' );

	//if ( ! empty( $courses_options ) ) {
	//	extract( $courses_options );
	//}

	$paypal_settings = LearnDash_Settings_Section::get_section_settings_all( 'LearnDash_Settings_Section_PayPal' );
	if ( ! empty( $paypal_settings ) ) {
		$paypal_settings['paypal_sandbox'] = $paypal_settings['paypal_sandbox'] == 'yes' ? 1 : 0;
	}

	if ( sfwd_lms_has_access( $course->ID, $user_id ) ) {
		return '';
	}

	if ( empty( $custom_button_label ) ) {
		$button_text = LearnDash_Custom_Label::get_label( 'button_take_this_course' );
	} else {
		$button_text = esc_attr( $custom_button_label );
	}

	if ( ! empty( $course_price_type ) && $course_price_type == 'closed' ) {

		if ( empty( $custom_button_url ) ) {
			$custom_button = '';
		} else {
			$custom_button_url = trim( $custom_button_url );
			/**
			 * If the value does NOT start with [http://, https://, /] we prepend the home URL.
			 */
			if ( ( stripos( $custom_button_url, 'http://', 0 ) !== 0 ) && ( stripos( $custom_button_url, 'https://', 0 ) !== 0 ) && ( strpos( $custom_button_url, '/', 0 ) !== 0 ) ) {
				$custom_button_url = get_home_url( null, $custom_button_url );
			}
			$custom_button = '<a class="btn-join" href="' . esc_url( $custom_button_url ) . '" id="btn-join">' . $button_text . '</a>';
		}

		$payment_params = array(
			'custom_button_url' => $custom_button_url,
			'post' => $course
		);

		/**
		 * Filter a closed course payment button
		 * 
		 * @since 2.1.0
		 * 
		 * @param  string  $custom_button       
		 */
		return 	apply_filters( 'learndash_payment_closed_button', $custom_button, $payment_params );

	} else if ( ! empty( $course_price ) ) {
		//include_once( 'vendor/paypal/enhanced-paypal-shortcodes.php' );
		include_once( LEARNDASH_LMS_LIBRARY_DIR . '/paypal/enhanced-paypal-shortcodes.php' );

		$paypal_button = '';

		if ( ! empty( $paypal_settings['paypal_email'] ) ) {

			$post_title = str_replace(array('[', ']'), array('', ''), $course->post_title);
			
			if ( empty( $course_price_type ) || $course_price_type == 'paynow' ) {
				$shortcode_content = do_shortcode( '[paypal type="paynow" amount="'. $course_price .'" sandbox="'. $paypal_settings['paypal_sandbox'] .'" email="'. $paypal_settings['paypal_email'] .'" itemno="'. $course->ID .'" name="'. $post_title .'" noshipping="1" nonote="1" qty="1" currencycode="'. $paypal_settings['paypal_currency'] .'" rm="2" notifyurl="'. $paypal_settings['paypal_notifyurl'] .'" returnurl="'. $paypal_settings['paypal_returnurl'] .'" cancelurl="'. $paypal_settings['paypal_cancelurl'] .'" imagewidth="100px" pagestyle="paypal" lc="'. $paypal_settings['paypal_country'] .'" cbt="'. esc_html__( 'Complete Your Purchase', 'learndash' ) . '" custom="'. $user_id. '"]' );
				if (!empty( $shortcode_content ) ) {
					$paypal_button = wptexturize( '<div class="learndash_checkout_button learndash_paypal_button">'. $shortcode_content .'</div>');
				}
				
			} else if ( $course_price_type == 'subscribe' ) {
				$course_price_billing_p3 = get_post_meta( $course_id, 'course_price_billing_p3',  true );
				$course_price_billing_t3 = get_post_meta( $course_id, 'course_price_billing_t3',  true );
				$srt = intval( $course_no_of_cycles );
				
				$shortcode_content = do_shortcode( '[paypal type="subscribe" a3="'. $course_price .'" p3="'. $course_price_billing_p3 .'" t3="'. $course_price_billing_t3 .'" sandbox="'. $paypal_settings['paypal_sandbox'] .'" email="'. $paypal_settings['paypal_email'] .'" itemno="'. $course->ID .'" name="'. $post_title .'" noshipping="1" nonote="1" qty="1" currencycode="'. $paypal_settings['paypal_currency'] .'" rm="2" notifyurl="'. $paypal_settings['paypal_notifyurl'] .'" cancelurl="'. $paypal_settings['paypal_cancelurl'] .'" returnurl="'. $paypal_settings['paypal_returnurl'] .'" imagewidth="100px" pagestyle="paypal" lc="'. $paypal_settings['paypal_country'] .'" cbt="'. esc_html__( 'Complete Your Purchase', 'learndash' ) .'" custom="'. $user_id .'" srt="'. $srt .'"]' );
				
				if (!empty( $shortcode_content ) ) {
					$paypal_button = wptexturize( '<div class="learndash_checkout_button learndash_paypal_button">'. $shortcode_content .'</div>' );
				}
			}
		}

		$payment_params = array(
			'price' => $course_price,
			'post' => $course,
		);

		/**
		 * Filter PayPal payment button
		 * 
		 * @since 2.1.0
		 * 
		 * @param  string  $paypal_button
		 */
		$payment_buttons = apply_filters( 'learndash_payment_button', $paypal_button, $payment_params );
		
		if ( ! empty( $payment_buttons ) ) {
		
			if ( ( !empty( $paypal_button ) ) && ( $payment_buttons != $paypal_button ) ) {

				$button = 	'';
				$button .= 	'<div id="learndash_checkout_buttons_course_'. $course->ID .'" class="learndash_checkout_buttons">';
				$button .= 		'<input id="btn-join-'. $course->ID .'" class="btn-join btn-join-'. $course->ID .' button learndash_checkout_button" data-jq-dropdown="#jq-dropdown-'. $course->ID .'" type="button" value="'. $button_text .'" />';
				$button .= 	'</div>';
			
				global $dropdown_button;
				$dropdown_button .= 	'<div id="jq-dropdown-'. $course->ID .'" class="jq-dropdown jq-dropdown-tip checkout-dropdown-button">';
				$dropdown_button .= 		'<ul class="jq-dropdown-menu">';
				$dropdown_button .= 		'<li>';
				$dropdown_button .= 			str_replace($button_text, esc_html__('Use Paypal', 'learndash'), $payment_buttons);
				$dropdown_button .= 		'</li>';
				$dropdown_button .= 		'</ul>';
				$dropdown_button .= 	'</div>';
			
				return apply_filters( 'learndash_dropdown_payment_button', $button );
				
			} else {
				return	'<div id="learndash_checkout_buttons_course_'. $course->ID .'" class="learndash_checkout_buttons">'. $payment_buttons .'</div>';
			}
		}
	} else {
		$join_button = '<div class="learndash_join_button"><form method="post">
							<input type="hidden" value="'. $course->ID .'" name="course_id" />
							<input type="hidden" name="course_join" value="'. wp_create_nonce( 'course_join_'. get_current_user_id() .'_'. $course->ID ) .'" />
							<input type="submit" value="'.$button_text.'" class="btn-join" id="btn-join" />
						</form></div>';

		$payment_params = array( 
			'price' => '0', 
			'post' => $course, 
			'course_price_type' => $course_price_type 
		);

		/**
		 * Filter Join payment button
		 * 
		 * @since 2.1.0
		 * 
		 * @param  string  $join_button
		 */
		$payment_buttons = apply_filters( 'learndash_payment_button', $join_button, $payment_params );
		return $payment_buttons;
	}

}

// Yes, global var here. This var is set within the payment button processing. The var will contain HTML for a fancy dropdown
$dropdown_button = '';
add_action("wp_footer", 'ld_footer_payment_buttons');
function ld_footer_payment_buttons() {
	global $dropdown_button;
	
	if (!empty($dropdown_button)) {
		echo $dropdown_button;
	}
}

add_action('get_footer', 'learndash_get_footer');
function learndash_get_footer() {
	if (is_admin()) return;

	global $dropdown_button;
	if (empty($dropdown_button)) {
		wp_dequeue_script('jquery-dropdown-js');
	}
}



/**
 * Payment buttons shortcode
 *
 * @since 2.1.0
 * 
 * @param  array $attr shortcode attributes
 * @return string      output of payment buttons
 */
function learndash_payment_buttons_shortcode( $attr ) {
	global $learndash_shortcode_used;
	$learndash_shortcode_used = true;

	$shortcode_atts = shortcode_atts( array( 'course_id' => 0 ), $attr );
	if ( empty( $shortcode_atts['course_id'] ) ) {
		$course_id = learndash_get_course_id();
		if ( empty( $course_id ) ) {
			return '';
		}
		$shortcode_atts['course_id'] = intval( $course_id );
	}

	return learndash_payment_buttons( $shortcode_atts['course_id'] );
}

add_shortcode( 'learndash_payment_buttons', 'learndash_payment_buttons_shortcode' );



/**
 * Check if lesson, topic, or quiz is a sample
 *
 * @since 2.1.0
 * 
 * @param  id|obj $post id of post or WP_Post object
 * @return bool
 */
function learndash_is_sample( $post ) {
	if ( empty( $post) ) {
		return false;
	}

	if ( is_numeric( $post ) ) {
		$post = get_post( $post );
	}

	if ( empty( $post->ID ) ) {
		return false;
	}

	if ( $post->post_type == learndash_get_post_type_slug( 'lesson' ) ) {
		$is_sample = false;
		if ( learndash_get_setting( $post->ID, 'sample_lesson' ) ) {
			$is_sample = true;
		}
		return apply_filters( 'learndash_lesson_is_sample', $is_sample, $post );
	}

	if ( $post->post_type == learndash_get_post_type_slug( 'topic' ) ) {
		if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
			$course_id = learndash_get_course_id( $post );
			$lesson_id = learndash_course_get_single_parent_step( $course_id, $post->ID );
		} else {
			$lesson_id = learndash_get_setting( $post->ID, 'lesson' );
		}
		if ( ( isset( $lesson_id ) ) && ( ! empty( $lesson_id ) ) ) {
			return learndash_is_sample( $lesson_id );
		}
	}

	if ( $post->post_type == learndash_get_post_type_slug( 'quiz' ) ) {
		if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
			$course_id = learndash_get_course_id( $post );
			$lesson_id = learndash_course_get_single_parent_step( $course_id, $post->ID );
		} else {
			$lesson_id = learndash_get_setting( $post->ID, 'lesson' );
		}
		if ( ( isset( $lesson_id ) ) && ( ! empty( $lesson_id ) ) ) {
			return learndash_is_sample( $lesson_id );
		}
	}

	return false;
}



/**
 * Helper function for php output buffering
 * 
 * @todo not sure what this is preventing with a while looping
 *       counting to 10 and checking current buffer level
 *
 * @since 2.1.0
 * 
 * @param  integer $level
 * @return string
 */
function learndash_ob_get_clean( $level = 0 ) {
	$content = '';
	$i = 1;

	while ( $i <= 10 && ob_get_level() > $level ) {
		$i++;
		$content = ob_get_clean();
	}

	return $content;
}



/**
 * Redirect to home if user lands on archive pages for lesson or quiz post types
 * 
 * @since 2.1.0
 * 
 * @param  object $wp WP object
 */
function ld_remove_lessons_and_quizzes_page( $wp ) {

	if ( ( is_archive() ) && ( ! is_admin() ) ) {
		$post_type = get_post_type();
		if ( ( is_post_type_archive( $post_type ) ) && ( in_array( $post_type, learndash_get_post_types() ) ) ) {
			$has_archive = learndash_post_type_has_archive( $post_type );
			if ( true !== $has_archive ) {
				wp_redirect( home_url() );
				exit;
			}
		}
	}
}

add_action( 'wp', 'ld_remove_lessons_and_quizzes_page' );

/**
 * Utility function to check if a LEarnDash post type supports Archive.
 *
 * @since 3.0
 * @param string $post_type LearnDash Post Type.
 * @return boolean true/false.
 */
function learndash_post_type_has_archive( $post_type = '' ) {
	$has_archive = false;

	switch( $post_type ) {
		case learndash_get_post_type_slug( 'course' ):
			if ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_CPT', 'has_archive' ) ) {
				$has_archive = true;
			}
			break;

		case learndash_get_post_type_slug( 'lesson' ):
			if ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Lessons_CPT', 'has_archive' ) ) {
				$has_archive = true;
			}
			break;

		case learndash_get_post_type_slug( 'topic' ):
			if ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Topics_CPT', 'has_archive' ) ) {
				$has_archive = true;
			}
			break;

		case learndash_get_post_type_slug( 'quiz' ):
			if ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Quizzes_CPT', 'has_archive' ) ) {
				$has_archive = true;
			}
			break;

		default:
			break;
	}

	/**
	 * Allow filtering override.
	 *
	 * @since 3.0
	 */
	return apply_filters( 'learndash_post_type_has_archive', $has_archive, $post_type );
}


/**
 * Removes comments
 * Filter callback for 'comments_array' (wp core hook)
 *
 * @since 2.1.0
 * 
 * @param  array $comments Array of comments for post_id.
 * @param  array $post_id Post ID showing.
 * @return array empty array
 */
function learndash_remove_comments( $comments = array(), $post_id = 0 ) {
	if ( ! empty( $post_id ) ) {
		$post_type = get_post_type( $post_id );
		if ( ( ! empty( $post_type ) ) && ( in_array( $post_type, learndash_get_post_types( 'course' ) ) ) ) {
			$post_type_object = get_post_type_object( $post_type );
			if ( ( $post_type_object ) && ( is_a( $post_type_object, 'WP_Post_Type' ) ) ) {
				if ( true !== learndash_post_type_supports_comments( $post_type ) ) {
					$comments = array();
				} else {
					$_post = get_post( $post_id );
					if ( ( $_post ) && ( is_a( $_post, 'WP_Post' ) ) && ( 'open' === $_post->comment_status ) ) {
						if ( ( in_array( $_post->post_type, learndash_get_post_types( 'course_steps' ) ) ) && ( 'ld30' === LearnDash_Theme_Register::get_active_theme_key() ) && ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Theme_LD30', 'focus_mode_enabled' ) ) ) {
							$focus_mode_comments = apply_filters( 'learndash_focus_mode_comments', 'closed', $_post );
							if ( 'closed' === $focus_mode_comments ) {
								$comments = array();
							}
						}
					} else {
						$comments = array();
					}
				}
			}
		}
	}

	return $comments;
}

/**
 * Ensure comments are open for assignments
 *
 * @since 2.1.0
 *
 * @param bool          $open    Whether the current post is open for comments.
 * @param int|obj       $post_id The post ID or WP_Post object.
 * @return int|obj      $post_id The post ID or WP_Post object.
 */
function learndash_comments_open( $open, $post_id = 0 ) {
	if ( ! empty( $post_id ) ) {
		$post_type = get_post_type( $post_id );
		if ( ( ! empty( $post_type ) ) && ( in_array( $post_type, learndash_get_post_types( 'course' ) ) ) ) {
			$post_type_object = get_post_type_object( $post_type );
			if ( ( $post_type_object ) && ( is_a( $post_type_object, 'WP_Post_Type' ) ) ) {
				if ( true === learndash_post_type_supports_comments( $post_type ) ) {
					$open = true;

					$_post = get_post( $post_id );
					if ( ( $_post ) && ( is_a( $_post, 'WP_Post' ) ) && ( 'open' === $_post->comment_status ) ) {
						if ( ( in_array( $_post->post_type, learndash_get_post_types( 'course_steps' ) ) ) && ( 'ld30' === LearnDash_Theme_Register::get_active_theme_key() ) && ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Theme_LD30', 'focus_mode_enabled' ) ) ) {
							if ( $open === true ) {
								$focus_mode_comments = 'open';
							} else {
								$focus_mode_comments = 'closed';
							}
							$focus_mode_comments = apply_filters( 'learndash_focus_mode_comments', $focus_mode_comments, $_post );
							if ( 'closed' === $focus_mode_comments ) {
								$open = false;
							}
						} 
					} else {
						$open = false;
					}
				} else {
					$open = false;
				}
			}
		}
	}

	return $open;
}
add_action( 'wp', function() {
	add_filter( 'comments_array', 'learndash_remove_comments', 1, 2 );
	add_filter( 'comments_open', 'learndash_comments_open', 10, 2 );
});


if ( ! function_exists( 'ld_debug' ) ) {

	/**
	 * Log debug messages to file
	 * 
	 * @param  int|str|arr|obj|bool 	$msg 	data to log
	 */
	function ld_debug( $msg ) {
	}
}



/**
 * Convert seconds to time
 *
 * @since 2.1.0
 * 
 * @param  int 		$inputSeconds
 * @return string   time output
 */
function learndash_seconds_to_time( $inputSeconds ) {
	$secondsInAMinute = 60;
	$secondsInAnHour  = 60 * $secondsInAMinute;
	$secondsInADay    = 24 * $secondsInAnHour;

	$return = '';
	// extract days
	$days = floor( $inputSeconds / $secondsInADay );
	$return .= empty( $days ) ? '' : $days.'day';

	// extract hours
	$hourSeconds = $inputSeconds % $secondsInADay;
	$hours = floor( $hourSeconds / $secondsInAnHour );
	$return .= ( empty( $hours ) && empty( $days ) )? '':' '.$hours.'hr';

	// extract minutes
	$minuteSeconds = $hourSeconds % $secondsInAnHour;
	$minutes = floor( $minuteSeconds / $secondsInAMinute );
	$return .= ( empty( $hours ) && empty( $days ) && empty( $minutes ) ) ? '' : ' '.$minutes.'min';

	// extract the remaining seconds
	$remainingSeconds = $minuteSeconds % $secondsInAMinute;
	$seconds = ceil( $remainingSeconds );
	$return .= ' '.$seconds.'sec';

	return trim( $return );
}

/**
 * Convert a timestamp to locally timezone adjusted output display
 *
 * @since 2.2.0
 *
 * @param int		$timestamp timestamp to display
 * @param string	$display_format optional display format
 * @return string	offset adjusted displayed date/time
 */
function learndash_adjust_date_time_display($timestamp = 0, $display_format = '' ) {
	$date_time_display = '';

	if ($timestamp != 0) {
		if ( empty( $display_format ) ) {
			$display_format = apply_filters('learndash_date_time_formats', get_option('date_format') .' '. get_option('time_format'));
		}

		// First we convert the timestamp to local Y-m-d H:i:s format
		$date_time_display = get_date_from_gmt( date('Y-m-d H:i:s', $timestamp), 'Y-m-d H:i:s' );
			
		// Then we take that value and reconvert it to a timestamp and call date_i18n to translate the month, date name etc. 	
		$date_time_display = date_i18n( $display_format, strtotime( $date_time_display ) );
	}
	return $date_time_display;	
} 

function learndash_get_timestamp_from_date_string( $date_string = '', $adjust_to_gmt = true ) {
	$value_timestamp = 0;
	
	if ( !empty( $date_string ) ) {
		$value_timestamp = strtotime( $date_string );
		if ( ( !empty( $value_timestamp ) ) && ( $adjust_to_gmt ) ) {
			$value_ymd = get_gmt_from_date( date( 'Y-m-d H:i:s', $value_timestamp ), 'Y-m-d H:i:s' );
			if ( !empty( $value_ymd ) ) {
				$value_timestamp = strtotime($value_ymd);
			} else {
				$value_timestamp = 0;
			}
		}
	}
	
	return $value_timestamp;
}

/**
 * Check if server is on Microsoft IIS
 *
 * @since 2.1.0
 * 
 * @return bool
 */
function learndash_on_iis() {
	$sSoftware = strtolower( $_SERVER['SERVER_SOFTWARE'] );
	if ( strpos( $sSoftware, 'microsoft-iis' ) !== false ) {
		return true;
	} else {
		return false;
	}
}



/**
 * Sql "Default NULL check" in version 5(strict mode)
 * Function to disable null checks
 * Refer to bug http://core.trac.wordpress.org/ticket/2115
 *
 * @since 2.1.0
 */
function mysql_5_hack() {
	if ( learndash_on_iis() ) {
		global $wpdb;
		$sqlVersion = $wpdb->get_var( 'select @@version' );

		if ( $sqlVersion{0} == 5 ) { 
			$wpdb->query( 'set sql_mode="";' ); //set "Strict" mode off
		}		
	}
}

add_action( 'init', 'mysql_5_hack' );



/**
 * Helper function to print_r() in preformatted text 
 * 
 * @since 2.1.0
 * 
 * @param  string $msg
 */
function ldp( $msg ) {
	echo '<pre>';
	print_r( $msg );
	echo '</pre>';
}

/**
 * Utility function to traverse multidimensional array and apply user function 
 * 
 * @since 2.1.2
 * 
 * @param function $func callable user defined or system function. This 
 *			should be 'esc_attr', or some similar function. 
 * @param array $arr This is the array to traverse and cleanup. 
 *
 * @return array $arr cleaned array
 */
function array_map_r( $func, $arr) {
    foreach( $arr as $key => $value ) {
		if (is_array( $value ) ) {
			$arr[ $key ] = array_map_r( $func, $value );
		} else if (is_array($func)) {
			$arr[ $key ] = call_user_func_array($func, $value);
		} else {
			$arr[ $key ] = call_user_func( $func, $value );
		}
    }

    return $arr;
}

function learndash_format_course_points( $points, $decimals = 1 ) {

	$points = preg_replace("/[^0-9.]/", '', $points );
	$points = round( floatval( $points ), apply_filters( 'learndash_course_points_format_round', $decimals ) );

	return floatval( $points );
}

/**
 * Utility function to accept a file path and swap it out for a URL
 * This function is used in combination with get_template() to take
 * a local file system path and filename and replace the beginning part 
 * matching ABSPATH with the home URL. 
 *
 * @since 2.4.2
 * 
 * @param string $filepath The file path and filename 
 *
 * @return string $$fileurl The URL to the template file
 */
function learndash_template_url_from_path( $filepath = '' ) {
	if ( !empty( $filepath ) ) {
		// Ensure we are handling Windows separators. 
		$WP_CONTENT_DIR_tmp = str_replace('\\', '/', WP_CONTENT_DIR );
		$filepath = str_replace('\\', '/', $filepath );
		$filepath = str_replace( $WP_CONTENT_DIR_tmp, WP_CONTENT_URL, $filepath );
		$filepath = str_replace( array('https://', 'http://' ), array('//', '//' ), $filepath );
	}

	return $filepath;
}

/**
 * Normally Course, Lesson, Topic and Quiz settings are stored into a single postmeta array. This 
 * function runs after after that save and will save the array elements into individual postmeta
 * fields. 
 * @param $course_id int required post_meta course_id
 * @param $settings array array of settings to be stored 
 *
 * @return none
 *
 * @since 2.4.3
 */
function learndash_convert_settings_to_single( $post_id = 0, $settings = array(), $prefix = '' ) {
	return;
	
	// Disabled for now. 
	if ( ( !empty( $post_id  ) ) && ( !empty( $settings ) ) && ( is_array( $settings ) ) ) {
		foreach( $settings as $setting_key => $setting_value ) {

			if ( ( !empty( $prefix ) ) && ( !empty( $setting_key ) ) ) {
				$setting_key = str_replace( $prefix.'_', '', $setting_key );
			}

			if ( ( is_array( $setting_value ) ) && ( empty( $setting_value ) ) ) {
				$setting_value = '';
			}
			
			update_post_meta( $post_id, $setting_key, $setting_value );
		}
		// Create a queryable marker so we know this settings has been converted. 
		update_post_meta( $post_id, '_settings_to_single', true );
	}
}

function learndash_check_convert_settings_to_single( $post_id = 0, $prefix = '' ) {
	return;
	
	// Disabled for now. 
	if ( !empty( $post_id ) ) {
		if ( !get_post_meta( $post_id, '_settings_to_single', true ) ) {
			
			$settings = get_post_meta( $post_id, '_'. $prefix, true );
			learndash_convert_settings_to_single( $post_id, $settings, $prefix );
		}
	}
}

// Used when saving a single setting. This will then trigger an update to the array setting
function learndash_update_post_meta( $meta_id = 0, $object_id = '', $meta_key = '', $meta_value = '' ) {
	static $in_process = false;

	if ( $in_process === true ) return;

	$object_post_type = get_post_type( $object_id );	
	if ( $object_post_type === 'sfwd-courses' ) {
		if ( $meta_key === '_sfwd-courses' ) {
			if ( isset( $meta_value['sfwd-courses_course_access_list'] ) ) {
				//remove_action( 'update_post_meta', 'learndash_update_post_meta' );
				$in_process = true;
				update_post_meta( $object_id, 'course_access_list', $meta_value['sfwd-courses_course_access_list'] );
				$in_process = false;
				//add_action( 'update_post_meta', 'learndash_update_post_meta' );
			}
		} else if ( in_array( $meta_key, array( 'course_access_list' ) ) ) {
			$settings = get_post_meta( $object_id, '_'. $object_post_type, true );
			$settings['sfwd-courses_'. $meta_key] = $meta_value;
			
			//remove_action( 'update_post_meta', 'learndash_update_post_meta' );
			$in_process = true;
			update_post_meta( $object_id, '_'. $object_post_type, $settings );
			$in_process = false;
			//add_action( 'update_post_meta', 'learndash_update_post_meta' );
		}
	}
}
add_action( 'update_post_meta', 'learndash_update_post_meta', 20, 4 );


/**
 * Used for the Support panel to get the MySQL priveleges for the DB_USER defined in the wp-config
 *
 * @since 2.4.7
 *
 * @returns array of grants
 */
function learndash_get_db_user_grants() {
	global $wpdb;
	
	$grants = array();

	if ( ( defined( 'DB_USER' ) ) && ( defined( 'DB_HOST' ) ) && ( DB_HOST === 'localhost' ) ) {
		$grants_sql_str = "SHOW GRANTS FOR '". DB_USER ."'@'". DB_HOST ."';";

		$level = ob_get_level();
		ob_start();
		
		$grants_results = $wpdb->query($grants_sql_str);
		if ( !empty( $grants_results ) ) {
			foreach( $wpdb->last_result as $result_object ) {
				foreach( $result_object as $result_key => $result_string ) {
					preg_match('/GRANT (.*?) ON /', $result_string, $result_perms);
					if ( ( isset( $result_perms[1] ) ) && ( !empty( $result_perms[1] ) ) ) {
						$perms = explode(',', $result_perms[1] );
						$perms = array_map( 'trim', $perms );
						$grants = array_merge( $grants, $perms );
					}
				}
			}
		}
		$contents = learndash_ob_get_clean( $level );		
		
		if ( !empty( $grants ) ) {
			$grants = array_unique( $grants );
		}
	}
	
	return $grants;
}

/**
 * Utility function to recursively remove a directory. 
 *
 * @since 1.0.3
 * @see 
 *
 * @param $dir directory path to remove
 * @return none
 */
function learndash_recursive_rmdir( $dir  = '' ) {
	if ( ( !empty( $dir ) ) && ( is_dir( $dir ) ) ) {
		$objects = scandir($dir);
		
		foreach ($objects as $object) {
			if ($object != "." && $object != "..") {
				if (filetype($dir."/".$object) == "dir") 
					learndash_recursive_rmdir($dir."/".$object); 
				else unlink($dir."/".$object);
			}
		}
     	reset($objects);
		rmdir($dir);
	}
}


/**
 * Utility function to parse and validate the Assignment upload extensions allowed. 
 * This utility function will trim, convert to lowercase and rmeove '.', ans unique
 *
 * @since 2.5
 * @see 
 *
 * @param $exts array of extensions: zip, doc, pdf
 * @return $exts array or corrected values. 
 */
function learndash_validate_extensions( $exts = array() ) {
	if ( ( is_string( $exts ) ) && ( !empty( $exts ) ) ) {
		$exts = explode(',', $exts );
		$exts = array_map( 'trim', $exts );
		$exts = array_map( function( $ext ){ return str_replace('.', '', $ext ); }, $exts );
	}
	return $exts;
}

/**
 * Utility function to check string for valid JSON.
 */
function learndash_is_valid_JSON( $string ) {
	$json = json_decode( $string );
	return (is_object($json) && json_last_error() == JSON_ERROR_NONE) ? true : false;
}

/**
 * Controls the output of the Feeds (RSS2 etc) for the various custom post types
 * used within LearnDash. By default the only feed should be for Courses (sfwd-courses).
 * All other post types are disabled by default. 
 * 
 * @since 2.6.0
 * @param object $query WP_Query instance.
 */
function learndash_pre_posts_feeds( $query ) {

	if ( ( ! is_admin() ) && ( $query->is_main_query() ) && ( true === $query->is_feed ) ) {
		$feed_post_type = get_query_var( 'post_type' );
		if ( ! empty( $feed_post_type ) ) {
			if ( true !== learndash_post_type_supports_feed( $feed_post_type ) ) {
				$query->set( 'post__in', array(0) );
			}
		}	
	}
}
add_action( 'pre_get_posts', 'learndash_pre_posts_feeds' );

function learndash_post_type_supports_feed( $feed_post_type = '' ) {
	if ( ( ! empty( $feed_post_type ) ) && ( in_array( $feed_post_type, LDLMS_Post_Types::get_post_types() ) ) ) {
		$feed_post_type_object = get_post_type_object( $feed_post_type );
		if ( ( $feed_post_type_object ) && ( is_a( $feed_post_type_object, 'WP_Post_Type' ) ) ) {
			// Default for LD Post types is false;
			$cpt_has_feed = false;

			$class_key = array(
				learndash_get_post_type_slug( 'course' ) => 'LearnDash_Settings_Courses_CPT',
				learndash_get_post_type_slug( 'lesson' ) => 'LearnDash_Settings_Lessons_CPT',
				learndash_get_post_type_slug( 'topic' ) => 'LearnDash_Settings_Topics_CPT',
				learndash_get_post_type_slug( 'quiz' ) => 'LearnDash_Settings_Quizzes_CPT',
			);

			$has_archive = false;
			$has_feed    = false;
			if ( isset( $class_key[ $feed_post_type ] ) ) {
				$has_archive = LearnDash_Settings_Section::get_section_setting( $class_key[ $feed_post_type ], 'has_archive' );
				$has_feed = LearnDash_Settings_Section::get_section_setting( $class_key[ $feed_post_type ], 'has_feed' );
				if ( ( 'yes' === $has_archive ) && ( 'yes' === $has_feed ) ) {
					$cpt_has_feed = true;
				}
			} 

			/**
			 * Allow filtering if the site want to show feeds for the custom post type.
			 * 
			 * @siince 2.6.0
			 * @param boolean false default value per post type has_archive setting.
			 * @param string $feed_post_type Post Type slug.
			 * @param object $feed_post_type_object WP_Post_Type instance.
			 * @return true to show feed. False to not show feed.
			 */
			$cpt_has_feed = apply_filters( 'learndash_post_type_feed', $cpt_has_feed, $feed_post_type, $feed_post_type_object );
		}
	} else {
		// For aNY non-LD post type is return true to let them pass thru.
		$cpt_has_feed = true;
	}

	return $cpt_has_feed;
}

function learndash_post_type_supports_comments( $feed_post_type = '' ) {
	if ( ( ! empty( $feed_post_type ) ) && ( in_array( $feed_post_type, learndash_get_post_types( 'course' ) ) ) ) {
		$feed_post_type_object = get_post_type_object( $feed_post_type );
		if ( ( $feed_post_type_object ) && ( is_a( $feed_post_type_object, 'WP_Post_Type' ) ) ) {
			// Default for LD Post types is false;
			$cpt_has_comments = false;

			$class_key = array(
				learndash_get_post_type_slug( 'course' ) => 'LearnDash_Settings_Courses_CPT',
				learndash_get_post_type_slug( 'lesson' ) => 'LearnDash_Settings_Lessons_CPT',
				learndash_get_post_type_slug( 'topic' ) => 'LearnDash_Settings_Topics_CPT',
				learndash_get_post_type_slug( 'quiz' ) => 'LearnDash_Settings_Quizzes_CPT',
			);

			if ( isset( $class_key[ $feed_post_type ] ) ) {
				$supports = LearnDash_Settings_Section::get_section_setting( $class_key[ $feed_post_type ], 'supports' );
				if ( ( ! empty( $supports  ) ) && ( in_array( 'comments', $supports ) ) ) {
					$cpt_has_comments = true;
				}
			} 

			/**
			 * Allow filtering if the site want to show feeds for the custom post type.
			 * 
			 * @siince 2.6.0
			 * @param boolean false default value per post type has_archive setting.
			 * @param string $feed_post_type Post Type slug.
			 * @param object $feed_post_type_object WP_Post_Type instance.
			 * @return true to show feed. False to not show feed.
			 */
			$cpt_has_comments = apply_filters( 'learndash_post_comments', $cpt_has_comments, $feed_post_type, $feed_post_type_object );

			return $cpt_has_comments;
		}
	} 
}

/**
 * Manage Post update message for legacy editor screen
 * 
 * @since 2.6.4
 * @param array $pst_messaged Array of post messages by post_type.
 * @return array $post_messages.
 */
function learndash_post_updated_messages( $post_messages = array() ) {
	global $pagenow, $post_ID, $post_type, $post_type_object, $post;
	
	if ( ( $post_type ) && ( in_array( $post_type, LDLMS_Post_Types::get_post_types() ) ) && ( ! isset( $post_messages[ $post_type ] ) ) ) {
		$preview_post_link_html = '';
		$scheduled_post_link_html = '';
		$view_post_link_html = '';
		
		$viewable = is_post_type_viewable( $post_type_object );
		if ( $viewable ) {

			$preview_url = get_preview_post_link( $post );
			$permalink = learndash_get_step_permalink( $post_ID );

			// Preview post link.
			$preview_post_link_html = sprintf( ' <a target="_blank" href="%1$s">%2$s</a>',
				esc_url( $preview_url ),
				__( 'Preview' )
			);

			// Scheduled post preview link.
			$scheduled_post_link_html = sprintf( ' <a target="_blank" href="%1$s">%2$s</a>',
				esc_url( $permalink ),
				__( 'Preview' )
			);

			// View post link.
			$view_post_link_html = sprintf( ' <a href="%1$s">%2$s</a>',
				esc_url( $permalink ),
				__( 'View' )
			);
		}

		/* translators: Publish box date format, see https://secure.php.net/date */
		$scheduled_date = date_i18n( __( 'M j, Y @ H:i', 'default' ), strtotime( $post->post_date ) );

		$post_messages[ $post_type ] = array(
			0 => '', // Unused. Messages start at index 1.
			1 => sprintf( _x( '%s updated.', 'placeholder: Post Type Singlular Label', 'learndash' ), $post_type_object->labels->singular_name ) . $view_post_link_html,
			2 => __( 'Custom field updated.', 'learndash' ),
			3 => __( 'Custom field deleted.', 'learndash' ),
			4 => sprintf( _x( '%s updated.', 'placeholder: Post Type Singlular Label', 'learndash' ), $post_type_object->labels->singular_name ),
			/* translators: %s: date and time of the revision */
			5 => isset($_GET['revision']) ? sprintf( _x( '%1$s restored to revision from %2$s.', 'placeholder: Post Type Singular Label, Revision Title', 'learndash' ), $post_type_object->labels->singular_name, wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => sprintf( _x( '%s published.', 'placeholder: Post Type Singlular Label', 'learndash' ), $post_type_object->labels->singular_name ) . $view_post_link_html,
			7 => sprintf( _x( '%s saved.', 'placeholder: Post Type Singlular Label', 'learndash' ), $post_type_object->labels->singular_name ),
			8 => sprintf( _x( '%s submitted.', 'placeholder: Post Type Singlular Label', 'learndash' ), $post_type_object->labels->singular_name ) . $preview_post_link_html,
			9 => sprintf( _x( '%1$s scheduled for: %2$s.', 'placeholder: Post Type Singlular Label, scheduled date', 'learndash' ), $post_type_object->labels->singular_name, '<strong>' . $scheduled_date . '</strong>' ) . $scheduled_post_link_html,
			10 => sprintf( _x( '%s draft updated.', 'placeholder: Post Type Singlular Label', 'learndash' ), $post_type_object->labels->singular_name ) . $preview_post_link_html,
		);
	}

	// Always return $post_messages;
	return $post_messages;
}
add_filter( 'post_updated_messages', 'learndash_post_updated_messages' );

/**
 * Retreive the number of posts by post_type.
 *
 * @ince 3.0
 * @param string $post_type Post Typ slug to count.
 * @return int Number of posts by type.
 */
function learndash_get_total_post_count( $post_type = '' ) {
	$count_total = 0;

	if ( ( $post_type ) && ( in_array( $post_type, LDLMS_Post_Types::get_post_types() ) ) ) {
		$post_counts = wp_count_posts( $post_type );
		
		// Convert to array.
		$post_counts = json_decode( json_encode( $post_counts ), true );

		/**
		 * We only count the post status shown in the admin
		 * @since 3.0.4
		*/
		$show_in_admin_post_stati = get_post_stati( array( 'show_in_admin_status_list' => true ) );
		$show_in_admin_post_stati = apply_filters( 'learndash_admin_post_stati', $show_in_admin_post_stati, $post_type, $post_counts );
		if ( ! empty( $show_in_admin_post_stati ) ) {
			foreach( $show_in_admin_post_stati as $post_status ) {
				if ( isset( $post_counts[ $post_status ] ) ) {
					$count_total += absint( $post_counts[ $post_status ] );	
				}
			}
 		}
	}

	return $count_total;
}

/**
 * Check the post_type is not empty
 *
 * @param array $query_args WP_Query query args array.
 */
function learndash_check_query_post_type( $query_args = array() ) {
	$total_post_count = 0;
	if ( ( isset( $query_args['post_type'] ) ) && ( ! empty( $query_args['post_type'] ) ) ) {
		if ( is_string( $query_args['post_type'] ) ) {
			$total_post_count += learndash_get_total_post_count( $query_args['post_type'] );
		} elseif ( is_array( $query_args['post_type'] ) ) {
			foreach( $query_args['post_type'] as $post_type ) {
				$total_post_count += learndash_get_total_post_count( $query_args['post_type'] );
			}
		}
	}

	return $total_post_count;
}
/**
 * convert the stored lesson timer value from the postmeta settings into number of total seconds.
 */
function learndash_convert_lesson_time_time( $timer_time = 0 ) {
	if ( ! empty( $timer_time ) ) {
		$time_sections = explode( ' ', $timer_time );
		$h             = $m = $s = 0;

		foreach ( $time_sections as $k => $v ) {
			$value = trim( $v );

			if ( strpos( $value, 'h' ) ) {
				$h = intVal( $value );
			} elseif ( strpos( $value, 'm' ) ) {
				$m = intVal( $value );
			} elseif ( strpos( $value, 's' ) ) {
				$s = intVal( $value );
			}
		}

		$time = ( $h * 60 * 60 ) + ( $m * 60 ) + $s;

		if ( $time != 0 ) {
			$timer_time = absint( $time );
		}
	}

	return $timer_time;
}

/**
 * Utility function to get the previous installed version of LD.
 */
function learndash_get_prior_installed_version() {
	$element = Learndash_Admin_Data_Upgrades::get_instance();
	if ( ( $element ) && ( is_a( $element, 'Learndash_Admin_Data_Upgrades' ) ) ) {
		return $element->get_data_settings( 'prior_version' );
	}
}

/**
 * Utility function to update the comment_status foeld for all posts of <post_type.
 *
 * @since 3.0
 * @param string $post_type Post Type to change.
 * @param string $comment_status New comment status. Allowed values 'open' or 'closed'.
 */
function learndash_update_posts_comment_status( $post_type = '', $comment_status = false ) {
	global $learndash_question_types;
	
	if ( ! empty( $post_type ) ) {	
		$ld_post_types = learndash_get_post_types();
		if ( in_array( $post_type, $ld_post_types ) ) {
			if ( in_array( $comment_status, array( 'open', 'closed' ) ) ) {
				if ( apply_filters( 'learndash_update_posts_comment_status', true, $post_type, $comment_status ) ) {
					global $wpdb;
					$wpdb->query(
						$wpdb->prepare( 
							'UPDATE wp_posts SET comment_status = %s WHERE post_type = %s', $comment_status, $post_type
						)
					); 
				}
			}
		}
	}
}

/**
 * Utility function to load minified version of CSS/JS assets.
 *
 * @since 3.0.3
 */
function leardash_min_asset() {
		return ( ( defined( 'LEARNDASH_SCRIPT_DEBUG' ) && ( LEARNDASH_SCRIPT_DEBUG === true ) ) ? '' : '.min' );
}

/**
 * Utility function to load minified version of CSS/JS builder assets.
 *
 * @since 3.0.3
 */
function leardash_min_builder_asset() {
		return ( ( defined( 'LEARNDASH_BUILDER_DEBUG' ) && ( LEARNDASH_BUILDER_DEBUG === true ) ) ? '' : '.min' );
}

/**
 * Builds a recursive listing of files from a given base path name.
 *
 * @since 3.0.3
 * @param string $base Top-level directory of tree to scan.
 * @return array Array of files found.
 */
function learndash_scandir_recursive( $base = '' ) {
	if ( ( ! $base ) || ( ! strlen( $base ) ) ) {
		return array();
	}
		
	if ( ! file_exists( $base ) ) {
		return array();
	}
	
	$data = array_diff( scandir( $base ), array( '.', '..' ) );

	$subs = array();
	foreach ( $data as $key => $value ) {
		if ( is_dir($base . '/' . $value) ) {
			unset($data[$key]);
			$subs[] = learndash_scandir_recursive($base . '/' . $value);
		} elseif ( is_file($base . '/' . $value) ) {
			$data[$key] = $base . '/' . $value;
		}
	}

	if ( count( $subs ) ) {
		foreach ( $subs as $sub ) {
			$data = array_merge($data, $sub);
		}
	}
	
	return $data;
}

/**
 * Filter to prevent Custom Fields metabox from showing/saving LD keys.
 *
 * @since 3.0.4
 * @param boolean $protected Boolean default to false.
 * @param string  $meta_key Meta Key to check.
 * @param string  $meta_type Will be 'post' for post meta.
 * @return boolean true if protected, false if not.
 */
function learndash_is_protected_meta( $protected = false, $meta_key = '', $meta_type = '' ) {
	if ( ( 'post' === $meta_type ) && ( ! empty( $meta_key ) ) && ( '_' !== $meta_key[0] ) ) {
		
		// Try and determine the post type used. 
		global $typenow;
		$post_type = $typenow;
		if ( empty( $post_type ) ) {
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				if ( ( isset( $_POST['action'] ) ) && ( 'add-meta' === $_POST['action'] ) ) {
					if ( ( isset( $_POST['post_id'] ) ) && ( ! empty( $_POST['post_id'] ) ) ) {
						$post_id = absint( $_POST['post_id'] );
						$post_type = get_post_type( $post_id );
					}
				}
			}
		} 

		// If post type is not empty and onf othe LD types.
		if ( ( ! empty( $post_type ) ) && ( in_array( $post_type, learndash_get_post_types() ) ) ) {
			$protected_meta_keys = array( 'course_id', 'lesson_id', 'course_price_billing_p3', 'course_price_billing_t3', 'course_sections', 'ld_course_steps', 'course_access_list', 'quiz_pro_id', 'ld_course_steps_dirty', 'ld_auto_enroll_group_courses', 'question_pro_id', 'course_points', 'ld_quiz_questions', 'ld_quiz_questions_dirty', 'learndash_certificate_options', 'question_id', 'ld_essay_grading_response', 'question_points', 'question_type', 'question_pro_id', 'question_pro_category' );

			if ( ( in_array( $meta_key, $protected_meta_keys ) ) ) {
				$protected = true;
			} else if ( 'ld_course_' === substr( $meta_key, 0, strlen( 'ld_course_' ) ) ) {
				$protected = true;
			} else if ( 'quiz_pro_id_' === substr( $meta_key, 0, strlen( 'quiz_pro_id_' ) ) ) {
				$protected = true;
			} else if ( 'quiz_pro_primary_' === substr( $meta_key, 0, strlen( 'quiz_pro_primary_' ) ) ) {
				$protected = true;
			} else if ( 'learndash_group_enrolled_' === substr( $meta_key, 0, strlen( 'learndash_group_enrolled_' ) ) ) {
				$protected = true;
			} else if ( 'learndash_group_users_' === substr( $meta_key, 0, strlen( 'learndash_group_users_' ) ) ) {
				$protected = true;
			}
		}
	}
	return $protected;
}
add_filter( 'is_protected_meta', 'learndash_is_protected_meta', 30, 3 );


/**
 * Filter the menus being displayed to show the login/logout.
 * Look for items where the 'url' is '#login'.
 * @since 3.0.7
 * @param array $menu_items From WP Menu items to be displayed.
 * @param array $menu_args From WP Menu args related to the menu set to be displayed.
 * @return array $menu_items
 */
function learndash_login_menu_items( $menu_items, $menu_args = array() ) {

	foreach ( $menu_items as $menu_key => &$menu_item ) {
		/**
		 * Check the properties we need exist and not empty. We shouldn't need to do this 
		 * since the array of menu items comes from WP. See LEARNDASH-3812.
		 */
		if ( ( ! isset( $menu_item->url ) ) || ( empty( $menu_item->url ) ) || ( ! isset( $menu_item->classes ) ) || ( ! is_array( $menu_item->classes ) ) || ( empty( $menu_item->classes ) ) ) {
			continue;
		}

		if ( ( strpos( $menu_item->url, '#login' ) !== false ) && ( in_array( 'ld-button', $menu_item->classes ) ) ) {
			/**
			 * Allow externals to override processing of menu_item.
			 *
			 * @since 3.0.7
			 * @var boolean Process this menu item. True.
			 * @var object  $menu_item WP_Post object for menu item.
			 * @var array   $menu_args Args array related to menu being processed / displayed.
			 * @return boolean. If true is not returned the menu item will not be processed for LD login modal.
			 */
			if ( apply_filters( 'learndash_login_menu_item_process', true, $menu_item, $menu_args ) ) {
				if ( ( empty( $menu_item->post_content ) ) || ( strpos( $menu_item->post_content, '[learndash_login' ) === false ) ) {
					$shortcode = '[learndash_login return="atts"]';
				} else {
					$shortcode = str_replace( '[learndash_login',  '[learndash_login return="atts" ', $menu_item->post_content );
				}

				$menu_item->post_content = '';
				$menu_item->description = '';

				$active_template_key = LearnDash_Theme_Register::get_active_theme_key();
				$login_mode_enabled = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Theme_LD30', 'login_mode_enabled' );
				if ( ( 'ld30' === $active_template_key ) && ( 'yes' === $login_mode_enabled ) ) {
					$shortcode_return = do_shortcode( $shortcode );
					$shortcode_atts = maybe_unserialize( $shortcode_return );

					learndash_load_login_modal_html();
				} else {
					// If here we are not using the LD30 templates. So the handling of the menu item is simple link to WP login/logout.
					$shortcode = str_replace( array( '[learndash_login', ']'), '', $shortcode);
					$atts = shortcode_parse_atts( $shortcode );
					$shortcode_atts = array();

					if ( is_user_logged_in() ) {
						if ( ( isset( $atts['logout_url'] ) ) && ( ! empty( $atts['logout_url'] ) ) ) {
							$shortcode_atts['url'] = $atts['logout_url'];
						} else {
							$shortcode_atts['url'] = wp_logout_url( get_permalink() );
						}

						if ( ( isset( $atts['logout_label'] ) ) && ( ! empty( $atts['logout_label'] ) ) ) {
							$shortcode_atts['title'] = $atts['logout_label'];
						} else {
							$shortcode_atts['title'] = __( 'Logout', 'learndash' );
						}
					} else {
						if ( ( isset( $atts['login_url'] ) ) && ( ! empty( $atts['login_url'] ) ) ) {
							$shortcode_atts['url'] = $atts['login_url'];
						} else {
							$shortcode_atts['url'] = wp_login_url( get_permalink() );
						}

						if ( ( isset( $atts['login_label'] ) ) && ( ! empty( $atts['login_label'] ) ) ) {
								$shortcode_atts['title'] = $atts['login_label'];
						} else {
							$shortcode_atts['title'] = __( 'Login', 'learndash' );
						}
					}
				}

				/**
				 * Allow externals to override menu_item attributes before they are applied.
				 *
				 * @since 2.0.7
				 * @var array  $shortcode_atts Shortcode array containing url, label, etc. 
				 * @var object $menu_item WP_Post object for menu item.
				 * @var array  $menu_args Args array related to menu being processed / displayed.
				 * @return object $menu_item.
				 */
				$shortcode_atts = apply_filters( 'learndash_login_menu_item_atts', $shortcode_atts, $menu_item, $menu_args );
				if ( ( isset( $shortcode_atts['url'] ) ) && ( ! empty( $shortcode_atts['url'] ) ) ) {
					$menu_item->url = $shortcode_atts['url'];
				}
				if ( ( isset( $shortcode_atts['label'] ) ) && ( ! empty( $shortcode_atts['label'] ) ) ) {
					$menu_item->title = $shortcode_atts['label'];
				}

				/**
				 * Allow externals to override final menu_item.
				 *
				 * @since 2.0.7
				 * @var object $menu_item WP_Post object for menu item.
				 * @var array  $menu_args Args array related to menu being processed / displayed.
				 * @return object $menu_item.
				 */
				$menu_item = apply_filters( 'learndash_login_menu_item', $menu_item, $menu_args );
			}
		}
	}
	return $menu_items;
}
add_filter( 'wp_nav_menu_objects', 'learndash_login_menu_items', 30, 2 );

global $learndash_login_model_html;
$learndash_login_model_html = false;
/**
 * Wrapper function to include the modal login in the footer of the HTML.
 */
function learndash_load_login_modal_html() {
	global $learndash_login_model_html;

	// Check that we are running the LD30 theme and login mode enabled. 
	$active_template_key = LearnDash_Theme_Register::get_active_theme_key();
	$login_mode_enabled = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Theme_LD30', 'login_mode_enabled' );
	if ( ( 'ld30' === $active_template_key ) && ( 'yes' === $login_mode_enabled ) ) {
		
		// Don't need to load the HTML if the user is already logged in. 
		if ( ( ! is_user_logged_in() ) && ( function_exists( 'learndash_get_template_part' ) ) ) {
			if ( false === $learndash_login_model_html ) {
				$learndash_login_model_html = learndash_get_template_part( 'modules/login-modal.php', array(), false );
				if ( false !== $learndash_login_model_html ) {
					add_action( 'wp_footer', function() {
						global $learndash_login_model_html;
						if ( ( isset( $learndash_login_model_html ) ) && ( ! empty( $learndash_login_model_html ) ) ) {
							echo '<div class="learndash-wrapper learndash-wrapper-login-modal">' . $learndash_login_model_html . '</div>';
						}
					});
				}
			}
		}
	}
}

/**
 * Add custom classes to body
 * @since 3.1
 * @param array $classes Array of current body classes.
 * @return array $classes.
 */
function learndash_body_classes( $classes = array() ) {
	
	if ( in_array( get_post_type(), learndash_get_post_types(), true ) ) {
		$custom_classes = array();
		$custom_classes[] = 'learndash-cpt';
		$custom_classes[] = 'learndash-cpt-' . get_post_type();

		if ( true === apply_filters( 'learndash_responsive_video', true, get_post_type(), get_the_ID() ) ) {
			$custom_classes[] = 'learndash-embed-responsive';
		}

		$custom_classes = apply_filters( 'learndash_body_classes', $custom_classes, get_post_type(), get_the_ID() );
		if ( ( ! empty( $custom_classes ) ) && ( is_array( $custom_classes ) ) ) {
			$classes = array_merge( $classes, $custom_classes );
			$classes = array_unique( $classes );
		}
	}

	return $classes;
}
add_filter( 'body_class', 'learndash_body_classes', 100, 1 );

/**
 * Utility function to recalcuate the length of string vars within serialized data. 
 * taken from http://lea.verou.me/2011/02/convert-php-serialized-data-to-unicode/
 * 
 * @since 3.1
 * @param string $serialized_text Serialized text. 
 * @return striing serialized text.
 */
function learndash_recount_serialized_bytes( $serialized_text = '' ) {
	if ( ! empty( $serialized_text ) ) {
		mb_internal_encoding("UTF-8");
		mb_regex_encoding("UTF-8");

		mb_ereg_search_init($serialized_text, 's:[0-9]+:"');

		$offset = 0;

		while(preg_match('/s:([0-9]+):"/u', $serialized_text, $matches, PREG_OFFSET_CAPTURE, $offset) ||
			preg_match('/s:([0-9]+):"/u', $serialized_text, $matches, PREG_OFFSET_CAPTURE, ++$offset)) {
			$number = $matches[1][0];
			$pos = $matches[1][1];

			$digits = strlen("$number");
			$pos_chars = mb_strlen(substr($serialized_text, 0, $pos)) + 2 + $digits;

			$str = mb_substr($serialized_text, $pos_chars, $number);

			$new_number = strlen($str);
			$new_digits = strlen($new_number);

			if($number != $new_number) {
				// Change stored number
				$serialized_text = substr_replace($serialized_text, $new_number, $pos, $digits);
				$pos += $new_digits - $digits;
			}

			$offset = $pos + 2 + $new_number;
		}
	}

	return $serialized_text;
}

function learndash_get_single_post( $post_type = '' ) {
	if ( ( ! empty( $post_type ) ) && ( in_array( $post_type, learndash_get_post_types() ) ) ) {
		$post_query_args = array(
			'post_type'      => $post_type,
			'posts_per_page' => 1,
			'post_status'    => 'publish',
			'fields'         => 'ids',
		);

		$post_query = new WP_Query( $post_query_args );
		if ( ( is_a( $post_query, 'WP_Query' ) ) && ( property_exists( $post_query, 'posts' ) ) && ( ! empty( $post_query->posts ) ) ) {
			return $post_query->posts[0];
		}
	}
}