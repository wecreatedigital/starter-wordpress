<?php
/**
 * LearnDash Settings Section for Permalink Taxonomies. These are shown are input fields on the WP Settings > Permalinks
 * page to allow override of the default slugs
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Section_Permalinks_Taxonomies' ) ) ) {
	/**
	 * Class to create the settings section.
	 */
	class LearnDash_Settings_Section_Permalinks_Taxonomies extends LearnDash_Settings_Section {

		/**
		 * Protected constructor for class
		 */
		protected function __construct() {
			$this->settings_page_id = 'permalink';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = 'learndash_settings_permalinks_taxonomies';

			// This is the HTML form field prefix used.
			$this->setting_field_prefix = 'learndash_settings_permalinks_taxonomies';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'learndash_settings_permalinks_taxonomies';

			// Section label/header.
			$this->settings_section_label = __( 'LearnDash Taxonomy Permalinks', 'learndash' );

			// Used to show the section description above the fields. Can be empty.
			$this->settings_section_description = __( 'Controls the URL slugs for the custom taxonomies used by LearnDash.', 'learndash' );

			add_action( 'admin_init', array( $this, 'admin_init' ) );

			global $wp_rewrite;
			if ( $wp_rewrite->using_permalinks() ) {
				parent::__construct();
				$this->save_settings_fields();
			}
		}

		/**
		 * Function to hook into WP admin init action.
		 */
		public function admin_init() {
			do_action( 'learndash_settings_page_init', $this->settings_page_id );
		}

		/**
		 * Function to handle metabox init.
		 *
		 * @param string $settings_screen_id Screen ID of current page.
		 */
		public function add_meta_boxes( $settings_screen_id = '' ) {
			global $wp_rewrite;
			if ( $wp_rewrite->using_permalinks() ) {

				add_meta_box(
					$this->metabox_key,
					$this->settings_section_label,
					array( $this, 'show_meta_box' ),
					$this->settings_screen_id,
					$this->metabox_context,
					$this->metabox_priority
				);
			}
		}

		/**
		 * Initialize the metabox settings values.
		 */
		public function load_settings_values() {
			parent::load_settings_values();

			if ( false === $this->setting_option_values ) {
				$this->setting_option_values = array();
			}

			$this->setting_option_values = wp_parse_args(
				$this->setting_option_values,
				array(
					'ld_course_category' => 'course-category',
					'ld_course_tag'      => 'course-tag',
					'ld_lesson_category' => 'lesson-category',
					'ld_lesson_tag'      => 'lesson-tag',
					'ld_topic_category'  => 'topic-category',
					'ld_topic_tag'       => 'topic-tag',
					'ld_quiz_category'   => 'quiz-category',
					'ld_quiz_tag'        => 'quiz-tag',
				)
			);
		}

		/**
		 * Initialize the metabox settings fields.
		 */
		public function load_settings_fields() {
			global $sfwd_lms;

			$this->setting_option_fields = array();

			// Course Taxonomies.
			$courses_taxonomies = $sfwd_lms->get_post_args_section( 'sfwd-courses', 'taxonomies' );
			if ( ( isset( $courses_taxonomies['ld_course_category'] ) ) && ( true === $courses_taxonomies['ld_course_category']['public'] ) ) {
				$this->setting_option_fields['ld_course_category'] = array(
					'name'  => 'ld_course_category',
					'type'  => 'text',
					'label' => sprintf(
						// translators: placeholder: Course.
						_x( '%s Category base', 'placeholder: Course', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					'value' => $this->setting_option_values['ld_course_category'],
					'class' => 'regular-text',
				);
			}

			if ( ( isset( $courses_taxonomies['ld_course_tag'] ) ) && ( true === $courses_taxonomies['ld_course_tag']['public'] ) ) {
				$this->setting_option_fields['ld_course_tag'] = array(
					'name'  => 'ld_course_tag',
					'type'  => 'text',
					'label' => sprintf(
						// translators: placeholder: Course.
						_x( '%s Tag base', 'placeholder: Course', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					'value' => $this->setting_option_values['ld_course_tag'],
					'class' => 'regular-text',
				);
			}

			// Lesson Taxonomies.
			$lessons_taxonomies = $sfwd_lms->get_post_args_section( 'sfwd-lessons', 'taxonomies' );
			if ( ( isset( $lessons_taxonomies['ld_lesson_category'] ) ) && ( true === $lessons_taxonomies['ld_lesson_category']['public'] ) ) {
				$this->setting_option_fields['ld_lesson_category'] = array(
					'name'  => 'ld_lesson_category',
					'type'  => 'text',
					'label' => sprintf(
						// translators: placeholder: Lesson.
						_x( '%s Category base', 'placeholder: Lesson', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'lesson' )
					),
					'value' => $this->setting_option_values['ld_lesson_category'],
					'class' => 'regular-text',
				);
			}

			if ( ( isset( $lessons_taxonomies['ld_lesson_tag'] ) ) && ( true === $lessons_taxonomies['ld_lesson_tag']['public'] ) ) {
				$this->setting_option_fields['ld_lesson_tag'] = array(
					'name'  => 'ld_lesson_tag',
					'type'  => 'text',
					'label' => sprintf(
						// translators: placeholder: Lesson.
						_x( '%s Tag base', 'placeholder: Lesson', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'lesson' )
					),
					'value' => $this->setting_option_values['ld_lesson_tag'],
					'class' => 'regular-text',
				);
			}

			// Topic Taxonomies.
			$topics_taxonomies = $sfwd_lms->get_post_args_section( 'sfwd-topic', 'taxonomies' );
			if ( ( isset( $topics_taxonomies['ld_topic_category'] ) ) && ( true === $topics_taxonomies['ld_topic_category']['public'] ) ) {
				$this->setting_option_fields['ld_topic_category'] = array(
					'name'  => 'ld_topic_category',
					'type'  => 'text',
					'label' => sprintf(
						// translators: placeholder: Topic.
						_x( '%s Category base', 'placeholder: Topic', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'topic' )
					),
					'value' => $this->setting_option_values['ld_topic_category'],
					'class' => 'regular-text',
				);
			}

			if ( ( isset( $topics_taxonomies['ld_topic_tag'] ) ) && ( true === $topics_taxonomies['ld_topic_tag']['public'] ) ) {
				$this->setting_option_fields['ld_topic_tag'] = array(
					'name'  => 'ld_topic_tag',
					'type'  => 'text',
					'label' => sprintf(
						// translators: placeholder: Topic.
						_x( '%s Tag base', 'placeholder: Topic', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'topic' )
					),
					'value' => $this->setting_option_values['ld_topic_tag'],
					'class' => 'regular-text',
				);
			}

			// Quiz Taxonomies.
			$quizzes_taxonomies = $sfwd_lms->get_post_args_section( 'sfwd-quiz', 'taxonomies' );
			if ( ( isset( $quizzes_taxonomies['ld_quiz_category'] ) ) && ( true === $quizzes_taxonomies['ld_quiz_category']['public'] ) ) {
				$this->setting_option_fields['ld_quiz_category'] = array(
					'name'  => 'ld_quiz_category',
					'type'  => 'text',
					'label' => sprintf(
						// translators: placeholder: Quiz.
						_x( '%s Category base', 'placeholder: Quiz', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'quiz' )
					),
					'value' => $this->setting_option_values['ld_quiz_category'],
					'class' => 'regular-text',
				);
			}

			if ( ( isset( $quizzes_taxonomies['ld_quiz_tag'] ) ) && ( true === $quizzes_taxonomies['ld_quiz_tag']['public'] ) ) {
				$this->setting_option_fields['ld_quiz_tag'] = array(
					'name'  => 'ld_quiz_tag',
					'type'  => 'text',
					'label' => sprintf(
						// translators: placeholder: Quiz.
						_x( '%s Tag base', 'placeholder: Quiz', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'quiz' )
					),
					'value' => $this->setting_option_values['ld_quiz_tag'],
					'class' => 'regular-text',
				);
			}

			if ( ! empty( $this->setting_option_fields ) ) {
				$this->setting_option_fields['nonce'] = array(
					'name'  => 'nonce',
					'type'  => 'hidden',
					'label' => '',
					'value' => wp_create_nonce( 'learndash_permalinks_taxonomies_nonce' ),
					'class' => 'hidden',
				);
			}

			$this->setting_option_fields = apply_filters( 'learndash_settings_fields', $this->setting_option_fields, $this->settings_section_key );

			parent::load_settings_fields();
		}

		/**
		 * Save the metabox fields. This is needed due to special processing needs.
		 */
		public function save_settings_fields() {
			if ( isset( $_POST[ $this->setting_field_prefix ] ) ) {
				if ( ( isset( $_POST[ $this->setting_field_prefix ]['nonce'] ) ) && ( wp_verify_nonce( $_POST[ $this->setting_field_prefix ]['nonce'], 'learndash_permalinks_taxonomies_nonce' ) ) ) {

					$post_fields = $_POST[ $this->setting_field_prefix ];

					foreach ( array( 'course', 'lesson', 'topic', 'quiz' ) as $slug ) {

						if ( ( isset( $post_fields[ 'ld_' . $slug . '_category' ] ) ) && ( ! empty( $post_fields[ 'ld_' . $slug . '_category' ] ) ) ) {
							$this->setting_option_values[ 'ld_' . $slug . '_category' ] = $this->esc_url( $post_fields[ 'ld_' . $slug . '_category' ] );

							learndash_setup_rewrite_flush();
						}

						if ( ( isset( $post_fields[ 'ld_' . $slug . '_tag' ] ) ) && ( ! empty( $post_fields[ 'ld_' . $slug . '_tag' ] ) ) ) {
							$this->setting_option_values[ 'ld_' . $slug . '_tag' ] = $this->esc_url( $post_fields[ 'ld_' . $slug . '_tag' ] );

							learndash_setup_rewrite_flush();
						}
					}

					update_option( $this->settings_section_key, $this->setting_option_values );
				}
			}
		}

		/**
		 * Class utility function to escape the URL
		 *
		 * @param string $value URL to Escape.
		 *
		 * @return string filtered URL.
		 */
		public function esc_url( $value = '' ) {
			if ( ! empty( $value ) ) {
				$value = esc_url_raw( trim( $value ) );
				$value = str_replace( 'http://', '', $value );
				return untrailingslashit( $value );
			}
		}
	}
}
add_action(
	'learndash_settings_sections_init',
	function() {
		LearnDash_Settings_Section_Permalinks_Taxonomies::add_section_instance();
	}
);
