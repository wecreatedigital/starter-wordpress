<?php
if ( ( class_exists( 'LearnDash_Shortcodes_Section' ) ) && ( ! class_exists( 'LearnDash_Shortcodes_Section_ld_profile' ) ) ) {
	class LearnDash_Shortcodes_Section_ld_profile extends LearnDash_Shortcodes_Section {

		public function __construct( $fields_args = array() ) {
			$this->fields_args = $fields_args;

			$this->shortcodes_section_key   = 'ld_profile';
			$this->shortcodes_section_title = esc_html__( 'Profile', 'learndash' );
			$this->shortcodes_section_type  = 1;

			// translators: placeholder: placeholder: placeholder: courses, course, quiz.
			$this->shortcodes_section_description = sprintf( esc_html_x( 'Displays user\'s enrolled %1$s, %2$s progress, %3$s scores, and achieved certificates.', 'placeholder: courses, course, quiz', 'learndash' ), learndash_get_custom_label_lower( 'courses' ), learndash_get_custom_label_lower( 'course' ), learndash_get_custom_label_lower( 'quiz' ) );

			parent::__construct();
		}

		public function init_shortcodes_section_fields() {
			$this->shortcodes_option_fields = array(

				'per_page'           => array(
					'id'        => $this->shortcodes_section_key . '_per_page',
					'name'      => 'per_page',
					'type'      => 'number',
					// translators: placeholder: Courses.
					'label'     => sprintf( esc_html_x( '%s per page', 'placeholder: Courses', 'learndash' ), LearnDash_Custom_Label::get_label( 'Courses' ) ),
					// translators: placeholder: placeholder: Courses, default per page.
					'help_text' => sprintf( esc_html_x( '%1$s per page. Default is %2$d. Set to zero for all.', 'placeholder: Courses, default per page', 'learndash' ), LearnDash_Custom_Label::get_label( 'Courses' ), LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Per_Page', 'per_page' ) ),
					'value'     => false,
					'class'     => 'small-text',
				),

				'orderby'            => array(
					'id'        => $this->shortcodes_section_key . '_orderby',
					'name'      => 'orderby',
					'type'      => 'select',
					'label'     => esc_html__( 'Order by', 'learndash' ),
					'help_text' => wp_kses_post( __( 'See <a target="_blank" href="https://codex.wordpress.org/Class_Reference/WP_Query#Order_.26_Orderby_Parameters">the full list of available orderby options here.</a>', 'learndash' ) ),
					'value'     => 'ID',
					'options'   => array(
						''           => esc_html__( 'ID - Order by post id. (default)', 'learndash' ),
						'title'      => esc_html__( 'Title - Order by post title', 'learndash' ),
						'date'       => esc_html__( 'Date - Order by post date', 'learndash' ),
						'menu_order' => esc_html__( 'Menu - Order by Page Order Value', 'learndash' ),
					),
				),
				'order'              => array(
					'id'        => $this->shortcodes_section_key . '_order',
					'name'      => 'order',
					'type'      => 'select',
					'label'     => esc_html__( 'Order', 'learndash' ),
					'help_text' => esc_html__( 'Order', 'learndash' ),
					'value'     => 'ID',
					'options'   => array(
						''    => esc_html__( 'DESC - highest to lowest values (default)', 'learndash' ),
						'ASC' => esc_html__( 'ASC - lowest to highest values', 'learndash' ),
					),
				),

				'show_search'       => array(
					'id'        => $this->shortcodes_section_key . 'show_search',
					'name'      => 'show_search',
					'type'      => 'select',
					'label'     => esc_html__( 'Show Search', 'learndash' ),
					'help_text' => esc_html__( 'Show Search', 'learndash' ),
					'value'     => 'yes',
					'options'   => array(
						''   => esc_html__( 'Yes', 'learndash' ),
						'no' => esc_html__( 'No', 'learndash' ),
					),
					'help_text' => esc_html__( 'LD30 template only', 'learndash' ),
				),

				'show_header'        => array(
					'id'        => $this->shortcodes_section_key . 'show_header',
					'name'      => 'show_header',
					'type'      => 'select',
					// translators: placeholder: Course.
					'label'     => esc_html__( 'Show Profile Header', 'learndash' ),
					// translators: placeholder: Course.
					'help_text' => esc_html__( 'show_header', 'learndash' ),
					'value'     => '',
					'options'   => array(
						''   => esc_html__( 'Yes', 'learndash' ),
						'no' => esc_html__( 'No', 'learndash' ),
					),
				),
				'course_points_user' => array(
					'id'        => $this->shortcodes_section_key . 'course_points_user',
					'name'      => 'course_points_user',
					'type'      => 'select',
					// translators: placeholder: Course.
					'label'     => sprintf( esc_html_x( 'Show Earned %s Points', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
					// translators: placeholder: Course.
					'help_text' => sprintf( esc_html_x( 'Show Earned %s Points', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
					'value'     => '',
					'options'   => array(
						''   => esc_html__( 'Yes', 'learndash' ),
						'no' => esc_html__( 'No', 'learndash' ),
					),
				),
				'profile_link'       => array(
					'id'        => $this->shortcodes_section_key . 'profile_link',
					'name'      => 'profile_link',
					'type'      => 'select',
					'label'     => esc_html__( 'Show Profile Link', 'learndash' ),
					'help_text' => esc_html__( 'Show Profile Link', 'learndash' ),
					'value'     => 'yes',
					'options'   => array(
						''   => esc_html__( 'Yes', 'learndash' ),
						'no' => esc_html__( 'No', 'learndash' ),
					),
				),
				'show_quizzes'       => array(
					'id'        => $this->shortcodes_section_key . 'show_quizzes',
					'name'      => 'show_quizzes',
					'type'      => 'select',
					'label'     => esc_html__( 'Show User Quiz Attempts', 'learndash' ),
					'help_text' => esc_html__( 'Show User Quiz Attempts', 'learndash' ),
					'value'     => 'yes',
					'options'   => array(
						''   => esc_html__( 'Yes', 'learndash' ),
						'no' => esc_html__( 'No', 'learndash' ),
					),
				),

				'expand_all'         => array(
					'id'        => $this->shortcodes_section_key . 'expand_all',
					'name'      => 'expand_all',
					'type'      => 'select',
					// translators: placeholder: Course.
					'label'     => sprintf( esc_html_x( 'Expand All %s Sections', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
					// translators: placeholder: Course.
					'help_text' => sprintf( esc_html_x( 'Expand All %s sections', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
					'value'     => 'no',
					'options'   => array(
						''    => esc_html__( 'No', 'learndash' ),
						'yes' => esc_html__( 'Yes', 'learndash' ),
					),
				),
			);

			$this->shortcodes_option_fields = apply_filters( 'learndash_settings_fields', $this->shortcodes_option_fields, $this->shortcodes_section_key );

			parent::init_shortcodes_section_fields();
		}
	}
}
