<?php
if ( ( class_exists( 'LearnDash_Shortcodes_Section' ) ) && ( !class_exists( 'LearnDash_Shortcodes_Section_user_groups' ) ) ) {
	class LearnDash_Shortcodes_Section_user_groups extends LearnDash_Shortcodes_Section {

		function __construct( $fields_args = array() ) {
			$this->fields_args = $fields_args;

			$this->shortcodes_section_key 			= 	'user_groups';
			$this->shortcodes_section_title 		= 	esc_html__( 'User Groups', 'learndash' );
			$this->shortcodes_section_type			=	1;
			$this->shortcodes_section_description	=	 esc_html__( 'This shortcode displays the list of groups users are assigned to as users or leaders.', 'learndash' );
			
			parent::__construct(); 
		}
		
		function init_shortcodes_section_fields() {
			$this->shortcodes_option_fields = array(
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
		
			$this->shortcodes_option_fields = apply_filters( 'learndash_settings_fields', $this->shortcodes_option_fields, $this->shortcodes_section_key );
			
			parent::init_shortcodes_section_fields();
		}
	}
}
