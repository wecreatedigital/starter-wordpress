<?php
/**
 * LearnDash Settings Metabox for Course Access Settings.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Metabox' ) ) && ( ! class_exists( 'LearnDash_Settings_Metabox_Course_Access_Settings' ) ) ) {
	/**
	 * Class to create the settings section.
	 */
	class LearnDash_Settings_Metabox_Course_Access_Settings extends LearnDash_Settings_Metabox {

		/**
		 * Public constructor for class
		 */
		public function __construct() {
			// What screen ID are we showing on.
			$this->settings_screen_id = 'sfwd-courses';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_metabox_key = 'learndash-course-access-settings';

			// Section label/header.
			$this->settings_section_label = sprintf(
				// translators: placeholder: Course.
				esc_html_x( '%s Access Settings', 'placeholder: Course', 'learndash' ),
				learndash_get_custom_label( 'course' )
			);

			$this->settings_section_description = sprintf(
				// translators: placeholder: course.
				esc_html_x( 'Controls how users will gain access to the %s', 'placeholder: course', 'learndash' ),
				learndash_get_custom_label_lower( 'course' )
			);

			add_filter( 'learndash_metabox_save_fields_' . $this->settings_metabox_key, array( $this, 'filter_saved_fields' ), 30, 3 );
			add_filter( 'learndash_admin_settings_data', array( $this, 'learndash_admin_settings_data' ), 30, 1 );

			// Map internal settings field ID to legacy field ID.
			$this->settings_fields_map = array(
				// New fields
				'course_access_list_enabled'        => 'course_access_list_enabled',

				// Legacy fields
				'course_price_type'                 => 'course_price_type',
				'course_price_type_paynow_price'    => 'course_price',
				'course_price_type_subscribe_price' => 'course_price',
				'course_price_type_subscribe_billing_cycle' => 'course_price_billing_cycle',
				'course_price_type_closed_custom_button_label' => 'custom_button_label',
				'course_price_type_closed_custom_button_url' => 'custom_button_url',
				'course_price_type_closed_price'    => 'course_price',
				'course_prerequisite_enabled'       => 'course_prerequisite_enabled',
				'course_prerequisite'               => 'course_prerequisite',
				'course_prerequisite_compare'       => 'course_prerequisite_compare',
				'course_points_enabled'             => 'course_points_enabled',
				'course_points'                     => 'course_points',
				'course_points_access'              => 'course_points_access',
				'expire_access'                     => 'expire_access',
				'expire_access_days'                => 'expire_access_days',
				'expire_access_delete_progress'     => 'expire_access_delete_progress',
				'course_disable_lesson_progression' => 'course_disable_lesson_progression',
				'course_access_list'                => 'course_access_list',
			);

			parent::__construct();
		}

		/**
		 * Add script data to array.
		 *
		 * @since 3.0
		 * @param array $script_data Script data array to be sent out to browser.
		 * @return array $script_data
		 */
		public function learndash_admin_settings_data( $script_data = array() ) {

			$script_data['valid_recurring_paypal_day_range']   = esc_html__( 'Valid range is 1 to 90 when the Billing Cycle is set to days.', 'learndash' );
			$script_data['valid_recurring_paypal_week_range']  = esc_html__( 'Valid range is 1 to 52 when the Billing Cycle is set to weeks.', 'learndash' );
			$script_data['valid_recurring_paypal_month_range'] = esc_html__( 'Valid range is 1 to 24 when the Billing Cycle is set to months.', 'learndash' );
			$script_data['valid_recurring_paypal_year_range']  = esc_html__( 'Valid range is 1 to 5 when the Billing Cycle is set to years.', 'learndash' );

			return $script_data;
		}

		/**
		 * Initialize the metabox settings values.
		 */
		public function load_settings_values() {
			parent::load_settings_values();
			if ( true === $this->settings_values_loaded ) {

				if ( ! isset( $this->setting_option_values['course_points_enabled'] ) ) {
					$this->setting_option_values['course_points_enabled'] = '';
				}
				if ( ! isset( $this->setting_option_values['expire_access'] ) ) {
					$this->setting_option_values['expire_access'] = '';
				}
				if ( ! isset( $this->setting_option_values['expire_access_delete_progress'] ) ) {
					$this->setting_option_values['expire_access_delete_progress'] = '';
				}
				if ( ! isset( $this->setting_option_values['course_access_list_enabled'] ) ) {
					$this->setting_option_values['course_access_list_enabled'] = '';
				}

				if ( ! isset( $this->setting_option_values['course_price_type_paynow_price'] ) ) {
					$this->setting_option_values['course_price_type_paynow_price'] = '';
				}

				if ( ! isset( $this->setting_option_values['course_price_type_subscribe_price'] ) ) {
					$this->setting_option_values['course_price_type_subscribe_price'] = '';
				}

				if ( ! isset( $this->setting_option_values['course_price_type_closed_price'] ) ) {
					$this->setting_option_values['course_price_type_closed_price'] = '';
				}

				if ( ! isset( $this->setting_option_values['course_price_type_closed_custom_button_url'] ) ) {
					$this->setting_option_values['course_price_type_closed_custom_button_url'] = '';
				}

				if ( ! isset( $this->setting_option_values['course_price_type'] ) ) {
					$this->setting_option_values['course_price_type'] = 'open';
				}

				if ( ! isset( $this->setting_option_values['course_prerequisite_enabled'] ) ) {
					$this->setting_option_values['course_prerequisite_enabled'] = '';
				}

				if ( ! isset( $this->setting_option_values['course_prerequisite'] ) ) {
					$this->setting_option_values['course_prerequisite'] = '';
				}
				if ( ! isset( $this->setting_option_values['course_prerequisite_compare'] ) ) {
					$this->setting_option_values['course_prerequisite_compare'] = 'ANY';
				}

				if ( ! isset( $this->setting_option_values['course_points_access'] ) ) {
					$this->setting_option_values['course_points_access'] = '';
				}

				if ( ! isset( $this->setting_option_values['course_points'] ) ) {
					$this->setting_option_values['course_points'] = '';
				}

				if ( ! isset( $this->setting_option_values['expire_access_days'] ) ) {
					$this->setting_option_values['expire_access_days'] = '';
				}

				if ( ! isset( $this->setting_option_values['course_access_list'] ) ) {
					$this->setting_option_values['course_access_list'] = '';
				}

				if ( ! isset( $this->setting_option_values['course_price_type_closed_custom_button_label'] ) ) {
					$this->setting_option_values['course_price_type_closed_custom_button_label'] = '';
				}
			}
		}

		/**
		 * Initialize the metabox settings fields.
		 */
		public function load_settings_fields() {
			global $sfwd_lms;

			$this->settings_sub_option_fields = array();

			$select_course_options = $sfwd_lms->select_a_course();
			if ( ( defined( 'LEARNDASH_SELECT2_LIB' ) ) && ( true === apply_filters( 'learndash_select2_lib', LEARNDASH_SELECT2_LIB ) ) ) {
				$select_course_options_default = sprintf(
					// translators: placeholder: course.
					esc_html_x( 'Search or select a %s…', 'placeholder: course', 'learndash' ),
					learndash_get_custom_label( 'course' )
				);
			} else {
				$select_course_options_default = array(
					'' => sprintf(
						// translators: placeholder: course.
						esc_html_x( 'Select %s', 'placeholder: course', 'learndash' ),
						learndash_get_custom_label( 'course' )
					),
				);
				if ( ( is_array( $select_course_options ) ) && ( ! empty( $select_course_options ) ) ) {
					$select_course_options = $select_course_options_default + $select_course_options;
				} else {
					$select_course_options = $select_course_options_default;
				}
				$select_course_options_default = '';
			}

			$this->setting_option_fields = array(
				'course_price_type_paynow_price' => array(
					'name'    => 'course_price_type_paynow_price',
					'label'   => sprintf(
						// translators: placeholder: Course.
						esc_html_x( '%s Price', 'placeholder: Course', 'learndash' ),
						learndash_get_custom_label( 'course' )
					),
					'type'    => 'text',
					'class'   => '-medium',
					'value'   => $this->setting_option_values['course_price_type_paynow_price'],
					'default' => '',
				),
			);
			parent::load_settings_fields();
			$this->settings_sub_option_fields['course_price_type_paynow_fields'] = $this->setting_option_fields;

			$this->setting_option_fields = array(
				'course_price_type_subscribe_price' => array(
					'name'    => 'course_price_type_subscribe_price',
					'label'   => sprintf(
						// translators: placeholder: Course.
						esc_html_x( '%s Price', 'placeholder: Course', 'learndash' ),
						learndash_get_custom_label( 'course' )
					),
					'type'    => 'text',
					'class'   => '-medium',
					'value'   => $this->setting_option_values['course_price_type_subscribe_price'],
					'default' => '',
				),
				'course_price_type_subscribe_billing_cycle' => array(
					'name'  => 'course_price_type_subscribe_billing_cycle',
					'label' => esc_html__( 'Billing Cycle', 'learndash' ),
					'type'  => 'custom',
					'html'  => $sfwd_lms->learndash_course_price_billing_cycle_html(),
				),
			);
			parent::load_settings_fields();
			$this->settings_sub_option_fields['course_price_type_subscribe_fields'] = $this->setting_option_fields;

			$this->setting_option_fields = array(
				/*
				'course_price_type_closed_custom_button_label' => array(
					'name' => 'course_price_type_closed_custom_button_label',
					'label' => esc_html__( 'Label (optional)', 'learndash' ),
					'type' => 'text',
					'placeholder' => learndash_get_custom_label( 'button_take_this_course' ),
					'value' => $this->setting_option_values['course_price_type_closed_custom_button_label'],
					'help_text' => esc_html__( 'Label displayed in the Course Grid listing. Requires Course Grid add-on.', 'learndash'),
					'default' => '',
				),
				*/
				'course_price_type_closed_price' => array(
					'name'    => 'course_price_type_closed_price',
					'label'   => sprintf(
						// translators: placeholder: Course.
						esc_html_x( '%s Price', 'placeholder: Course', 'learndash' ),
						learndash_get_custom_label( 'course' )
					),
					'type'    => 'text',
					'class'   => '-medium',
					'value'   => $this->setting_option_values['course_price_type_closed_price'],
					'default' => '',
				),
				'course_price_type_closed_custom_button_url' => array(
					'name'      => 'course_price_type_closed_custom_button_url',
					'label'     => esc_html__( 'Button URL', 'learndash' ),
					'type'      => 'url',
					'class'   => 'full-text',
					'value'     => $this->setting_option_values['course_price_type_closed_custom_button_url'],
					'help_text' => sprintf(
						// translators: placeholder: "Take this Course" button label
						esc_html_x( 'Redirect the "%s" button to a specific URL.', 'placeholder: "Take this Course" button label', 'learndash' ),
						learndash_get_custom_label( 'button_take_this_course' )
					),
					'default'   => '',
				),
			);
			/*
			if ( ! defined( 'LEARNDASH_COURSE_GRID_VERSION' ) ) {
				unset( $this->setting_option_fields['course_price_type_closed_custom_button_label'] );
			}
			*/

			parent::load_settings_fields();
			$this->settings_sub_option_fields['course_price_type_closed_fields'] = $this->setting_option_fields;

			$this->setting_option_fields = array(
				'course_price_type'             => array(
					'name'    => 'course_price_type',
					'label'   => esc_html__( 'Access Mode', 'learndash' ),
					'type'    => 'radio',
					'value'   => $this->setting_option_values['course_price_type'],
					'default' => 'open',
					'options' => array(
						'open'      => array(
							'label'       => esc_html__( 'Open', 'learndash' ),
							'description' => sprintf(
								// translators: placeholder: course.
								esc_html_x( 'The %s is not protected. Any user can access its content without the need to be logged-in or enrolled.', 'placeholder: course', 'learndash' ),
								learndash_get_custom_label_lower( 'course' )
							),
						),
						'free'      => array(
							'label'       => esc_html__( 'Free', 'learndash' ),
							'description' => sprintf(
								// translators: placeholder: course.
								esc_html_x( 'The %s is protected. Registration and enrollment are required in order to access the content.', 'placeholder: course', 'learndash' ),
								learndash_get_custom_label_lower( 'course' )
							),
						),
						'paynow'    => array(
							'label'               => esc_html__( 'Buy now', 'learndash' ),
							'description'         => sprintf(
								// translators: placeholder: course, course.
								esc_html_x( 'The %1$s is protected via the LearnDash built-in PayPal and/or Stripe. Users need to purchase the %2$s (one-time fee) in order to gain access.', 'placeholder: course, course', 'learndash' ),
								learndash_get_custom_label_lower( 'course' ),
								learndash_get_custom_label_lower( 'course' )
							),
							'inline_fields'       => array(
								'course_price_type_paynow' => $this->settings_sub_option_fields['course_price_type_paynow_fields'],
							),
							'inner_section_state' => ( 'paynow' === $this->setting_option_values['course_price_type'] ) ? 'open' : 'closed',
						),
						'subscribe' => array(
							'label'               => esc_html__( 'Recurring', 'learndash' ),
							'description'         => sprintf(
								// translators: placeholder: course, course.
								esc_html_x( 'The %1$s is protected via the LearnDash built-in PayPal and/or Stripe. Users need to purchase the %2$s (recurring fee) in order to gain access.', 'placeholder: course, course', 'learndash' ),
								learndash_get_custom_label_lower( 'course' ),
								learndash_get_custom_label_lower( 'course' )
							),
							'inline_fields'       => array(
								'course_price_type_subscribe' => $this->settings_sub_option_fields['course_price_type_subscribe_fields'],
							),
							'inner_section_state' => ( 'subscribe' === $this->setting_option_values['course_price_type'] ) ? 'open' : 'closed',
						),
						'closed'    => array(
							'label'               => esc_html__( 'Closed', 'learndash' ),
							'description'         => sprintf(
								// translators: placeholder: course.
								esc_html_x( 'The %s can only be accessed through admin enrollment (manual), group enrollment, or integration (shopping cart or membership) enrollment. No enrollment button will be displayed, unless a URL is set (optional).', 'placeholder: course', 'learndash' ),
								learndash_get_custom_label_lower( 'course' )
							),
							'inline_fields'       => array(
								'course_price_type_closed' => $this->settings_sub_option_fields['course_price_type_closed_fields'],
							),
							'inner_section_state' => ( 'closed' === $this->setting_option_values['course_price_type'] ) ? 'open' : 'closed',
						),
					),
				),
				'course_prerequisite_enabled'   => array(
					'name'                => 'course_prerequisite_enabled',
					'label'               => sprintf(
						// translators: placeholder: Course.
						esc_html_x( '%s Prerequisites', 'placeholder: Course', 'learndash' ),
						learndash_get_custom_label( 'course' )
					),
					'type'                => 'checkbox-switch',
					'value'               => $this->setting_option_values['course_prerequisite_enabled'],
					'default'             => '',
					'options'             => array(
						'on' => '',
					),
					'child_section_state' => ( 'on' === $this->setting_option_values['course_prerequisite_enabled'] ) ? 'open' : 'closed',
				),
				'course_prerequisite_compare'   => array(
					'name'           => 'course_prerequisite_compare',
					'label'          => esc_html__( 'Compare Mode', 'learndash' ),
					'type'           => 'radio',
					'default'        => 'ANY',
					'value'          => $this->setting_option_values['course_prerequisite_compare'],
					'options'        => array(
						'ANY' => array(
							'label'       => esc_html__( 'Any Selected', 'learndash' ),
							'description' => sprintf(
								// translators: placeholder: courses, course.
								esc_html_x( 'The user must complete any one of the selected %1$s in order to access this %2$s', 'placeholder: course, course', 'learndash' ),
								learndash_get_custom_label_lower( 'courses' ),
								learndash_get_custom_label_lower( 'course' )
							),
						),
						'ALL' => array(
							'label'       => esc_html__( 'All Selected', 'learndash' ),
							'description' => sprintf(
								// translators: placeholder: course, course.
								esc_html_x( 'The user must complete all selected %1$s in order to access this %2$s', 'placeholder: course, course', 'learndash' ),
								learndash_get_custom_label_lower( 'course' ),
								learndash_get_custom_label_lower( 'course' )
							),
						),
					),
					'parent_setting' => 'course_prerequisite_enabled',
				),
				'course_prerequisite'           => array(
					'name'           => 'course_prerequisite',
					'type'           => 'multiselect',
					'multiple'       => 'true',
					'default'        => '',
					'value'          => $this->setting_option_values['course_prerequisite'],
					'placeholder'    => $select_course_options_default,
					'value_type'     => 'intval',
					'label'          => sprintf(
						// translators: placeholder: Courses.
						esc_html_x( '%s to Complete', 'placeholder: courses', 'learndash' ),
						learndash_get_custom_label( 'courses' )
					),
					'parent_setting' => 'course_prerequisite_enabled',
					'options'        => $select_course_options,
				),
				'course_points_enabled'         => array(
					'name'                => 'course_points_enabled',
					'label'               => sprintf(
						// translators: placeholder: Course
						esc_html_x( '%s Points', 'placeholder: Course', 'learndash' ),
						learndash_get_custom_label( 'course' )
					),
					'type'                => 'checkbox-switch',
					'value'               => $this->setting_option_values['course_points_enabled'],
					'options'             => array(
						'on' => '',
					),
					'child_section_state' => ( 'on' === $this->setting_option_values['course_points_enabled'] ) ? 'open' : 'closed',
				),
				'course_points_access'          => array(
					'name'           => 'course_points_access',
					'label'          => esc_html__( 'Required for Access', 'learndash' ),
					'type'           => 'number',
					'value'          => $this->setting_option_values['course_points_access'],
					'default'        => 0,
					'class'          => 'small-text',
					'input_label'    => esc_html__( 'point(s)', 'learndash' ),
					'input_error'    => esc_html__( 'Value should be zero or greater with up to 2 decimal places.', 'learndash' ),
					'parent_setting' => 'course_points_enabled',
					'attrs'          => array(
						'step'        => 'any',
						'min'         => '0.00',
						//'max'         => '10.00',
						'can_decimal' => 2,
						'can_empty'   => true,
					),
					'help_text'      => sprintf(
						// translators: placeholder: course.
						esc_html_x( 'Number of points required in order to gain access to this %s.', 'placeholder: course.', 'learndash' ),
						learndash_get_custom_label_lower( 'course' )
					),
				),
				'course_points'                 => array(
					'name'           => 'course_points',
					'label'          => esc_html__( 'Awarded on Completion', 'learndash' ),
					'type'           => 'number',
					'step'           => 'any',
					'min'            => '0',
					'value'          => $this->setting_option_values['course_points'],
					'default'        => '',
					'class'          => 'small-text',
					'input_label'    => esc_html__( 'point(s)', 'learndash' ),
					'parent_setting' => 'course_points_enabled',
					'help_text'      => sprintf(
						// translators: placeholder: course.
						esc_html_x( 'Number of points awarded for completing this %s.', 'placeholder: course.', 'learndash' ),
						learndash_get_custom_label_lower( 'course' )
					),
					'input_error'    => esc_html__( 'Value should be zero or greater with up to 2 decimal places.', 'learndash' ),
					'attrs'          => array(
						'step'        => 'any',
						'min'         => '0.00',
						//'max'         => '10.00',
						'can_decimal' => 2,
						'can_empty'   => true,
					),
				),

				'expire_access'                 => array(
					'name'                => 'expire_access',
					'label'               => sprintf(
						// translators: placeholder: Course.
						esc_html_x( '%s Access Expiration', 'placeholder: Course', 'learndash' ),
						learndash_get_custom_label( 'course' )
					),
					'type'                => 'checkbox-switch',
					'options'             => array(
						'on' => '',
					),
					'value'               => $this->setting_option_values['expire_access'],
					'child_section_state' => ( 'on' === $this->setting_option_values['expire_access'] ) ? 'open' : 'closed',
				),
				'expire_access_days'            => array(
					'name'           => 'expire_access_days',
					'label'          => esc_html__( 'Access Period', 'learndash' ),
					'type'           => 'number',
					'class'          => 'small-text',
					'min'            => '0',
					'value'          => $this->setting_option_values['expire_access_days'],
					'input_label'    => esc_html__( 'days', 'learndash' ),
					'parent_setting' => 'expire_access',
					'help_text'      => sprintf(
						// translators: placeholder: course.
						esc_html_x( 'Set the number of days a user will have access to the %s from enrollment date.', 'placeholder: course.', 'learndash' ),
						learndash_get_custom_label_lower( 'course' )
					),
				),
				'expire_access_delete_progress' => array(
					'name'           => 'expire_access_delete_progress',
					'label'          => esc_html__( 'Data Deletion', 'learndash' ),
					'type'           => 'checkbox-switch',
					'options'        => array(
						'on' => sprintf(
							// translators: placeholder: course.
							esc_html_x( 'All user %s data will be deleted upon access expiration', 'placeholder: course.', 'learndash' ),
							learndash_get_custom_label_lower( 'course' )
						),
						''   => '',
					),
					'value'          => $this->setting_option_values['expire_access_delete_progress'],
					'parent_setting' => 'expire_access',
					'help_text'      => sprintf(
						// translators: placeholder: course.
						esc_html_x( 'Delete the user\'s %1$s and %2$s data when the %3$s access expires.', 'placeholder: course, quiz, course.', 'learndash' ),
						learndash_get_custom_label_lower( 'course' ),
						learndash_get_custom_label_lower( 'quiz' ),
						learndash_get_custom_label_lower( 'course' )
					),
				),
				'course_access_list_enabled'    => array(
					'name'                => 'course_access_list_enabled',
					'label'               => sprintf(
						// translators: placeholder: Course
						esc_html_x( 'Alter %s Access List', 'placeholder: Course', 'learndash' ),
						learndash_get_custom_label( 'course' )
					),
					'type'                => 'checkbox-switch',
					'options'             => array(
						'on' => sprintf(
							// translators: placeholder: Course
							esc_html_x( 'You can change the LD-%s enrollees by user ID (Proceed with caution)', 'placeholder: Course', 'learndash' ),
							learndash_get_custom_label( 'course' )
						),
						''   => '',
					),
					'value'               => $this->setting_option_values['course_access_list_enabled'],
					'default'             => '',
					'child_section_state' => ( 'on' === $this->setting_option_values['course_access_list_enabled'] ) ? 'open' : 'closed',
					'help_text'           => sprintf(
						// translators: placeholder: course.
						esc_html_x( 'Displays a list of %s enrollees by user ID. Note that not all enrollees may be reflected. We do not recommend editing this field.', 'placeholder: course.', 'learndash' ),
						learndash_get_custom_label_lower( 'course' )
					),
				),
				'course_access_list'            => array(
					'name'           => 'course_access_list',
					'type'           => 'textarea',
					'value'          => $this->setting_option_values['course_access_list'],
					'default'        => '',
					'parent_setting' => 'course_access_list_enabled',
					'placeholder'    => sprintf(
						// translators: placeholder: course.
						esc_html_x( 'Add a comma-list of user IDs to grant access to this %s', 'placeholder: course', 'learndash' ),
						learndash_get_custom_label( 'course' )
					),
					'attrs'          => array(
						'rows' => '2',
						'cols' => '57',
					),
				),
			);

			/*
			if ( isset( $_GET['course_access_list_meta'] ) ) {
				$this->setting_option_fields['course_access_list_enabled']['value'] = 'on';
				$this->setting_option_fields['course_access_list_enabled']['child_section_state'] = 'open';

				$course_access_list_meta_array = learndash_get_course_users_access_from_meta( get_the_ID() );
				if ( ! empty( $course_access_list_meta_array ) ) {
					$course_access_list_meta_array = learndash_convert_course_access_list( $course_access_list_meta_array, true );
				} else {
					$course_access_list_meta_array = array();
				}

				$course_access_list_array = learndash_convert_course_access_list( $this->setting_option_values['course_access_list'], true );
				
				error_log('course_access_list_meta_array<pre>'. print_r($course_access_list_meta_array, true) .'</pre>');
				error_log('course_access_list_array<pre>'. print_r($course_access_list_array, true) .'</pre>');
				
				$course_access_list_diff_array = array_diff( $course_access_list_meta_array, $course_access_list_array );
				if ( ! empty( $course_access_list_diff_array ) ) {
					$course_access_list_diff_str = learndash_convert_course_access_list( $course_access_list_diff_array );
				} else {
					$course_access_list_diff_str = '';
				}

				$this->setting_option_fields['course_access_list_meta'] = array(
					'name'           => 'course_access_list_meta',
					'label'          => esc_html__( 'Show Missing Users', 'learndash' ),
					'type'           => 'textarea',
					'value'          => $course_access_list_diff_str,
					'default'        => '',
					'parent_setting' => 'course_access_list_enabled',
					'attrs'          => array(
						'rows' => '2',
						'cols' => '57',
					),
				);
			} else {
				$this->setting_option_fields['course_access_list_meta'] = array(
					'name'           => 'course_access_list_meta',
					'label'          => esc_html__( 'Show Missing Users', 'learndash' ),
					'type'           => 'html',
					'value'          => '<a href="'. add_query_arg( 'course_access_list_meta', '1' ) . '">' . esc_html( 'click to show missing users', 'learndash' ) .'</a>',
					'parent_setting' => 'course_access_list_enabled',
				);
			}
			*/

			if ( false === learndash_use_legacy_course_access_list() ) {
				unset( $this->setting_option_fields['course_access_list_enabled'] ); 
				unset( $this->setting_option_fields['course_access_list'] ); 
			}

			$this->setting_option_fields = apply_filters( 'learndash_settings_fields', $this->setting_option_fields, $this->settings_metabox_key );

			parent::load_settings_fields();
		}

		protected function get_save_settings_fields_map_form_post_values( $post_values = array() ) {
			$settings_fields_map = $this->settings_fields_map;
			if ( ( isset( $post_values['course_price_type'] ) ) && ( ! empty( $post_values['course_price_type'] ) ) ) {
				if ( 'paynow' === $post_values['course_price_type'] ) {
					unset( $settings_fields_map['course_price_type_subscribe_price'] );
					unset( $settings_fields_map['course_price_type_subscribe_billing_cycle'] );
					unset( $settings_fields_map['course_price_type_closed_price'] );
					unset( $settings_fields_map['course_price_type_closed_custom_button_label'] );
					unset( $settings_fields_map['course_price_type_closed_custom_button_url'] );
				} elseif ( 'subscribe' === $post_values['course_price_type'] ) {
					unset( $settings_fields_map['course_price_type_paynow_price'] );
					unset( $settings_fields_map['course_price_type_closed_price'] );
					unset( $settings_fields_map['course_price_type_closed_custom_button_label'] );
					unset( $settings_fields_map['course_price_type_closed_custom_button_url'] );
				} elseif ( 'closed' === $post_values['course_price_type'] ) {
					unset( $settings_fields_map['course_price_type_subscribe_price'] );
					unset( $settings_fields_map['course_price_type_subscribe_billing_cycle'] );
					unset( $settings_fields_map['course_price_type_paynow_price'] );
				} else {
					unset( $settings_fields_map['course_price_type_paynow_price'] );
					unset( $settings_fields_map['course_price_type_subscribe_price'] );
					unset( $settings_fields_map['course_price_type_subscribe_billing_cycle'] );
					unset( $settings_fields_map['course_price_type_closed_price'] );
					unset( $settings_fields_map['course_price_type_closed_custom_button_label'] );
					unset( $settings_fields_map['course_price_type_closed_custom_button_url'] );
				}
			}
			return $settings_fields_map;
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

				if ( ! isset( $settings_values['course_price_type'] ) ) {
					$settings_values['course_price_type'] = '';
				}

				if ( 'paynow' === $settings_values['course_price_type'] ) {
					$settings_values['custom_button_url']       = '';
					$settings_values['course_price_billing_p3'] = '';
					$settings_values['course_price_billing_t3'] = '';
				} elseif ( 'subscribe' === $settings_values['course_price_type'] ) {
					$settings_values['custom_button_url'] = '';
				} elseif ( 'closed' === $settings_values['course_price_type'] ) {
					$settings_values['course_price_billing_p3'] = '';
					$settings_values['course_price_billing_t3'] = '';
				} else {
					$settings_values['course_price']            = '';
					$settings_values['custom_button_url']       = '';
					$settings_values['course_price_billing_p3'] = '';
					$settings_values['course_price_billing_t3'] = '';
				}

				/**
				 * Check the Course Materials set course_points_enabled/course_points/course_points_access. If 'course_points_enabled' setting is
				 * 'on' then make sure 'course_points' and 'course_points_access' are not empty.
				 */
				if ( ( isset( $settings_values['course_points_enabled'] ) ) && ( 'on' === $settings_values['course_points_enabled'] ) ) {
					if ( ( isset( $settings_values['course_points'] ) ) && ( empty( $settings_values['course_points'] ) ) && ( isset( $settings_values['course_points_access'] ) ) && ( empty( $settings_values['course_points_access'] ) ) ) {
						$settings_values['course_points_enabled'] = '';
					}
				}

				/**
				 * Check the Lessons Per Page set course_prerequisite_enabled/course_prerequisite. If 'course_prerequisite_enabled' setting is
				 * 'on' then make sure 'course_prerequisite' is not empty.
				 */
				if ( ( isset( $settings_values['course_prerequisite_enabled'] ) ) && ( 'on' === $settings_values['course_prerequisite_enabled'] ) ) {
					if ( ( isset( $settings_values['course_prerequisite'] ) ) && ( is_array( $settings_values['course_prerequisite'] ) ) && ( ! empty( $settings_values['course_prerequisite'] ) ) ) {
						$settings_values['course_prerequisite'] = array_diff( $settings_values['course_prerequisite'], array( 0 ) );
						if ( empty( $settings_values['course_prerequisite'] ) ) {
							$settings_values['course_prerequisite_enabled'] = '';
						}
					} else {
						$settings_values['course_prerequisite_enabled'] = '';
					}
				}

				/**
				 * Check the Lessons Per Page set expire_access/expire_access_days. If 'expire_access' setting is
				 * 'on' then make sure 'expire_access_days' is not empty.
				 */
				if ( ( isset( $settings_values['expire_access'] ) ) && ( 'on' === $settings_values['expire_access'] ) ) {
					if ( ( isset( $settings_values['expire_access_days'] ) ) && ( empty( $settings_values['expire_access_days'] ) ) ) {
						$settings_values['expire_access'] = '';
					}
				}

				/**
				 * Check the Lessons Per Page set expire_access/expire_access_days. If 'expire_access' setting is
				 * 'on' then make sure 'expire_access_days' is not empty.
				 */
				if ( ( isset( $settings_values['course_access_list_enabled'] ) ) && ( 'on' === $settings_values['course_access_list_enabled'] ) ) {
					if ( ( isset( $settings_values['course_access_list'] ) ) && ( empty( $settings_values['course_access_list'] ) ) ) {
						$settings_values['course_access_list_enabled'] = '';
					}
				}

				$settings_values = apply_filters( 'learndash_settings_save_values', $settings_values, $this->settings_metabox_key );
			}

			return $settings_values;
		}

		// End of functions.
	}

	add_filter(
		'learndash_post_settings_metaboxes_init_' . learndash_get_post_type_slug( 'course' ),
		function( $metaboxes = array() ) {
			if ( ( ! isset( $metaboxes['LearnDash_Settings_Metabox_Course_Access_Settings'] ) ) && ( class_exists( 'LearnDash_Settings_Metabox_Course_Access_Settings' ) ) ) {
				$metaboxes['LearnDash_Settings_Metabox_Course_Access_Settings'] = LearnDash_Settings_Metabox_Course_Access_Settings::add_metabox_instance();
			}

			return $metaboxes;
		},
		50,
		1
	);
}
