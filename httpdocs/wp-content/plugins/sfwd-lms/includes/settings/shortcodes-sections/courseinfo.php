<?php
if ( ( class_exists( 'LearnDash_Shortcodes_Section' ) ) && ( ! class_exists( 'LearnDash_Shortcodes_Section_courseinfo' ) ) ) {
	class LearnDash_Shortcodes_Section_courseinfo extends LearnDash_Shortcodes_Section {

		public function __construct( $fields_args = array() ) {
			$this->fields_args = $fields_args;

			$this->shortcodes_section_key = 'courseinfo';
			// translators: placeholder: Course.
			$this->shortcodes_section_title = sprintf( esc_html_x( '%s Info', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) );
			$this->shortcodes_section_type  = 1;

			// translators: placeholder: course, quizzes, course.
			$this->shortcodes_section_description = sprintf( wp_kses_post( _x( 'This shortcode displays %1$s related information on the certificate. <strong>Unless specified otherwise, all points, scores and percentages relate to the %2$s associated with the %3$s.</strong>', 'placeholder: course, quizzes, course', 'learndash' ) ), learndash_get_custom_label_lower( 'course' ), learndash_get_custom_label_lower( 'quizzes' ), learndash_get_custom_label_lower( 'course' ) );

			parent::__construct();
		}

		public function init_shortcodes_section_fields() {
			$this->shortcodes_option_fields = array(
				'show'   => array(
					'id'        => $this->shortcodes_section_key . '_show',
					'name'      => 'show',
					'type'      => 'select',
					'label'     => esc_html__( 'Show', 'learndash' ),
					'help_text' => sprintf(
						// translators: placeholders: quizzes, course, quizzes, course.
						wp_kses_post( _x( 'This parameter determines the information to be shown by the shortcode.<br />cumulative - average for all %1$s of the %2$s.<br />aggregate - sum for all %3$s of the %4$s.', 'placeholders: quizzes, course, quizzes, course', 'learndash' ) ),
						learndash_get_custom_label_lower( 'quizzes' ),
						learndash_get_custom_label_lower( 'course' ),
						learndash_get_custom_label_lower( 'quizzes' ),
						learndash_get_custom_label_lower( 'course' )
					),
					'value'     => 'ID',
					'options'   => array(
						'course_title'            => esc_html__( 'Title', 'learndash' ),

						// translators: placeholder: Course.
						'course_points'           => sprintf( esc_html_x( 'Earned %s Points', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),

						// translators: placeholder: Courses
						'user_course_points'      => sprintf( esc_html_x( 'Total User %s Points', 'placeholder: Courses', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),

						'completed_on'            => esc_html__( 'Completed On (date)', 'learndash' ),
						'cumulative_score'        => esc_html__( 'Cumulative Score', 'learndash' ),
						'cumulative_points'       => esc_html__( 'Cumulative Points', 'learndash' ),
						'cumulative_total_points' => esc_html__( 'Possible Cumulative Total Points', 'learndash' ),
						'cumulative_percentage'   => esc_html__( 'Cumulative Percentage', 'learndash' ),
						'cumulative_timespent'    => esc_html__( 'Cumulative Time Spent', 'learndash' ),
						'aggregate_percentage'    => esc_html__( 'Aggregate Percentage', 'learndash' ),
						'aggregate_score'         => esc_html__( 'Aggregate Score', 'learndash' ),
						'aggregate_points'        => esc_html__( 'Aggregate Points', 'learndash' ),
						'aggregate_total_points'  => esc_html__( 'Possible Aggregate Total Points', 'learndash' ),
						'aggregate_timespent'     => esc_html__( 'Aggregate Time Spent', 'learndash' ),
					),
				),
				'format' => array(
					'id'          => $this->shortcodes_section_key . '_format',
					'name'        => 'format',
					'type'        => 'text',
					'label'       => esc_html__( 'Format', 'learndash' ),
					'help_text'   => wp_kses_post( __( 'This can be used to change the date format. Default: "F j, Y, g:i a" shows as <i>March 10, 2001, 5:16 pm</i>. See <a target="_blank" href="http://php.net/manual/en/function.date.php">the full list of available date formating strings  here.</a>', 'learndash' ) ),
					'value'       => '',
					'placeholder' => 'F j, Y, g:i a',
				),
			);

			if ( ( ! isset( $this->fields_args['typenow'] ) ) || ( in_array( $this->fields_args['typenow'], learndash_get_post_types( 'course' ) ) ) ) {
				$this->shortcodes_option_fields['course_id'] = array(
					'id'        => $this->shortcodes_section_key . '_course_id',
					'name'      => 'course_id',
					'type'      => 'number',

					// translators: placeholder: Course.
					'label'     => sprintf( esc_html_x( '%s ID', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),

					// translators: placeholders: Course.
					'help_text' => sprintf( esc_html_x( 'Enter single %s ID.', 'placeholders: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
					'value'     => '',
					'class'     => 'small-text',
					'required'  => 'required',
				);

				$this->shortcodes_option_fields['user_id'] = array(
					'id'        => $this->shortcodes_section_key . '_user_id',
					'name'      => 'user_id',
					'type'      => 'number',
					'label'     => esc_html__( 'User ID', 'learndash' ),
					'help_text' => esc_html__( 'Enter specific User ID. Leave blank for current User.', 'learndash' ),
					'value'     => '',
					'class'     => 'small-text',
				);
			}

			$this->shortcodes_option_fields['decimals'] = array(
				'id'        => $this->shortcodes_section_key . '_decimals',
				'name'      => 'decimals',
				'type'      => 'number',
				'label'     => esc_html__( 'Decimals', 'learndash' ),
				'help_text' => esc_html__( 'Number of decimal places to show. Default is 2.', 'learndash' ),
				'value'     => '',
				'class'     => 'small-text',
			);

			$this->shortcodes_option_fields = apply_filters( 'learndash_settings_fields', $this->shortcodes_option_fields, $this->shortcodes_section_key );

			parent::init_shortcodes_section_fields();
		}

		public function show_shortcodes_section_footer_extra() {
			?>
			<script>
				jQuery(document).ready(function() {
					if ( jQuery( 'form#learndash_shortcodes_form_courseinfo select#courseinfo_show' ).length) {
						jQuery( 'form#learndash_shortcodes_form_courseinfo select#courseinfo_show').change( function() {
							var selected = jQuery(this).val();
							if ( selected == 'completed_on' ) {
								jQuery( 'form#learndash_shortcodes_form_courseinfo #courseinfo_format_field').slideDown();
							} else {
								jQuery( 'form#learndash_shortcodes_form_courseinfo #courseinfo_format_field').hide();
								jQuery( 'form#learndash_shortcodes_form_courseinfo #courseinfo_format_field input').val('');
							}

							if ( ( selected == 'course_points' ) || ( selected == 'user_course_points' ) ) {
								jQuery( 'form#learndash_shortcodes_form_courseinfo #courseinfo_decimals_field').slideDown();
							} else {
								jQuery( 'form#learndash_shortcodes_form_courseinfo #courseinfo_decimals_field').hide();
								jQuery( 'form#learndash_shortcodes_form_courseinfo #courseinfo_decimals_field input').val('');
							}
						});		
						jQuery( 'form#learndash_shortcodes_form_courseinfo select#courseinfo_show').change();
					} 
				});
			</script>
			<?php
		}
	}
}
