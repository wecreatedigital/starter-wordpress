<?php
if ( ( class_exists( 'LearnDash_Shortcodes_Section' ) ) && ( !class_exists( 'LearnDash_Shortcodes_Section_learndash_course_progress' ) ) ) {
	class LearnDash_Shortcodes_Section_learndash_course_progress extends LearnDash_Shortcodes_Section {

		function __construct( $fields_args = array() ) {
			$this->fields_args = $fields_args;
			
			$this->shortcodes_section_key 			= 	'learndash_course_progress';
			$this->shortcodes_section_title 		= 	sprintf( esc_html_x( '%s Progress', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) );
			$this->shortcodes_section_type			=	1;
			$this->shortcodes_section_description	=	sprintf( esc_html_x( 'This shortcode displays users progress bar for the %1$s in any %2$s/%3$s/%4$s pages.', 'placeholders: course, course, lesson, quiz', 'learndash' ), learndash_get_custom_label_lower( 'course' ), learndash_get_custom_label_lower( 'course' ), learndash_get_custom_label_lower( 'lesson' ), learndash_get_custom_label_lower( 'quiz' ) );
						
			parent::__construct(); 
		}
		
		function init_shortcodes_section_fields() {
			$this->shortcodes_option_fields = array(
				'course_id' => array(
					'id'			=>	$this->shortcodes_section_key . '_course_id',
					'name'  		=> 	'course_id', 
					'type'  		=> 	'number',
					'label' 		=> 	sprintf( esc_html_x( '%s ID', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
					'help_text'		=>	sprintf( esc_html_x( 'Enter single %1$s ID. Leave blank for all %2$s.', 'placeholders: Course, Courses', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ), LearnDash_Custom_Label::get_label( 'courses' ) ),
					'value' 		=> 	'',
					'class'			=>	'small-text'
				),
				'user_id' => array(
					'id'			=>	$this->shortcodes_section_key . '_user_id',
					'name'  		=> 	'user_id', 
					'type'  		=> 	'number',
					'label' 		=> 	esc_html__( 'User ID', 'learndash' ),
					'help_text'		=>	esc_html__('Enter specific User ID. Leave blank for current User.', 'learndash' ),
					'value' 		=> 	'',
					'class'			=>	'small-text'
				),
			);
		
			if ( ( !isset( $this->fields_args['post_type'] ) ) || ( ( $this->fields_args['post_type'] != 'sfwd-courses' ) && ( $this->fields_args['post_type'] != 'sfwd-lessons' ) && ( $this->fields_args['post_type'] != 'sfwd-topic' ) ) ) {
				$this->shortcodes_option_fields['course_id']['required'] = 'required';	
				$this->shortcodes_option_fields['course_id']['help_text'] =	sprintf( esc_html_x( 'Enter single %s ID', 'placeholders: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) );
			} 
		
			$this->shortcodes_option_fields = apply_filters( 'learndash_settings_fields', $this->shortcodes_option_fields, $this->shortcodes_section_key );
			
			parent::init_shortcodes_section_fields();
		}
	}
}
