<?php
if ( ( class_exists( 'LearnDash_Shortcodes_Section' ) ) && ( !class_exists( 'LearnDash_Shortcodes_Section_ld_lesson_list' ) ) ) {
	class LearnDash_Shortcodes_Section_ld_lesson_list extends LearnDash_Shortcodes_Section {

		function __construct( $fields_args = array() ) {
			$this->fields_args = $fields_args;

			$this->shortcodes_section_key 			= 	'ld_lesson_list';
			$this->shortcodes_section_title 		= 	sprintf( esc_html_x( '%s List', 'placeholder: Lesson', 'learndash' ), LearnDash_Custom_Label::get_label( 'lesson' ) );
			$this->shortcodes_section_type			=	1;
			$this->shortcodes_section_description	=	sprintf( wp_kses_post( _x( "This shortcode shows list of %s. You can use this shortcode on any page if you don't want to use the default <code>/%s/</code> page.", 'placeholders: lessons, lessons (URL slug)', 'learndash' ) ), learndash_get_custom_label_lower( 'lessons' ), LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Section_Permalinks', 'lessons' ) );
			parent::__construct(); 
		}
		
		function init_shortcodes_section_fields() {
			$this->shortcodes_option_fields = array(
				
				'course_id' => array(
					'id'			=>	$this->shortcodes_section_key . '_course_id',
					'name'  		=> 	'course_id', 
					'type'  		=> 	'number',
					'label' 		=> 	sprintf( esc_html_x( '%s ID', 'placeholder: Course', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ),
					'help_text'		=>	sprintf( esc_html_x( 'Enter single %1$s ID. Leave blank for all %2$s.', 'placeholders: Course, Courses', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ), LearnDash_Custom_Label::get_label( 'courses' ) ),
					'value' 		=> 	'',
					'class'			=>	'small-text'
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
					'label' 		=> 	sprintf( esc_html_x( '%s Per Page', 'placeholders: lessons', 'learndash' ), LearnDash_Custom_Label::get_label( 'lessons' ) ),
					'help_text'		=>	sprintf( esc_html_x( '%1$s per page. Default is %2$d. Set to zero for all.', 'placeholders: lessons, default per page', 'learndash' ), LearnDash_Custom_Label::get_label( 'lessons' ), LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Per_Page', 'per_page' ) ),
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
					'label' 		=> 	sprintf( esc_html_x( 'Show %s Content', 'placeholder: Lesson', 'learndash'), LearnDash_Custom_Label::get_label( 'lesson' ) ),
					'help_text'		=>	sprintf( esc_html_x( 'show %s content.', 'placeholders: lesson', 'learndash' ), learndash_get_custom_label_lower( 'lesson' ) ),
					'value' 		=> 	'true',
					'options'		=>	array(
											'' => esc_html__( 'Yes (default)', 'learndash' ),
											'false' =>	esc_html__('No', 'learndash'),
										)
				),
				'show_thumbnail' => array(
					'id'			=>	$this->shortcodes_section_key . 'show_thumbnail',
					'name'  		=> 	'show_thumbnail', 
					'type'  		=> 	'select',
					'label' 		=> 	sprintf( esc_html_x( 'Show %s Thumbnail', 'placeholder: Lesson', 'learndash'), LearnDash_Custom_Label::get_label( 'lesson' ) ),
					'help_text'		=>	sprintf( esc_html_x( 'shows a %s thumbnail.', 'placeholders: lesson', 'learndash' ), learndash_get_custom_label_lower( 'lesson' ) ),
					'value' 		=> 	'true',
					'options'		=>	array(
											'' => esc_html__( 'Yes (default)', 'learndash' ),
											'false' =>	esc_html__( 'No', 'learndash' ),
										)
				),
				
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


			if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Lessons_Taxonomies', 'ld_lesson_category' ) == 'yes') {
				$this->shortcodes_option_fields['lesson_cat'] = array(
					'id'			=>	$this->shortcodes_section_key . '_lesson_cat',
					'name'  		=> 	'lesson_cat', 
					'type'  		=> 	'number',
					'label' 		=> 	sprintf( esc_html_x('%s Category ID', 'placeholder: Lesson', 'learndash'), LearnDash_Custom_Label::get_label( 'lesson' ) ),
					'help_text'		=>	sprintf( esc_html_x( 'shows %s with mentioned category id.', 'placeholders: lessons', 'learndash' ), learndash_get_custom_label_lower( 'lessons' ) ),
					'value' 		=> 	'',
					'class'			=>	'small-text'
				);
				
				$this->shortcodes_option_fields['lesson_category_name'] = array(
					'id'			=>	$this->shortcodes_section_key . '_lesson_category_name',
					'name'  		=> 	'lesson_category_name', 
					'type'  		=> 	'text',
					'label' 		=> 	sprintf( esc_html_x('%s Category Slug', 'placeholder: Lesson', 'learndash'), LearnDash_Custom_Label::get_label( 'lesson' ) ),
					'help_text'		=>	sprintf( esc_html_x( 'shows %s with mentioned category slug.', 'placeholders: lessons', 'learndash' ), learndash_get_custom_label_lower( 'lessons' ) ),
					'value' 		=> 	'',
				);
				
				$this->shortcodes_option_fields['lesson_categoryselector'] = array(
					'id'			=>	$this->shortcodes_section_key . '_lesson_categoryselector',
					'name'  		=> 	'lesson_categoryselector', 
					'type'  		=> 	'checkbox',
					'label' 		=> 	sprintf( esc_html_x('%s Category Selector', 'placeholder: Lesson', 'learndash'), LearnDash_Custom_Label::get_label( 'lesson' ) ),
					'help_text'		=>	sprintf( esc_html_x( 'shows a %s category dropdown.', 'placeholders: lesson', 'learndash' ), learndash_get_custom_label_lower( 'lesson' ) ),
					'value' 		=> 	'',
					'options'		=>	array(
											'true'	=>	esc_html__('Yes', 'learndash'),
										)
				);
			}
		
			if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Lessons_Taxonomies', 'ld_lesson_tag' ) == 'yes') {
				$this->shortcodes_option_fields['lesson_tag_id'] = array(
					'id'			=>	$this->shortcodes_section_key . '_lesson_tag_id',
					'name'  		=> 	'lesson_tag_id', 
					'type'  		=> 	'number',
					'label' 		=> 	sprintf( esc_html_x('%s Tag ID', 'placeholder: Lesson', 'learndash'), LearnDash_Custom_Label::get_label( 'Lesson' ) ),
					'help_text'		=>	sprintf( esc_html_x( 'shows %s with mentioned tag id.', 'placeholders: lessons', 'learndash' ), learndash_get_custom_label_lower( 'lessons' ) ),
					'value' 		=> 	'',
					'class'			=>	'small-text'
				);

				$this->shortcodes_option_fields['lesson_tag'] = array(
					'id'			=>	$this->shortcodes_section_key . '_lesson_tag',
					'name'  		=> 	'lesson_tag', 
					'type'  		=> 	'text',
					'label' 		=> 	sprintf( esc_html_x( '%s Tag Slug', 'placeholder: Lesson', 'learndash'), LearnDash_Custom_Label::get_label( 'lesson' ) ),
					'help_text'		=>	sprintf( esc_html_x( 'shows %s with mentioned tag slug.', 'placeholders: lessons', 'learndash' ), learndash_get_custom_label_lower( 'lessons' ) ),
					'value' 		=> 	'',
				);
			}
		
			if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Lessons_Taxonomies', 'wp_post_category' ) == 'yes') {
				
				$this->shortcodes_option_fields['cat'] = array(
					'id'			=>	$this->shortcodes_section_key . '_cat',
					'name'  		=> 	'cat', 
					'type'  		=> 	'number',
					'label' 		=> 	esc_html__('WP Category ID', 'learndash'),
					'help_text'		=>	sprintf( esc_html_x( 'shows %s with mentioned WP category id.', 'placeholders: lessons', 'learndash' ), learndash_get_custom_label_lower( 'lessons' ) ),
					'value' 		=> 	'',
					'class'			=>	'small-text'
				);
				
				$this->shortcodes_option_fields['category_name'] = array(
					'id'			=>	$this->shortcodes_section_key . '_category_name',
					'name'  		=> 	'category_name', 
					'type'  		=> 	'text',
					'label' 		=> 	esc_html__('WP Category Slug', 'learndash'),
					'help_text'		=>	sprintf( esc_html_x( 'shows %s with mentioned WP category slug.', 'placeholders: lessons', 'learndash' ), learndash_get_custom_label_lower( 'lessons' ) ),
					'value' 		=> 	'',
				);
				
				$this->shortcodes_option_fields['categoryselector'] = array(
					'id'			=>	$this->shortcodes_section_key . '_categoryselector',
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
		
			if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Lessons_Taxonomies', 'wp_post_tag' ) == 'yes') {
				$this->shortcodes_option_fields['tag'] = array(
					'id'			=>	$this->shortcodes_section_key . '_tag',
					'name'  		=> 	'tag', 
					'type'  		=> 	'text',
					'label' 		=> 	esc_html__( 'WP Tag Slug', 'learndash'),
					'help_text'		=>	sprintf( esc_html_x( 'shows %s with mentioned WP tag slug.', 'placeholders: lessons', 'learndash' ), learndash_get_custom_label_lower( 'lessons' ) ),
					'value' 		=> 	'',
				);

				$this->shortcodes_option_fields['tag_id'] = array(
					'id'			=>	$this->shortcodes_section_key . '_tag_id',
					'name'  		=> 	'tag_id', 
					'type'  		=> 	'number',
					'label' 		=> 	esc_html__('WP Tag ID', 'learndash'),
					'help_text'		=>	sprintf( esc_html_x( 'shows %s with mentioned WP tag id.', 'placeholders: lessons', 'learndash' ), learndash_get_custom_label_lower( 'lessons' ) ),
					'value' 		=> 	'',
					'class'			=>	'small-text'
				);
			}
		
			$this->shortcodes_option_fields = apply_filters( 'learndash_settings_fields', $this->shortcodes_option_fields, $this->shortcodes_section_key );
			
			parent::init_shortcodes_section_fields();
		}
	}
}
