<?php
/**
 * LearnDash Settings Section for Display Order Metabox.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Section_Lessons_Display_Order' ) ) ) {
	/**
	 * Class to create the settings section.
	 */
	class LearnDash_Settings_Section_Lessons_Display_Order extends LearnDash_Settings_Section {

		/**
		 * Protected constructor for class
		 */
		protected function __construct() {
			$this->settings_screen_id = 'sfwd-lessons_page_lessons-options';

			// The page ID (different than the screen ID).
			$this->settings_page_id = 'lessons-options';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = 'learndash_settings_lessons_display_order';

			// This is the HTML form field prefix used.
			$this->setting_field_prefix = 'learndash_settings_lessons_display_order';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'display_order';

			// Section label/header.
			$this->settings_section_label = sprintf(
				// translators: placeholder: Lesson.
				esc_html_x( '%s Display Settings', 'placeholder: Lesson', 'learndash' ),
				LearnDash_Custom_Label::get_label( 'lesson' )
			);

			parent::__construct();

			add_action( 'update_option_' . $this->setting_option_key, array( $this, 'save_settings_values' ), 20, 3 );
		}

		/**
		 * Save Settings Values.
		 *
		 * @param string $old_value Previous value for setting.
		 * @param string $value New value.
		 * @param string $option Option.
		 */
		public function save_settings_values( $old_value = '', $value = '', $option = '' ) {
			// When the Lesson Options are changed we also want to purge the transients. All of them.
			LDLMS_Transients::purge_all();
		}

		/**
		 * Initialize the metabox settings values.
		 */
		public function load_settings_values() {
			parent::load_settings_values();

			/**
			 * If all the settings are empty then this is probably the first run. So we import the
			 * settings from the previous version.
			 */
			if ( false === $this->setting_option_values ) {
				$lessons_options = array();
				$options         = get_option( 'sfwd_cpt_options' );
				if ( ( empty( $setting ) ) && ( ! empty( $options['modules']['sfwd-lessons_options'] ) ) ) {
					foreach ( $options['modules']['sfwd-lessons_options'] as $key => $val ) {
						$lessons_options[ str_replace( 'sfwd-lessons_', '', $key ) ] = $val;
					}
					$this->setting_option_values = $lessons_options;
				}
			}

			if ( ! isset( $this->setting_option_values['orderby'] ) ) {
				$this->setting_option_values['orderby'] = 'date';
			}

			if ( ! isset( $this->setting_option_values['order'] ) ) {
				$this->setting_option_values['order'] = 'DESC';
			}

			if ( ( ! isset( $this->setting_option_values['posts_per_page'] ) ) || ( is_null( $this->setting_option_values['posts_per_page'] ) ) ) {
				$this->setting_option_values['posts_per_page'] = 25;
			} else {
				$this->setting_option_values['posts_per_page'] = intval( $this->setting_option_values['posts_per_page'] );
			}
		}

		/**
		 * Initialize the metabox settings fields.
		 */
		public function load_settings_fields() {

			$this->setting_option_fields = array(
				'orderby'        => array(
					'name'      => 'orderby',
					'type'      => 'select',
					'label'     => esc_html__( 'Sort By', 'learndash' ),
					'help_text' => esc_html__( 'Choose the sort order.', 'learndash' ),
					'value'     => $this->setting_option_values['orderby'],
					'default'   => 'date',
					'options'   => array(
						'date'       => esc_html__( 'Date (default)', 'learndash' ),
						'title'      => esc_html__( 'Title', 'learndash' ),
						'menu_order' => esc_html__( 'Menu Order', 'learndash' ),
					),
				),
				'order'          => array(
					'name'      => 'order',
					'type'      => 'select',
					'label'     => esc_html__( 'Sort Direction', 'learndash' ),
					'help_text' => esc_html__( 'Choose the sort direction.', 'learndash' ),
					'value'     => $this->setting_option_values['order'],
					'default'   => 'DESC',
					'options'   => array(
						'ASC'  => esc_html__( 'Ascending', 'learndash' ),
						'DESC' => esc_html__( 'Descending (default)', 'learndash' ),
					),
				),
				'posts_per_page' => array(
					'name'      => 'posts_per_page',
					'type'      => 'number',
					'label'     => esc_html__( 'Posts Per Page', 'learndash' ),
					'help_text' => esc_html__( 'Enter the number of posts to display per page. Enter zero to display all.', 'learndash' ),
					'value'     => $this->setting_option_values['posts_per_page'],
					'class'     => 'small-text',
					'attrs'     => array(
						'step' => 1,
						'min'  => 0,
					),
				),
			);

			if ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) ) {
				$this->setting_option_fields['orderby']['attrs'] = array( 'disabled' => 'disabled' );
				$this->setting_option_fields['order']['attrs']   = array( 'disabled' => 'disabled' );
			}

			$this->setting_option_fields = apply_filters( 'learndash_settings_fields', $this->setting_option_fields, $this->settings_section_key );

			parent::load_settings_fields();
		}

		// End of functions.
	}
}
add_action(
	'learndash_settings_sections_init',
	function() {
		LearnDash_Settings_Section_Lessons_Display_Order::add_section_instance();
	}
);
