<?php
/**
 * Shortcodes and helper functions for listing
 * courses, lessons, quizzes, and topics
 * 
 * @since 2.1.0
 * 
 * @package LearnDash\Shortcodes
 */




/**
 * Shortcode to list courses
 * 
 * @since 2.1.0
 * 
 * @param  array 	$attr 	shortcode attributes
 * @return string   		shortcode output
 */
function ld_course_list( $attr ) {
	global $learndash_shortcode_used;
	
	$attr_defaults = apply_filters( 'ld_course_list_shortcode_attr_defaults', array(
		
		'include_outer_wrapper' => 'true',
		
		'num' => false, 
		'paged' => 1,
		
		'post_type' => 'sfwd-courses', 
		'post_status' => 'publish', 
		'order' => 'DESC', 
		'orderby' => 'ID',
		 
		'user_id' => false,
		'mycourses' => null, 
		'post__in'	=> null,

		'course_id' => '',
// Not sure why these are here as there is not supported logic. 	
//		'lesson_id' => '',
//		'topic_id' => '',

		'meta_key' => '', 
		'meta_value' => '', 
		'meta_compare' => '',
		
		'tag' => '', 
		'tag_id' => 0, 
		'tag__and' => '', 
		'tag__in' => '', 
		'tag__not_in' => '', 
		'tag_slug__and' => '', 
		'tag_slug__in' => '', 

		'cat' => '', 
		'category_name' => 0, 
		'category__and' => '', 
		'category__in' => '', 
		'category__not_in' => '', 

		'tax_compare' => 'AND',
		'categoryselector' => '', 
		
		'show_thumbnail' => 'true',
		'show_content' => 'true',

		'author__in' => '', 
		'col' => '',
		'progress_bar' => 'false',
		'array' => false,
		'course_grid' => 'true',
	), $attr );

	
	$post_type_slug = 'course';
	$post_type_Class = 'LearnDash_Settings_Courses_Taxonomies';
	
	if ( ( isset( $attr['post_type'] ) ) && ( !empty( $attr['post_type'] ) ) ) {
	
	
		if ( $attr['post_type'] == learndash_get_post_type_slug( 'lesson' ) ) {
			$post_type_slug = 'lesson';
			$post_type_Class = 'LearnDash_Settings_Lessons_Taxonomies';
		} elseif ( $attr['post_type'] == learndash_get_post_type_slug( 'topic' ) ) {
			$post_type_slug = 'topic';
			$post_type_Class = 'LearnDash_Settings_Topics_Taxonomies';
		} elseif ( $attr['post_type'] == learndash_get_post_type_slug( 'quiz' ) ) {
			$post_type_slug = 'quiz';
			$post_type_Class = 'LearnDash_Settings_Quizzes_Taxonomies';
		}
	}
	
	if ( !empty( $post_type_slug ) ) {
		$attr_defaults = array_merge(
			$attr_defaults,
			array(
				$post_type_slug . '_categoryselector' => '',
				$post_type_slug . '_cat' => '',
				$post_type_slug . '_category_name' => '',
				$post_type_slug . '_category__and' => '',
				$post_type_slug . '_category__in' => '',
				$post_type_slug . '_category__not_in' => '',
	
				$post_type_slug . '_tag' => '',
				$post_type_slug . '_tag_id' => '',
				$post_type_slug . '_tag__and' => '',
				$post_type_slug . '_tag__in' => '',
				$post_type_slug . '_tag__not_in' => '',
				$post_type_slug . '_tag_slug__and' => '',
				$post_type_slug . '_tag_slug__in' => '',
			)
		);
	}
	
	$atts = shortcode_atts( $attr_defaults, $attr );
	
	if ( ( $atts['mycourses'] == 'true' ) || ( $atts['mycourses'] == 'enrolled' ) ) {
		if ( is_user_logged_in() ) {
			$atts['mycourses'] = 'enrolled';
		} else {
			return '';
		}
	} else if ( $atts['mycourses'] == 'not-enrolled' ) {
		if ( is_user_logged_in() ) {
			$atts['mycourses'] = 'not-enrolled';
		} else {
			return '';
		}
	} else {
		$atts['mycourses'] = null;
	}
	if ( $atts['post__in'] === '' )
		$atts['post__in'] = null;

	//if ( isset( $atts['num'] ) )
	//	$atts['num'] = intval( $atts['num'] );
	
	if ( $atts['num'] === false ) {
		if ( ( isset( $atts['course_id'] ) ) && ( !empty( $atts['course_id'] ) ) ) {
			$atts['num'] = learndash_get_course_lessons_per_page( intval( $atts['course_id'] ) );
		} else {
			$atts['num'] = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Per_Page', 'per_page' );
		}
	} else if ( $atts['num'] == '-1' ) {
		$atts['num'] = 0;
	} else {
		$atts['num'] = intval( $atts['num'] );
	}

	if ( $atts['num'] == 0 ) {
		$atts['num'] = -1;
	}

	$atts = apply_filters( 'ld_course_list_shortcode_attr_values', $atts, $attr );
	
	if ( is_user_logged_in() ) {
	
		if ( ( isset( $atts['user_id'] ) ) && ( $atts['user_id'] === false ) ) {
			$atts['user_id'] = get_current_user_id();
		} else if ( ( isset( $atts['user_id'] ) ) && ( $atts['user_id'] !== false ) ) {
			if ( learndash_is_admin_user() ) {
				// Good leave the user_id in place.
			} else if ( learndash_is_group_leader_user( get_current_user_id() ) ) {
				$groups = learndash_get_administrators_group_ids( get_current_user_id() );
				if ( !empty( $groups ) ) {
					$user_courses = array();
					foreach ( $groups as $group_id ) {
						if ( learndash_is_user_in_group( $atts['user_id'], $group_id ) ) {
							$group_courses = learndash_group_enrolled_courses( $group_id );
							if ( !empty( $group_courses ) ) {
								$user_courses = array_merge( $user_courses, $group_courses );
							}
						}
					}
					if ( !empty( $user_courses ) ) {
						$atts['post__in'] = $user_courses;
					}
				} else {
					$atts['user_id'] = get_current_user_id();
				}
			} else {
				$atts['user_id'] = get_current_user_id();
			}
		}
	} else {
		$atts['user_id'] = false;
		$atts['mycourses'] = null;
	}	
		
	extract( $atts );
	
	global $post;
	
	$filter = array(
		'post_type' => $post_type, 
		'post_status' => $post_status, 
		'posts_per_page' => $num, 
		'paged' => $paged,
		'order' => $order, 
		'orderby' => $orderby
	);
	$meta_query = array();
	

	// Added an empty meta query set. Then we check later and if still empty we remove it before calling get_posts. 
	if ( !isset( $filter['meta_query'] ) ) $filter['meta_query'] = array();
	
	if ( ! empty( $author__in ) ) {
		$filter['author__in'] = $author__in;
	}
	
	/*
	if ( ! empty( $meta_key ) ) {
		$filter['meta_key'] = $meta_key;
	}
	
	if ( ! empty( $meta_value ) ) {
		$filter['meta_value'] = $meta_value;
	}
	
	if ( ! empty( $meta_compare ) ) {
		if ( !empty( $filter['meta_key'] ) ) {
			$filter['meta_compare'] = $meta_compare;
		}
	}
	*/
	
	if ( ( ! empty( $meta_key ) ) && ( ! empty( $meta_value ) ) ) {
		//if ( $meta_key == 'course_id' ) {
		//	if ( empty( $course_id ) ) {
		//		$course_id = $meta_value;
		//		$atts['course_id'] = $meta_value;
		//	} 
		//} else {
	
			$meta_query = array(
				'key' => $meta_key,
				'value' => $meta_value
			);

			if ( empty( $meta_compare ) ) 
				$meta_compare = '=';

			$meta_query['compare'] = $meta_compare;

			$filter['meta_query'][] = $meta_query;
		//}
	}	
	
	if ( ( !empty( $course_id ) ) && ( is_null( $post__in ) ) ) {
		if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
			$filter['post__in'] = learndash_course_get_steps_by_type( $course_id, $atts['post_type']);
		} else {
			$meta_query = array(
				'key' => 'course_id',
				'value' => intval( $course_id ),
				'compare' => '=',
			);
		}
		
		$filter['meta_query'][] = $meta_query;
	} else if ( ! empty( $post__in ) ) {
		$filter['post__in'] = $post__in;
	}	
	
	if ( LearnDash_Settings_Section::get_section_setting( $post_type_Class, 'wp_post_category' ) == 'yes') {
	
		if ( ! empty( $cat ) ) {
			//$filter['cat'] = $cat;
			
			if ( !isset( $filter['tax_query'] ) ) 
				$filter['tax_query'] = array();
			
			$filter['tax_query'][] = array(
				'taxonomy'	=>	'category',
				'field'		=>	'term_id',
				'terms'		=>	intval( $cat )
			);
		}
		
		if ( ! empty( $category_name ) ) {
			//$filter['category_name'] = $category_name;
			
			if ( !isset( $filter['tax_query'] ) ) 
				$filter['tax_query'] = array();
			
			$filter['tax_query'][] = array(
				'taxonomy'	=>	'category',
				'field'		=>	'slug',
				'terms'		=>	trim( $category_name )
			);
		}
	
		if ( ! empty( $category__and ) ) {
			//$filter['category__and'] = explode( ',', $category__and );
			
			$category__and = array_map('intval', explode( ',', $category__and ) );
			
			if ( !isset( $filter['tax_query'] ) ) 
				$filter['tax_query'] = array();
			
			$filter['tax_query'][] = array(
				'taxonomy'	=>	'category',
				'field'		=>	'term_id',
				'terms'		=>	$category__and,
				'operator'	=>	'AND'
			);
		}
	
		if ( ! empty( $category__in ) ) {
			//$filter['category__in'] = explode( ',', $category__in );

			$category__in = array_map('intval', explode( ',', $category__in ) );
			
			if ( !isset( $filter['tax_query'] ) ) 
				$filter['tax_query'] = array();
			
			$filter['tax_query'][] = array(
				'taxonomy'	=>	'category',
				'field'		=>	'term_id',
				'terms'		=>	$category__in,
				'operator'	=>	'IN'
			);
		}
	
		if ( ! empty( $category__not_in ) ) {
			//$filter['category__not_in'] = explode( ',', $category__not_in );
			
			$category__not_in = array_map('intval', explode( ',', $category__not_in ) );

			if ( !isset( $filter['tax_query'] ) ) 
				$filter['tax_query'] = array();
			
			$filter['tax_query'][] = array(
				'taxonomy'	=>	'category',
				'field'		=>	'term_id',
				'terms'		=>	$category__not_in,
				'operator'	=>	'NOT IN'
			);
		}
	}
		
	if ( LearnDash_Settings_Section::get_section_setting( $post_type_Class, 'wp_post_tag' ) == 'yes') {
	
		if ( ! empty( $tag ) ) {
			//$filter['tag'] = $tag;
			
			if ( !isset( $filter['tax_query'] ) ) 
				$filter['tax_query'] = array();
			
			$filter['tax_query'][] = array(
				'taxonomy'	=>	'post_tag',
				'field'		=>	'slug',
				'terms'		=>	trim( $tag )
			);
			
		}
	
		if ( ! empty( $tag_id ) ) {
			//$filter['tag_id'] = $tag;
			
			if ( !isset( $filter['tax_query'] ) ) 
				$filter['tax_query'] = array();
			
			$filter['tax_query'][] = array(
				'taxonomy'	=>	'post_tag',
				'field'		=>	'term_id',
				'terms'		=>	intval( $tag_id ),
			);
			
		}
	
		if ( ! empty( $tag__and ) ) {
			//$filter['tag__and'] = explode( ',', $tag__and );
			
			$tag__and = array_map('intval', explode( ',', $tag__and ) );
			
			if ( !isset( $filter['tax_query'] ) ) 
				$filter['tax_query'] = array();
			
			$filter['tax_query'][] = array(
				'taxonomy'	=>	'post_tag',
				'field'		=>	'term_id',
				'terms'		=>	$tag__and,
				'operator'	=>	'AND'
			);
		}
	
		if ( ! empty( $tag__in ) ) {
			//$filter['tag__in'] = explode( ',', $tag__in );
			
			$tag__in = array_map('intval', explode( ',', $tag__in ) );
			
			if ( !isset( $filter['tax_query'] ) ) 
				$filter['tax_query'] = array();
			
			$filter['tax_query'][] = array(
				'taxonomy'	=>	'post_tag',
				'field'		=>	'term_id',
				'terms'		=>	$tag__in,
				'operator'	=>	'IN'
			);
			
		}
	
		if ( ! empty( $tag__not_in ) ) {
			//$filter['tag__not_in'] = explode( ',', $tag__not_in );
			
			$tag__not_in = array_map('intval', explode( ',', $tag__not_in ) );
			
			if ( !isset( $filter['tax_query'] ) ) 
				$filter['tax_query'] = array();
			
			$filter['tax_query'][] = array(
				'taxonomy'	=>	'post_tag',
				'field'		=>	'term_id',
				'terms'		=>	$tag__not_in,
				'operator'	=>	'NOT IN'
			);
		}
	
		if ( ! empty( $tag_slug__and ) ) {
			//$filter['tag_slug__and'] = explode( ',', $tag_slug__and );
			
			$tag_slug__and = array_map('trim', explode( ',', $tag_slug__and ) );

			if ( !isset( $filter['tax_query'] ) ) 
				$filter['tax_query'] = array();
			
			$filter['tax_query'][] = array(
				'taxonomy'	=>	'post_tag',
				'field'		=>	'slug',
				'terms'		=>	$tag_slug__and,
				'operator'	=>	'AND'
			);
		}
	
		if ( ! empty( $tag_slug__in ) ) {
			//$filter['tag_slug__in'] = explode( ',', $tag_slug__in );
			
			$tag_slug__in = array_map('trim', explode( ',', $tag_slug__in ) );

			if ( !isset( $filter['tax_query'] ) ) 
				$filter['tax_query'] = array();
			
			$filter['tax_query'][] = array(
				'taxonomy'	=>	'post_tag',
				'field'		=>	'slug',
				'terms'		=>	$tag_slug__in,
				'operator'	=>	'IN'
			);
		}
	}
		
	
	if ( LearnDash_Settings_Section::get_section_setting( $post_type_Class, 'ld_'. $post_type_slug .'_category' ) == 'yes') {

		// course_cat="123" 
		if ( ( isset( $atts[$post_type_slug .'_cat'] ) )  && ( ! empty( $atts[$post_type_slug .'_cat'] ) ) ) {

			if ( !isset( $filter['tax_query'] ) ) 
				$filter['tax_query'] = array();
			
			$filter['tax_query'][] = array(
				'taxonomy'	=>	'ld_'. $post_type_slug .'_category',
				'field'		=>	'term_id',
				'terms'		=>	intval( $atts[$post_type_slug .'_cat'] )
			);
		}
		
		// course_category_name (string) - use category slug.	
		// course_category_name="course-category-one"
		if ( ( isset( $atts[$post_type_slug .'_category_name'] ) )  && ( ! empty( $atts[$post_type_slug .'_category_name'] ) ) ) {

			if ( !isset( $filter['tax_query'] ) ) 
				$filter['tax_query'] = array();
			
			$filter['tax_query'][] = array(
				'taxonomy'	=>	'ld_'. $post_type_slug .'_category',
				'field'		=>	'slug',
				'terms'		=>	trim( $atts[$post_type_slug .'_category_name'] )
			);
		}

		// course_category__and (array) - use category id.
		if ( ( isset( $atts[$post_type_slug .'_category__and'] ) )  && ( ! empty( $atts[$post_type_slug .'_category__and'] ) ) ) {
			
			$cat__and = array_map('intval', explode( ',', $atts[$post_type_slug .'_category__and'] ) );
			
			if ( !isset( $filter['tax_query'] ) ) 
				$filter['tax_query'] = array();
			
			$filter['tax_query'][] = array(
				'taxonomy'	=>	'ld_'. $post_type_slug .'_category',
				'field'		=>	'term_id',
				'terms'		=>	$cat__and,
				'operator'	=>	'AND',
				'include_children'	=>	false,
			);
		}
		
		// course_category__in (array) - use category id.
		if ( ( isset( $atts[$post_type_slug .'_category__in'] ) )  && ( ! empty( $atts[$post_type_slug .'_category__in'] ) ) ) {

			$cat__in = array_map('intval', explode( ',', $atts[$post_type_slug .'_category__in'] ) );

			if ( !isset( $filter['tax_query'] ) ) 
				$filter['tax_query'] = array();
			
			$filter['tax_query'][] = array(
				'taxonomy'	=>	'ld_'. $post_type_slug .'_category',
				'field'		=>	'term_id',
				'terms'		=>	$cat__in,
				'operator'	=>	'IN',
				'include_children'	=>	false,
			);
		}
		
		// course_category___not_in (array) - use category id.
		if ( ( isset( $atts[$post_type_slug .'_category__not_in'] ) )  && ( ! empty( $atts[$post_type_slug .'_category__not_in'] ) ) ) {

			$cat__not_in = array_map('intval', explode( ',', $atts[$post_type_slug .'_category__not_in'] ) );
			
			if ( !isset( $filter['tax_query'] ) ) 
				$filter['tax_query'] = array();
			
			$filter['tax_query'][] = array(
				'taxonomy'	=>	'ld_'. $post_type_slug .'_category',
				'field'		=>	'term_id',
				'terms'		=>	$cat__not_in,
				'operator'	=>	'NOT IN',
				'include_children'	=>	false,
			);
		}
	}
	
	if ( LearnDash_Settings_Section::get_section_setting( $post_type_Class, 'ld_'. $post_type_slug .'_tag' ) == 'yes') {
	
		// course_tag (string) - use tag slug.
		if ( ( isset( $atts[$post_type_slug .'_tag'] ) )  && ( ! empty( $atts[$post_type_slug .'_tag'] ) ) ) {

			if ( !isset( $filter['tax_query'] ) ) 
				$filter['tax_query'] = array();
			
			$filter['tax_query'][] = array(
				'taxonomy'	=>	'ld_'. $post_type_slug .'_tag',
				'field'		=>	'slug',
				'terms'		=>	trim( $atts[$post_type_slug .'_tag'] )
			);
		}
		
		
		// course_tag_id (int) - use tag id.
		if ( ( isset( $atts[$post_type_slug .'_tag_id'] ) )  && ( ! empty( $atts[$post_type_slug .'_tag_id'] ) ) ) {

			if ( !isset( $filter['tax_query'] ) ) 
				$filter['tax_query'] = array();
			
			$filter['tax_query'][] = array(
				'taxonomy'	=>	'ld_'. $post_type_slug .'_tag',
				'field'		=>	'term_id',
				'terms'		=>	intval( $atts[$post_type_slug .'_tag_id'] )
			);
		}
		
		// course_tag__and (array) - use tag ids.
		if ( ( isset( $atts[$post_type_slug .'_tag__and'] ) )  && ( ! empty( $atts[$post_type_slug .'_tag__and'] ) ) ) {

			$tag__and = array_map('intval', explode( ',', $atts[$post_type_slug .'_tag__and'] ) );
			
			if ( !isset( $filter['tax_query'] ) ) 
				$filter['tax_query'] = array();
						
			$filter['tax_query'][] = array(
				'taxonomy'	=>	'ld_'. $post_type_slug .'_tag',
				'field'		=>	'term_id',
				'terms'		=>	$tag__and,
				'operator'	=>	'AND'
			);
		}
		
		// course_tag__in (array) - use tag ids.
		if ( ( isset( $atts[$post_type_slug .'_tag__in'] ) )  && ( ! empty( $atts[$post_type_slug .'_tag__in'] ) ) ) {

			$tag__in = array_map('intval', explode( ',', $atts[$post_type_slug .'_tag__in'] ) );

			if ( !isset( $filter['tax_query'] ) ) 
				$filter['tax_query'] = array();
			
			$filter['tax_query'][] = array(
				'taxonomy'	=>	'ld_'. $post_type_slug .'_tag',
				'field'		=>	'term_id',
				'terms'		=>	$tag__in,
				'operator'	=>	'IN'
			);
		}

		// course_tag__not_in (array) - use tag ids.
		if ( ( isset( $atts[$post_type_slug .'_tag__not_in'] ) )  && ( ! empty( $atts[$post_type_slug .'_tag__not_in'] ) ) ) {

			$tag__not_in = array_map('intval', explode( ',', $atts[$post_type_slug .'_tag__not_in'] ) );
			
			if ( !isset( $filter['tax_query'] ) ) 
				$filter['tax_query'] = array();
			
			$filter['tax_query'][] = array(
				'taxonomy'	=>	'ld_'. $post_type_slug .'_tag',
				'field'		=>	'term_id',
				'terms'		=>	$tag__not_in,
				'operator'	=>	'NOT IN'
			);
		}
		
		// course_tag_slug__and (array) - use tag slugs.
		if ( ( isset( $atts[$post_type_slug .'_tag_slug__and'] ) )  && ( ! empty( $atts[$post_type_slug .'_tag_slug__and'] ) ) ) {

			$tag_slug__and = array_map('trim', explode( ',', $atts[$post_type_slug .'_tag_slug__and'] ) );
			
			if ( !isset( $filter['tax_query'] ) ) 
				$filter['tax_query'] = array();
			
			$filter['tax_query'][] = array(
				'taxonomy'	=>	'ld_'. $post_type_slug .'_tag',
				'field'		=>	'slug',
				'terms'		=>	$tag_slug__and,
				'operator'	=>	'AND'
			);
		}
		
		
		// course_tag_slug__in (array) - use tag slugs.
		if ( ( isset( $atts[$post_type_slug .'_tag_slug__in'] ) )  && ( ! empty( $atts[$post_type_slug .'_tag_slug__in'] ) ) ) {
			
			$tag_slug__in = array_map('trim', explode( ',', $atts[$post_type_slug .'_tag_slug__in'] ) );
			
			if ( !isset( $filter['tax_query'] ) ) 
				$filter['tax_query'] = array();
			
			$filter['tax_query'][] = array(
				'taxonomy'	=>	'ld_'. $post_type_slug.'_tag',
				'field'		=>	'slug',
				'terms'		=>	$tag_slug__in,
				'operator'	=>	'IN'
			);
		}
	}	


	if ( ( isset( $filter['tax_query'] ) ) && ( count( $filter['tax_query'] ) > 1 ) ) {
		// Due to a quick on WP_Query the 'compare' option needs to be in the first position. 
		// So we save off the current tax_query, add the 'relation', then merge in the original tax_query 
		$tax_query = $filter['tax_query'];
		$filter['tax_query'] = array( 'relation' => $tax_compare );
		$filter['tax_query'] = array_merge( $filter['tax_query'], $tax_query );
		
	} else if ( ! empty( $meta_compare ) ) {
		$filter['meta_compare'] = $meta_compare;
	}
	
	// Logic to determine the exact post ids to query. This will help drive the category selectors below and prevent extra queries. 
	
	$shortcode_course_id = null;
	if ( is_null( $post__in ) ) {
		if ( $mycourses == 'enrolled' ) {
			$filter['post__in'] = learndash_user_get_enrolled_courses( $atts['user_id'] );
			if ( empty( $filter['post__in'] ) ) return;
		
		} else if ( $mycourses == 'not-enrolled' ) {
			$filter['post__not_in'] = learndash_user_get_enrolled_courses( $atts['user_id'] );
			if ( empty( $filter['post__not_in'] ) ) unset( $filter['post__not_in'] );
		} 
	}
	
	$filter = apply_filters('learndash_ld_course_list_query_args', $filter, $atts );
	
	if ( $array == 'true' ) {
		return get_posts( $filter );
	}
	
	if ( ( $post ) && ( is_a( $post, 'WP_Post' ) ) && ( $post->post_type == $post_type ) ) {
		if ( ( isset( $filter['post__not_in'] ) ) && ( !empty( $filter['post__not_in'] ) ) ) {
			$filter['post__not_in'][] = $post->ID;
		} else {
			$filter['post__not_in'] = array( $post->ID );
		}
	}
	
	// At this point the $filter var contains all the shortcode processing logic. 
	// So now we want to save off the var to one used by the category selector (if used).
	$filter_cat = $filter;
	$filter_cat['posts_per_page'] = -1;
			
	$ld_categorydropdown = '';

	$categories = array();
	$ld_categories = array();

	//if ( $include_outer_wrapper == 'true' ) {

		if ( ( trim( $categoryselector ) == 'true' ) && ( LearnDash_Settings_Section::get_section_setting( $post_type_Class, 'wp_post_category' ) == 'yes') ) {
			$cats = array();

			if ( ( isset( $_GET['catid'] ) ) && ( !empty( $_GET['catid'] ) ) ) {
				$atts['cat'] = intval( $_GET['catid'] );
				
				if ( !isset( $filter['tax_query'] ) ) 
					$filter['tax_query'] = array();
		
				$filter['tax_query'][] = array(
					'taxonomy'	=>	'category',
					'field'		=>	'term_id',
					'terms'		=>	intval( $_GET['catid'] )
				);
			}
		
			//if ( isset( $filter_cat['post__in'] ) ) {
				//$filter_cat['include'] = $filter_cat['post__in'];
			//	unset( $filter_cat['post__in'] );
			//}
			//if ( isset( $filter_cat['post__not_in'] ) ) {
				//$filter_cat['include'] = $filter_cat['post__in'];
			//	unset( $filter_cat['post__not_in'] );
			//}
		
			$cat_posts = get_posts( $filter_cat );
		
			// We first need to build a listing of the categories used by each of the queried posts. 
			if ( !empty( $cat_posts ) ) {
				foreach( $cat_posts as $cat_post ) {
					$post_categories = wp_get_post_categories( $cat_post->ID );
					if ( !empty( $post_categories ) ) {
						foreach( $post_categories as $c ) {

							if ( empty( $cats[ $c ] ) ) {
								$cat = get_category( $c );
								$cats[ $c ] = array(
									'id' => $cat->cat_ID, 
									'name' => $cat->name, 
									'slug' => $cat->slug, 
									'parent' => $cat->parent, 
									'count' => 0, 
									'posts' => array()
								); 
							}

							$cats[ $c ]['count']++;
							$cats[ $c ]['posts'][] = $post->ID;
						}
					}
				}
			
				// Once we have these categories we need to requery the categories in order to get them into a proper ordering. 
				if ( !empty( $cats ) ) {
		
					// And also let this query be filtered.
					$get_categories_args = apply_filters(
						'learndash_course_list_category_args', 
						array(
							'taxonomy'	=>	'category',
							'type' 		=>	$post_type,
							'include'	=>	array_keys($cats),
							'orderby'	=>	'name',
							'order'		=>	'ASC'
						)
					);
		
					if ( !empty( $get_categories_args ) ) {
						$categories = get_categories( $get_categories_args );
					}
				}
			}
		} else {
			$categoryselector = '';
			$atts['categoryselector'];
		}
	
	
		// We can only support one of the other category OR course_category selectors
		if ( ( trim( $atts[$post_type_slug .'_categoryselector'] ) == 'true' ) && ( empty( $categoryselector ) )
		  && ( LearnDash_Settings_Section::get_section_setting( $post_type_Class, 'ld_'. $post_type_slug .'_category' ) == 'yes') ) {
			$ld_cats = array();
		
			if ( ( isset( $_GET[$post_type_slug . '_catid'] ) ) && ( !empty( $_GET[$post_type_slug . '_catid'] ) ) ) {
				
				$atts[$post_type_slug .'_cat'] = intval( $_GET[$post_type_slug . '_catid'] );
				
				if ( !isset( $filter['tax_query'] ) ) 
					$filter['tax_query'] = array();
		
				$filter['tax_query'][] = array(
					'taxonomy'	=>	'ld_'. $post_type_slug .'_category',
					'field'		=>	'term_id',
					'terms'		=>	intval( $_GET[$post_type_slug . '_catid'] )
				);
			}
			
			$cat_posts = get_posts( $filter_cat );
		
			// We first need to build a listing of the categories used by each of the queried posts. 
			if ( !empty( $cat_posts ) ) {
				$args = array('fields' => 'ids');
				foreach( $cat_posts as $cat_post ) {
					$post_categories = wp_get_object_terms($cat_post->ID, 'ld_'. $post_type_slug .'_category', $args);
					if ( !empty( $post_categories ) ) {
						foreach( $post_categories as $c ) {

							if ( empty( $ld_cats[ $c ] ) ) {
								$ld_cat = get_term( $c, 'ld_'. $post_type_slug .'_category' );
								$ld_cats[ $c ] = array(
									'id' => $ld_cat->cat_ID, 
									'name' => $ld_cat->name, 
									'slug' => $ld_cat->slug, 
									'parent' => $ld_cat->parent, 
									'count' => 0, 
									'posts' => array()
								); 
							}

							$ld_cats[ $c ]['count']++;
							$ld_cats[ $c ]['posts'][] = $cat_post->ID;
						}
					}
				}
			
				// Once we have these categories we need to requery the categories in order to get them into a proper ordering. 
				if ( !empty( $ld_cats ) ) {
				
					// And also let this query be filtered.
					$get_ld_categories_args = apply_filters(
						'learndash_course_list_'. $post_type_slug .'_category_args', 
						array(
							'taxonomy'	=>	'ld_'. $post_type_slug .'_category',
							'type' 		=>	$post_type,
							'include'	=>	array_keys( $ld_cats ),
							'orderby'	=>	'name',
							'order'		=>	'ASC'
						)
					);
		
					$post_type_object = get_post_type_object( $atts['post_type'] );
					//error_log('post_type_slug['. $atts['post_type'] .'] post_type_object<pre>'. print_r($post_type_object, true) .'</pre>');
				
					$tax_object = get_taxonomy('ld_'. $post_type_slug .'_category');
				
					if ( !empty( $get_ld_categories_args ) ) {
						$ld_categories = get_terms( $get_ld_categories_args );
					}
				}
			}
		} else {
			$atts[$post_type_slug .'_categoryselector'] = '';
		}
	//}
	
	//error_log('filter<pre>'. print_r($filter, true) .'</pre>');
	$loop = new WP_Query( $filter );
	//error_log('loop<pre>'. print_r($loop, true) .'</pre>');
	
	$level = ob_get_level();
	ob_start();

	if ( $include_outer_wrapper == 'true' ) {
		if ( !empty( $categories ) ) {

			$categorydropdown = '<div id="ld_categorydropdown">';
			$categorydropdown.= '<form method="get">
					<label for="ld_categorydropdown_select">' . esc_html__( 'Categories', 'learndash' ) . '</label>
					<select id="ld_categorydropdown_select" name="catid" onChange="jQuery(\'#ld_categorydropdown form\').submit()">';
			$categorydropdown.= '<option value="">' . esc_html__( 'Select category', 'learndash' ) . '</option>';

			foreach( $categories as $category ) {
		
				if ( isset( $cats[$category->term_id] ) ) {
					$cat = $cats[$category->term_id];
					$selected =( empty( $_GET['catid'] ) || $_GET['catid'] != $cat['id'] ) ? '' : 'selected="selected"';
					$categorydropdown.= "<option value='" . $cat['id'] . "' " . $selected . '>' . $cat['name'] . ' (' . $cat['count'] . ')</option>';
				}
			}

			$categorydropdown.= "</select><input type='submit' style='display:none'></form></div>";

			/**
			 * Filter HTML output of category dropdown
			 * 
			 * @since 2.1.0
			 * 
			 * @param  string  $categorydropdown
			 */
			echo apply_filters( 'ld_categorydropdown', $categorydropdown, $atts, $filter );
		}
	
		if ( !empty( $ld_categories ) ) {

			$ld_categorydropdown = '<div id="ld_'. $post_type_slug .'_categorydropdown">';
			$ld_categorydropdown.= '<form method="get">
					<label for="ld_'. $post_type_slug .'_categorydropdown_select">' . $tax_object->labels->name . '</label>
					<select id="ld_'. $post_type_slug .'_categorydropdown_select" name="'. $post_type_slug .'_catid" onChange="jQuery(\'#ld_'. $post_type_slug .'_categorydropdown form\').submit()">';
			$ld_categorydropdown.= '<option value="">' . sprintf( esc_html_x( 'Select %s', 'placeholder: LD Category label', 'learndash' ), $tax_object->labels->name )  . '</option>';

			foreach( $ld_categories as $ld_category ) {
		
				if ( isset( $ld_cats[$ld_category->term_id] ) ) {
					$ld_cat = $ld_cats[$ld_category->term_id];
					$selected =( empty( $_GET[$post_type_slug . '_catid'] ) || $_GET[$post_type_slug . '_catid'] != $ld_category->term_id ) ? '' : 'selected="selected"';
					$ld_categorydropdown .= "<option value='" . $ld_category->term_id . "' " . $selected . '>' . $ld_cat['name'] . ' (' . $ld_cat['count'] . ')</option>';
				}
			}

			$ld_categorydropdown.= "</select><input type='submit' style='display:none'></form></div>";

			/**
			 * Filter HTML output of category dropdown
			 * 
			 * @since 2.1.0
			 * 
			 * @param  string  $categorydropdown
			 */
			echo apply_filters( 'ld_'. $post_type_slug .'_categorydropdown', $ld_categorydropdown, $atts, $filter );
		}
	}
	
	$filter_json = htmlspecialchars( json_encode( $atts ) );
	$filter_md5 = md5( $filter_json ); 
	
	//error_log('include_outer_wrapper['. $include_outer_wrapper .']');
	
	if ( $include_outer_wrapper == 'true' ) {
		?><div id="ld-course-list-content-<?php echo $filter_md5 ?>" class="ld-course-list-content" data-shortcode-atts="<?php echo $filter_json; ?>"><?php
	}
	?><div class="ld-course-list-items row"><?php
	
	/**
	 * The following was added in 2.5.9 to allow better work with Gutenberg block rendering. 
	 * Seems when we call the $loop->the_post() in the section below we are changing the 
	 * global $post object. The problem is after this loop we call wp_reset_postdata() but 
	 * the global $post is not being reset. This is really only an issue with the Gutenberg 
	 * render blocks.
	 * 
	 * @since 2.5.9
	 */
//	if ( ( defined( 'REST_REQUEST' ) ) && ( true === REST_REQUEST ) ) {
		$post_save = $post;
//	}

	while ( $loop->have_posts() ) {
		$loop->the_post();
			if ( empty( $atts['course_id'] ) ) {
				$course_id = $course_id = learndash_get_course_id( get_the_ID());
			} else {
				$course_id = $atts['course_id'];
			}
			
			echo SFWD_LMS::get_template( 
				'course_list_template', 
				array(
					'shortcode_atts' => $atts,
					'course_id'	=> $course_id
				) 
			);
		//}
	}
	?></div><?php
	
	if ( ( isset( $filter['posts_per_page'] ) ) && ( intval( $filter['posts_per_page'] ) > 0 ) ) {
		$course_list_pager = array();
		if ( isset( $loop->query_vars['paged'] ) )
			$course_list_pager['paged'] = $loop->query_vars['paged'];
		else 
			$course_list_pager['paged'] = $filter['paged'];
			
		$course_list_pager['total_items'] = intval( $loop->found_posts );
		$course_list_pager['total_pages'] = intval( $loop->max_num_pages );
		
		echo SFWD_LMS::get_template( 
			'learndash_pager.php', 
			array(
				'pager_results' => $course_list_pager, 
				'pager_context' => 'course_list'
			) 
		);
	}

	if ( $include_outer_wrapper == 'true' ) {
		?></div><?php
	}

	$output = learndash_ob_get_clean( $level );
	
	//if ( ( defined( 'REST_REQUEST' ) ) && ( true === REST_REQUEST ) ) {
		$post = $post_save;
		//$GLOBALS['post'] = $post_save;
		setup_postdata( $post_save );
	//} else {
		/* Restore original Post Data */
	//	wp_reset_postdata();
	//}

	$learndash_shortcode_used = true;

	/**
	 * Filter HTML output of category dropdown
	 * 
	 * @since 2.1.0
	 * 
	 * @param  string $output
	 */
	return apply_filters( 'ld_course_list', $output, $atts, $filter );
}

add_shortcode( 'ld_course_list', 'ld_course_list' );



/**
 * Shortcode to list lessons
 * 
 * @since 2.1.0
 * 
 * @param  array 	$attr 	shortcode attributes
 * @return string   		shortcode output
 */
function ld_lesson_list( $attr = array() ) {
	global $learndash_shortcode_used;
	$learndash_shortcode_used = true;
	
	if ( !is_array( $attr ) ) {
		$attr = array();
	}
	
	$attr['post_type'] = 'sfwd-lessons';
	$attr['mycourses'] = false;
	
	// If we have a course_id. Then we set the orderby to match the items within the course. 
	if ( ( isset( $attr['course_id'] ) ) && ( !empty( $attr['course_id'] ) ) ) {
		if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
			if ( !isset( $attr['order'] ) ) $attr['order'] = 'ASC';
			if ( !isset( $attr['orderby'] ) ) $attr['orderby'] = 'post__in';
		
			$course_steps = learndash_course_get_steps_by_type( intval( $attr['course_id'] ), $attr['post_type'] );
			if ( !empty( $course_steps ) ) {
				$attr['post__in'] = $course_steps;
			}
		}
	} 
		
	return ld_course_list( $attr );
}

add_shortcode( 'ld_lesson_list', 'ld_lesson_list' );



/**
 * Shortcode to list quizzes
 * 
 * @since 2.1.0
 * 
 * @param  array 	$attr 	shortcode attributes
 * @return string   		shortcode output
 */
function ld_quiz_list( $attr = array() ) {
	global $learndash_shortcode_used;
	$learndash_shortcode_used = true;
	
	if ( !is_array( $attr ) ) {
		$attr = array();
	}

	$attr['post_type'] = 'sfwd-quiz';
	$attr['mycourses'] = false;
	
	// If we have a course_id. Then we set the orderby to match the items within the course. 
	if ( ( isset( $attr['course_id'] ) ) && ( !empty( $attr['course_id'] ) ) ) {
		if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
			if ( !isset( $attr['order'] ) ) $attr['order'] = 'ASC';
			if ( !isset( $attr['orderby'] ) ) $attr['orderby'] = 'post__in';
		
			$course_steps = learndash_course_get_steps_by_type( intval( $attr['course_id'] ), $attr['post_type'] );
			if ( !empty( $course_steps ) ) {
				$attr['post__in'] = $course_steps;
			}
		}
	} 
	
	return ld_course_list( $attr );
}

add_shortcode( 'ld_quiz_list', 'ld_quiz_list' );



/**
 * Shortcode to list topics
 * 
 * @since 2.1.0
 * 
 * @param  array 	$attr 	shortcode attributes
 * @return string   		shortcode output
 */
function ld_topic_list( $attr = array() ) {
	global $learndash_shortcode_used;
	$learndash_shortcode_used = true;
	
	if ( !is_array( $attr ) ) {
		$attr = array();
	}
	
	$attr['post_type'] = 'sfwd-topic';
	$attr['mycourses'] = false;
	
	// If we have a course_id. Then we set the orderby to match the items within the course. 
	if ( ( isset( $attr['course_id'] ) ) && ( !empty( $attr['course_id'] ) ) ) {
		if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
			if ( !isset( $attr['order'] ) ) $attr['order'] = 'ASC';
			if ( !isset( $attr['orderby'] ) ) $attr['orderby'] = 'post__in';
		
			$course_steps = learndash_course_get_steps_by_type( intval( $attr['course_id'] ), $attr['post_type'] );
			if ( !empty( $course_steps ) ) {
				$attr['post__in'] = $course_steps;
			}
		}
	} 
	
	return ld_course_list( $attr );
}

add_shortcode( 'ld_topic_list', 'ld_topic_list' );



/**
 * Check if user has access
 *
 * @todo  duplicate function, exists in other places
 *        check it's use and consolidate
 * 
 * @since 2.1.0
 * 
 * @param  int $course_id
 * @param  int $user_id
 * @return bool
 */
function ld_course_check_user_access( $course_id, $user_id = null ) {
	return sfwd_lms_has_access( $course_id, $user_id );
}



/**
 * Shortcode to display content to users that have access to current course id
 *
 * @todo  function is duplicate of learndash_student_check_shortcode()
 * 
 * @since 2.1.0
 * 
 * @param  array 	$attr 		shortcode attributes
 * @param  string 	$content 	content of shortcode
 * @return string   			shortcode output
 */
function learndash_visitor_check_shortcode( $atts, $content = '' ) {
	global $learndash_shortcode_used;

	if ( ! empty( $content ) ) {
	
		if ( ! is_array( $atts ) ) {
			if ( ! empty( $atts ) ) {
				$atts = array( $atts );
			} else {
				$atts = array();
			}
		}

		$defaults = array(
			'course_id' => learndash_get_course_id(),
			'content'	=> $content,
			'autop'		=> true
		);
		$atts = wp_parse_args( $atts, $defaults );
		
		if ( ( true === $atts['autop'] ) || ( 'true' === $atts['autop'] ) || ( '1' === $atts['autop'] ) ) {
			$atts['autop'] = true;
		} else {
			$atts['autop'] = false;
		}

		$atts = apply_filters('learndash_visitor_shortcode_atts', $atts );
		
		if ( ( ! is_user_logged_in() ) || ( ( ! empty( $atts['course_id'] ) ) && ( ! sfwd_lms_has_access( $atts['course_id'] ) ) ) ) {
			$learndash_shortcode_used = true;
			$atts['content'] = do_shortcode( $atts['content'] );
			return SFWD_LMS::get_template( 
				'learndash_course_visitor_message', 
				array(
					'shortcode_atts' => $atts,
				), false
			);
	
		} else {
			$content = '';
		}
	}	

	return $content;
}

add_shortcode( 'visitor', 'learndash_visitor_check_shortcode' );



/**
 * Shortcode to display content to users that have access to current course id
 *
 * @todo  function is duplicate of learndash_visitor_check_shortcode()
 * 
 * @since 2.1.0
 * 
 * @param  array 	$attr 		shortcode attributes
 * @param  string 	$content 	content of shortcode
 * @return string   			shortcode output
 */
function learndash_student_check_shortcode( $atts, $content = null ) {
	global $learndash_shortcode_used;

	if ( ( ! empty( $content ) ) && ( is_user_logged_in() ) ) {
	
		if ( ! is_array( $atts ) ) {
			if ( ! empty( $atts ) ) {
				$atts = array( $atts );
			} else {
				$atts = array();
			}
		}

		$defaults = array(
			'course_id' => 	learndash_get_course_id(),
			'user_id'	=>	get_current_user_id(),
			'content'	=>	$content,
			'autop'		=>	true
		);
		$atts = wp_parse_args( $atts, $defaults );
		
		if ( ( true === $atts['autop'] ) || ( 'true' === $atts['autop'] ) || ( '1' === $atts['autop'] ) ) {
			$atts['autop'] = true;
		} else {
			$atts['autop'] = false;
		}

		$atts = apply_filters('learndash_student_shortcode_atts', $atts );

		if ( ( !empty( $atts['content'] ) ) && ( !empty( $atts['user_id'] ) ) && ( !empty( $atts['course_id'] ) ) && ( $atts['user_id'] == get_current_user_id() ) ) {
			// The reason we are doing this check is because 'sfwd_lms_has_access' will return true if the course does not exist. 
			// This needs to be changed to return some other value because true signals the calling function that all is well. 
			$course_id = learndash_get_course_id( $atts['course_id'] );
			if ( $course_id == $atts['course_id'] ) {
				if ( sfwd_lms_has_access( $atts['course_id'], $atts['user_id'] ) ) {
					$learndash_shortcode_used = true;
					$atts['content'] = do_shortcode( $atts['content'] );
					return SFWD_LMS::get_template( 
						'learndash_course_student_message', 
						array(
							'shortcode_atts'	=>	$atts,
						), false
					);
				}
			}
		}
	}
	
	return '';
}

add_shortcode( 'student', 'learndash_student_check_shortcode' );


/**
 * Shortcode to display content to users that have access to current group id
 *
 * @todo  function is duplicate of learndash_visitor_check_shortcode()
 * 
 * @since 2.3
 * 
 * @param  array 	$attr 		shortcode attributes
 * @param  string 	$content 	content of shortcode
 * @return string   			shortcode output
 */
function learndash_ld_group_check_shortcode( $atts, $content = null ) { 
	global $learndash_shortcode_used; 

	if ( ( is_singular() ) && ( !is_null( $content ) ) && ( is_user_logged_in() ) ) {

		$defaults = array(
			'group_id' 	=> 	0,
			'user_id'	=>	get_current_user_id(),
			'content'	=>	$content,
			'autop'		=>	true
		);
		$atts = wp_parse_args( $atts, $defaults );

		if ( ( true === $atts['autop'] ) || ( 'true' === $atts['autop'] ) || ( '1' === $atts['autop'] ) ) {
			$atts['autop'] = true;
		} else {
			$atts['autop'] = false;
		}

		$atts = apply_filters('learndash_ld_group_shortcode_atts', $atts, $content);

		if ( ( !empty( $atts['content'] ) ) && ( !empty( $atts['user_id'] ) ) && ( !empty( $atts['group_id'] ) ) && ( $atts['user_id'] == get_current_user_id() ) ) {
			if ( learndash_is_user_in_group( $atts['user_id'], $atts['group_id'] ) ) { 
				$learndash_shortcode_used = true;
				 $atts['content'] = do_shortcode( $atts['content'] ); 
				 return SFWD_LMS::get_template( 
						'learndash_group_message', 
						array(
							'shortcode_atts'	=>	$atts,
						), false
					);
			}
		}
	}
	
	return ''; 
}

add_shortcode( 'ld_group', 'learndash_ld_group_check_shortcode' );

/**
 * Generates output for course status shortcodes
 * 
 * @since 2.1.0
 * 
 * @param  array 	$attr 		shortcode attributes
 * @param  string 	$content 	content of shortcode
 * @param  string 	$status  	status of course
 * @return string 				shortcode output
 */
function learndash_course_status_content_shortcode( $atts, $content, $status ) {

	$atts['user_id'] = empty( $atts['user_id'] ) ? get_current_user_id() : intval( $atts['user_id'] );
	$atts['course_id'] = empty( $atts['course_id'] ) ? learndash_get_course_id() : learndash_get_course_id( intval( $atts['course_id'] ) );
	
	if ( ( ! empty( $atts['course_id'] ) ) && ( ! empty( $atts['user_id'] ) ) && ( $atts['user_id'] == get_current_user_id() ) ) {
		if ( sfwd_lms_has_access( $atts['course_id'], $atts['user_id'] ) ) {
			if ( learndash_course_status( $atts['course_id'], $atts['user_id'] ) == $status ) {
				return do_shortcode( $content );
			}
		}
	}
	return '';
}



/**
 * Shortcode that shows the content if the user has completed the course. 
 * 
 * @since 2.1.0
 * 
 * @param  array 	$attr 		shortcode attributes
 * @param  string 	$content 	content of shortcode
 * @return string   			shortcode output
 */
function learndash_course_complete_shortcode( $atts = array(), $content = '' ) {
	global $learndash_shortcode_used;
	$learndash_shortcode_used = true;
	
	if ( ! empty( $content ) ) {
	
		if ( ! is_array( $atts ) ) {
			if ( !empty( $atts ) ) {
				$atts = array( $atts );
			} else {
				$atts = array();
			}
		}

		$defaults = array(
			'content'	=> $content,
			'course_id' => false,
			'user_id'	=> false,
			'autop'		=> true
		);
		$atts = wp_parse_args( $atts, $defaults );

		if ( ( true === $atts['autop'] ) || ( 'true' === $atts['autop'] ) || ( '1' === $atts['autop'] ) ) {
			$atts['autop'] = true;
		} else {
			$atts['autop'] = false;
		}

		$atts = apply_filters( 'learndash_course_complete_shortcode_atts', $atts );

		$atts['content'] = learndash_course_status_content_shortcode( $atts, $atts['content'], esc_html__( 'Completed', 'learndash' ) );
		return SFWD_LMS::get_template( 
			'learndash_course_complete_message', 
			array(
				'shortcode_atts' => $atts,
			), false
		);
	}
}

add_shortcode( 'course_complete', 'learndash_course_complete_shortcode' );



/**
 * Shortcode that shows the content if the user is in progress on the course.
 * 
 * @since 2.1.0
 * 
 * @param  array 	$attr 		shortcode attributes
 * @param  string 	$content 	content of shortcode
 * @return string   			shortcode output
 */
function learndash_course_inprogress_shortcode( $atts = array(), $content = '' ) {
	global $learndash_shortcode_used;
	$learndash_shortcode_used = true;
	
	if ( ! empty( $content ) ) {

		if ( ! is_array( $atts ) ) {
			if ( !empty( $atts ) ) {
				$atts = array( $atts );
			} else {
				$atts = array();
			}
		}

		$defaults = array(
			'content'	=> $content,
			'course_id' => false,
			'user_id'	=> false,
			'autop'		=> true
		);
		$atts = wp_parse_args( $atts, $defaults );
		if ( ( true === $atts['autop'] ) || ( 'true' === $atts['autop'] ) || ( '1' === $atts['autop'] ) ) {
			$atts['autop'] = true;
		} else {
			$atts['autop'] = false;
		}

		$atts = apply_filters( 'learndash_course_inprogress_shortcode_atts', $atts );

		$atts['content'] = learndash_course_status_content_shortcode( $atts, $atts['content'], esc_html__( 'In Progress', 'learndash' ) );
		return SFWD_LMS::get_template( 
			'learndash_course_inprogress_message', 
			array(
				'shortcode_atts'	=>	$atts,
			), false
		);
	}
}

add_shortcode( 'course_inprogress', 'learndash_course_inprogress_shortcode' );



/**
 * Shortcode that shows the content if the user has mnot started the course
 * 
 * @since 2.1.0
 * 
 * @param  array 	$attr 		shortcode attributes
 * @param  string 	$content 	content of shortcode
 * @return string   			shortcode output
 */
function learndash_course_notstarted_shortcode( $atts = array(), $content = '' ) {
	global $learndash_shortcode_used;
	$learndash_shortcode_used = true;
	
	if ( ! empty( $content ) ) {

		if ( ! is_array( $atts ) ) {
			if ( !empty( $atts ) ) {
				$atts = array( $atts );
			} else {
				$atts = array();
			}
		}

		$defaults = array(
			'content'	=> $content,
			'course_id' => false,
			'user_id'	=> false,
			'autop'		=> true
		);
		$atts = wp_parse_args( $atts, $defaults );

		if ( ( true === $atts['autop'] ) || ( 'true' === $atts['autop'] ) || ( '1' === $atts['autop'] ) ) {
			$atts['autop'] = true;
		} else {
			$atts['autop'] = false;
		}

		$atts = apply_filters( 'learndash_course_notstarted_shortcode_atts', $atts );

		$atts['content'] = learndash_course_status_content_shortcode( $atts, $atts['content'], esc_html__( 'Not Started', 'learndash' ) );
		return SFWD_LMS::get_template(
			'learndash_course_not_started_message', 
			array(
				'shortcode_atts'	=>	$atts,
			), false
		);
	}
}

add_shortcode( 'course_notstarted', 'learndash_course_notstarted_shortcode' );


/**
 * Shortcode that shows the Course Expire date for user access. 
 * 
 * @since 2.1.0
 * 
 * @param  array 	$attr 		shortcode attributes
 * @param  string 	$content 	content of shortcode
 * @return string   			shortcode output
 */
function learndash_course_expire_status_shortcode( $atts, $content ) {
	global $learndash_shortcode_used;
	$learndash_shortcode_used = true;
	
	$content_shortcode = '';
	
	$atts = shortcode_atts( 
		array(
			'course_id' 	=> 	learndash_get_course_id(), 
			'user_id' 		=> 	get_current_user_id(), 
			'label_before'	=>	sprintf( esc_html_x('%s access will expire on:', 'Course access will expire on:', 'learndash'), LearnDash_Custom_Label::get_label( 'course' ) ),
			'label_after'	=>	sprintf( esc_html_x('%s access expired on:', 'Course access expired on:', 'learndash'), LearnDash_Custom_Label::get_label( 'course' ) ),
			'format'		=>	get_option('date_format') .' '. get_option('time_format'),
			'autop'			=>	true
		), 
		$atts
	);

	if ( ( true === $atts['autop'] ) || ( 'true' === $atts['autop'] ) || ( '1' === $atts['autop'] ) ) {
		$atts['autop'] = true;
	} else {
		$atts['autop'] = false;
	}

	$atts = apply_filters('learndash_ld_course_expire_status_shortcode_atts', $atts );
	
	if ( ( !empty( $atts['course_id'] ) ) && ( !empty( $atts['user_id'] ) ) ) {
		if ( sfwd_lms_has_access( $atts['course_id'], $atts['user_id'] ) ) {
			$course_meta = get_post_meta( $atts['course_id'], '_sfwd-courses', true );
			
			$courses_access_from = ld_course_access_from( $atts['course_id'], $atts['user_id'] );			
			if ( empty( $courses_access_from ) ) {
				$courses_access_from = learndash_user_group_enrolled_to_course_from( $atts['user_id'], $atts['course_id'] );
			}
			
			if ( !empty( $courses_access_from ) ) {
						
				$expire_on = ld_course_access_expires_on( $atts['course_id'], $atts['user_id'] );
				if (!empty($expire_on)) {
					if ($expire_on > time()) {
						$content_shortcode .= $atts['label_before'];
					} else {
						$content_shortcode .= $atts['label_after'];
					}
					$content_shortcode .= ' '. date($atts['format'], $expire_on + (get_option('gmt_offset') * 3600));
				}
			}
			
			$atts['content'] = do_shortcode( $content_shortcode );
			return SFWD_LMS::get_template( 
				'learndash_course_expire_status_message', 
				array(
					'shortcode_atts'	=>	$atts,
				), false
			);
		}
	} 
	
	if (!empty( $content_shortcode ) ) {
		$content .= $content_shortcode;
	}
	return $content;
}

add_shortcode( 'ld_course_expire_status', 'learndash_course_expire_status_shortcode' );


function learndash_quiz_shortcode( $atts, $content = '', $show_materials = false ) {

	global $learndash_shortcode_used, $learndash_shortcode_atts;
	
	$atts = shortcode_atts(
		array(
			'quiz_id'     => 0,
			'course_id'   => 0,
			'quiz_pro_id' => 0,
		),
		$atts
	);

	// Just to ensure compliance.
	$quiz_id = $atts['quiz_id'] = absint( $atts['quiz_id'] );
	$course_id = $atts['course_id'] = absint( $atts['course_id'] );
	$quiz_pro_id = $atts['quiz_pro_id'] = absint( $atts['quiz_pro_id'] );

	if ( empty( $atts['quiz_id'] ) ) {
		return $content;
	}
	$quiz_post = get_post( $atts['quiz_id'] );
	if ( ! is_a( $quiz_post, 'WP_Post' ) ) {
		return $content;
	}

	if ( empty( $course_id ) ) {
		if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) !== 'yes' ) {
			$course_id = learndash_get_setting( $quiz_post, 'lesson' );
			$course_id = absint( $course_id );
			if ( ! empty( $course_id ) ) {
				$atts['course_id'] = $course_id;
			}
		}
	}
	$learndash_shortcode_atts['ld_quiz'] = $atts;
	// Clear out any previous 'LDAdvQuiz' data.
	if ( isset( $learndash_shortcode_atts['LDAdvQuiz'] ) ) {
		unset( $learndash_shortcode_atts['LDAdvQuiz'] );
	}
	$learndash_shortcode_used = true;
		
	$lesson_progression_enabled = false;
	if ( ! empty( $atts['course_id'] ) ) {
		$lesson_progression_enabled = learndash_lesson_progression_enabled( $atts['course_id'] );
	}

	$has_access = '';

	$user_id = get_current_user_id();

	$quiz_post = get_post( $atts['quiz_id'] );
	if ( $quiz_post instanceof WP_Post ) {
		$quiz_settings = learndash_get_setting( $atts['quiz_id'] );
		$meta = SFWD_CPT_Instance::$instances[ 'sfwd-quiz' ]->get_settings_values( 'sfwd-quiz' );
	
		$show_content = ! ( ! empty( $lesson_progression_enabled ) && ! is_quiz_accessable( $user_id, $quiz_post, false, $course_id ) );
		$attempts_count = 0;
		$repeats = ( isset( $quiz_settings['repeats'] ) ) ? trim( $quiz_settings['repeats'] ) : '';
		if ( '' === $repeats ) {
			if ( ! empty( $quiz_settings['quiz_pro'] ) ) {
				$quiz_mapper = new WpProQuiz_Model_QuizMapper();
				$pro_quiz_edit = $quiz_mapper->fetch( $quiz_settings['quiz_pro'] );
				if ( ( $pro_quiz_edit ) && ( is_a( $pro_quiz_edit, 'WpProQuiz_Model_Quiz' ) ) ) {
					if ( ( isset( $atts['quiz_id'] ) ) && ( ! empty( $atts['quiz_id'] ) ) ) {
						$pro_quiz_edit->setPostId( $atts['quiz_id'] );
					}

					if ( $pro_quiz_edit->isQuizRunOnce() ) {
						$repeats = 0;
						// Update for later.
						learndash_update_setting( $quiz_post, 'repeats', $repeats );
					}
				}
			}
		}

		if ( $repeats !== '' ) {

			if ( $user_id ) {
				$usermeta = get_user_meta( $user_id, '_sfwd-quizzes', true );
				$usermeta = maybe_unserialize( $usermeta );

				if ( ! is_array( $usermeta ) ) { 
					$usermeta = array();
				}

				if ( ! empty( $usermeta ) )	{
					foreach ( $usermeta as $k => $v ) {
						if ( ( intval( $v['quiz'] ) === $atts['quiz_id'] ) ) {
							if ( ! empty( $atts['course_id'] ) ) {
								if ( ( isset( $v['course'] ) ) && ( ! empty( $v['course'] ) ) && ( absint( $v['course'] ) === absint( $atts['course_id'] ) ) ) {
									// Count the number of time the student has taken the quiz where the course_id matches.
									$attempts_count++;
								}
							} elseif ( empty( $atts['course_id'] ) ) {
								if ( ( isset( $v['course'] ) ) && ( empty( $v['course'] ) ) && ( absint( $v['course'] ) === absint( $atts['course_id'] ) ) ) {
									// Count the number of time the student has taken the quiz where the course_id is zero.
									$attempts_count++;
								}
							}
						}
					}
				}
			}
		}

		$attempts_left = ( ( $repeats === '' ) || ( absint( $repeats ) >= absint( $attempts_count ) ) );
		
		/**
		 * Filters the quiz attempts left for user.
		 *
		 * @since 3.1
		 *
		 * @param boolean $attempts_left True is Quiz attempts left. False if none.
		 * @param integer $attempts_count Number of Quiz attemplts already taken.
		 * @param integer $user_id ID of User taking Quiz.
		 * @param integer $quiz_id ID of Quiz being taken.
		 * @return integer Zero or greater value.
		 * See example https://bitbucket.org/snippets/learndash/Gjygja
		 */
		$attempts_left = apply_filters( 'learndash_quiz_attempts', $attempts_left, absint( $attempts_count ), absint( $user_id ), absint( $quiz_post->ID ) );
		$attempts_left = absint( $attempts_left );

		if ( ! empty( $lesson_progression_enabled ) && ! is_quiz_accessable( $user_id, $quiz_post, false, $course_id  ) ) {
			add_filter( 'comments_array', 'learndash_remove_comments', 1, 2 );
		}

		$materials = '';

			/**
		 * Filter for content access
		 *
		 * If not null, will display instead of quiz content
		 * 
		 * @since 2.1.0
		 * 
		 * @param  string
		 */
		$access_message = apply_filters( 'learndash_content_access', null, $quiz_post );
		if ( ! is_null( $access_message ) ) {
			$quiz_content = $access_message;
		} else {
			if ( true === $show_materials ) {
				if ( ! empty( $quiz_settings['quiz_materials'] ) ) {
					$materials = wp_specialchars_decode( $quiz_settings['quiz_materials'], ENT_QUOTES );
					if ( ! empty( $materials ) ) {
						$materials = do_shortcode( $materials );
					}
				}
			}
			
			$quiz_content = '';
			if ( ! empty( $quiz_settings['quiz_pro'] ) ) {
				$quiz_settings['lesson'] = 0;
				$quiz_settings['topic'] = 0;

				if ( ( ! empty( $course_id ) ) && ( ! empty( $quiz_id ) ) ) {
					$quiz_settings['topic'] = learndash_course_get_single_parent_step( $course_id, $quiz_id, learndash_get_post_type_slug( 'topic' ) );
					$quiz_settings['topic'] = absint( $quiz_settings['topic'] );

					$quiz_settings['lesson'] = learndash_course_get_single_parent_step( $course_id, $quiz_id, learndash_get_post_type_slug( 'lesson' ) );
					$quiz_settings['lesson'] = absint( $quiz_settings['lesson'] );
				}

				$quiz_content = wptexturize(
					do_shortcode( '[LDAdvQuiz ' . $quiz_settings['quiz_pro'] . ' quiz_pro_id="' . $quiz_settings['quiz_pro'] . '" quiz_id="' . $quiz_post->ID . '" course_id="' . $quiz_settings['course'] . '" lesson_id="' . $quiz_settings['lesson'] . '" topic_id="' . $quiz_settings['topic'] . '"]' )
				);
			} 

				/**
			 * Filter quiz content
			 * 
			 * @since 2.1.0
			 * 
			 * @param  string  $quiz_content
			 */
			$quiz_content = apply_filters( 'learndash_quiz_content', $quiz_content, $quiz_post );
		}

		$level = ob_get_level();
		ob_start();
		$template_file = SFWD_LMS::get_template( 'quiz', null, null, true );
		if ( ! empty( $template_file ) ) {
			include $template_file;
		}

		$content = learndash_ob_get_clean( $level );
	
		// Added this defined wrap in v2.1.8 as it was effecting <pre></pre>, <code></code> and other formatting of the content. 
		// See wrike https://www.wrike.com/open.htm?id=77352698 as to why this define exists
		if ( ( defined( 'LEARNDASH_NEW_LINE_AND_CR_TO_SPACE' ) ) && ( LEARNDASH_NEW_LINE_AND_CR_TO_SPACE == true ) ) {

			// Why is this here? 
			$content = str_replace( array( "\n", "\r" ), ' ', $content );
		}
	
		$user_has_access = $has_access ? 'user_has_access':'user_has_no_access';

			/**
		 * Filter content to be return inside div
		 * 
		 * @since 2.1.0
		 * 
		 * @param  string  $content 
		 */
		$content = '<div class="learndash ' . $user_has_access . '"  id="learndash_post_' . $quiz_post->ID . '">' . apply_filters( 'learndash_content', $content, $quiz_post ) . '</div>';
	}
	
	return $content;
}
add_shortcode( 'ld_quiz', 'learndash_quiz_shortcode' );

function ld_course_list_shortcode_pager() {
	$reply_data = array();

	//error_log('_POST<pre>'. print_r( $_POST, true ) .'</pre>' );

	if ( ( isset( $_POST['paged'] ) ) && ( !empty( $_POST['paged'] ) ) ) 
		$paged = intval( $_POST['paged'] );
	else
		$paged = 1;
	

	if ( ( isset( $_POST['shortcode_atts'] ) ) && ( !empty( $_POST['shortcode_atts'] ) ) ) 
		$shortcode_atts = $_POST['shortcode_atts'];
	else
		$shortcode_atts = array();
	
	$shortcode_atts['include_outer_wrapper'] = 'false';
	$shortcode_atts['paged'] = $paged;
	//error_log('shortcode_atts<pre>'. print_r( $shortcode_atts, true ) .'</pre>' );
		
	$reply_data['content'] = ld_course_list( $shortcode_atts );
	
	echo json_encode( $reply_data );
	die();
	
}
	
add_action( 'wp_ajax_ld_course_list_shortcode_pager', 'ld_course_list_shortcode_pager' );
add_action( 'wp_ajax_nopriv_ld_course_list_shortcode_pager', 'ld_course_list_shortcode_pager' );