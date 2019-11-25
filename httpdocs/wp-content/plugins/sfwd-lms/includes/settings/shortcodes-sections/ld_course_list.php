<?php
if ( ( class_exists( 'LearnDash_Shortcodes_Section' ) ) && ( !class_exists( 'LearnDash_Shortcodes_Section_ld_course_list' ) ) ) {
	class LearnDash_Shortcodes_Section_ld_course_list extends LearnDash_Shortcodes_Section {

		function __construct( $fields_args = array() ) {
			$this->fields_args = $fields_args;

			$this->shortcodes_section_key 			= 	'ld_course_list';
			$this->shortcodes_section_title 		= 	sprintf( esc_html_x( '%s List', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) );
			$this->shortcodes_section_type			=	1;
			$this->shortcodes_section_description	=	sprintf( wp_kses_post( _x( "This shortcode shows list of %s. You can use this shortcode on any page if you don't want to use the default <code>/%s/</code> page.", 'placeholders: courses, courses (URL slug)', 'learndash' ) ), learndash_get_custom_label_lower( 'courses' ), LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Section_Permalinks', 'courses' ) );
			
			parent::__construct(); 
		}
		
		function init_shortcodes_section_fields() {
			$this->shortcodes_option_fields = array(
				'orderby' => array(
					'id'			=>	$this->shortcodes_section_key . '_orderby',
					'name'  		=> 	'orderby', 
					'type'  		=> 	'select',
					'label' 		=> 	esc_html__( 'Order by', 'learndash' ),
					'help_text'		=>	wp_kses_post( __( 'See <a target="_blank" href="https://codex.wordpress.org/Class_Reference/WP_Query#Order_.26_Orderby_Parameters">the full list of available orderby options here.</a>', 'learndash' ) ),
					'value' 		=> 	'ID',
					'options'		=>	array(
											''				=>	esc_html__('ID - Order by post id. (default)', 'learndash'),
											'title'			=>	esc_html__('Title - Order by post title', 'learndash'),
											'date'			=>	esc_html__('Date - Order by post date', 'learndash'),
											'menu_order'	=>	esc_html__('Menu - Order by Page Order Value', 'learndash')
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
											''				=>	esc_html__('DESC - highest to lowest values (default)', 'learndash'),
											'ASC'			=>	esc_html__('ASC - lowest to highest values', 'learndash'),
										)
				),
				'num' => array(
					'id'			=>	$this->shortcodes_section_key . '_num',
					'name'  		=> 	'num', 
					'type'  		=> 	'number',
					'label' 		=> 	sprintf( esc_html_x('%s Per Page', 'placeholders: courses', 'learndash' ), LearnDash_Custom_Label::get_label( 'courses' ) ),
					'help_text'		=>	sprintf( esc_html_x( '%s per page. Default is %d. Set to zero for all.', 'placeholders: courses, default per page', 'learndash' ), LearnDash_Custom_Label::get_label( 'courses' ), LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Per_Page', 'per_page' ) ),
					'value' 		=> 	'',
					'class'			=>	'small-text',
					'attrs'			=>	array(
											'min' => 0,
											'step' => 1
										)
				),

				'mycourses' => array(
					'id'			=>	$this->shortcodes_section_key . '_mycourses',
					'name'  		=> 	'mycourses', 
					'type'  		=> 	'select',
					'label' 		=> 	sprintf( esc_html_x( 'My %s', 'placeholder: Courses', 'learndash'), LearnDash_Custom_Label::get_label( 'courses' ) ),
					'help_text'		=>	sprintf( esc_html_x( 'show current user\'s %s.', 'placeholders: courses', 'learndash' ), learndash_get_custom_label_lower( 'courses' ) ),
					'value' 		=> 	'',
					'options'		=>	array(
											''				=>	sprintf( esc_html_x('Show All %s (default)', 'placeholders: courses', 'learndash'), learndash_get_custom_label_lower( 'Courses' ) ),
											'enrolled'		=>	sprintf( esc_html_x('Show Enrolled %s only', 'placeholders: courses', 'learndash' ), learndash_get_custom_label_lower( 'Courses' ) ),
											'not-enrolled'	=>	sprintf( esc_html_x('Show not-Enrolled %s only', 'placeholders: courses', 'learndash' ), learndash_get_custom_label_lower( 'Courses' ) ),
										)
				),
				'show_content' => array(
					'id'			=>	$this->shortcodes_section_key . 'show_content',
					'name'  		=> 	'show_content', 
					'type'  		=> 	'select',
					'label' 		=> 	sprintf( esc_html_x('Show %s Content', 'placeholder: Course', 'learndash'), LearnDash_Custom_Label::get_label( 'course' ) ),
					'help_text'		=>	sprintf( esc_html_x( 'shows %s content.', 'placeholders: course', 'learndash' ), learndash_get_custom_label_lower( 'course' ) ),
					'value' 		=> 	'true',
					'options'		=>	array(
											'' => esc_html__('Yes (default)', 'learndash'),
											'false' =>	esc_html__('No', 'learndash'),
										)
				),
				'show_thumbnail' => array(
					'id'			=>	$this->shortcodes_section_key . '_show_thumbnail',
					'name'  		=> 	'show_thumbnail', 
					'type'  		=> 	'select',
					'label' 		=> 	sprintf( esc_html_x('Show %s Thumbnail', 'placeholder: Course', 'learndash'), LearnDash_Custom_Label::get_label( 'course' ) ),
					'help_text'		=>	sprintf( esc_html_x( 'shows a %s thumbnail.', 'placeholders: course', 'learndash' ), learndash_get_custom_label_lower( 'course' ) ),
					'value' 		=> 	'true',
					'options'		=>	array(
											'' => esc_html__('Yes (default)', 'learndash'),
											'false' =>	esc_html__('No', 'learndash'),
										)
				),
			);

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


			if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Courses_Taxonomies', 'ld_course_category' ) == 'yes') {

				$this->shortcodes_option_fields['course_category_name'] = array(
					'id'			=>	$this->shortcodes_section_key . 'course_category_name',
					'name'  		=> 	'course_category_name', 
					'type'  		=> 	'text',
					'label' 		=> 	sprintf( esc_html_x('%s Category Slug', 'placeholder: Course', 'learndash'), LearnDash_Custom_Label::get_label( 'course' ) ),
					'help_text'		=>	sprintf( esc_html_x( 'shows %s with mentioned category slug.', 'placeholders: courses', 'learndash' ), learndash_get_custom_label_lower( 'courses' ) ),
					'value' 		=> 	'',
				);

				$this->shortcodes_option_fields['course_cat'] = array(
					'id'			=>	$this->shortcodes_section_key . 'course_cat',
					'name'  		=> 	'course_cat', 
					'type'  		=> 	'number',
					'label' 		=> 	sprintf( esc_html_x('%s Category ID', 'placeholder: Course', 'learndash'), LearnDash_Custom_Label::get_label( 'course' ) ),
					'help_text'		=>	sprintf( esc_html_x( 'shows %s with mentioned category id.', 'placeholders: courses', 'learndash' ), learndash_get_custom_label_lower( 'courses' ) ),
					'value' 		=> 	'',
					'class'			=>	'small-text'
				);

				$this->shortcodes_option_fields['course_categoryselector'] = array(
					'id'			=>	$this->shortcodes_section_key . 'course_categoryselector',
					'name'  		=> 	'course_categoryselector', 
					'type'  		=> 	'checkbox',
					'label' 		=> 	sprintf( esc_html_x('%s Category Selector', 'placeholder: Course', 'learndash'), LearnDash_Custom_Label::get_label( 'course' ) ),
					'help_text'		=>	sprintf( esc_html_x( 'shows a %s category dropdown.', 'placeholders: course', 'learndash' ), learndash_get_custom_label_lower( 'course' ) ),
					'value' 		=> 	'',
					'options'		=>	array(
											'true'	=>	esc_html__('Yes', 'learndash'),
										)
				);
			}
		
			if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Courses_Taxonomies', 'ld_course_tag' ) == 'yes') {
				$this->shortcodes_option_fields['course_tag'] = array(
					'id'			=>	$this->shortcodes_section_key . 'course_tag',
					'name'  		=> 	'course_tag', 
					'type'  		=> 	'text',
					'label' 		=> 	sprintf( esc_html_x( '%s Tag Slug', 'placeholder: Course', 'learndash'), LearnDash_Custom_Label::get_label( 'course' ) ),
					'help_text'		=>	sprintf( esc_html_x( 'shows %s with mentioned tag slug.', 'placeholders: courses', 'learndash' ), learndash_get_custom_label_lower( 'courses' ) ),
					'value' 		=> 	'',
				);

				$this->shortcodes_option_fields['course_tag_id'] = array(
					'id'			=>	$this->shortcodes_section_key . 'course_tag_id',
					'name'  		=> 	'course_tag_id', 
					'type'  		=> 	'number',
					'label' 		=> 	sprintf( esc_html_x('%s Tag ID', 'placeholder: Course', 'learndash'), LearnDash_Custom_Label::get_label( 'course' ) ),
					'help_text'		=>	sprintf( esc_html_x( 'shows %s with mentioned tag id.', 'placeholders: courses', 'learndash' ), learndash_get_custom_label_lower( 'courses' ) ),
					'value' 		=> 	'',
					'class'			=>	'small-text'
				);
			}
		
			if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Courses_Taxonomies', 'wp_post_category' ) == 'yes') {
				
				$this->shortcodes_option_fields['category_name'] = array(
					'id'			=>	$this->shortcodes_section_key . 'category_name',
					'name'  		=> 	'category_name', 
					'type'  		=> 	'text',
					'label' 		=> 	esc_html__('WP Category Slug', 'learndash'),
					'help_text'		=>	sprintf( esc_html_x( 'shows %s with mentioned WP category slug.', 'placeholders: courses', 'learndash' ), learndash_get_custom_label_lower( 'courses' ) ),
					'value' 		=> 	'',
				);
				
				$this->shortcodes_option_fields['cat'] = array(
					'id'			=>	$this->shortcodes_section_key . 'cat',
					'name'  		=> 	'cat', 
					'type'  		=> 	'number',
					'label' 		=> 	esc_html__('WP Category ID', 'learndash'),
					'help_text'		=>	sprintf( esc_html_x( 'shows %s with mentioned WP category id.', 'placeholders: courses', 'learndash' ), learndash_get_custom_label_lower( 'courses' ) ),
					'value' 		=> 	'',
					'class'			=>	'small-text'
				);
								
				$this->shortcodes_option_fields['categoryselector'] = array(
					'id'			=>	$this->shortcodes_section_key . 'categoryselector',
					'name'  		=> 	'categoryselector', 
					'type'  		=> 	'checkbox',
					'label' 		=> 	esc_html__('WP Category Selector', 'learndash'),
					'help_text'		=>	esc_html__( 'shows a WP category dropdown.', 'learndash' ),
					'value' 		=> 	'',
					'options'		=>	array(
											'true'	=>	esc_html__('Yes', 'learndash'),
										)
				);
			}
		
			if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Courses_Taxonomies', 'wp_post_tag' ) == 'yes') {
				$this->shortcodes_option_fields['tag'] = array(
					'id'			=>	$this->shortcodes_section_key . 'tag',
					'name'  		=> 	'tag', 
					'type'  		=> 	'text',
					'label' 		=> 	esc_html__( 'WP Tag Slug', 'learndash'),
					'help_text'		=>	sprintf( esc_html_x( 'shows %s with mentioned WP tag slug.', 'placeholders: courses', 'learndash' ), learndash_get_custom_label_lower( 'courses' ) ),
					'value' 		=> 	'',
				);

				$this->shortcodes_option_fields['tag_id'] = array(
					'id'			=>	$this->shortcodes_section_key . 'tag_id',
					'name'  		=> 	'tag_id', 
					'type'  		=> 	'number',
					'label' 		=> 	esc_html__('WP Tag ID', 'learndash'),
					'help_text'		=>	sprintf( esc_html_x( 'shows %s with mentioned WP tag id.', 'placeholders: courses', 'learndash' ), learndash_get_custom_label_lower( 'courses' ) ),
					'value' 		=> 	'',
					'class'			=>	'small-text'
				);
			}
		
			$this->shortcodes_option_fields = apply_filters( 'learndash_settings_fields', $this->shortcodes_option_fields, $this->shortcodes_section_key );
			
			parent::init_shortcodes_section_fields();
		}
	}
}
