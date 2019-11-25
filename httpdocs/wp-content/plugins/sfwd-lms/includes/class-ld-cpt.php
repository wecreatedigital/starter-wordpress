<?php
/**
 * Sets up a class that contains the properties and functions that each
 * LearnDash custom post type will have
 * 
 * @since 2.1.0
 * 
 * @package LearnDash\CPT
 */



if ( ! class_exists( 'SFWD_CPT' ) ) {

	abstract class SFWD_CPT extends Semper_Fi_Module {

		protected $post_name;
		protected $post_type;
		protected $post_options;
		//protected $tax_options;
		protected $slug_name;
		protected $taxonomies = null;

		

		/**
		 * Set up post type and taxonomy to be registered
		 */
		function __construct() {
			parent::__construct();

			$this->post_options = array(
				'label' => $this->post_name,
				'labels' => array(
					'name'					=> $this->post_name,
					'singular_name'			=> $this->post_name,
					'add_new'				=> esc_html__( 'Add New', 'learndash' ),
					'all_items'				=> $this->post_name,
					'add_new_item'			=> sprintf( esc_html_x( 'Add New %s', 'placeholder: Post Name', 'learndash' ), $this->post_name ),
					'edit_item'				=> sprintf( esc_html_x( 'Edit %s', 'placeholder: Post Name', 'learndash' ), $this->post_name ),
					'new_item'				=> sprintf( esc_html_x( 'New %s', 'placeholder: Post Name', 'learndash' ), $this->post_name ),
					'view_item'				=> sprintf( esc_html_x( 'View %s', 'placeholder: Post Name', 'learndash' ), $this->post_name ),
					'search_items'			=> sprintf( esc_html_x( 'Search %s', 'placeholder: Post Name', 'learndash' ), $this->post_name ),
					'not_found'				=> sprintf( esc_html_x( 'No %s found', 'placeholder: Post Name', 'learndash' ), $this->post_name ),
					'not_found_in_trash'	=> sprintf( esc_html_x( 'No %s found in Trash', 'placeholder: Post Name', 'learndash' ), $this->post_name ),
					'parent_item_colon'		=> sprintf( esc_html_x( 'Parent %s', 'placeholder: Post Name', 'learndash' ), $this->post_name ),
					'menu_name'				=> $this->post_name,
					'item_published'		=>	sprintf( esc_html_x( '%s Published', 'placeholder: Post Name', 'learndash' ), $this->post_name ),
					'item_published_privately' => sprintf( esc_html_x( '%s Published Privately', 'placeholder: Post Name', 'learndash' ), $this->post_name ),
					'item_reverted_to_draft' => sprintf( esc_html_x( '%s Reverted to Draft', 'placeholder: Post Name', 'learndash' ), $this->post_name ),
					'item_scheduled'		=>	sprintf( esc_html_x( '%s Scheduled', 'placeholder: Post Name', 'learndash' ), $this->post_name ),
					'item_updated'			=>	sprintf( esc_html_x( '%s Updated', 'placeholder: Post Name', 'learndash' ), $this->post_name ),
				),
				'public' => true,
				'rewrite' => array( 
					'slug' => $this->slug_name, 
					'with_front' => false,
					//'feeds' => false,
				),
				'show_ui' => true,
				'has_archive' => false,
				'show_in_nav_menus' => true,
				'supports' => array(
					'title',
					'editor',
				),
			);

			//$this->tax_options = array( 'public' => true, 'hierarchical' => true );
		}



		/**
		 * @todo  consider for removal, this never gets fired
		 *        add_post_type is fired elsewhere
		 * 
		 * @since 2.1.0
		 */
		function activate() {
			remove_action( 'init', array( $this, 'add_post_type' ) );
			$this->add_post_type();
		}



		/**
		 * @todo  consider for removal, this never gets fired
		 * 
		 * @since 2.1.0
		 */
		function deactivate() {
			remove_action( 'init', array( $this, 'add_post_type' ) );
		}



		/**
		 * Sets up admin menu item for post type
		 * 
		 * @since 2.1.0
		 */
		function admin_menu() {
			$this->add_menu( "edit.php?post_type={$this->post_type}" );
		}



		/**
		 * Registers the custom post type, adds filter to register taxonomy
		 * and flushes rewrite rules after registration
		 * 
		 * @since 2.1.0
		 */
		function add_post_type() {
			static $rewrite_flushed = false;

			/**
			 * Filter the CPT registration options
			 * 
			 * @since 2.1.0
			 * 
			 * @param  array  $this->post_options
			 */			
			$this->post_options = apply_filters( 'sfwd_cpt_options', $this->post_options, $this->post_type );

			register_post_type( $this->post_type, $this->post_options );
			add_filter( 'sfwd_cpt_register_tax', array( $this, 'register_tax' ), 10 );

			// No longer needed
			//$flush = is_admin() && $this->post_type == 'sfwd-certificates';
			$flush = false;
			
			// If this is an AJAX call then abort. 
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				return;
			}

			// If for some reason we have already flushed rewrites. abort
			if ( $rewrite_flushed ) {
				return;
			}

			/**
			 * Filter if rewrite rules should flush
			 * 
			 * @since 2.1.0
			 * 
			 * @param  bool  $flush
			 */
			
			$learndash_flush_rewrite_rules = apply_filters( 'learndash_flush_rewrite_rules', $flush, $this->post_options );

			if ( $learndash_flush_rewrite_rules ) {

				// Set our flag so we know we have already been here. 
				$rewrite_flushed = true;
				
				// We set a transient. This is checked during the 'shutdown' action where the rewrites will then be flushed. 
				learndash_setup_rewrite_flush();
			}
		}


		/**
		 * Sets up taxonomy to be registered, does not actually register the taxonomy
		 *
		 * Filter callback for 'sfwd_cpt_register_tax'
		 * 
		 * @since 2.1.0
		 * 
		 * @param  array $tax_data
		 * @return array $tax_data
		 */
		function register_tax( $tax_data ) {
			if ( ! is_array( $tax_data ) ) {
				$tax_data = array();
			}

			if ( is_array( $this->taxonomies ) ) {
				foreach ( $this->taxonomies as $k_tax_slug => $t_tax_options ) {
					if ( $k_tax_slug !== $t_tax_options ) {

						/**
						 * Filter taxonomy registration options
						 * 
						 * @since 2.1.0
						 * 
						 * @param  array  $this->tax_options
						 */
						$t_tax_options = apply_filters( 'sfwd_cpt_tax', $t_tax_options, $this->post_type, $k_tax_slug );
					}
					
					if ( !empty( $t_tax_options ) ) {
						if ( !isset( $tax_data[ $k_tax_slug ] ) ) {
							$tax_data[ $k_tax_slug ] = array(
								'post_types' 	=>	array( $this->post_type ),
								'tax_args'		=>	$t_tax_options
							);
						} else if ( isset( $tax_data[ $k_tax_slug ]['post_types'] ) ) {
							$tax_data[ $k_tax_slug ]['post_types'] = array_merge( $tax_data[ $k_tax_slug ]['post_types'], array( $this->post_type ) );
						}
					}						
				}
			}

			return $tax_data;
		}



		/**
		 * Shortcode that generates a list of items in this post type
		 * 
		 * @since 2.1.0
		 * 
		 * @param  array 	$atts    shortcode attributes
		 * @param  string 	$content short content
		 * @return string          	 shortcode output
		 */
		static function loop_shortcode( $atts, $content = null ) {
			global $learndash_shortcode_used;
			
			$args = array(
				'pagination'      => '',
				'paged'			  => 1,
				'pager_context'	  => '',
				'posts_per_page'  => '',
				'query'           => '',
				'category'        => '',
				'post_type'       => '',
				'order'           => '',
				'orderby'         => '',
				'post__in'		  => '',
				'include'		  => '',
				'meta_key'        => '',
				'meta_value'      => '',
				'taxonomy'        => '',
				'tax_field'       => '',
				'tax_terms'       => '',
				'user_id'         => null,
				'course_id'		  => 0,
				'topic_list_type' => '',
				'return'          => 'text', /* text or array */
			);

			if ( ! empty( $atts) ) {
				foreach ( $atts as $k => $v ) {
					if ( $v === '' ) {
						unset( $atts[ $k ] );
					}
				}
			}

			$filter = shortcode_atts( $args, $atts );
			extract( shortcode_atts( $args, $atts ) );
			//global $paged;

			//$posts = new WP_Query();

			$sno   = 1;
			
			//if ( $pagination == 'true' ) {
			//	$query .= '&paged=' . $paged;
			//}
			if ( ( $pagination == 'true' ) && ( isset( $posts_per_page ) ) && ( !empty( $posts_per_page ) ) ) {
				if ( !isset( $atts['paged'] ) ) 
					global $paged;
				
				$query .= '&paged=' . $paged;
				$start_no = intval( $posts_per_page ) * (intval( $paged ) - 1 ) + 1;
				$sno = $start_no;
			}

			if ( ! empty( $category) ) {
				$query .= '&category_name=' . $category;
			}

			foreach ( array( 'post_type', 'order', 'orderby', 'meta_key', 'meta_value', 'query' ) as $field ) {
				if ( ! empty( $$field ) ) {
					$query .= "&$field=" . $$field;
				}
			}

			if ( ( isset( $include ) ) && ( !empty( $include ) ) ) {
				$query .= "&include=". $include;
			}

			$query = wp_parse_args( $query, $filter );

			if ( ! empty( $taxonomy ) && ! empty( $tax_field ) && ! empty( $tax_terms ) ) {
				$query['tax_query'] = array(
					array( 
						'taxonomy' => $taxonomy, 
						'field' => $tax_field, 
						'terms' => explode( ',', $tax_terms ),
					),
				);
			}

			if ( ( isset( $query['post__in'] ) ) && ( !empty( $query['post__in'] ) ) ) {
				if ( is_string( $query['post__in'] ) ) {
					$query['post__in'] = explode(',', $query['post__in'] );
				} 
			} else if ( ( isset( $query['include'] ) ) && ( !empty( $query['include'] ) ) ) {
				$query['post__in'] = explode(',', $query['include'] );
				$query['post__in'] = array_map( 'trim', $query['post__in'] );
				unset( $query['include'] );
			}

			$query_posts = new WP_Query( $query );
			if ( $query_posts->have_posts() ) {
				$posts = $query_posts->posts;
			} else {
				$posts = array();
			}
			
			//$posts = get_posts( $query );
			if ( $return == 'array' ) {
				$buf = array();
			} else {
				$buf = '';
			}
			//$sno   = 1;

			if ( empty( $user_id ) ) {
				$user_id = get_current_user_id();
			}

			foreach ( $posts as $post ) {
				//	$posts->the_post();	// run shortcodes in loop
				$id                    = $post->ID; // allow use of id variable in template
				$class                 = '';
				$status                = '';
				$sample                = '';
				$sub_title             = '';
				$ld_lesson_access_from = '';

				if ( $post->post_type == 'sfwd-quiz' ) {

					$sample = ( learndash_is_sample( $post ) ) ? 'is_sample' : 'is_not_sample';
					$id .= ' class="' . $sample . '"';
					$status = ( learndash_is_quiz_notcomplete( $user_id, array( $post->ID => 1 ), false, $course_id ) ) ? 'notcompleted' : 'completed';

				} else if ( $post->post_type == 'sfwd-lessons' ) {

					$sample = ( learndash_is_sample( $post ) ) ? 'is_sample' : 'is_not_sample';
					$id .= ' class="' . $sample . '"';

					if ( ! learndash_is_lesson_notcomplete( $user_id, array( $post->ID => 1 ), $course_id ) ) {
						$status = 'completed';
					} else {
						$ld_lesson_access_from = ld_lesson_access_from( $post->ID, $user_id, $course_id );

						if ( empty( $ld_lesson_access_from ) ) {
							$status = 'notcompleted';
						} else {
							$status    = 'notavailable';
								$sub_title = SFWD_LMS::get_template( 
									'learndash_course_lesson_not_available', 
									array(
										'user_id'					=>	$user_id,
										'course_id'					=>	learndash_get_course_id( $post->ID ),
										'lesson_id'					=>	$post->ID,
										'lesson_access_from_int'	=>	$ld_lesson_access_from,
										'lesson_access_from_date'	=>	learndash_adjust_date_time_display( $ld_lesson_access_from ),
										'context'					=>	'loop_content_shortcode'
									), 
									false
								);
						}
					}

					if ( empty( $sub_title) && ! empty( $topic_list_type) ) {
						$sub_title .= learndash_topic_dots( $post->ID, false, $topic_list_type, $user_id, $course_id );
					}

				}

				if ( $meta_key != 'course_id' ) {
					$show_content = true;
				} else {
					$show_content = SFWD_CPT::show_content( $post );
				}

				if ( $show_content ) {
					$permalink = get_permalink( $post->ID );
					
					if ( $return == 'array' ) {
						$buf[ $sno ] = array(
							'sno'                => $sno,
							'post'               => $post,
							//'permalink'          => get_permalink( $post->ID ),
							'sub_title'          => $sub_title,
							'status'             => $status,
							'sample'             => $sample,
							'lesson_access_from' => $ld_lesson_access_from,
						);
						
						if ( ( isset( $course_id ) ) && ( !empty( $course_id ) ) && ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Section_Permalinks', 'nested_urls' ) == 'yes' ) ) {
							$buf[ $sno ]['permalink'] = learndash_get_step_permalink( $post->ID, $course_id );
						} else {
							$buf[ $sno ]['permalink'] = get_permalink( $post->ID );
						}
						
					} else {
						$show_content = str_replace( '{learndash_completed_class}', 'class="' . $status . '"', $content );
						$show_content = str_replace( '{the_title}', $post->post_title, $show_content );
						//$show_content = str_replace( '{the_permalink}', get_permalink( $post->ID ), $show_content );
						if ( ( isset( $course_id ) ) && ( !empty( $course_id ) ) && ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Section_Permalinks', 'nested_urls' ) == 'yes' ) ) {
							$show_content = str_replace( '{the_permalink}', learndash_get_step_permalink( $post->ID, $course_id ), $show_content );
						} else {
							$show_content = str_replace( '{the_permalink}', get_permalink( $post->ID ), $show_content );
						}
						
						$show_content = str_replace( '{sub_title}', $sub_title, $show_content );
						$show_content = str_replace( '$id', "$id", $show_content );
						$show_content = str_replace( '{sno}', $sno, $show_content );
						$buf .= do_shortcode( $show_content );
					}
				}

				if ( ! empty( $show_content) ) {
					$sno++;
				}
			}

			if ( $pagination == 'true' ) {
				/*
				$buf .= '<div class="navigation">
					  <div class="alignleft">' . get_previous_posts_link( '« Previous' ) . '</div>
					  <div class="alignright">' . get_next_posts_link( 'More »' ) . '</div>
					</div>';
				*/
				do_action( 'learndash_course_lessons_list_pager', $query_posts, $pager_context );
			}

			//wp_reset_query();
			
			$learndash_shortcode_used = true;
			
			return $buf;
		}


		/**
		 * To show content or not
		 * 
		 * @since 2.1.0
		 * 
		 * @param  object $post WP_Post
		 * @return bool
		 */
		static function show_content( $post ) {
			if ( $post->post_type == 'sfwd-quiz' ) {
				if ( LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
					$course_id = learndash_get_course_id( $post );
					$lesson_id = learndash_course_get_single_parent_step( $course_id, $post->ID );
				} else {
					$lesson_id = learndash_get_setting( $post, 'lesson' );
				}
				return empty( $lesson_id );
			} else {
				return true;
			}
		}



		/**
		 * Set up shortcode for custom post type
		 *
		 * @todo  evaluate if this shortcode is still being used
		 *        called from sfwd_lms.php
		 *        accepts $code as an argument, but $code doesn't exist
		 * 
		 * @since 2.1.0
		 * 
		 * @param  array 	$atts    shortcode attributes
		 * @param  string 	$content short content
		 * @param  string 	$code    post type 
		 * @return string          	 shortcode output
		 */
		function shortcode( $atts, $content = null, $code ) {
			global $learndash_shortcode_used;
			
			extract( 
				shortcode_atts( 
					array(
						'post_type'       => $code,
						'posts_per_page'  => -1,
						'taxonomy'        => '',
						'tax_field'       => '',
						'tax_terms'       => '',
						'meta_key'        => '',
						'meta_value'      => '',
						'order'           => 'DESC',
						'orderby'         => 'date',
						'wrapper'         => 'div',
						'title'           => 'h4',
						'topic_list_type' => 'dots',
						'post__in'		  => '',
					), 
					$atts 
				) 
			);

			global $shortcode_tags;
			$save_tags = $shortcode_tags;

			add_shortcode( 'loop', array( $this, 'loop_shortcode' ) );

			$template = "[loop post_type='$post_type' posts_per_page='$posts_per_page' meta_key='{$meta_key}' meta_value='{$meta_value}' order='$order' orderby='$orderby' taxonomy='$taxonomy' tax_field='$tax_field' tax_terms='$tax_terms' post__in='". $post__in ."' topic_list_type='" . $topic_list_type . "']"
			. "<$wrapper id=post-\$id><$title><a {learndash_completed_class} href='{the_permalink}'>{the_title}</a>{sub_title}</$title>"
			. "</$wrapper>[/loop]";

			$template = apply_filters( 'sfwd_cpt_template', $template );
			$buf      = do_shortcode( $template );

			$shortcode_tags = $save_tags;
			
			$learndash_shortcode_used = true;
			
			return $buf;
		}



		/**
		 * Get settings for post type
		 * 
		 * @since 2.1.0
		 * 
		 * @param  string $location post type
		 * @return array  $setting  post type setting
		 */
		function get_settings_values( $location = null ) {
			$settings = $this->setting_options( $location );
			$values = $this->get_current_options( array(), $location );

			foreach ( $settings as $k => $v ) {
				if (isset( $values[ $k ] ) )
					$settings[ $k ]['value'] = $values[ $k ];
			}

			return $settings;
		}



		/**
		 * Output the settings for post type
		 * 
		 * @since 2.1.0
		 * 
		 * @param  string $location post type
		 * @return string           settings output
		 */
		function display_settings_values( $location = null ) {
			$meta = $this->get_settings_values( $location );

			if ( ! empty( $meta ) ) {
				?>
					<ul class='post-meta'>
						<?php
							foreach ( $meta as $m ) {
								echo "<li><span class='post-meta-key'>{$m['name']}</span> {$m['value']}</li>\n";
							}
						?>
					</ul>
				<?php
			}
		}
	}
}