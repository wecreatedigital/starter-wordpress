<?php
if ( ( class_exists( 'LearnDash_Shortcodes_Section' ) ) && ( !class_exists( 'LearnDash_Shortcodes_Section_learndash_payment_buttons' ) ) ) {
	class LearnDash_Shortcodes_Section_learndash_payment_buttons extends LearnDash_Shortcodes_Section {

		function __construct( $fields_args = array() ) {
			$this->fields_args = $fields_args;

			$this->shortcodes_section_key 			= 	'learndash_payment_buttons';
			$this->shortcodes_section_title 		= 	esc_html__( 'Payment Buttons', 'learndash' );
			$this->shortcodes_section_type			=	1;
			$this->shortcodes_section_description	=	esc_html__( 'This shortcode can show the payment buttons on any page.', 'learndash' );
			
			parent::__construct(); 
		}
		
		function init_shortcodes_section_fields() {
			$this->shortcodes_option_fields = array(
				'course_id' => array(
					'id'			=>	$this->shortcodes_section_key . '_course_id',
					'name'  		=> 	'course_id', 
					'type'  		=> 	'number',
					'label' 		=> 	sprintf( esc_html_x( '%s ID', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
					'help_text'		=>	sprintf( esc_html_x( 'Enter single %s ID', 'placeholders: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
					'value' 		=> 	'',
					'class'			=>	'small-text',
					'required'		=>	'required'
				),
			);
		
			$this->shortcodes_option_fields = apply_filters( 'learndash_settings_fields', $this->shortcodes_option_fields, $this->shortcodes_section_key );
			
			parent::init_shortcodes_section_fields();
		}
	}
}
