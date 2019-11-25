<?php
if ( ( class_exists( 'LearnDash_Shortcodes_Section' ) ) && ( ! class_exists( 'LearnDash_Shortcodes_Section_visitor' ) ) ) {
	class LearnDash_Shortcodes_Section_visitor extends LearnDash_Shortcodes_Section {

		function __construct( $fields_args = array() ) {
			$this->fields_args = $fields_args;

			$this->shortcodes_section_key         = 'visitor';
			$this->shortcodes_section_title       = esc_html__( 'Visitor', 'learndash' );
			$this->shortcodes_section_type        = 2;
			$this->shortcodes_section_description = sprintf( wp_kses_post( _x( 'This shortcode shows the content if the user is not enrolled in the %s. The shortcode can be used on <strong>any</strong> page or widget area.', 'placeholders: course', 'learndash' ) ), learndash_get_custom_label_lower( 'course' ) );

			parent::__construct();
		}

		function init_shortcodes_section_fields() {
			$this->shortcodes_option_fields = array(
				'message'   => array(
					'id'        => $this->shortcodes_section_key . '_message',
					'name'      => 'message',
					'type'      => 'textarea',
					'label'     => esc_html__( 'Message shown to user', 'learndash' ),
					'help_text' => esc_html__( 'Message shown to user', 'learndash' ),
					'value'     => '',
					'required'  => 'required',
				),
				'course_id' => array(
					'id'        => $this->shortcodes_section_key . '_course_id',
					'name'      => 'course_id',
					'type'      => 'number',
					'label'     => sprintf( esc_html_x( '%s ID', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
					'help_text' => sprintf( esc_html_x( 'Enter single %1$s ID. Leave blank for current %2$s.', 'placeholders: Course, Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ), LearnDash_Custom_Label::get_label( 'course' ) ),
					'value'     => '',
					'class'     => 'small-text',
				),
				'autop'     => array(
					'id'        => $this->shortcodes_section_key . 'autop',
					'name'      => 'autop',
					'type'      => 'select',
					'label'     => esc_html__( 'Auto Paragraph', 'learndash' ),
					'help_text' => esc_html__( 'Format shortcode content into proper pararaphs.', 'learndash' ),
					'value'     => 'true',
					'options'   => array(
						''      => esc_html__( 'Yes (default)', 'learndash' ),
						'false' => esc_html__( 'No', 'learndash' ),
					),
				),
			);

			if ( ( ! isset( $this->fields_args['post_type'] ) ) || ( ( $this->fields_args['post_type'] !== 'sfwd-courses' ) && ( $this->fields_args['post_type'] != 'sfwd-lessons' ) && ( $this->fields_args['post_type'] != 'sfwd-topic' ) ) ) {

				$this->shortcodes_option_fields['course_id']['required']  = 'required';
				$this->shortcodes_option_fields['course_id']['help_text'] = sprintf( esc_html_x( 'Enter single %s ID', 'placeholders: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) );
			}

			$this->shortcodes_option_fields = apply_filters( 'learndash_settings_fields', $this->shortcodes_option_fields, $this->shortcodes_section_key );

			parent::init_shortcodes_section_fields();
		}
	}
}

