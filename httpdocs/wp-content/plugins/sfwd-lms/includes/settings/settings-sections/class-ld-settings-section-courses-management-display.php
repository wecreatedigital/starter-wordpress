<?php
/**
 * LearnDash Settings Section for Course Builder Metabox.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Courses_Management_Display' ) ) ) {
	/**
	 * Class to create the settings section.
	 */
	class LearnDash_Settings_Courses_Management_Display extends LearnDash_Settings_Section {

		/**
		 * Protected constructor for class
		 */
		protected function __construct() {

			// What screen ID are we showing on.
			$this->settings_screen_id = 'sfwd-courses_page_courses-options';

			// The page ID (different than the screen ID).
			$this->settings_page_id = 'courses-options';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = 'learndash_settings_courses_management_display';

			// This is the HTML form field prefix used.
			$this->setting_field_prefix = 'learndash_settings_courses_management_display';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'course_management_display';

			// Section label/header.
			$this->settings_section_label = sprintf(
				// translators: placeholder: Course.
				esc_html_x( 'Global %s Management & Display Settings', 'placeholder: Course', 'learndash' ),
				LearnDash_Custom_Label::get_label( 'course' )
			);

			// Used to show the section description above the fields. Can be empty
			$this->settings_section_description = sprintf(
				// translators: placeholder: course.
				esc_html_x( 'Control settings for %s creation, and visual organization', 'placeholder: course', 'learndash' ),
				learndash_get_custom_label_lower( 'course' )
			);

			// Define the depreacted Class and Fields
			$this->settings_deprecated = array(
				'LearnDash_Settings_Courses_Builder' => array(
					'option_key' => 'learndash_settings_courses_builder',
					'fields'     => array(
						'enabled'      => 'course_builder_enabled',
						'shared_steps' => 'course_builder_shared_steps',
						'per_page'     => 'course_builder_per_page',
					),
				),
				'LearnDash_Settings_Section_Lessons_Display_Order' => array(
					'option_key' => 'learndash_settings_lessons_display_order',
					'fields'     => array(
						'posts_per_page' => 'course_pagination_lessons',
						'order'          => 'lesson_topic_order',
						'orderby'        => 'lesson_topic_orderby',
					),
				),
			);

			parent::__construct();
		}

		/**
		 * Initialize the metabox settings values.
		 */
		public function load_settings_values() {
			parent::load_settings_values();

			// If the settings set as a whole is empty then we set a default.
			if ( ( false === $this->setting_option_values ) || ( '' === $this->setting_option_values ) ) {
				if ( '' === $this->setting_option_values ) {
					$this->setting_option_values = array();
				}
				$this->transition_deprecated_settings();

				if ( ! isset( $this->setting_option_values['course_builder_enabled'] ) ) {
					$this->setting_option_values['course_builder_enabled'] = 'yes';
				}
			}

			if ( '' === $this->setting_option_values ) {
				$this->setting_option_values = array();
			}

			if ( ! isset( $this->setting_option_values['course_builder_enabled'] ) ) {
				$this->setting_option_values['course_builder_enabled'] = '';
			}

			if ( ! isset( $this->setting_option_values['course_builder_shared_steps'] ) ) {
				$this->setting_option_values['course_builder_shared_steps'] = '';
			}

			if ( ! isset( $this->setting_option_values['course_builder_per_page'] ) ) {
				$this->setting_option_values['course_builder_per_page'] = LEARNDASH_LMS_DEFAULT_WIDGET_PER_PAGE;
			}

			$this->setting_option_values['course_builder_per_page'] = absint( $this->setting_option_values['course_builder_per_page'] );
			if ( empty( $this->setting_option_values['course_builder_per_page'] ) ) {
				$this->setting_option_values['course_builder_per_page'] = LEARNDASH_LMS_DEFAULT_WIDGET_PER_PAGE;
			}

			if ( ! isset( $this->setting_option_values['course_pagination_lessons'] ) ) {
				if ( isset( $this->setting_option_values['lesson_per_page'] ) ) {
					$this->setting_option_values['course_pagination_lessons'] = absint( $this->setting_option_values['lesson_per_page'] );
				} else {
					$this->setting_option_values['course_pagination_lessons'] = LEARNDASH_LMS_DEFAULT_WIDGET_PER_PAGE;
				}
			}

			if ( ! isset( $this->setting_option_values['course_pagination_topics'] ) ) {
				if ( isset( $this->setting_option_values['course_pagination_lessons'] ) ) {
					$this->setting_option_values['course_pagination_topics'] = absint( $this->setting_option_values['course_pagination_lessons'] );
				} else {
					$this->setting_option_values['course_pagination_topics'] = LEARNDASH_LMS_DEFAULT_WIDGET_PER_PAGE;
				}
			}

			if ( ( LEARNDASH_LMS_DEFAULT_WIDGET_PER_PAGE === $this->setting_option_values['course_pagination_lessons'] ) && ( LEARNDASH_LMS_DEFAULT_WIDGET_PER_PAGE === $this->setting_option_values['course_pagination_topics'] ) ) {
				$this->setting_option_values['course_pagination_enabled'] = '';
			} else {
				$this->setting_option_values['course_pagination_enabled'] = 'yes';
			}

			if ( ! isset( $this->setting_option_values['lesson_topic_order'] ) ) {
				$this->setting_option_values['lesson_topic_order'] = 'ASC';
			}
			if ( ! isset( $this->setting_option_values['lesson_topic_orderby'] ) ) {
				$this->setting_option_values['lesson_topic_orderby'] = 'date';
			}

			if ( ( 'date' === $this->setting_option_values['lesson_topic_orderby'] ) && ( 'ASC' === $this->setting_option_values['lesson_topic_order'] ) ) {
				$this->setting_option_values['lesson_topic_order_enabled'] = '';
			} else {
				$this->setting_option_values['lesson_topic_order_enabled'] = 'yes';
			}
		}

		/**
		 * Initialize the metabox settings fields.
		 */
		public function load_settings_fields() {
			$this->setting_option_fields = array();

			if ( ( defined( 'LEARNDASH_COURSE_BUILDER' ) ) && ( LEARNDASH_COURSE_BUILDER === true ) ) {
				$this->setting_option_fields = array_merge(
					$this->setting_option_fields,
					array(
						'course_builder_enabled'      => array(
							'name'                => 'course_builder_enabled',
							'type'                => 'checkbox-switch',
							'label'               => sprintf(
								// translators: placeholder: Course.
								esc_html_x( '%s Builder', 'placeholder: Course', 'learndash' ),
								learndash_get_custom_label( 'course' )
							),
							'help_text'           => sprintf(
								// translators: placeholder: Lesson, Topic, Quiz, Course.
								esc_html_x( 'Manage all %1$s, %2$s, and %3$s associations within the %4$s Builder.', 'placeholder: Lesson, Topic, Quiz, Course.', 'learndash' ),
								learndash_get_custom_label( 'lesson' ),
								learndash_get_custom_label( 'topic' ),
								learndash_get_custom_label( 'quiz' ),
								learndash_get_custom_label( 'course' )
							),
							'value'               => $this->setting_option_values['course_builder_enabled'],
							'options'             => array(
								'yes' => '',
							),
							'child_section_state' => ( 'yes' === $this->setting_option_values['course_builder_enabled'] ) ? 'open' : 'closed',
						),
						'course_builder_per_page'     => array(
							'name'           => 'course_builder_per_page',
							'type'           => 'number',
							'label'          => esc_html__( 'Steps Displayed', 'learndash' ),
							'value'          => $this->setting_option_values['course_builder_per_page'],
							'class'          => 'small-text',
							'input_label'    => esc_html__( 'per page', 'learndash' ),
							'attrs'          => array(
								'step' => 1,
								'min'  => 0,
							),
							'parent_setting' => 'course_builder_enabled',
						),
						'course_builder_shared_steps' => array(
							'name'           => 'course_builder_shared_steps',
							'type'           => 'checkbox-switch',
							'label'          => sprintf(
								// translators: placeholder: Course.
								esc_html_x( 'Shared %s Steps', 'placeholder: Course', 'learndash' ),
								learndash_get_custom_label( 'course' )
							),
							'help_text'      => sprintf(
								wp_kses_post(
									// translators: placeholder: lessons, topics, quizzes, courses, course, URL to admin Permalinks.
									_x( 'Share steps (%1$s, %2$s, %3$s) across multiple %4$s. Progress is maintained on a per-%5$s basis.<br /><br />Note: Enabling this option will also enable the <a href="%6$s">nested permalinks</a> setting.', 'placeholder: lessons, topics, quizzes, courses, course, URL to admin Permalinks.', 'learndash' )
								),
								learndash_get_custom_label_lower( 'lessons' ),
								learndash_get_custom_label_lower( 'topics' ),
								learndash_get_custom_label_lower( 'quizzes' ),
								learndash_get_custom_label_lower( 'courses' ),
								learndash_get_custom_label_lower( 'course' ),
								admin_url( 'options-permalink.php#learndash_settings_permalinks_nested_urls' )
							),
							'value'          => $this->setting_option_values['course_builder_shared_steps'],
							'options'        => array(
								''    => '',
								'yes' => sprintf(
									// translators: placeholders: Lesson, topics and quizzes, courses.
									esc_html_x( '%1$s, %2$s and %3$s can be shared across multiple %4$s', 'placeholders: Lesson, topics and quizzes, courses', 'learndash' ),
									learndash_get_custom_label( 'lessons' ),
									learndash_get_custom_label_lower( 'topics' ),
									learndash_get_custom_label_lower( 'quizzes' ),
									learndash_get_custom_label_lower( 'courses' )
								),
							),
							'parent_setting' => 'course_builder_enabled',
						),
					)
				);
			}

			$this->setting_option_fields = array_merge(
				$this->setting_option_fields,
				array(
					'course_pagination_enabled' => array(
						'name'                => 'course_pagination_enabled',
						'type'                => 'checkbox-switch',
						'label'               => sprintf(
							// translators: placeholder: Course.
							esc_html_x( '%s Table Pagination', 'placeholder: Course', 'learndash' ),
							learndash_get_custom_label( 'course' )
						),
						'help_text'           => sprintf(
							// translators: placeholder: course, course.
							esc_html_x( 'Customize the pagination options for ALL %1$s content tables and %2$s navigation widgets.', 'placeholder: course, course', 'learndash' ),
							learndash_get_custom_label_lower( 'courses' ),
							learndash_get_custom_label_lower( 'courses' )
						),
						'value'               => $this->setting_option_values['course_pagination_enabled'],
						'options'             => array(
							''    => sprintf(
								// translators: placeholder: default per page number.
								esc_html_x( 'Currently showing default pagination %d', 'placeholder: default per page number', 'learndash' ),
								LEARNDASH_LMS_DEFAULT_WIDGET_PER_PAGE
							),
							'yes' => '',
						),
						'child_section_state' => ( 'yes' === $this->setting_option_values['course_pagination_enabled'] ) ? 'open' : 'closed',
					),
					'course_pagination_lessons' => array(
						'name'           => 'course_pagination_lessons',
						'type'           => 'number',
						'label'          => sprintf(
							// translators: placeholder: Lessons
							esc_html_x( '%s', 'placeholder: Lessons', 'learndash' ),
							learndash_get_custom_label( 'lessons' )
						),
						'value'          => $this->setting_option_values['course_pagination_lessons'],
						'class'          => 'small-text',
						'input_label'    => esc_html__( 'per page', 'learndash' ),
						'attrs'          => array(
							'step' => 1,
							'min'  => 0,
						),
						'parent_setting' => 'course_pagination_enabled',
					),
					'course_pagination_topics'  => array(
						'name'           => 'course_pagination_topics',
						'type'           => 'number',
						'label'          => sprintf(
							// translators: placeholder: Topics
							esc_html_x( '%s', 'placeholder: Topics', 'learndash' ),
							learndash_get_custom_label( 'topics' )
						),
						'value'          => $this->setting_option_values['course_pagination_topics'],
						'class'          => 'small-text',
						'input_label'    => esc_html__( 'per page', 'learndash' ),
						'attrs'          => array(
							'step' => 1,
							'min'  => 0,
						),
						'parent_setting' => 'course_pagination_enabled',
					),

				)
			);

			if ( 'yes' !== $this->setting_option_values['course_builder_shared_steps'] ) {
				$this->setting_option_fields = array_merge(
					$this->setting_option_fields,
					array(
						'lesson_topic_order_enabled' => array(
							'name'                => 'lesson_topic_order_enabled',
							'type'                => 'checkbox-switch',
							'label'               => sprintf(
								// translators: placeholder: Lesson, Topic.
								esc_html_x( '%1$s and %2$s Order', 'placeholder: Lesson, Topic', 'learndash' ),
								learndash_get_custom_label( 'lesson' ),
								learndash_get_custom_label( 'topic' )
							),
							'help_text'           => sprintf(
								// translators: placeholder: lessons, topics.
								esc_html_x( 'Customize the display order of %1$s and %2$s.', 'placeholder: lessons, topics', 'learndash' ),
								learndash_get_custom_label_lower( 'lessons' ),
								learndash_get_custom_label_lower( 'topics' )
							),
							'value'               => $this->setting_option_values['lesson_topic_order_enabled'],
							'options'             => array(
								''    => array(
									'label'       => sprintf(
										// translators: placeholder: Default Order By, Order.
										esc_html_x( 'Using default sorting by %1$s in %2$s order', 'placeholder: Default Order By, Order', 'learndash' ),
										'<u>Date</u>',
										'<u>Ascending</u>'
									),
									'description' => '',
								),
								'yes' => array(
									'label'       => '',
									'description' => '',
								),
							),
							'child_section_state' => ( 'yes' === $this->setting_option_values['lesson_topic_order_enabled'] ) ? 'open' : 'closed',
						),
						'lesson_topic_orderby'       => array(
							'name'           => 'lesson_topic_orderby',
							'type'           => 'select',
							'label'          => esc_html__( 'Sort By', 'learndash' ),
							'value'          => $this->setting_option_values['lesson_topic_orderby'],
							'default'        => 'menu_order',
							'options'        => array(
								'menu_order' => esc_html__( 'Menu Order', 'learndash' ),
								'date'       => esc_html__( 'Date', 'learndash' ),
								'title'      => esc_html__( 'Title', 'learndash' ),
							),
							'parent_setting' => 'lesson_topic_order_enabled',
						),
						'lesson_topic_order'         => array(
							'name'           => 'lesson_topic_order',
							'type'           => 'select',
							'label'          => esc_html__( 'Order Direction', 'learndash' ),
							'value'          => $this->setting_option_values['lesson_topic_order'],
							'default'        => 'ASC',
							'options'        => array(
								'ASC'  => esc_html__( 'Ascending', 'learndash' ),
								'DESC' => esc_html__( 'Descending', 'learndash' ),
							),
							'parent_setting' => 'lesson_topic_order_enabled',
						),
					)
				);
			}

			$this->setting_option_fields = apply_filters( 'learndash_settings_fields', $this->setting_option_fields, $this->settings_section_key );

			global $wp_rewrite;
			if ( ! $wp_rewrite->using_permalinks() ) {
				$this->setting_option_fields['shared_steps']['value'] = '';
				$this->setting_option_fields['shared_steps']['attrs'] = array( 'disabled' => 'disabled' );
			}
			parent::load_settings_fields();
		}

		/**
		 * Intercept the WP options save logic and check that we have a valid nonce.
		 *
		 * @since 3.0
		 * @param array $value Array of section fields values.
		 * @param array $old_value Array of old values.
		 * @param string $section_key Section option key should match $this->setting_option_key.
		 */
		public function section_pre_update_option( $current_values = '', $old_values = '', $option = '' ) {
			if ( $option === $this->setting_option_key ) {
				$current_values = parent::section_pre_update_option( $current_values, $old_values, $option );
				if ( $current_values !== $old_values ) {

					// Manage Course Builder, Per Page, and Share Steps.
					if ( ( isset( $current_values['course_builder_enabled'] ) ) && ( 'yes' === $current_values['course_builder_enabled'] ) ) {
						$current_values['course_builder_per_page'] = absint( $current_values['course_builder_per_page'] );
						
					} else {
						$current_values['course_builder_shared_steps'] = '';
						$current_values['course_builder_per_page']     = LEARNDASH_LMS_DEFAULT_WIDGET_PER_PAGE;
					}

					if ( ( isset( $current_values['course_builder_shared_steps'] ) ) && ( 'yes' === $current_values['course_builder_shared_steps'] ) ) {
						$current_values['lesson_topic_order_enabled'] = '';
					}

					if ( ( isset( $current_values['course_pagination_enabled'] ) ) && ( 'yes' === $current_values['course_pagination_enabled'] ) ) {
						$current_values['course_pagination_lessons'] = absint( $current_values['course_pagination_lessons'] );
						$current_values['course_pagination_topics']  = absint( $current_values['course_pagination_topics'] );

						if ( ( LEARNDASH_LMS_DEFAULT_WIDGET_PER_PAGE === $current_values['course_pagination_topics'] ) && ( LEARNDASH_LMS_DEFAULT_WIDGET_PER_PAGE === $current_values['course_pagination_lessons'] ) ) {
							$current_values['course_pagination_enabled'] = '';
						}
					} else {
						$current_values['course_pagination_lessons'] = LEARNDASH_LMS_DEFAULT_WIDGET_PER_PAGE;
						$current_values['course_pagination_topics']  = LEARNDASH_LMS_DEFAULT_WIDGET_PER_PAGE;
					}

					// Lessonand Topic Order and Order By
					if ( ( isset( $current_values['lesson_topic_order_enabled'] ) ) && ( 'yes' === $current_values['lesson_topic_order_enabled'] ) ) {
						if ( ( ! isset( $current_values['lesson_topic_order'] ) ) || ( empty( $current_values['lesson_topic_order'] ) ) ) {
							$current_values['lesson_topic_order'] = 'ASC';
						}
						if ( ( ! isset( $current_values['lesson_topic_orderby'] ) ) || ( empty( $current_values['lesson_topic_orderby'] ) ) ) {
							$current_values['lesson_topic_orderby'] = 'date';
						}

						if ( ( 'ASC' === $current_values['lesson_topic_order'] ) && ( 'date' === $current_values['lesson_topic_orderby'] ) ) {
							$current_values['lesson_topic_order_enabled'] = '';
						}
					} else {
						$current_values['lesson_topic_order']   = 'ASC';
						$current_values['lesson_topic_orderby'] = 'date';
					}
				}

				if ( ( isset( $current_values['course_builder_enabled'] ) ) && ( 'yes' === $current_values['course_builder_enabled'] ) && ( isset( $current_values['course_builder_shared_steps'] ) ) && ( 'yes' === $current_values['course_builder_shared_steps'] ) ) {

					$ld_permalink_options = get_option( 'learndash_settings_permalinks', array() );
					if ( ! isset( $ld_permalink_options['nested_urls'] ) ) {
						$ld_permalink_options['nested_urls'] = 'no';
					}

					if ( 'yes' !== $ld_permalink_options['nested_urls'] ) {
						$ld_permalink_options['nested_urls'] = 'yes';

						update_option( 'learndash_settings_permalinks', $ld_permalink_options );

						learndash_setup_rewrite_flush();
					}
				}
			}

			return $current_values;
		}
	}
}
add_action(
	'learndash_settings_sections_init',
	function() {
		LearnDash_Settings_Courses_Management_Display::add_section_instance();
	}
);
