<?php
/**
 * LearnDash Settings Metabox for Course Display and Content Options.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Metabox' ) ) && ( ! class_exists( 'LearnDash_Settings_Metabox_Course_Display_Content' ) ) ) {
	/**
	 * Class to create the settings section.
	 */
	class LearnDash_Settings_Metabox_Course_Display_Content extends LearnDash_Settings_Metabox {

		/**
		 * Public constructor for class
		 */
		public function __construct() {
			// What screen ID are we showing on.
			$this->settings_screen_id = 'sfwd-courses';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_metabox_key = 'learndash-course-display-content-settings';

			// Section label/header.
			$this->settings_section_label = esc_html__( 'Display and Content Options', 'learndash' );

			// Used to show the section description above the fields. Can be empty.
			$this->settings_section_description = sprintf(
				// translators: placeholder: course.
				esc_html_x( 'Controls the look and feel of the %s and optional content settings', 'placeholder: course', 'learndash' ),
				learndash_get_custom_label_lower( 'course' )
			);

			add_filter( 'learndash_metabox_save_fields_' . $this->settings_metabox_key, array( $this, 'filter_saved_fields' ), 30, 3 );

			// Map internal settings field ID to legacy field ID.
			$this->settings_fields_map = array(
				// New fields
				'course_materials_enabled'      => 'course_materials_enabled',
				//'course_topics_per_page'        => 'course_topics_per_page',
				'course_lesson_order_enabled'   => 'course_lesson_order_enabled',

				// Legacy fields
				'course_materials'              => 'course_materials',
				'certificate'                   => 'certificate',
				'course_disable_content_table'  => 'course_disable_content_table',
				'course_lesson_per_page'        => 'course_lesson_per_page',
				'course_lesson_per_page_custom' => 'course_lesson_per_page_custom',
				'course_topic_per_page_custom'  => 'course_topic_per_page_custom',
				'course_lesson_orderby'         => 'course_lesson_orderby',
				'course_lesson_order'           => 'course_lesson_order',
			);

			parent::__construct();
		}

		/**
		 * Initialize the metabox settings values.
		 */
		public function load_settings_values() {
			global $sfwd_lms;

			parent::load_settings_values();
			if ( true === $this->settings_values_loaded ) {

				if ( ! isset( $this->setting_option_values['course_materials_enabled'] ) ) {
					$this->setting_option_values['course_materials_enabled'] = '';
					if ( ( isset( $this->setting_option_values['course_materials'] ) ) && ( ! empty( $this->setting_option_values['course_materials'] ) ) ) {
						$this->setting_option_values['course_materials_enabled'] = 'on';
					}
				}

				if ( ! isset( $this->setting_option_values['course_materials'] ) ) {
					$this->setting_option_values['course_materials'] = '';
				}

				if ( ! isset( $this->setting_option_values['certificate'] ) ) {
					$this->setting_option_values['certificate'] = '';
				}

				if ( ! isset( $this->setting_option_values['course_disable_content_table'] ) ) {
					$this->setting_option_values['course_disable_content_table'] = '';
				}

				if ( ! isset( $this->setting_option_values['course_lesson_per_page'] ) ) {
					$this->setting_option_values['course_lesson_per_page'] = '';
				}

				if ( 'CUSTOM' === $this->setting_option_values['course_lesson_per_page'] ) {
					$this->setting_option_values['course_lesson_per_page_custom'] = absint( $this->setting_option_values['course_lesson_per_page_custom'] );
					$this->setting_option_values['course_topic_per_page_custom'] = absint( $this->setting_option_values['course_topic_per_page_custom'] );
				} else {
					$this->setting_option_values['course_lesson_per_page_custom'] = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Management_Display', 'course_pagination_lessons' );
					$this->setting_option_values['course_topic_per_page_custom'] = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Management_Display', 'course_pagination_topics' );
				}

				if ( ! isset( $this->setting_option_values['course_lesson_orderby'] ) ) {
					$this->setting_option_values['course_lesson_orderby'] = learndash_get_setting( get_the_ID(), 'course_lesson_orderby' );
				}
				if ( ! isset( $this->setting_option_values['course_lesson_order'] ) ) {
					$this->setting_option_values['course_lesson_order'] = learndash_get_setting( get_the_ID(), 'course_lesson_order' );
				}

				if ( ! isset( $this->setting_option_values['course_lesson_order_enabled'] ) ) {
					$this->setting_option_values['course_lesson_order_enabled'] = '';
				}

				if ( ( isset( $this->setting_option_values['course_lesson_orderby'] ) ) && ( ! empty( $this->setting_option_values['course_lesson_orderby'] ) ) || ( isset( $this->setting_option_values['course_lesson_order'] ) ) && ( ! empty( $this->setting_option_values['course_lesson_order'] ) ) ) {
					$this->setting_option_values['course_lesson_order_enabled'] = 'on';
				}
			}
		}

		/**
		 * Initialize the metabox settings fields.
		 */
		public function load_settings_fields() {
			global $sfwd_lms;

			$course_lessons_options_labels = array(
				//'orderby' => LearnDash_Settings_Section_Lessons_Display_Order::get_setting_select_option_label( 'orderby' ),
				'orderby' 	=> 	LearnDash_Settings_Section::get_section_setting_select_option_label( 'LearnDash_Settings_Section_Lessons_Display_Order', 'orderby' ),

				//'order'   => LearnDash_Settings_Section_Lessons_Display_Order::get_setting_select_option_label( 'order' ),
				'order' 	=>	LearnDash_Settings_Section::get_section_setting_select_option_label( 'LearnDash_Settings_Section_Lessons_Display_Order', 'order' ),
			);

			if ( ( defined( 'LEARNDASH_SELECT2_LIB' ) ) && ( true === apply_filters( 'learndash_select2_lib', LEARNDASH_SELECT2_LIB ) ) ) {
				$select_cert_options_default = array(
					'-1' => esc_html__( 'Search or select a certificate…', 'learndash' ),
				);
			} else {
				$select_cert_options_default = array(
					'' => esc_html__( 'Select Certificate', 'learndash' ),
				);
			}
			$select_cert_options = $sfwd_lms->select_a_certificate();
			if ( ( is_array( $select_cert_options ) ) && ( ! empty( $select_cert_options ) ) ) {
				$select_cert_options = $select_cert_options_default + $select_cert_options;
			} else {
				$select_cert_options = $select_cert_options_default;
			}

			$this->setting_option_fields = array(
				'course_materials_enabled'      => array(
					'name'                => 'course_materials_enabled',
					'type'                => 'checkbox-switch',
					'label'               => sprintf(
						// translators: placeholder: Course.
						esc_html_x( '%s Materials', 'placeholder: Course', 'learndash' ),
						learndash_get_custom_label( 'course' )
					),
					'help_text'           => sprintf(
						// translators: placeholder: course.
						esc_html_x( 'List and display support materials for the %s. This is visible to all users (including non-enrollees) by default.', 'placeholder: course', 'learndash' ),
						learndash_get_custom_label_lower( 'course' )
					),
					'value'               => $this->setting_option_values['course_materials_enabled'],
					'default'             => '',
					'options'             => array(
						'on' => sprintf(
							// translators: placeholder: Course.
							esc_html_x( 'Any content added below is displayed on the main %s page', 'placeholder: Course', 'learndash' ),
							learndash_get_custom_label( 'course' )
						),
						''   => '',

					),
					'child_section_state' => ( 'on' === $this->setting_option_values['course_materials_enabled'] ) ? 'open' : 'closed',
				),
				'course_materials'              => array(
					'name'           => 'course_materials',
					'type'           => 'wpeditor',
					'parent_setting' => 'course_materials_enabled',
					'value'          => $this->setting_option_values['course_materials'],
					'default'        => '',
					'placeholder'    => esc_html__( 'Add a list of needed documents or URLs. This field supports HTML.', 'learndash' ),
					'editor_args' => array(
						'textarea_name' => $this->settings_metabox_key . '[course_materials]',
						'textarea_rows' => 3,
					),
				),
				'certificate'                   => array(
					'name'    => 'certificate',
					'type'    => 'select',
					'label'   => sprintf(
						// translators: placeholder: Course.
						esc_html_x( '%s Certificate', 'placeholder: Course', 'learndash' ),
						learndash_get_custom_label( 'course' )
					),
					'default' => '',
					'value'   => $this->setting_option_values['certificate'],
					'options' => $select_cert_options,
				),
				'course_disable_content_table'  => array(
					'name'      => 'course_disable_content_table',
					'label'     => sprintf(
						// translators: placeholder: Course.
						esc_html_x( '%s Content', 'placeholder: Course', 'learndash' ),
						learndash_get_custom_label( 'course' )
					),
					'type'      => 'radio',
					'default'   => '',
					'help_text' => sprintf(
						// translators: placeholder: Course.
						esc_html_x( 'Choose whether to display the %s content table to ALL users or only enrollees', 'placeholder: Course', 'learndash' ),
						learndash_get_custom_label( 'course' )
					),
					'options'   => array(
						''   => esc_html__( 'Always visible', 'learndash' ),
						'on' => esc_html__( 'Only visible to enrollees', 'learndash' ),
					),
					'value'     => $this->setting_option_values['course_disable_content_table'],
				),

				'course_lesson_per_page'        => array(
					'name'                => 'course_lesson_per_page',
					'label'               => esc_html__( 'Custom Pagination', 'learndash' ),
					'type'                => 'checkbox-switch',
					'help_text'           => sprintf(
						// translators: placeholders: course, course.
						esc_html_x( 'Customize the pagination options for this %1$s content table and %2$s navigation widget.', 'placeholders: course, course', 'learndash' ),
						learndash_get_custom_label_lower( 'course' ),
						learndash_get_custom_label_lower( 'course' )
					),
					'options'             => array(
						''       => esc_html__( 'Currently showing default pagination', 'learndash' ),
						'CUSTOM' => '',
					),
					'value'               => $this->setting_option_values['course_lesson_per_page'],
					'child_section_state' => ( 'CUSTOM' === $this->setting_option_values['course_lesson_per_page'] ) ? 'open' : 'closed',
				),
				'course_lesson_per_page_custom' => array(
					'name'           => 'course_lesson_per_page_custom',
					'type'           => 'number',
					'class'          => 'small-text',
					'label'          => sprintf(
						// translators: placeholder: Lessons per page.
						esc_html_x( '%s', 'placeholder: Lessons per page', 'learndash' ),
						learndash_get_custom_label( 'lessons' )
					),
					'input_label'    => esc_html__( 'per page', 'learndash' ),
					'value'          => $this->setting_option_values['course_lesson_per_page_custom'],
					'default'        => '',
					'attrs'          => array(
						'step' => 1,
						'min'  => 0,
					),
					'parent_setting' => 'course_lesson_per_page',
				),
				'course_topic_per_page_custom'  => array(
					'name'           => 'course_topic_per_page_custom',
					'type'           => 'number',
					'class'          => 'small-text',
					'label'          => sprintf(
						// translators: placeholder: Topics per page.
						esc_html_x( '%s', 'placeholder: Topics per page', 'learndash' ),
						learndash_get_custom_label( 'topics' )
					),
					'input_label'    => esc_html__( 'per page', 'learndash' ),
					'default'        => '',
					'value'          => $this->setting_option_values['course_topic_per_page_custom'],
					'attrs'          => array(
						'step' => 1,
						'min'  => 0,
					),
					'parent_setting' => 'course_lesson_per_page',
				),

				'course_lesson_order_enabled'   => array(
					'name'                => 'course_lesson_order_enabled',
					'label'               => sprintf(
						// translators: placeholder: Lesson.
						esc_html_x( 'Custom %s Order', 'placeholder: Lesson', 'learndash' ),
						learndash_get_custom_label( 'lesson' )
					),
					'type'                => 'checkbox-switch',
					'help_text'           => sprintf(
						// translators: placeholders: lessons, topics.
						esc_html_x( 'Customize the display order of %1$s and %2$s.', 'placeholders: lessons, topics', 'learndash' ),
						learndash_get_custom_label_lower( 'lessons' ),
						learndash_get_custom_label_lower( 'topics' )
					),
					'options'             => array(
						''   => sprintf(
							// translators: placeholder: lesson order by, lesson order direction labels.
							esc_html_x( 'Using default sorting by %1$s in %2$s order', 'placeholder: lesson order by, lesson order direction labels', 'learndash' ),
							'<em>' . 
							//LearnDash_Settings_Section_Lessons_Display_Order::get_setting_select_option_label( 'orderby' ) 
							LearnDash_Settings_Section::get_section_setting_select_option_label( 'LearnDash_Settings_Section_Lessons_Display_Order', 'orderby' )
							. '</em>',
							'<em>' . 
							//LearnDash_Settings_Section_Lessons_Display_Order::get_setting_select_option_label( 'order' ) 
							LearnDash_Settings_Section::get_section_setting_select_option_label( 'LearnDash_Settings_Section_Lessons_Display_Order', 'order' )
							. '</em>'
						),
						'on' => '',
					),
					'value'               => $this->setting_option_values['course_lesson_order_enabled'],
					'child_section_state' => ( 'on' === $this->setting_option_values['course_lesson_order_enabled'] ) ? 'open' : 'closed',
				),

				'course_lesson_orderby'         => array(
					'name'           => 'course_lesson_orderby',
					'label'          => esc_html__( 'Sort By', 'learndash' ),
					'type'           => 'select',
					'options'        => array(
						''           => esc_html__( 'Use Default', 'learndash' ) . ' ( ' . $course_lessons_options_labels['orderby'] . ' )',
						'title'      => esc_html__( 'Title', 'learndash' ),
						'date'       => esc_html__( 'Date', 'learndash' ),
						'menu_order' => esc_html__( 'Menu Order', 'learndash' ),
					),
					'default'        => '',
					'value'          => $this->setting_option_values['course_lesson_orderby'],
					'parent_setting' => 'course_lesson_order_enabled',
				),
				'course_lesson_order'           => array(
					'name'           => 'course_lesson_order',
					'label'          => esc_html__( 'Order Direction', 'learndash' ),
					'type'           => 'select',
					'options'        => array(
						''     => esc_html__( 'Use Default', 'learndash' ) . ' ( ' . $course_lessons_options_labels['order'] . ' )',
						'ASC'  => esc_html__( 'Ascending', 'learndash' ),
						'DESC' => esc_html__( 'Descending', 'learndash' ),
					),
					'default'        => '',
					'value'          => $this->setting_option_values['course_lesson_order'],
					'parent_setting' => 'course_lesson_order_enabled',
				),
			);

			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) === 'yes' ) {
				unset( $this->setting_option_fields['course_lesson_order_enabled'] );
				unset( $this->setting_option_fields['course_lesson_orderby'] );
				unset( $this->setting_option_fields['course_lesson_order'] );
			}

			$this->setting_option_fields = apply_filters( 'learndash_settings_fields', $this->setting_option_fields, $this->settings_metabox_key );

			parent::load_settings_fields();
		}

		/**
		 * Filter settings values for metabox before save to database.
		 *
		 * @param array $settings_values Array of settings values.
		 * @param string $settings_metabox_key Metabox key.
		 * @param string $settings_screen_id Screen ID.
		 * @return array $settings_values.
		 */
		public function filter_saved_fields( $settings_values = array(), $settings_metabox_key = '', $settings_screen_id = '' ) {
			if ( ( $settings_screen_id === $this->settings_screen_id ) && ( $settings_metabox_key === $this->settings_metabox_key ) ) {

				/**
				 * Check the Course Materials set course_materials_enabled/course_materials. If 'course_materials_enabled' setting is
				 * 'on' then make sure 'course_materials' is not empty.
				 */
				if ( ! isset( $settings_values['course_materials_enabled'] ) ) {
					$settings_values['course_materials_enabled'] = '';
				}

				if ( ! isset( $settings_values['course_materials'] ) ) {
					$settings_values['course_materials'] = '';
				}

				if ( 'on' !== $settings_values['course_materials_enabled'] ) {
					$settings_values['course_materials'] = '';
				}

				if ( ( 'on' === $settings_values['course_materials_enabled'] ) && ( empty( $settings_values['course_materials'] ) ) ) {
					$settings_values['course_materials_enabled'] = '';
				}

				/**
				 * Check the Lessons Per Page set course_lesson_per_page/course_lesson_per_page_custom. If 'course_lesson_per_page' setting is
				 * 'CUSTOM' then make sure 'course_lesson_per_page_custom' is not empty.
				 */
				if ( ! isset( $settings_values['course_lesson_per_page'] ) ) {
					$settings_values['course_lesson_per_page'] = '';
				}

				if ( ! isset( $settings_values['course_lesson_per_page_custom'] ) ) {
					$settings_values['course_lesson_per_page_custom'] = '';
				}
				if ( ! isset( $settings_values['course_topic_per_page_custom'] ) ) {
					$settings_values['course_topic_per_page_custom'] = '';
				}

				//if ( 'CUSTOM' === $settings_values['course_lesson_per_page'] ) {
				//	if ( ( empty( $settings_values['course_lesson_per_page_custom'] ) ) && ( empty( $settings_values['course_topic_per_page_custom'] ) ) ) {
				//		$settings_values['course_lesson_per_page'] = '';
				//	}
				//}

				if ( empty( $settings_values['course_lesson_per_page'] ) ) {
					$settings_values['course_lesson_per_page_custom'] = '';
					$settings_values['course_topic_per_page_custom']  = '';
				}

				/**
				 * Check Certificate choice.
				 */
				if ( ! isset( $settings_values['certificate'] ) ) {
					$settings_values['certificate'] = '';
				}
				if ( '-1' === $settings_values['certificate'] ) {
					$settings_values['certificate'] = '';
				}

				if ( ! isset( $settings_values['course_lesson_order_enabled'] ) ) {
					$settings_values['course_lesson_order_enabled'] = '';
				}

				if ( ! isset( $settings_values['course_lesson_orderby'] ) ) {
					$settings_values['course_lesson_orderby'] = '';
				}

				if ( ! isset( $settings_values['course_lesson_order'] ) ) {
					$settings_values['course_lesson_order'] = '';
				}

				if ( 'on' === $settings_values['course_lesson_order_enabled'] ) {
					if ( ( empty( $settings_values['course_lesson_orderby'] ) ) || ( empty( $settings_values['course_lesson_order'] ) ) ) {
						$settings_values['course_lesson_order_enabled'] = '';
					}
				}

				if ( empty( $settings_values['course_lesson_order_enabled'] ) ) {
					$settings_values['course_lesson_orderby'] = '';
					$settings_values['course_lesson_order']   = '';
				}

				apply_filters( 'learndash_settings_save_values', $settings_values, $this->settings_metabox_key );
			}

			return $settings_values;
		}

		// End of functions.
	}

	add_filter(
		'learndash_post_settings_metaboxes_init_' . learndash_get_post_type_slug( 'course' ),
		function( $metaboxes = array() ) {
			if ( ( ! isset( $metaboxes['LearnDash_Settings_Metabox_Course_Display_Content'] ) ) && ( class_exists( 'LearnDash_Settings_Metabox_Course_Display_Content' ) ) ) {
				$metaboxes['LearnDash_Settings_Metabox_Course_Display_Content'] = LearnDash_Settings_Metabox_Course_Display_Content::add_metabox_instance();
			}

			return $metaboxes;
		},
		50,
		1
	);
}
