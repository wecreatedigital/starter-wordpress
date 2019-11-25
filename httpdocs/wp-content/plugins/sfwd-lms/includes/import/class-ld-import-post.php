<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * LearnDash Import CPT
 *
 * This file contains functions to handle import of the LearnDash CPT Courses, Lessons, Topics, Quizzes
 *
 * @package LearnDash
 * @subpackage LearnDash
 * @since 1.0.0
 */

if ( !class_exists( 'LearnDash_Import_Post' ) ) {
	class LearnDash_Import_Post {

		var $converted_items = array();
		var $config = array();

	    function __construct() {
		}
		
		public function get_duplicate_link( $post_id = 0, $action = '' ) {

			$post = get_post( $post_id );
			if ( is_a( $post, 'WP_Post' ) ) {
				$action = 'ld_copy';

				$url_params = array(
					'action' 	=> 	$action,
					'post' 		=>	$post->ID,
					'ld_nonce' 	=>	wp_create_nonce( $action .'_'. $post->ID .'_'. $post->post_type .'_'. get_current_user_id() )
				);

				$url_params = apply_filters( 'ld_sensei_url_params',  $url_params );
			
				if ( !empty( $url_params ) ) {
					$url = add_query_arg( $url_params, admin_url( 'admin.php' ) );
					return apply_filters( 'ld_sensei_url_link', $url, $url_params, $post_id, $action );
				}
			}
		}
		
		public function duplicate_post( $source_post_id = 0, $force_copy = false ) {

			if ( !empty( $source_post_id ) ) {
				
				$source_post = get_post( $source_post_id );
				if ( ( $source_post ) && ( $source_post->post_type == $this->source_post_type ) && ( is_a( $source_post, 'WP_Post' ) ) ) {
					
					$previous_imported_post_id = $this->get_imported( $source_post_id );
					
					// For now set this to true so we don't have to clear all posts after each run. 
					$force_copy = true;
					
					if ( ( empty( $previous_imported_post_id ) ) || ( $force_copy == true ) ) {
						$dest_post = array();

						foreach( $source_post as $k => $v ) {
							if( ! in_array( $k, array( 'ID', 'post_type', 'guid', 'post_parent', 'comment_count', 'to_ping' ) ) ) {
								$dest_post[ $k ] = $v;
							}
						}
						$dest_post['post_type'] = $this->dest_post_type;
			
						// As per wp_update_post() we need to escape the data from the db.
						$dest_post = wp_slash( $dest_post );

						$dest_post = apply_filters( 'learndash_duplicate_post_array', $dest_post, $source_post );
						$dest_post_id = wp_insert_post( $dest_post );

						if ( !is_wp_error( $dest_post_id ) ) {

							$dest_post = get_post( $dest_post_id );
				
							add_post_meta( $dest_post->ID, '_ld_import_org', $source_post->ID );
				
							$dest_post_meta = SFWD_CPT_Instance::$instances[ $dest_post->post_type ]->get_settings_values( $dest_post->post_type );
							if ( !empty( $dest_post_meta ) ) {
								$dest_post_meta = wp_list_pluck( $dest_post_meta, 'value' );
							}
				
							$dest_post_meta = apply_filters( 'learndash_sensei_import_meta', $dest_post_meta, $source_post, $dest_post );	
							add_post_meta( $dest_post->ID, '_' . $dest_post->post_type, $dest_post_meta );
				
							return $dest_post;
						}
					}
				}
			}
			
			return false;
		}
		
		function duplicate_post_tax_term( $source_term, $create_parents = false ) {
			
			if ( ( $source_term ) && ( is_a( $source_term, 'WP_Term' ) ) ) {
				$terms_to_add = array( );
				$ld_parent_term_id = 0;
		
				// First we build the parent tree if needed
				if ( ( !empty( $source_term->parent ) ) && ( is_taxonomy_hierarchical( $source_term->taxonomy ) ) && ( $create_parents == true ) ) {
					$term_parents = get_ancestors( $source_term->term_id, $source_term->taxonomy );
					if ( !empty( $term_parents ) ) {
						//$terms_to_add = array_merge( array( $source_term->term_id ), $term_parents );
						$terms_to_add = $term_parents;
						if ( !empty( $terms_to_add ) ) {
							krsort( $terms_to_add );
					
							foreach( $terms_to_add as $s_term_idx => $s_term_id ) {
								$s_term = get_term_by( 'id', $s_term_id, $source_term->taxonomy );
								if ( $s_term ) {
				
									$n_term = get_term_by( 'slug', $s_term->slug, $this->dest_taxonomy );
									if ( !$n_term ) {
										$n_term = wp_insert_term( 
											$s_term->name, $this->dest_taxonomy, 
											array( 'slug' => $s_term->slug, 'parent' => $ld_parent_term_id ) 
										);
								
										if ( isset( $n_term['term_id'] ) ) {
											$ld_parent_term_id = $n_term['term_id'];
										}
									} else {
										$ld_parent_term_id = $n_term->term_id;
									}
								}
							}
						}
					}
				}
				
				$new_term = get_term_by( 'slug', $source_term->slug, $this->dest_taxonomy );
				if ( !$new_term ) {
					$n_term = wp_insert_term( $source_term->name, $this->dest_taxonomy, array( 'slug' => $source_term->name, 'parent' => $ld_parent_term_id ) );
					if ( !is_wp_error( $n_term ) ) {
						if ( isset( $n_term['term_id'] ) ) {
							$new_term = get_term_by( 'id', $n_term['term_id'], $this->dest_taxonomy );
						}
					}
				} 
				
				return $new_term;
			}
		}
	
		function set_post_tax_terms( $dest_post_id, $term_ids, $replace = false ) {
			if ( ( !empty( $dest_post_id )) && ( !empty( $term_ids ) ) ) {
				wp_set_object_terms( $dest_post_id, $term_ids, $this->dest_taxonomy, true );
			}
		}
		
		function get_imported( $source_post_id = 0 ) {
			if ( !empty( $source_post_id ) ) {
				
				$dest_posts = get_posts( 
					array(
						'post_type' 	=> 	$this->dest_post_type,
						'meta_key'     	=> 	'_ld_import_org',
						'meta_value'   	=> 	intval( $source_post_id ),
					)
				);
				
				if ( ( !empty( $dest_posts ) ) && ( is_array( $dest_posts ) ) ) {
					$dest_post = $dest_posts[0];

					if ( is_a( $dest_post, 'WP_Post' ) ) {
						return $dest_post->ID;
					}
				}
			}
			
			return false;
		}
		
		// End of functions
	}
}