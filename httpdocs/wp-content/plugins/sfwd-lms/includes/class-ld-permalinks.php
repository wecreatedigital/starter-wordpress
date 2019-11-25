<?php

if ( !class_exists( 'LearnDash_Permalinks' ) ) {
	class LearnDash_Permalinks  {

		function __construct() {
			add_action( 'generate_rewrite_rules', array( $this, 'generate_rewrite_rules' ) );
			add_filter( 'post_type_link', array( $this, 'post_type_link' ), 10, 4 );
			add_filter( 'get_edit_post_link', array( $this, 'get_edit_post_link' ), 10, 3 );
			add_filter( 'get_sample_permalink', array( $this, 'get_sample_permalink' ), 99, 5 );

			add_action( 'comment_form_top', array( $this, 'comment_form_top' ) );
			add_action( 'comment_post', array( $this, 'comment_post' ) );
		}
		
		/**
		 * Setup custom rewrtie URLs. 
		 * Important note: This is very much dependant on the order of the registered post types. This is import when WP goes to parse the request. See
		 * the logic in wp-includes/class-wp.php starting in the loop at line 289 where it loops the registered CPTs. Within this loop at line 311 it
		 * set the queried post_type with the last matched post_type per the parse/marched request. So if the Quiz CPT is registered before Topic then 
		 * when we try to match the /courses/course-slug/lessons/lesson-slug/topics/topic-slug/quizzes/quiz-slug/ the queried 'post_type' will be set to 
		 * topic not quiz. As a result in LD v2.5 in includes/class-ld-lms.php where we build the $post_args array we ensure the order of the to-be 
		 * CPTs.
		 */
		function generate_rewrite_rules( $wp_rewrite ) {
			if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Section_Permalinks', 'nested_urls' ) == 'yes' ) {
				$courses_cpt	=	get_post_type_object( 'sfwd-courses' );
				$lessons_cpt	=	get_post_type_object( 'sfwd-lessons' );
				$topics_cpt		= 	get_post_type_object( 'sfwd-topic' );
				$quizzes_cpt	= 	get_post_type_object( 'sfwd-quiz' );
	
				/*
				$wp_rewrite->rules = array_merge( 
					array( 
						$courses_cpt->rewrite['slug'] .'/([^/]+)/'. $quizzes_cpt->rewrite['slug'] .'/([^/]+)/?$' => 'index.php?'. $courses_cpt->name .'=$matches[1]&'. $quizzes_cpt->name .'=$matches[2]',
						$courses_cpt->rewrite['slug'] .'/([^/]+)/'. $lessons_cpt->rewrite['slug'] .'/([^/]+)/?$' => 'index.php?'. $courses_cpt->name .'=$matches[1]&'. $lessons_cpt->name .'=$matches[2]',
						$courses_cpt->rewrite['slug'] .'/([^/]+)/'. $lessons_cpt->rewrite['slug'] .'/([^/]+)/'. $quizzes_cpt->rewrite['slug'] .'/([^/]+)/?$' => 'index.php?'. $courses_cpt->name .'=$matches[1]&'. $lessons_cpt->name .'=$matches[2]&'. $quizzes_cpt->name .'=$matches[3]',
						$courses_cpt->rewrite['slug'] .'/([^/]+)/'. $lessons_cpt->rewrite['slug'] .'/([^/]+)/'. $topics_cpt->rewrite['slug'] .'/([^/]+)/?$' => 'index.php?'. $courses_cpt->name .'=$matches[1]&'. $lessons_cpt->name .'=$matches[2]&'. $topics_cpt->name .'=$matches[3]',
						$courses_cpt->rewrite['slug'] .'/([^/]+)/'. $lessons_cpt->rewrite['slug'] .'/([^/]+)/'. $topics_cpt->rewrite['slug'] .'/([^/]+)/'. $quizzes_cpt->rewrite['slug'] .'/([^/]+)/?$' => 'index.php?'. $courses_cpt->name .'=$matches[1]&'. $lessons_cpt->name .'=$matches[2]&'. $topics_cpt->name .'=$matches[3]&'. $quizzes_cpt->name .'=$matches[4]',
					), $wp_rewrite->rules 
				);
				*/
				
				$ld_rewrite_rules = apply_filters( 'learndash_permalinks_nested_urls', array( 
					// Course > Quiz
					//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				    //	[quizzes/[^/]+/attachment/([^/]+)/?$] => index.php?attachment=$matches[1]
				    //	[quizzes/[^/]+/attachment/([^/]+)/trackback/?$] => index.php?attachment=$matches[1]&tb=1
				    //	[quizzes/[^/]+/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$] => index.php?attachment=$matches[1]&feed=$matches[2]
				    //	[quizzes/[^/]+/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$] => index.php?attachment=$matches[1]&feed=$matches[2]
				    //	[quizzes/[^/]+/attachment/([^/]+)/comment-page-([0-9]{1,})/?$] => index.php?attachment=$matches[1]&cpage=$matches[2]
				    //	[quizzes/[^/]+/attachment/([^/]+)/embed/?$] => index.php?attachment=$matches[1]&embed=true
				    //	[quizzes/([^/]+)/embed/?$] => index.php?sfwd-quiz=$matches[1]&embed=true
				    //	[quizzes/([^/]+)/trackback/?$] => index.php?sfwd-quiz=$matches[1]&tb=1
				    //	[quizzes/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$] => index.php?sfwd-quiz=$matches[1]&feed=$matches[2]
				    //	[quizzes/([^/]+)/(feed|rdf|rss|rss2|atom)/?$] => index.php?sfwd-quiz=$matches[1]&feed=$matches[2]
				    //	[quizzes/([^/]+)/page/?([0-9]{1,})/?$] => index.php?sfwd-quiz=$matches[1]&paged=$matches[2]
				    //	[quizzes/([^/]+)/comment-page-([0-9]{1,})/?$] => index.php?sfwd-quiz=$matches[1]&cpage=$matches[2]
				    $courses_cpt->rewrite['slug'] .'/([^/]+)/'. $quizzes_cpt->rewrite['slug'] .'/([^/]+)/' . 'comment-page-([0-9]{1,})/?$' => 'index.php?'. $courses_cpt->name .'=$matches[1]&'. $quizzes_cpt->name .'=$matches[2]&cpage=$matches[3]',
					
					//	[quizzes/([^/]+)(?:/([0-9]+))?/?$] => index.php?sfwd-quiz=$matches[1]&page=$matches[2]
					$courses_cpt->rewrite['slug'] .'/([^/]+)/'. $quizzes_cpt->rewrite['slug'] .'/([^/]+)' . '(?:/([0-9]+))?/?$' => 'index.php?'. $courses_cpt->name .'=$matches[1]&'. $quizzes_cpt->name .'=$matches[2]&page=$matches[3]',
					
				    //	[quizzes/[^/]+/([^/]+)/?$] => index.php?attachment=$matches[1]
				    //	[quizzes/[^/]+/([^/]+)/trackback/?$] => index.php?attachment=$matches[1]&tb=1
				    //	[quizzes/[^/]+/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$] => index.php?attachment=$matches[1]&feed=$matches[2]
				    //	[quizzes/[^/]+/([^/]+)/(feed|rdf|rss|rss2|atom)/?$] => index.php?attachment=$matches[1]&feed=$matches[2]
				    //	[quizzes/[^/]+/([^/]+)/comment-page-([0-9]{1,})/?$] => index.php?attachment=$matches[1]&cpage=$matches[2]
				    //	[quizzes/[^/]+/([^/]+)/embed/?$] => index.php?attachment=$matches[1]&embed=true
					
					
										
					// Course > Lesson
					//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				    //	[lessons/[^/]+/attachment/([^/]+)/?$] => index.php?attachment=$matches[1]
				    //	[lessons/[^/]+/attachment/([^/]+)/trackback/?$] => index.php?attachment=$matches[1]&tb=1
				    //	[lessons/[^/]+/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$] => index.php?attachment=$matches[1]&feed=$matches[2]
				    //	[lessons/[^/]+/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$] => index.php?attachment=$matches[1]&feed=$matches[2]
				    //	[lessons/[^/]+/attachment/([^/]+)/comment-page-([0-9]{1,})/?$] => index.php?attachment=$matches[1]&cpage=$matches[2]
				    //	[lessons/[^/]+/attachment/([^/]+)/embed/?$] => index.php?attachment=$matches[1]&embed=true
				    //	[lessons/([^/]+)/embed/?$] => index.php?sfwd-lessons=$matches[1]&embed=true
				    //	[lessons/([^/]+)/trackback/?$] => index.php?sfwd-lessons=$matches[1]&tb=1
				    //	[lessons/([^/]+)/page/?([0-9]{1,})/?$] => index.php?sfwd-lessons=$matches[1]&paged=$matches[2]

				    //	[lessons/([^/]+)/comment-page-([0-9]{1,})/?$] => index.php?sfwd-lessons=$matches[1]&cpage=$matches[2]
					$courses_cpt->rewrite['slug'] .'/([^/]+)/'. $lessons_cpt->rewrite['slug'] .'/([^/]+)/' . 'comment-page-([0-9]{1,})/?$' => 'index.php?'. $courses_cpt->name .'=$matches[1]&'. $lessons_cpt->name .'=$matches[2]&cpage=$matches[3]',
				    
					//	[lessons/([^/]+)(?:/([0-9]+))?/?$] => index.php?sfwd-lessons=$matches[1]&page=$matches[2]
					$courses_cpt->rewrite['slug'] .'/([^/]+)/'. $lessons_cpt->rewrite['slug'] .'/([^/]+)' . '(?:/([0-9]+))?/?$' => 'index.php?'. $courses_cpt->name .'=$matches[1]&'. $lessons_cpt->name .'=$matches[2]&page=$matches[3]',
										
				    //	[lessons/[^/]+/([^/]+)/?$] => index.php?attachment=$matches[1]					
				    //	[lessons/[^/]+/([^/]+)/trackback/?$] => index.php?attachment=$matches[1]&tb=1
				    //	[lessons/[^/]+/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$] => index.php?attachment=$matches[1]&feed=$matches[2]
				    //	[lessons/[^/]+/([^/]+)/(feed|rdf|rss|rss2|atom)/?$] => index.php?attachment=$matches[1]&feed=$matches[2]
				    //	[lessons/[^/]+/([^/]+)/comment-page-([0-9]{1,})/?$] => index.php?attachment=$matches[1]&cpage=$matches[2]
				    //	[lessons/[^/]+/([^/]+)/embed/?$] => index.php?attachment=$matches[1]&embed=true
					
					
										
					// Course > Lesson > Quiz
					//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
					$courses_cpt->rewrite['slug'] .'/([^/]+)/'. $lessons_cpt->rewrite['slug'] .'/([^/]+)/'. $quizzes_cpt->rewrite['slug'] .'/([^/]+)/' . 'comment-page-([0-9]{1,})/?$' => 'index.php?'. $courses_cpt->name .'=$matches[1]&'. $lessons_cpt->name .'=$matches[2]&'. $quizzes_cpt->name .'=$matches[3]&cpage=$matches[4]',

					$courses_cpt->rewrite['slug'] .'/([^/]+)/'. $lessons_cpt->rewrite['slug'] .'/([^/]+)/'. $quizzes_cpt->rewrite['slug'] .'/([^/]+)' . '(?:/([0-9]+))?/?$' => 'index.php?'. $courses_cpt->name .'=$matches[1]&'. $lessons_cpt->name .'=$matches[2]&'. $quizzes_cpt->name .'=$matches[3]&page=$matches[4]',
					
					// Course > Lesson > Topic
					//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				    //	[topic/[^/]+/attachment/([^/]+)/?$] => index.php?attachment=$matches[1]
				    //	[topic/[^/]+/attachment/([^/]+)/trackback/?$] => index.php?attachment=$matches[1]&tb=1
				    //	[topic/[^/]+/attachment/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$] => index.php?attachment=$matches[1]&feed=$matches[2]
				    //	[topic/[^/]+/attachment/([^/]+)/(feed|rdf|rss|rss2|atom)/?$] => index.php?attachment=$matches[1]&feed=$matches[2]
				    //	[topic/[^/]+/attachment/([^/]+)/comment-page-([0-9]{1,})/?$] => index.php?attachment=$matches[1]&cpage=$matches[2]
				    //	[topic/[^/]+/attachment/([^/]+)/embed/?$] => index.php?attachment=$matches[1]&embed=true
				    //	[topic/([^/]+)/embed/?$] => index.php?sfwd-topic=$matches[1]&embed=true
				    //	[topic/([^/]+)/trackback/?$] => index.php?sfwd-topic=$matches[1]&tb=1
				    //	[topic/([^/]+)/page/?([0-9]{1,})/?$] => index.php?sfwd-topic=$matches[1]&paged=$matches[2]
					
				    //	[topic/([^/]+)/comment-page-([0-9]{1,})/?$] => index.php?sfwd-topic=$matches[1]&cpage=$matches[2]
					$courses_cpt->rewrite['slug'] .'/([^/]+)/'. $lessons_cpt->rewrite['slug'] .'/([^/]+)/'. $topics_cpt->rewrite['slug'] .'/([^/]+)/' . 'comment-page-([0-9]{1,})/?$' => 'index.php?'. $courses_cpt->name .'=$matches[1]&'. $lessons_cpt->name .'=$matches[2]&'. $topics_cpt->name .'=$matches[3]&cpage=$matches[4]',
				    
					//	[topic/([^/]+)(?:/([0-9]+))?/?$] => index.php?sfwd-topic=$matches[1]&page=$matches[2]
					$courses_cpt->rewrite['slug'] .'/([^/]+)/'. $lessons_cpt->rewrite['slug'] .'/([^/]+)/'. $topics_cpt->rewrite['slug'] .'/([^/]+)' . '(?:/([0-9]+))?/?$' => 'index.php?'. $courses_cpt->name .'=$matches[1]&'. $lessons_cpt->name .'=$matches[2]&'. $topics_cpt->name .'=$matches[3]&page=$matches[4]',
					
				    //	[topic/[^/]+/([^/]+)/?$] => index.php?attachment=$matches[1]
				    //	[topic/[^/]+/([^/]+)/trackback/?$] => index.php?attachment=$matches[1]&tb=1
				    //	[topic/[^/]+/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$] => index.php?attachment=$matches[1]&feed=$matches[2]
				    //	[topic/[^/]+/([^/]+)/(feed|rdf|rss|rss2|atom)/?$] => index.php?attachment=$matches[1]&feed=$matches[2]
				    //	[topic/[^/]+/([^/]+)/comment-page-([0-9]{1,})/?$] => index.php?attachment=$matches[1]&cpage=$matches[2]
				    //	[topic/[^/]+/([^/]+)/embed/?$] => index.php?attachment=$matches[1]&embed=true
					
					// Course > Lesson > Topic > Quiz
					//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
					$courses_cpt->rewrite['slug'] .'/([^/]+)/'. $lessons_cpt->rewrite['slug'] .'/([^/]+)/'. $topics_cpt->rewrite['slug'] .'/([^/]+)/'. $quizzes_cpt->rewrite['slug'] .'/([^/]+)/' . 'comment-page-([0-9]{1,})/?$' => 'index.php?'. $courses_cpt->name .'=$matches[1]&'. $lessons_cpt->name .'=$matches[2]&'. $topics_cpt->name .'=$matches[3]&'. $quizzes_cpt->name .'=$matches[4]&cpage=$matches[5]',
					$courses_cpt->rewrite['slug'] .'/([^/]+)/'. $lessons_cpt->rewrite['slug'] .'/([^/]+)/'. $topics_cpt->rewrite['slug'] .'/([^/]+)/'. $quizzes_cpt->rewrite['slug'] .'/([^/]+)' . '(?:/([0-9]+))?/?$' => 'index.php?'. $courses_cpt->name .'=$matches[1]&'. $lessons_cpt->name .'=$matches[2]&'. $topics_cpt->name .'=$matches[3]&'. $quizzes_cpt->name .'=$matches[4]&page=$matches[5]',
					)
				);
				
				if ( !empty( $ld_rewrite_rules ) ) {
					$wp_rewrite->rules = array_merge( $ld_rewrite_rules, $wp_rewrite->rules );
				}
			}
		}
		
		// This second filter will correct calls to the WordPress get_permalink() function to use the new structure
		function post_type_link( $post_link = '', $post = null, $leavename  = false, $sample = false ) {
			global $pagenow, $wp_rewrite;
			
			$url_part_old = '';
			$url_part_new = '';

			if ( ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Section_Permalinks', 'nested_urls' ) == 'yes' ) && ( in_array( $post->post_type, array( 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz' ) ) ) ) {
				
				// If we are viewing one of the list tables we only effect the link if the course_id URL param is set
				if ( ( is_admin() ) && ( 'edit.php' == $pagenow ) ) {
					if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
						$course_id = 0;
					
						if ( isset( $_GET['course_id'] ) ) {
							$course_id = intval( $_GET['course_id'] );
						}
					
						if ( empty( $course_id ) ) {
							return $post_link;
						}
					}
				}

				$courses_cpt 		= 	get_post_type_object( 'sfwd-courses' );
				$lessons_cpt 		= 	get_post_type_object( 'sfwd-lessons' );
				$topics_cpt 		= 	get_post_type_object( 'sfwd-topic' );
				$quizzes_cpt 		= 	get_post_type_object( 'sfwd-quiz' );

			    if ( $lessons_cpt->name == $post->post_type  ) {

					$lesson = $post;
	
					//$course_id = get_post_meta( $lesson->ID, 'course_id', true );
					$course_id = apply_filters( 'learndash_post_link_course_id', learndash_get_course_id( $lesson->ID ), $post_link, $post );
		
					if ( !empty( $course_id ) ) {
						$course = get_post( $course_id );
						if ( $course instanceof WP_Post ) {

							if ( $sample === false ) {
								if ( $wp_rewrite->using_permalinks() ) {
									$url_part_old = '/'. $lessons_cpt->rewrite['slug'] .'/'. $lesson->post_name;
								} else {
									$url_part_old = add_query_arg( $lessons_cpt->name, $lesson->post_name, $url_part_old );
								}
							} else {
								if ( $wp_rewrite->using_permalinks() ) {
									$url_part_old = '/'. $lessons_cpt->rewrite['slug'] .'/%pagename%';
								} else {
									$url_part_old = add_query_arg( $lessons_cpt->name, $lesson->post_name, $url_part_old );
								}
							}

							if ( $sample === false ) {
								if ( $wp_rewrite->using_permalinks() ) {
									$url_part_new = '/'. $courses_cpt->rewrite['slug'] .'/'. $course->post_name .'/'. $lessons_cpt->rewrite['slug'] .'/'. $lesson->post_name;
								} else {
									$url_part_new = add_query_arg( $courses_cpt->name, $course->post_name, $url_part_new );
									$url_part_new = add_query_arg( $lessons_cpt->name, $lesson->post_name, $url_part_new );
								}
							} else {
								if ( $wp_rewrite->using_permalinks() ) {
									$url_part_new = '/'. $courses_cpt->rewrite['slug'] .'/'. $course->post_name .'/'. $lessons_cpt->rewrite['slug'] .'/%pagename%';
								} else {
									$url_part_new = add_query_arg( $courses_cpt->name, $course->post_name, $url_part_new );
									$url_part_new = add_query_arg( $lessons_cpt->name, $lesson->post_name, $url_part_new );
								}
							}
						}
					}
			    } else if ( $topics_cpt->name == $post->post_type  ) {

					$topic = $post;

					//$course_id = learndash_get_course_id( $topic->ID );
					$course_id = apply_filters( 'learndash_post_link_course_id', learndash_get_course_id( $topic->ID ), $post_link, $post );
					if ( !empty( $course_id ) ) {
						if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
							$lesson_id = learndash_course_get_single_parent_step( $course_id, $topic->ID );
						} else {
							$lesson_id = learndash_get_lesson_id( $topic->ID );
						}

						if ( !empty( $lesson_id ) ) {
							$course = get_post( $course_id );
							$lesson = get_post( $lesson_id );
		
							if ( ( $course instanceof WP_Post ) && ( $lesson instanceof WP_Post ) ) {

								if ( $sample === false ) {
									if ( $wp_rewrite->using_permalinks() ) {
										$url_part_old = '/'. $topics_cpt->rewrite['slug'] .'/'. $topic->post_name;
									} else {
										$url_part_old = add_query_arg( $topics_cpt->name, $topic->post_name, $url_part_old );
									}
								} else {
									if ( $wp_rewrite->using_permalinks() ) {
										$url_part_old = '/'. $topics_cpt->rewrite['slug'] .'/%pagename%';
									} else {
										$url_part_old = add_query_arg( $topics_cpt->name, $topic->post_name, $url_part_old );
									}
								}
								
								if ( $sample === false ) {
									if ( $wp_rewrite->using_permalinks() ) {
										$url_part_new = '/'. $courses_cpt->rewrite['slug'] .'/'. $course->post_name .'/'. $lessons_cpt->rewrite['slug'] .'/'. $lesson->post_name .'/'. $topics_cpt->rewrite['slug'] .'/'. $topic->post_name;
									} else {
										$url_part_new = add_query_arg( $courses_cpt->name, $course->post_name, $url_part_new );
										$url_part_new = add_query_arg( $lessons_cpt->name, $lesson->post_name, $url_part_new );
										$url_part_new = add_query_arg( $topics_cpt->name, $topic->post_name, $url_part_new );
									}
								} else {
									if ( $wp_rewrite->using_permalinks() ) {
										$url_part_new = '/'. $courses_cpt->rewrite['slug'] .'/'. $course->post_name .'/'. $lessons_cpt->rewrite['slug'] .'/'. $lesson->post_name .'/'. $topics_cpt->rewrite['slug'] .'/%pagename%';
									} else {
										$url_part_new = add_query_arg( $courses_cpt->name, $course->post_name, $url_part_new );
										$url_part_new = add_query_arg( $lessons_cpt->name, $lesson->post_name, $url_part_new );
										$url_part_new = add_query_arg( $topics_cpt->name, $topic->post_name, $url_part_new );
									}
								}
							}
						}
					}
			    } else if ( $quizzes_cpt->name == $post->post_type  ) {
					$quiz = $post;

					//$course_id = learndash_get_course_id( $quiz->ID );
					$course_id = apply_filters( 'learndash_post_link_course_id', learndash_get_course_id( $quiz->ID ), $post_link, $post );

					if ( !empty( $course_id ) ) {
						if ( $sample === false ) {
							if ( $wp_rewrite->using_permalinks() ) {
								$url_part_old = '/'. $quizzes_cpt->rewrite['slug'] . '/' . $quiz->post_name;
							} else {
								$url_part_old = add_query_arg( $quizzes_cpt->name, $quiz->post_name, $url_part_old );
							}
						} else {
							if ( $wp_rewrite->using_permalinks() ) {
								$url_part_old = '/'. $quizzes_cpt->rewrite['slug'] . '/%pagename%';
							} else {
								$url_part_old = add_query_arg( $quizzes_cpt->name, $quiz->post_name, $url_part_old );
							}
						}
						
						$course = get_post( $course_id );
						if ( $course instanceof WP_Post ) {
							$quiz_parents = array();
							
							if ( $wp_rewrite->using_permalinks() ) {
								$url_part_new = '/'. $courses_cpt->rewrite['slug'] .'/'. $course->post_name;
							} else {
								$url_part_new = add_query_arg( $courses_cpt->name, $course->post_name, $url_part_new );
							}

							if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
							$quiz_parents = learndash_course_get_all_parent_step_ids( $course_id, $quiz->ID );
							} else {
								$lesson_id = learndash_get_lesson_id( $quiz->ID );
								if ( !empty( $lesson_id ) ) {
									if ( get_post_type( $lesson_id ) == $topics_cpt->name ) {
										$topic_id = $lesson_id;
										$lesson_id = learndash_get_lesson_id( $topic_id );
										if ( !empty( $lesson_id ) ) {
											if ( get_post_type( $lesson_id ) == $lessons_cpt->name ) {
												$quiz_parents[] = $lesson_id;
												$quiz_parents[] = $topic_id;
											}
										}
									}
								}
							}

							if ( !empty( $quiz_parents ) ) {
								foreach( $quiz_parents as $quiz_parent_id ) {
									$quiz_parent_post = get_post( $quiz_parent_id );
									if ( $quiz_parent_post->post_type == $lessons_cpt->name ) {
										if ( $wp_rewrite->using_permalinks() ) {
											$parent_slug = $lessons_cpt->rewrite['slug'];
										} else {
											$parent_slug = $lessons_cpt->name;
										}
									} else if ( $quiz_parent_post->post_type == $topics_cpt->name ) {
										if ( $wp_rewrite->using_permalinks() ) {
											$parent_slug = $topics_cpt->rewrite['slug'];
										} else {
											$parent_slug = $topics_cpt->name;
										}
									}

									if ( $wp_rewrite->using_permalinks() ) {
										$url_part_new .= '/'. $parent_slug .'/'. $quiz_parent_post->post_name;
									} else {
										$url_part_new = add_query_arg( $parent_slug, $quiz_parent_post->post_name, $url_part_new );
									}
								}
							}

							if ( $sample === false ) {
								if ( $wp_rewrite->using_permalinks() ) {
									$url_part_new .= '/'. $quizzes_cpt->rewrite['slug'] .'/'. $quiz->post_name;
								} else {
									$url_part_new = add_query_arg( $quizzes_cpt->name, $quiz->post_name, $url_part_new );
								}
							} else {
								if ( $wp_rewrite->using_permalinks() ) {
									$url_part_new .= '/'. $quizzes_cpt->rewrite['slug'] .'/%pagename%';
								} else {
									$url_part_new = add_query_arg( $quizzes_cpt->name, $quiz->post_name, $url_part_new );
								}
							}
						}
					} else if ( !empty( $course_id ) ) {
						$course = get_post( $course_id );
						if ( $course instanceof WP_Post ) {
							if ( $sample === false ) {
								if ( $wp_rewrite->using_permalinks() ) {
									$url_part_old = '/'. $quizzes_cpt->rewrite['slug'] .'/'. $quiz->post_name;
								} else {
									$url_part_old = add_query_arg( $quizzes_cpt->name, $quiz->post_name, $url_part_old );
								}
							} else {
								if ( $wp_rewrite->using_permalinks() ) {
									$url_part_old = '/'. $quizzes_cpt->rewrite['slug'] .'/%pagename%';
								} else {
									$url_part_old = add_query_arg( $quizzes_cpt->name, $quiz->post_name, $url_part_old );
								}
							}

							if ( $sample === false ) {
								if ( $wp_rewrite->using_permalinks() ) {
									$url_part_new = '/'. $courses_cpt->rewrite['slug'] .'/'. $course->post_name .'/'. $quizzes_cpt->rewrite['slug'] .'/'. $quiz->post_name;
								} else {
									$url_part_new = add_query_arg( $courses_cpt->rewrite['slug'], $course->post_name, $url_part_new );
									$url_part_new = add_query_arg( $quizzes_cpt->rewrite['slug'], $quiz->post_name, $url_part_new );
								}
							} else {
								if ( $wp_rewrite->using_permalinks() ) {
									$url_part_new = '/'. $courses_cpt->rewrite['slug'] .'/'. $course->post_name .'/'. $quizzes_cpt->rewrite['slug'] .'/%pagename%';
								} else {
									$url_part_new = add_query_arg( $courses_cpt->rewrite['slug'], $course->post_name, $url_part_new );
									$url_part_new = add_query_arg( $quizzes_cpt->rewrite['slug'], $quiz->post_name, $url_part_new );
								}
							}
						}
					}
			    }
			}

			if ( ( ! empty( $url_part_new ) ) && ( !empty( $url_part_old ) ) ) {
				if ( ! $wp_rewrite->using_permalinks() ) {
					$url_part_old = str_replace( '?', '', $url_part_old );
					$url_part_new = str_replace( '?', '', $url_part_new );

					/**
					 * We could normally just append the new args to the end of the URL. But 
					 * we want to control the ordering for readability. 
					 */
					$args = wp_parse_args( $url_part_new, array() );
					if ( ! empty( $args ) ) {
						foreach( $args as $arg_key => $arg_val ) {
							$post_link = remove_query_arg( $arg_key, $post_link );
						}

						$post_link_parts_old = wp_parse_url( $post_link );
						if ( ( isset( $post_link_parts_old['query'] ) ) && ( ! empty( $post_link_parts_old['query'] ) ) ) {
							$post_link = str_replace( $post_link_parts_old['query'], '', $post_link );
						}

						$post_link = add_query_arg( $args, $post_link );
						$post_link_parts_new = wp_parse_url( $post_link );

						/**
						 * Here we have removed the original LD post type elements and any non-LD elements from the 
						 * original URL. Now we want to add the non-LD elements back.
						 */
						if ( ( isset( $post_link_parts_old['query'] ) ) && ( ! empty( $post_link_parts_old['query'] ) ) ) {	
							if ( ( isset( $post_link_parts_old['query'] ) ) && ( ! empty( $post_link_parts_old['query'] ) ) ) {
								$post_link .= '&' . $post_link_parts_old['query'];
							} else {
								$post_link .= '?' . $post_link_parts_old['query'];
							}
						}
					}
				} else {
					$post_link = str_replace( $url_part_old, $url_part_new, $post_link );
				}
			}

			return $post_link;
		}
		
		function row_actions( $actions = array(), $post = '' ) {
			global $pagenow, $typenow;
			
			if ( ( is_admin() ) && ( 'edit.php' == $pagenow ) ) {
				if ( ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) && ( in_array( $typenow, array( 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz' ) ) ) ) {
					$course_id = 0;
				
					if ( isset( $_GET['course_id'] ) ) {
						$course_id = intval( $_GET['course_id'] );
					}
				
					if ( ( empty( $course_id ) ) && ( isset( $actions['view'] ) ) ) { 
						unset( $actions['view'] );
					}
				}
			}
			
			return $actions;
		}
		
		// 
		function get_edit_post_link( $link = '', $post_id = 0, $context = '' ) {
			global $pagenow;

			if ( ( !empty( $post_id ) ) && ( !is_admin() ) || ( ( is_admin() && ( in_array( $pagenow, array( 'post.php', 'edit.php' ) ) ) ) ) ) {
				$post_type_name = get_post_type( $post_id );
				if ( ( !empty( $post_type_name ) ) && ( in_array( $post_type_name, array( 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz' ) ) ) ) {
					if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {

						$course_id = 0;					
						$course_id = learndash_get_course_id( $post_id );
						if ( !empty( $course_id ) ) {
							$link = add_query_arg( 'course_id', $course_id, $link );
						}
					}
				}

				if ( ( ! empty( $post_type_name ) ) && ( in_array( $post_type_name, LDLMS_Post_Types::get_post_types( 'quiz_questions' ) ) ) ) {
					$quiz_id = 0;
					$quiz_id = learndash_get_quiz_id( $post_id );
					if ( ! empty( $quiz_id ) ) {
						$link = add_query_arg( 'quiz_id', $quiz_id, $link );
					}
				}
			}
			
			return $link;
		}

		// Hook into the admin post editor Permalink display. We override the LD post items so they include the full nested URL
		function get_sample_permalink( $permalink = '', $post_id = 0, $title = '', $name = '', $post = '' ) {
			global $pagenow;
			
			if ( ( is_admin() ) && ( $pagenow == 'post.php' ) && ( $post instanceof WP_Post ) && ( in_array( $post->post_type, array( 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz' ) ) ) ) {
				//if ( $post->post_type == 'sfwd-quiz' ) {
				//	if ( ( !isset( $_GET['course_id'] ) ) || ( empty( $_GET['course_id'] ) ) ) {
				//		return $permalink;
				//	}
				//}
				$permalink_new = $this->post_type_link( $permalink[0], $post, false, true );
				if ( ( !empty( $permalink_new ) ) && ( $permalink_new !== $permalink[0] ) ) {
					$permalink[0] = $permalink_new;
				}
			}
			
			return $permalink;
		}


		/**
		 * Action for comment form when nested URLs are enabled. This way ther user is returned to this course step URL
		 *
		 * @since 2.5.5
		 */
		function comment_form_top( ) {
			$queried_object = get_queried_object();
			
			if ( ( is_a( $queried_object, 'WP_Post' ) ) && ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Section_Permalinks', 'nested_urls' ) == 'yes' ) ) {
				if ( in_array( $queried_object->post_type, array( 'sfwd-lessons', 'sfwd-topic', 'sfwd-quiz' ) ) ) {
					echo '<input type="hidden" name="step_id" value="'. $queried_object->ID .'" />';

					$course_id = learndash_get_course_id( $queried_object->ID );
					if ( !empty( $course_id ) ) {
						echo '<input type="hidden" name="course_id" value="'. $course_id .'" />';
						
						$redirect_to = learndash_get_step_permalink( $queried_object->ID, $course_id );
						if ( !empty( $redirect_to ) ) {
							// This 'redirect_to' is used by WP in wp-comments-post.php to redirect back to a specific URL. 
							// This is the important part. 
							echo '<input type="hidden" name="redirect_to" value="'. $redirect_to .'" />';
						}
					}
				}
			}
		}
		
		/**
		 * Add the course_id to comment meta
		 *
		 * @since 2.5.5
		 */
		function comment_post( $comment_ID = 0 ) {
			if ( ( isset( $_POST['course_id'] ) ) && ( !empty( $_POST['course_id'] ) ) ) {
				update_comment_meta( $comment_ID, 'course_id', intval( $_POST['course_id'] ) );
			}
		}

		// End of function
	}
}

add_action( 'plugins_loaded', function() {
	new LearnDash_Permalinks();
} );


function learndash_get_step_permalink( $step_id = 0, $step_course_id = null ) {
	 
	if ( !empty( $step_id ) ) {
		if ( !is_null( $step_course_id ) ) {
			$GLOBALS['step_course_id'] = $step_course_id;
			add_filter( 'learndash_post_link_course_id', function( $course_id ) {
				if ( ( isset( $GLOBALS['step_course_id'] ) ) && ( !is_null( $GLOBALS['step_course_id'] ) ) ) {
					$course_id = $GLOBALS['step_course_id'];
				}
				return $course_id;
			} );
		}
		$step_permalink = get_permalink( $step_id );
		
		if ( isset( $GLOBALS['step_course_id'] ) ) {
			unset( $GLOBALS['step_course_id'] );
		}
		
		return $step_permalink;
	}
}


/**
 * Used when editing Lesson, Topic, Quiz or Question post items. This filter is needed to add 
 * the 'course_id' parameter back to the edit URL after the post is submitted (saved).
 * 
 * @since 2.5 
 */
function learndash_redirect_post_location( $location = '' ) {
	if ( ( is_admin() ) && ( !empty( $location ) ) ) {
		
		global $typenow;
		
		if ( ( $typenow == 'sfwd-lessons' ) || ( $typenow == 'sfwd-topic' ) || ( $typenow == 'sfwd-quiz' ) ) {
			if ( ( isset( $_POST['ld-course-switcher'] ) ) && ( !empty( $_POST['ld-course-switcher'] ) ) ) {
				$post_args = wp_parse_args( $_POST['ld-course-switcher'], array() );
				if ( ( isset( $post_args['course_id'] ) ) && ( !empty( $post_args['course_id'] ) ) ) {
					$location = add_query_arg( 'course_id', intval( $post_args['course_id'] ), $location );
				}
			}
		} elseif ( $typenow == 'sfwd-question' ) {
			if ( ( isset( $_POST['ld-quiz-switcher'] ) ) && ( ! empty( $_POST['ld-quiz-switcher'] ) ) ) {
				$post_args = wp_parse_args( $_POST['ld-quiz-switcher'], array() );
				if ( ( isset( $post_args['quiz_id'] ) ) && ( ! empty( $post_args['quiz_id'] ) ) ) {
					$location = add_query_arg( 'quiz_id', absint( $post_args['quiz_id'] ), $location );
				}
			}
		}
	}
	
	return $location;
}
add_filter('redirect_post_location', 'learndash_redirect_post_location', 10, 2 );


/**
 * Utility function to set the option to trigger flush of rewrite rules. 
 * This is checked during the 'shutdown' action where the rewrites will
 * then be flushed.
 */ 
function learndash_setup_rewrite_flush() {
	update_option( 'sfwd_lms_rewrite_flush', true, false );
}
