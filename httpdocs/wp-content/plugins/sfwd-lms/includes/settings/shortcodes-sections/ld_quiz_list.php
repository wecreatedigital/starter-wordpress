<?php
/**
 * LearnDash [ld_quiz_list] Shortcode options.
 *
 * @package LearnDash
 * @subpackage shortcode/ld_quiz_list
 */

if ( ( class_exists( 'LearnDash_Shortcodes_Section' ) ) && ( ! class_exists( 'LearnDash_Shortcodes_Section_ld_quiz_list' ) ) ) {
	/**
	 * Class for LearnDash Shortcode Section.
	 */
	class LearnDash_Shortcodes_Section_ld_quiz_list extends LearnDash_Shortcodes_Section {

		/**
		 * Public constructor for class.
		 */
		public function __construct( $fields_args = array() ) {
			$this->fields_args                    = $fields_args;
			$this->shortcodes_section_key         = 'ld_quiz_list';
			$this->shortcodes_section_title       = sprintf(
				// translators: placeholder: Quiz.
				esc_html_x( '%s List', 'placeholder: Quiz', 'learndash' ),
				LearnDash_Custom_Label::get_label( 'quiz' )
			);
			$this->shortcodes_section_type        = 1;
			$this->shortcodes_section_description = sprintf(
				wp_kses_post(
					// translators: placeholders: quizzes, quizzes (URL slug).
					_x( 'This shortcode shows list of %1$s. You can use this shortcode on any page if you don\'t want to use the default <code>/%2$s/</code> page.', 'placeholders: quizzes, quizzes (URL slug)', 'learndash' )
				),
				learndash_get_custom_label_lower( 'quizzes' ), LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Permalinks', 'quizzes' )
			);

			parent::__construct();
		}

		/**
		 * Initialize shortcode fields.
		 *
		 * @since 2.4.0
		 */
		public function init_shortcodes_section_fields() {
			$this->shortcodes_option_fields = array(
				'course_id' => array(
					'id'        => $this->shortcodes_section_key . '_course_id',
					'name'      => 'course_id',
					'type'      => 'number',
					'label'     => sprintf(
						// translators: placeholder: Course.
						esc_html_x( '%s ID', 'placeholder: Course', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					'help_text' => sprintf(
						// translators: placeholders: Course, Courses.
						esc_html_x( 'Enter single %1$s ID. Leave blank for all %2$s.', 'placeholders: Course, Courses', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' ), LearnDash_Custom_Label::get_label( 'courses' )
					),
					'value'     => '',
					'class'     => 'small-text',
				),

				'orderby' => array(
					'id'			=>	$this->shortcodes_section_key . '_orderby',
					'name'  		=> 	'orderby', 
					'type'  		=> 	'select',
					'label' 		=> 	esc_html__( 'Order by', 'learndash' ),
					'help_text'		=>	wp_kses_post( __( 'See <a target="_blank" href="https://codex.wordpress.org/Class_Reference/WP_Query#Order_.26_Orderby_Parameters">the full list of available orderby options here.</a>', 'learndash' ) ),
					'value' 		=> 	'ID',
					'options'		=>	array(
											''	 		 => sprintf( esc_html_x('Order by %s. (default)', 'placeholder: course', 'learndash'), learndash_get_custom_label_lower( 'course' ) ),
											'id'		 =>	esc_html__('ID - Order by post id.', 'learndash'),
											'title'		 =>	esc_html__('Title - Order by post title', 'learndash'),
											'date'		 =>	esc_html__('Date - Order by post date', 'learndash'),
											'menu_order' =>	esc_html__('Menu - Order by Page Order Value', 'learndash'),
										)
				),
				'order' => array(
					'id'			=>	$this->shortcodes_section_key . '_order',
					'name'  		=> 	'order', 
					'type'  		=> 	'select',
					'label' 		=> 	esc_html__( 'Order', 'learndash' ),
					'help_text'		=>	esc_html__( 'Order', 'learndash' ),
					'value' 		=> 	'ID',
					'options'		=>	array(
											''	 		 =>	sprintf( esc_html_x('Order per %s (default)', 'placeholder: course', 'learndash'), learndash_get_custom_label_lower( 'course' ) ),
											'DESC'		 =>	esc_html__('DESC - highest to lowest values', 'learndash'),
											'ASC'		 =>	esc_html__('ASC - lowest to highest values', 'learndash'),
										)
				),
				'num' => array(
					'id'			=>	$this->shortcodes_section_key . '_num',
					'name'  		=> 	'num', 
					'type'  		=> 	'number',
					'label' 		=> 	sprintf( esc_html_x( '%s Per Page', 'placeholders: quizzes', 'learndash'), LearnDash_Custom_Label::get_label( 'quizzes' ) ),
					'help_text'		=>	sprintf( esc_html_x( '%s per page. Default is %d. Set to zero for all.', 'placeholders: quizzes, default per page', 'learndash' ), LearnDash_Custom_Label::get_label( 'quizzes' ), LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Per_Page', 'per_page' ) ),
					'value' 		=> 	'',
					'class'			=>	'small-text',
					'attrs'			=>	array(
											'min' => 0,
											'step' => 1
										)
				),
				'show_content' => array(
					'id'			=>	$this->shortcodes_section_key . 'show_content',
					'name'  		=> 	'show_content', 
					'type'  		=> 	'select',
					'label' 		=> 	sprintf( esc_html_x('Show %s Content', 'placeholder: Quiz', 'learndash'), LearnDash_Custom_Label::get_label( 'quiz' ) ),
					'help_text'		=>	sprintf( esc_html_x( 'shows %s content.', 'placeholders: quiz', 'learndash' ), learndash_get_custom_label_lower( 'quiz' ) ),
					'value' 		=> 	'true',
					'options'		=>	array(
											'' => esc_html__('Yes (default)', 'learndash'),
											'false' =>	esc_html__('No', 'learndash'),
										)
				),
				'show_thumbnail' => array(
					'id'			=>	$this->shortcodes_section_key . 'show_thumbnail',
					'name'  		=> 	'show_thumbnail', 
					'type'  		=> 	'select',
					'label' 		=> 	sprintf( esc_html_x('Show %s Thumbnail', 'placeholder: Quiz', 'learndash'), LearnDash_Custom_Label::get_label( 'quiz' ) ),
					'help_text'		=>	sprintf( esc_html_x( 'shows a %s thumbnail.', 'placeholders: quiz', 'learndash' ), learndash_get_custom_label_lower( 'quiz' ) ),
					'value' 		=> 	'true',
					'options'		=>	array(
											'' => esc_html__('Yes (default)', 'learndash'),
											'false' =>	esc_html__('No', 'learndash'),
										)
				)
			);

			if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Courses_Builder', 'shared_steps' ) != 'yes' ) {
				foreach( $this->shortcodes_option_fields['orderby']['options'] as $option_key => $option_label ) {
					if ( empty( $option_key ) ) {
						unset( $this->shortcodes_option_fields['orderby']['options'][$option_key] );
					}
				}

				foreach( $this->shortcodes_option_fields['order']['options'] as $option_key => $option_label ) {
					if ( empty( $option_key ) ) {
						unset( $this->shortcodes_option_fields['order']['options'][$option_key] );
					}
				}
			}

			if ( defined( 'LEARNDASH_COURSE_GRID_FILE' ) ) {
				$this->shortcodes_option_fields['col'] = array(
					'id'			=>	$this->shortcodes_section_key . '_col',
					'name'  		=> 	'col', 
					'type'  		=> 	'number',
					'label' 		=> 	esc_html__('Columns','learndash'),
					'help_text'		=>	sprintf( esc_html_x( 'number of columns to show when using %s grid addon', 'placeholders: course', 'learndash' ), learndash_get_custom_label_lower( 'course' ) ),
					'value' 		=> 	'',
					'class'			=>	'small-text'
				);
			}

			if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Quizzes_Taxonomies', 'ld_quiz_category' ) == 'yes') {
				$this->shortcodes_option_fields['quiz_cat'] = array(
					'id'			=>	$this->shortcodes_section_key . '_quiz_cat',
					'name'  		=> 	'quiz_cat', 
					'type'  		=> 	'number',
					'label' 		=> 	sprintf( esc_html_x( '%s Category ID', 'placeholder: Quiz', 'learndash' ), LearnDash_Custom_Label::get_label( 'quiz' ) ),
					'help_text'		=>	sprintf( esc_html_x( 'shows %s with mentioned category id.', 'placeholders: quizzes', 'learndash' ), learndash_get_custom_label_lower( 'quizzes' ) ),
					'value' 		=> 	'',
					'class'			=>	'small-text'
				);
			
				$this->shortcodes_option_fields['quiz_category_name'] = array(
					'id'			=>	$this->shortcodes_section_key . '_quiz_category_name',
					'name'  		=> 	'quiz_category_name', 
					'type'  		=> 	'text',
					'label' 		=> 	sprintf( esc_html_x('%s Category Slug', 'placeholder: Quiz', 'learndash'), LearnDash_Custom_Label::get_label( 'quiz' ) ),
					'help_text'		=>	sprintf( esc_html_x( 'shows %s with mentioned category slug.', 'placeholders: quizzes', 'learndash' ), learndash_get_custom_label_lower( 'quizzes' ) ),
					'value' 		=> 	'',
				);
				
				$this->shortcodes_option_fields['quiz_categoryselector'] = array(
					'id'			=>	$this->shortcodes_section_key . '_quiz_categoryselector',
					'name'  		=> 	'quiz_categoryselector', 
					'type'  		=> 	'checkbox',
					'label' 		=> 	sprintf( esc_html_x('%s Category Selector', 'placeholder: Quiz', 'learndash'), LearnDash_Custom_Label::get_label( 'quiz' ) ),
					'help_text'		=>	sprintf( esc_html_x( 'shows a %s category dropdown.', 'placeholders: quiz', 'learndash' ), learndash_get_custom_label_lower( 'quiz' ) ),
					'value' 		=> 	'',
					'options'		=>	array(
											'true'	=>	esc_html__('Yes', 'learndash'),
										)
				);
			}
		
			if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Quizzes_Taxonomies', 'ld_quiz_tag' ) == 'yes') {
				$this->shortcodes_option_fields['quiz_tag_id'] = array(
					'id'			=>	$this->shortcodes_section_key . '_quiz_tag_id',
					'name'  		=> 	'quiz_tag_id', 
					'type'  		=> 	'number',
					'label' 		=> 	sprintf( esc_html_x('%s Tag ID', 'placeholder: Quizzes', 'learndash'), LearnDash_Custom_Label::get_label( 'quiz' ) ),
					'help_text'		=>	sprintf( esc_html_x( 'shows %s with mentioned tag id.', 'placeholders: quizzes', 'learndash' ), learndash_get_custom_label_lower( 'quizzes' ) ),
					'value' 		=> 	'',
					'class'			=>	'small-text'
				);

				$this->shortcodes_option_fields['quiz_tag'] = array(
					'id'			=>	$this->shortcodes_section_key . '_quiz_tag',
					'name'  		=> 	'quiz_tag', 
					'type'  		=> 	'text',
					'label' 		=> 	sprintf( esc_html_x( '%s Tag Slug', 'placeholder: Quiz', 'learndash'), LearnDash_Custom_Label::get_label( 'quiz' ) ),
					'help_text'		=>	sprintf( esc_html_x( 'shows %s with mentioned tag slug.', 'placeholders: quizzes', 'learndash' ), learndash_get_custom_label_lower( 'quizzes' ) ),
					'value' 		=> 	'',
				);
			}

			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Quizzes_Taxonomies', 'wp_post_category' ) == 'yes' ) {
				$this->shortcodes_option_fields['cat'] = array(
					'id'			=>	$this->shortcodes_section_key . '_cat',
					'name'  		=> 	'cat', 
					'type'  		=> 	'number',
					'label' 		=> 	esc_html__('WP Category ID', 'learndash'),
					'help_text'		=>	sprintf( esc_html_x( 'shows %s with mentioned WP category id.', 'placeholders: quizzes', 'learndash' ), learndash_get_custom_label_lower( 'quizzes' ) ),
					'value' 		=> 	'',
					'class'			=>	'small-text'
				);
				
				$this->shortcodes_option_fields['category_name'] = array(
					'id'			=>	$this->shortcodes_section_key . '_category_name',
					'name'  		=> 	'category_name', 
					'type'  		=> 	'text',
					'label' 		=> 	esc_html__( 'WP Category Slug', 'learndash' ),
					'help_text'		=>	sprintf( esc_html_x( 'shows %s with mentioned WP category slug.', 'placeholders: quizzes', 'learndash' ), learndash_get_custom_label_lower( 'quizzes' ) ),
					'value' 		=> 	'',
				);
				
				$this->shortcodes_option_fields['categoryselector'] = array(
					'id'			=>	$this->shortcodes_section_key . '_categoryselector',
					'name'  		=> 	'categoryselector', 
					'type'  		=> 	'checkbox',
					'label' 		=> 	esc_html__( 'WP Category Selector', 'learndash' ),
					'help_text'		=>	esc_html__( 'shows a WP category dropdown.', 'learndash' ),
					'value' 		=> 	'',
					'options'		=>	array(
											'true'	=>	esc_html__('Yes', 'learndash'),
										)
				);
			}
		
			if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Quizzes_Taxonomies', 'wp_post_tag' ) == 'yes') {
				$this->shortcodes_option_fields['tag'] = array(
					'id'			=>	$this->shortcodes_section_key . '_tag',
					'name'  		=> 	'tag', 
					'type'  		=> 	'text',
					'label' 		=> 	esc_html__( 'WP Tag Slug', 'learndash'),
					'help_text'		=>	sprintf( esc_html_x( 'shows %s with mentioned WP tag slug.', 'placeholders: quizzes', 'learndash' ), learndash_get_custom_label_lower( 'quizzes' ) ),
					'value' 		=> 	'',
				);

				$this->shortcodes_option_fields['tag_id'] = array(
					'id'			=>	$this->shortcodes_section_key . '_tag_id',
					'name'  		=> 	'tag_id', 
					'type'  		=> 	'number',
					'label' 		=> 	esc_html__('WP Tag ID', 'learndash'),
					'help_text'		=>	sprintf( esc_html_x( 'shows %s with mentioned WP tag id.', 'placeholders: quizzes', 'learndash' ), learndash_get_custom_label_lower( 'quizzes' ) ),
					'value' 		=> 	'',
					'class'			=>	'small-text'
				);
			}

			$this->shortcodes_option_fields = apply_filters( 'learndash_settings_fields', $this->shortcodes_option_fields, $this->shortcodes_section_key );
			
			parent::init_shortcodes_section_fields();
		}
	}
}
