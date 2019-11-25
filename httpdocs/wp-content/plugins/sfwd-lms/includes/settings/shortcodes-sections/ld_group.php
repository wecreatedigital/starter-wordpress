<?php
if ( ( class_exists( 'LearnDash_Shortcodes_Section' ) ) && ( ! class_exists( 'LearnDash_Shortcodes_Section_ld_group' ) ) ) {
	class LearnDash_Shortcodes_Section_ld_group extends LearnDash_Shortcodes_Section {

		function __construct( $fields_args = array() ) {
			$this->fields_args = $fields_args;

			$this->shortcodes_section_key         = 'ld_group';
			$this->shortcodes_section_title       = esc_html__( 'Group', 'learndash' );
			$this->shortcodes_section_type        = 2;
			$this->shortcodes_section_description = esc_html__( 'This shortcode shows the content if the user is enrolled in a specific group.', 'learndash' );

			parent::__construct();
		}

		function init_shortcodes_section_fields() {
			$this->shortcodes_option_fields = array(
				'message'  => array(
					'id'        => $this->shortcodes_section_key . '_message',
					'name'      => 'message',
					'type'      => 'textarea',
					'label'     => esc_html__( 'Message shown to user', 'learndash' ),
					'help_text' => esc_html__( 'Message shown to user', 'learndash' ),
					'value'     => '',
					'required'  => 'required',
				),
				'group_id' => array(
					'id'        => $this->shortcodes_section_key . '_group_id',
					'name'      => 'group_id',
					'type'      => 'number',
					'label'     => esc_html__( 'Group ID', 'learndash' ),
					'help_text' => esc_html__( 'Enter single Group ID. Leave blank for any Group.', 'learndash' ),
					'value'     => '',
					'class'     => 'small-text',
				),
				'user_id'  => array(
					'id'        => $this->shortcodes_section_key . '_user_id',
					'name'      => 'user_id',
					'type'      => 'number',
					'label'     => esc_html__( 'User ID', 'learndash' ),
					'help_text' => esc_html__( 'Enter specific User ID. Leave blank for current User.', 'learndash' ),
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

			if ( ( ! isset( $this->fields_args['post_type'] ) ) || ( 'groups' != $this->fields_args['post_type'] ) ) {
				$this->shortcodes_option_fields['group_id']['required']  = 'required';
				$this->shortcodes_option_fields['group_id']['help_text'] = esc_html__( 'Enter single Group ID.', 'learndash' );
			}

			$this->shortcodes_option_fields = apply_filters( 'learndash_settings_fields', $this->shortcodes_option_fields, $this->shortcodes_section_key );

			parent::init_shortcodes_section_fields();
		}
	}
}
