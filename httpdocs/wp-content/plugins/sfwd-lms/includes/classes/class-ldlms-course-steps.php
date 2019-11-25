<?php
/**
 * LearnDash Course Steps Questions Class.
 *
 * @package LearnDash
 * @subpackage Course
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LDLMS_Course_Steps' ) ) {

	/**
	 * Class for LearnDash Course Steps.
	 */
	class LDLMS_Course_Steps {

		/**
		 * Course ID for use in this instance.
		 *
		 * @var integer $course_id
		 */
		private $course_id = 0;

		/**
		 * Course Steps Loaded flag.
		 *
		 * @var boolean $steps_loaded Set to false initially. Set to true once course
		 * steps have been loaded.
		 */
		private $steps_loaded = false;

		/**
		 * Course Steps are dirty.
		 *
		 * @var boolean $steps_dirty Set to false initially but can be set to true if the
		 * dirty meta is read in and it true.
		 */
		private $steps_dirty = false;

		/**
		 * Course Steps array.
		 *
		 * @var array $steps Array of course steps.
		 */
		protected $steps = array();

		/**
		 * Course post types
		 *
		 * @var array $steps_post_types Course post types.
		 */
		protected $steps_post_types = array();

		/**
		 * Public constructor for class.
		 *
		 * @since 2.6.0
		 * @param integer $course_id Course post ID.
		 */
		public function __construct( $course_id = 0 ) {
			if ( ! empty( $course_id ) ) {
				$this->course_id = absint( $course_id );

				$this->steps_post_types = LDLMS_Post_Types::get_post_types( 'course_steps' );
			}
		}

		/**
		 * Load Quiz Questions.
		 */
		public function load_steps() {
			if ( ! $this->steps_loaded ) {
				$this->steps_loaded = true;

				if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) == 'yes' ) {
					$this->steps = get_post_meta( $this->course_id, 'ld_course_steps', true );
				}

				if ( ! is_array( $this->steps ) ) {
					$this->steps = array();
				}

				if ( ! empty( $this->steps['h'] ) ) {
					if ( $this->is_steps_dirty() ) {
						$this->steps['h'] = $this->validate_steps( $this->steps['h'] );
						$this->set_steps( $this->steps['h'] );
						$this->clear_steps_dirty();
					}
				} else {
					// Note here since we are loading the steps via legacy methods we don't need to validate.
					$this->steps['h'] = $this->load_steps_legacy();
				}

				$this->build_steps();
			}
		}

		/**
		 * Sets the Course steps dirty flag and will force the steps to be
		 * reloaded from queries.
		 */
		public function set_steps_dirty() {
			if ( ! empty( $this->course_id ) ) {
				$this->steps_dirty = true;
				update_post_meta( $this->course_id, 'ld_course_steps_dirty', $this->course_id );
			}
		}

		/**
		 * Check if the steps dirty flag is set.
		 */
		public function is_steps_dirty() {
			// If the steps_dirty boolean has been previously set to try it save a call to postmeta.
			if ( false === $this->steps_dirty ) {
				if ( ! empty( $this->course_id ) ) {
					$is_dirty = get_post_meta( $this->course_id, 'ld_course_steps_dirty', true );
					if ( absint( $is_dirty ) === absint( $this->course_id ) ) {
						$this->steps_dirty = true;
					}
				}
			}

			return $this->steps_dirty;
		}

		/**
		 * Clear the steps dirty flag.
		 */
		public function clear_steps_dirty() {
			if ( ! empty( $this->course_id ) ) {
				$this->steps_dirty = false;
				delete_post_meta( $this->course_id, 'ld_course_steps_dirty' );
			}
		}

		/**
		 * Get the total steps course for the course.
		 */
		public function get_steps_count() {
			$course_steps_count = 0;

			$this->get_steps( 't' );

			if ( isset( $this->steps['t']['sfwd-lessons'] ) ) {
				$course_steps_count += count( $this->steps['t']['sfwd-lessons'] );
			}
			if ( isset( $this->steps['t']['sfwd-topic'] ) ) {
				$course_steps_count += count( $this->steps['t']['sfwd-topic'] );
			}
			if ( ( isset( $this->steps['h']['sfwd-quiz'] ) ) && ( ! empty( $this->steps['h']['sfwd-quiz'] ) ) ) {
				$course_steps_count++;
			}

			return $course_steps_count;
		}

		/**
		 * Build Course Steps nodes.
		 */
		protected function build_steps() {
			if ( ! isset( $this->steps['h'] ) ) {
				$this->steps['h'] = array();
			}
			if ( ! isset( $this->steps['t'] ) ) {
				$this->steps['t'] = array();
			}
			if ( ! isset( $this->steps['r'] ) ) {
				$this->steps['r'] = array();
			}
			if ( ! isset( $this->steps['l'] ) ) {
				$this->steps['l'] = array();
			}

			if ( ! empty( $this->steps['h'] ) ) {

				if ( empty( $this->steps['t'] ) ) {
					$this->steps['t'] = $this->steps_grouped_by_type( $this->steps['h'] );
				}

				if ( empty( $this->steps['l'] ) ) {
					$this->steps['l'] = $this->steps_grouped_linear( $this->steps['h'] );
				}

				if ( empty( $this->steps['r'] ) ) {
					$this->steps['r'] = $this->steps_grouped_reverse_keys( $this->steps['h'] );
				}
			}
		}

		/**
		 * Validate Course Steps nodes and items.
		 *
		 * @since 2.5.0
		 * @param array $steps Current steps nodes and items.
		 */
		protected function validate_steps( $steps = array() ) {
			if ( ! empty( $steps ) ) {
				foreach ( $steps as $steps_type => $steps_type_set ) {
					if ( ( is_array( $steps_type_set ) ) && ( ! empty( $steps_type_set ) ) ) {
						$steps_query_args = array(
							'post_type'      => $steps_type,
							'post__in'       => array_keys( $steps_type_set ),
							'posts_per_page' => -1,
							'post_status'    => 'publish',
							'fields'         => 'ids',
							'orderby'        => 'post__in',
						);

						$steps_query = new WP_Query( $steps_query_args );
						if ( ( $steps_query instanceof WP_Query ) && ( property_exists( $steps_query, 'posts' ) ) ) {
							if ( ! is_array( $steps_query->posts ) ) {
								$steps_query->posts = array();
							}
							$step_ids_diff = array_diff( array_keys( $steps_type_set ), $steps_query->posts );
							if ( ! empty( $step_ids_diff ) ) {
								foreach ( $step_ids_diff as $step_id_diff ) {
									if ( isset( $steps[ $steps_type ][ $step_id_diff ] ) ) {
										unset( $steps[ $steps_type ][ $step_id_diff ] );
									}

									if ( isset( $steps_type_set[ $step_id_diff ] ) ) {
										unset( $steps_type_set[ $step_id_diff ] );
									}
								}
							}
						}

						if ( ! empty( $steps_type_set ) ) {
							foreach ( $steps_type_set as $step_id => $step_id_set ) {
								if ( ( is_array( $step_id_set ) ) && ( ! empty( $step_id_set ) ) ) {
									$steps[ $steps_type ][ $step_id ] = $this->validate_steps( $step_id_set );
								}
							}
						}
					}
				}
			}

			return $steps;
		}

		/**
		 * This converts the normal hierachy steps into an array groups be the post type. This is easier for search.
		 *
		 * @since 2.5.0
		 * @param array $steps Array of Course steps nodes and items.
		 * @return array Array of steps by type.
		 */
		protected function steps_grouped_by_type( $steps = array() ) {
			$steps_by_type = array();

			if ( ! empty( $steps ) ) {
				foreach ( $steps as $steps_type => $steps_type_set ) {
					if ( ! isset( $steps_by_type[ $steps_type ] ) ) {
						$steps_by_type[ $steps_type ] = array();
					}

					if ( ( is_array( $steps_type_set ) ) && ( ! empty( $steps_type_set ) ) ) {
						foreach ( $steps_type_set as $step_id => $step_id_set ) {
							$steps_by_type[ $steps_type ][] = $step_id;
							if ( ( is_array( $step_id_set ) ) && ( ! empty( $step_id_set ) ) ) {
								$sub_steps = $this->steps_grouped_by_type( $step_id_set );
								if ( ! empty( $sub_steps ) ) {
									foreach ( $sub_steps as $sub_step_type => $sub_step_ids ) {
										if ( ! isset( $steps_by_type[ $sub_step_type ] ) ) {
											$steps_by_type[ $sub_step_type ] = array();
										}

										if ( ! empty( $sub_step_ids ) ) {
											$steps_by_type[ $sub_step_type ] = array_merge( $steps_by_type[ $sub_step_type ], $sub_step_ids );
										}
									}
								}
							}
						}
					}
				}
			}

			return $steps_by_type;
		}

		/**
		 * Group Steps linear.
		 *
		 * @since 2.5.0
		 * @param array $steps Array of Course step nodes and items.
		 * @return array Array of steps by linear.
		 */
		protected function steps_grouped_linear( $steps = array() ) {
			$steps_linear = array();

			if ( ! empty( $steps ) ) {
				foreach ( $steps as $steps_type => $steps_type_set ) {
					if ( ! isset( $steps_by_type[ $steps_type ] ) ) {
						if ( ( is_array( $steps_type_set ) ) && ( ! empty( $steps_type_set ) ) ) {
							foreach ( $steps_type_set as $step_id => $step_id_set ) {
								$steps_linear[] = $steps_type . ':' . $step_id;
								if ( ( is_array( $step_id_set ) ) && ( ! empty( $step_id_set ) ) ) {
									$sub_steps = $this->steps_grouped_linear( $step_id_set );
									if ( ! empty( $sub_steps ) ) {
										$steps_linear = array_merge( $steps_linear, $sub_steps );
									}
								}
							}
						}
					}
				}
			}

			return $steps_linear;
		}

		/**
		 * Group Steps reversed keys.
		 *
		 * @since 2.5.0
		 * @param array $steps Array of Course step nodes and items.
		 * @return array Array of steps.
		 */
		protected function steps_grouped_reverse_keys( $steps = array() ) {
			$steps_reversed = $this->_steps_reverse_keys_walk( $steps );
			if ( ! empty( $steps_reversed ) ) {
				foreach ( $steps_reversed as $reversed_key => $reversed_set ) {
					if ( ! empty( $reversed_set ) ) {
						$steps_reversed[ $reversed_key ] = $this->_flatten_item_parent_steps( $reversed_set );
					} else {
						$steps_reversed[ $reversed_key ] = array();
					}
				}
			}

			return $steps_reversed;
		}

		/**
		 * Internal utility function to reverse walk the Course steps nodes and items
		 */
		private function _steps_reverse_keys_walk( $steps, $parent_tree = array() ) {
			$steps_reversed = array();

			if ( ! empty( $steps ) ) {
				foreach ( $steps as $steps_type => $steps_type_set ) {

					if ( ( is_array( $steps_type_set ) ) && ( ! empty( $steps_type_set ) ) ) {
						foreach ( $steps_type_set as $step_id => $step_id_set ) {
							$steps_parents = array();
							$steps_parents[ $steps_type . ':' . $step_id ] = $parent_tree;

							if ( ( is_array( $step_id_set ) ) && ( ! empty( $step_id_set ) ) ) {
								$sub_steps = $this->_steps_reverse_keys_walk( $step_id_set, $steps_parents );
								if ( ! empty( $sub_steps ) ) {
									$steps_parents = array_merge( $steps_parents, $sub_steps );
								}
							}

							if ( ! empty( $steps_parents ) ) {
								$steps_reversed = array_merge( $steps_reversed, $steps_parents );
							}
						}
					}
				}
			}

			return $steps_reversed;
		}

		/**
		 * Internal utility function to reverse parent keys of the Course nodes and items.
		 */
		private function _flatten_item_parent_steps( $steps = array() ) {
			$flattened_steps = array();

			if ( ! empty( $steps ) ) {
				foreach ( $steps as $a_step_key => $a_steps ) {
					$flattened_steps[] = $a_step_key;
					$sub_steps = $this->_flatten_item_parent_steps( $a_steps );
					if ( !empty( $sub_steps ) ) {
						$flattened_steps = array_merge( $flattened_steps, $sub_steps );
					}
				}
			}

			return $flattened_steps;
		}

		/**
		 * Set Course steps.
		 * This is generally called when editing the course and the course steps has been changed.
		 *
		 * @since 2.5.0
		 * @param array $course_steps Array of Course steps.
		 */
		public function set_steps( $course_steps = array() ) {
			if ( ! empty( $this->course_id ) ) {
				$this->steps = array();
				$this->steps['h'] = $course_steps;

				$this->build_steps();

				if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) != 'yes' ) {
					$this->set_step_to_course_legacy();

					$course_steps = get_post_meta( $this->course_id, 'ld_course_steps', true );
					if ( ! is_null( $course_steps ) ) {
						delete_post_meta( $this->course_id, 'ld_course_steps' );
					}
				} else {
					$this->set_step_to_course();
					update_post_meta( $this->course_id, 'ld_course_steps', $this->steps ); 
				}
			}
		}

		/**
		 * Get Course steps by node type.
		 *
		 * @since 2.5.0
		 * @param string $steps_type Course Steps node type.
		 * @return array of Course Step items found in node.
		 */
		function get_steps( $steps_type = 'h' ) {
			$this->load_steps();

			if ( isset( $this->steps[ $steps_type ] ) ) {
				return $this->steps[ $steps_type ];
			} elseif ( 'all' === $steps_type ) {
				return $this->steps;
			}

			return array();
		}

		/**
		 * This function sets a post_meta association for the various steps within the course.
		 * The new association is 'ld_course_XXX' where 'XXX' is the course ID.
		 *
		 * @since 2.5.0
		 */
		function set_step_to_course() {
			global $wpdb;

			$course_steps_new = array();

			if ( ( isset( $this->steps['t'] ) ) && ( ! empty( $this->steps['t'] ) ) ) {
				foreach ( $this->steps['t'] as $step_type => $step_type_set ) {
					if ( ! empty( $step_type_set ) ) {
						$course_steps_new = array_merge( $course_steps_new, $step_type_set );
					}
				}
			}
			if ( ! empty( $course_steps_new ) ) {
				sort( $course_steps_new, SORT_NUMERIC );
			}

			$sql_str = $wpdb->prepare( "SELECT post_id as post_id FROM " . $wpdb->postmeta . " WHERE meta_key LIKE %s", 'ld_course_' . $this->course_id );
			$course_steps_old = $wpdb->get_col( $sql_str );
			if ( ! empty( $course_steps_old ) ) {
				sort( $course_steps_old, SORT_NUMERIC );
			}

			$course_steps_intersect = array_intersect( $course_steps_new, $course_steps_old );

			// Add Steps.
			$course_steps_add = array_diff( $course_steps_new, $course_steps_intersect );
			if ( ! empty( $course_steps_add ) ) {
				$course_steps_add_chunks = array_chunk( $course_steps_add, LEARNDASH_LMS_DEFAULT_CB_INSERT_CHUNK_SIZE );
				foreach ( $course_steps_add_chunks as $insert_post_ids ) {
					$insert_sql_str = "";
					foreach ( $insert_post_ids as $post_id ) {
						if ( ! empty( $insert_sql_str ) ) {
							$insert_sql_str .= ',';
						}

						$insert_sql_str .= "(" . $post_id . ", 'ld_course_" . $this->course_id . "'," . $this->course_id . ")";
					}
					if ( ! empty( $insert_sql_str ) ) {
						$insert_sql_str = "INSERT INTO " . $wpdb->postmeta . " (`post_id`, `meta_key`, `meta_value`) VALUES " . $insert_sql_str;
						$wpdb->query( $insert_sql_str );
					}
				}
			}

			// Remove Steps.
			$course_steps_remove = array_diff( $course_steps_old, $course_steps_intersect );
			if ( ! empty( $course_steps_remove ) ) {
				$delete_sql_str = "DELETE FROM " . $wpdb->postmeta . " WHERE meta_key LIKE 'ld_course_" . $this->course_id . "' AND post_id IN (" . implode(',', $course_steps_remove ) . ")";
				$wpdb->query( $delete_sql_str );
			}

			/**
			 * Secondary processing here we need to determine all the primary associations for this course and remove any items no longer associated.
			 * For example prior to v2.5 you may have a course ID #123. The course has a lesson, topic and global quiz. Each of these items will have
			 * a post_meta reference 'course_id'. Now in v2.5 the course steps are stored into a collection or nodes. But if for example the quiz is
			 * remove we need to also remove the legacy 'course_id' association.
			 */
			$sql_str = $wpdb->prepare( "SELECT posts.ID as post_id FROM " . $wpdb->posts . " as posts
				INNER JOIN " . $wpdb->postmeta . " as postmeta 
				ON posts.ID = postmeta.post_id 
				WHERE 1=1
				AND posts.post_type IN (" . "'" . implode("','", $this->steps_post_types ) . "'" . ")
				AND postmeta.meta_key = %s 
				AND postmeta.meta_value = %d",
				'course_id', $this->course_id
			);

			$course_steps_primary = $wpdb->get_col( $sql_str );
			if ( ! empty( $course_steps_primary ) ) {
				$course_steps_primary = array_map( 'intval', $course_steps_primary );
			}

			$course_steps_primary_intersect = array_intersect( $course_steps_new, $course_steps_primary );

			$course_steps_primary_remove = array_diff( $course_steps_primary, $course_steps_primary_intersect );
			if ( ! empty( $course_steps_primary_remove ) ) {
				$delete_sql_str = "DELETE FROM " . $wpdb->postmeta . " WHERE meta_key = 'course_id' AND post_id IN (" . implode(',', $course_steps_primary_remove ) . ")";
				$wpdb->query( $delete_sql_str );
			}
		}

		/**
		 * Set Steps to Course Legacy.
		 * This is used when the Course Option Share Steps is not used.
		 *
		 * @since 2.5.0
		 */
		protected function set_step_to_course_legacy() {
			global $wpdb;

			$course_steps_new = array();

			if ( ( isset( $this->steps['t'] ) ) && ( ! empty( $this->steps['t'] ) ) ) {
				foreach ( $this->steps['t'] as $step_type => $step_type_set ) {
					if ( ! empty( $step_type_set ) ) {
						$this->set_step_to_course_order( $step_type_set );
						$course_steps_new = array_merge( $course_steps_new, $step_type_set );
					}
				}
			}

			// Finally we set the Course order to Menu Order/ASC so we can retain te ordering.
			learndash_update_setting( $this->course_id, 'course_lesson_orderby', 'menu_order' );
			learndash_update_setting( $this->course_id, 'course_lesson_order', 'ASC' );

			if ( ! empty( $course_steps_new ) ) {
				sort( $course_steps_new, SORT_NUMERIC );
			}

			$sql_str = $wpdb->prepare( "SELECT posts.ID as post_id FROM " . $wpdb->posts . " as posts INNER JOIN " . $wpdb->postmeta . " as postmeta ON posts.ID = postmeta.post_id WHERE 1=1 AND posts.post_type IN (" . "'" . implode("','", $this->steps_post_types ) . "'" . ") AND postmeta.meta_key = %s AND postmeta.meta_value = %d",
				'course_id', $this->course_id
			);
			$course_steps_old = $wpdb->get_col( $sql_str );

			if ( ! empty( $course_steps_old ) ) {
				$course_steps_old = array_map( 'intval', $course_steps_old );
			}

			$course_steps_intersect = array_intersect( $course_steps_new, $course_steps_old );

			// Add Steps.
			$course_steps_add = array_diff( $course_steps_new, $course_steps_intersect );
			if ( ! empty( $course_steps_add ) ) {
				foreach( $course_steps_add as $post_id ) {
					learndash_update_setting( $post_id, 'course', $this->course_id );
				}
			}


			// Remove Steps
			$course_steps_remove = array_diff( $course_steps_old, $course_steps_intersect );
			if ( !empty( $course_steps_remove ) ) {
				foreach( $course_steps_remove as $post_id ) {
					learndash_update_setting( $post_id, 'course', 0 );
					learndash_update_setting( $post_id, 'lesson', 0 );
				}
			}

			if ( ( isset( $this->steps['h'] ) ) && ( !empty( $this->steps['h'] ) ) ) {
				$this->set_step_to_course_relationship( $this->steps['h'] );
			}
		}
			
		function set_step_to_course_relationship( $steps = array(), $parent_lesson_id = 0 ) {
			global $wpdb;
			
			if ( !empty( $steps ) ) {
				foreach( $steps as $steps_type => $steps_type_set ) {
					if ( $steps_type === 'sfwd-lessons' ) {
						// A note about the queries. These should have been run through WP_Query 
						// but there is more overhead there than we need.
						$sql_str = $wpdb->prepare( "SELECT  DISTINCT posts.ID
									FROM ". $wpdb->posts ." as posts
										INNER JOIN ". $wpdb->postmeta ." as postmeta_course ON posts.ID=postmeta_course.post_id
									WHERE 1=1
										AND posts.post_type = %s
										AND postmeta_course.meta_key = 'course_id' AND postmeta_course.meta_value = %d
									", $steps_type, $this->course_id );
					} else if ( ( $steps_type === 'sfwd-quiz' ) && ( $parent_lesson_id === 0 ) ) {
						
						$sql_str = $wpdb->prepare( "SELECT posts.ID FROM ". $wpdb->posts ." as posts
								LEFT JOIN ". $wpdb->postmeta ." postmeta 
									ON ( posts.ID = postmeta.post_id )  
								LEFT JOIN ". $wpdb->postmeta ." AS mt1 
									ON ( posts.ID = mt1.post_id )  
								LEFT JOIN ". $wpdb->postmeta ." AS mt2 
									ON (posts.ID = mt2.post_id AND mt2.meta_key = 'lesson_id' ) 
								WHERE 1=1  	
								AND ( 
									( postmeta.meta_key = 'course_id' 
										AND CAST(postmeta.meta_value AS SIGNED) = %d ) 
								AND 
  							  		( 
									( mt1.meta_key = 'lesson_id' AND CAST(mt1.meta_value AS SIGNED) = '0' ) 
    								OR 
									mt2.post_id IS NULL
									)
								) 
								AND posts.post_type = %s
								GROUP BY posts.ID ORDER BY posts.post_date DESC ", $this->course_id, $steps_type );

					} else if ( !empty( $parent_lesson_id ) ) {
						$sql_str = $wpdb->prepare( "SELECT  DISTINCT posts.ID
									FROM ". $wpdb->posts ." as posts
										INNER JOIN ". $wpdb->postmeta ." as postmeta_course ON posts.ID=postmeta_course.post_id
										INNER JOIN ". $wpdb->postmeta ." as postmeta_lesson ON posts.ID=postmeta_lesson.post_id
									WHERE 1=1
										AND posts.post_type = %s
										AND postmeta_course.meta_key = 'course_id' AND postmeta_course.meta_value = %d
										AND postmeta_lesson.meta_key = 'lesson_id' AND postmeta_lesson.meta_value = %d
									", $steps_type, $this->course_id, $parent_lesson_id );
					}
					
					if ( !empty( $sql_str ) ) {
						if ( ( is_array( $steps_type_set ) ) && (count( $steps_type_set ) ) ) {
							$step_type_ids_new = array_keys( $steps_type_set );
						} else {
							$step_type_ids_new = array();
						}

						$step_type_ids_old = $wpdb->get_col( $sql_str );
						if ( !empty( $step_type_ids_old ) ) {
							$step_type_ids_old = array_map( 'intval', $step_type_ids_old );
						}
						$step_type_ids_intersect = array_intersect( $step_type_ids_new, $step_type_ids_old );
						
						$step_type_ids_add = array_diff( $step_type_ids_new, $step_type_ids_intersect );
						if ( ( !empty( $step_type_ids_add ) ) && ( !empty( $parent_lesson_id ) ) ) {
							foreach( $step_type_ids_add as $post_id ) {
								//update_post_meta( $post_id, 'lesson_id', $parent_lesson_id );
								learndash_update_setting( $post_id, 'lesson', $parent_lesson_id );
							}
						}
						
						$step_type_ids_remove = array_diff( $step_type_ids_old, $step_type_ids_intersect );
						if ( !empty( $step_type_ids_remove ) ) {
							foreach( $step_type_ids_remove as $post_id ) {
								//delete_post_meta( $post_id, 'lesson_id' );
								learndash_update_setting( $post_id, 'lesson', 0 );
							}
						}
					}
					
					foreach( $steps_type_set as $step_id => $step_id_set ) {
						if ( ( is_array( $step_id_set ) ) && ( !empty( $step_id_set ) ) ) {
							$this->set_step_to_course_relationship( $step_id_set, $step_id );
						}
					}
				}
			}
		}
		
		function set_step_to_course_order( $steps = array() ) {
			global $wpdb;
			
			if ( !empty( $steps ) ) {
 				$sql_str = '';
				
				foreach( $steps as $step_order => $step_id ) {
					$step_order += 1;
					$wpdb->update( 
						$wpdb->posts,
						array( 'menu_order' => $step_order ),
						array( 'ID' => $step_id ),
						array( '%d' ),
						array( '%d' )
					);
				}
			}
		}
				
		function load_steps_legacy( ) {

			$steps = array();

			if ( !empty( $this->course_id ) ) {
	
				$course_settings = learndash_get_setting( $this->course_id );
				if ( !is_array( $course_settings ) ) {
					if ( !empty( $course_settings ) ) 
						$course_settings = array( $course_settings );
					else
						$course_settings = array();
				}
				$lesson_settings = sfwd_lms_get_post_options( 'sfwd-lessons' );
	
				if ( ( !isset( $course_settings['course_lesson_order'] ) ) || ( empty( $course_settings['course_lesson_order'] ) ) ) {
					if ( ( isset( $lesson_settings['order'] ) ) && ( !empty( $lesson_settings['order'] ) ) ) {
						$course_settings['course_lesson_order'] = $lesson_settings['order'];
					}
				}

				if ( ( !isset( $course_settings['course_lesson_orderby'] ) ) || ( empty( $course_settings['course_lesson_orderby'] ) ) ) {
					if ( ( isset( $lesson_settings['orderby'] ) ) && ( !empty( $lesson_settings['orderby'] ) ) ) {
						$course_settings['course_lesson_orderby'] = $lesson_settings['orderby'];
					}
				}
	
				if ( ( !isset( $course_settings['course_lesson_per_page'] ) ) || ( empty( $course_settings['course_lesson_per_page'] ) ) ) {
					if ( ( isset( $lesson_settings['posts_per_page'] ) ) && ( !empty( $lesson_settings['posts_per_page'] ) ) ) {
						$course_settings['course_lesson_per_page'] = $lesson_settings['posts_per_page'];
					}
				}
	
	
				// Course > Lessons
				$lesson_steps_query_args = array(
					'post_type' 		=> 'sfwd-lessons',
					'posts_per_page' 	=> 	-1,
					'post_status' 		=> 	'publish',
					'fields'			=>	'ids',
					'orderby' 			=> 	$course_settings['course_lesson_orderby'], 
					'order' 			=> 	$course_settings['course_lesson_order'],
					'meta_query' 		=> 	array(
						array(
							'key'     	=> 'course_id',
							'value'   	=> intval( $this->course_id ),
							'compare' 	=> '=',
							'type'		=>	'NUMERIC'
						)
					)
				);

				$lesson_steps_query = new WP_Query( $lesson_steps_query_args );
				if ( ( $lesson_steps_query instanceof WP_Query ) && ( property_exists( $lesson_steps_query, 'posts' ) ) && ( !empty( $lesson_steps_query->posts) ) ) {

					foreach( $lesson_steps_query->posts as $lesson_id ) {
						$steps['sfwd-lessons'][$lesson_id] = array();
						$steps['sfwd-lessons'][$lesson_id]['sfwd-topic'] = array();
						$steps['sfwd-lessons'][$lesson_id]['sfwd-quiz'] = array();
			
						// Course > Lesson > Topics
						$topic_steps_query_args = array(
							'post_type' 		=> 'sfwd-topic',
							'posts_per_page' 	=> 	-1,
							'post_status' 		=> 	'publish',
							'fields'			=>	'ids',
							'orderby' 			=> 	$course_settings['course_lesson_orderby'], 
							'order' 			=> 	$course_settings['course_lesson_order'],
							'meta_query' 		=> 	array(
								'relation' 		=> 'AND',
								array(
									'key'     	=> 'course_id',
									'value'   	=> intval( $this->course_id ),
									'compare' 	=> '=',
									'type'		=>	'NUMERIC'
								),
								array(
									'key'     	=> 'lesson_id',
									'value'   	=> intval( $lesson_id ),
									'compare' 	=> '=',
									'type'		=>	'NUMERIC'
								),
							)
						);
			
						$topic_steps_query = new WP_Query( $topic_steps_query_args );
						if ( ( $topic_steps_query instanceof WP_Query ) && ( property_exists( $topic_steps_query, 'posts' ) ) && ( !empty( $topic_steps_query->posts) ) ) {
							foreach( $topic_steps_query->posts as $topic_id ) {
								$steps['sfwd-lessons'][$lesson_id]['sfwd-topic'][$topic_id] = array();
								$steps['sfwd-lessons'][$lesson_id]['sfwd-topic'][$topic_id]['sfwd-quiz'] = array();
					
								// Course > Lesson > Topic > Quizzes
								$topic_quiz_steps_query_args = array(
									'post_type' 		=> 'sfwd-quiz',
									'posts_per_page' 	=> 	-1,
									'post_status' 		=> 	'publish',
									'fields'			=>	'ids',
									'orderby' 			=> 	$course_settings['course_lesson_orderby'], 
									'order' 			=> 	$course_settings['course_lesson_order'],
									'meta_query' 		=> 	array(
										'relation' 		=> 'AND',
										array(
											'key'     	=> 'course_id',
											'value'   	=> intval( $this->course_id ),
											'compare' 	=> '=',
											'type'		=>	'NUMERIC'
										),
										array(
											'key'     	=> 'lesson_id',
											'value'   	=> intval( $topic_id ),
											'compare' 	=> '=',
											'type'		=>	'NUMERIC'
										),
									)
								);
					
								$topic_quiz_steps_query = new WP_Query( $topic_quiz_steps_query_args );
								if ( ( $topic_quiz_steps_query instanceof WP_Query ) && ( property_exists( $topic_quiz_steps_query, 'posts' ) ) && ( !empty( $topic_quiz_steps_query->posts) ) ) {
									foreach( $topic_quiz_steps_query->posts as $quiz_id ) {
										$steps['sfwd-lessons'][$lesson_id]['sfwd-topic'][$topic_id]['sfwd-quiz'][$quiz_id] = array();
									}
								}
							}
						}
			
			
						// Course > Lesson > Quizzes
						$lesson_quiz_steps_query_args = array(
							'post_type' 		=> 'sfwd-quiz',
							'posts_per_page' 	=> 	-1,
							'post_status' 		=> 	'publish',
							'fields'			=>	'ids',
							'orderby' 			=> 	$course_settings['course_lesson_orderby'], 
							'order' 			=> 	$course_settings['course_lesson_order'],
							'meta_query' 		=> 	array(
								'relation' => 'AND',
								array(
									'key'     	=> 'course_id',
									'value'   	=> intval( $this->course_id ),
									'compare' 	=> '=',
									'type'		=>	'NUMERIC'
								),
								array(
									'key'     	=> 'lesson_id',
									'value'   	=> intval( $lesson_id ),
									'compare' 	=> '=',
									'type'		=>	'NUMERIC'
								),
							)
						);
						$lesson_quiz_steps_query = new WP_Query( $lesson_quiz_steps_query_args );
						if ( ( $lesson_quiz_steps_query instanceof WP_Query ) && ( property_exists( $lesson_quiz_steps_query, 'posts' ) ) && ( !empty( $lesson_quiz_steps_query->posts) ) ) {
							foreach( $lesson_quiz_steps_query->posts as $quiz_id ) {
								$steps['sfwd-lessons'][$lesson_id]['sfwd-quiz'][$quiz_id] = array();
							}
						}
					}
				} else {
					$steps['sfwd-lessons'] = array();
				}
	
	
				// Course > Quizzes (Global Quizzes)
				$quiz_steps_query_args = array(
					'post_type' 		=> 'sfwd-quiz',
					'posts_per_page' 	=> 	-1,
					'post_status' 		=> 	'publish',
					'fields'			=>	'ids',
					'orderby' 			=> 	$course_settings['course_lesson_orderby'], 
					'order' 			=> 	$course_settings['course_lesson_order'],
					'meta_query' 		=> 	array(
						'relation' => 'AND',
						array(
							'key'     	=> 'course_id',
							'value'   	=> intval( $this->course_id ),
							'compare' 	=> '=',
							'type'		=>	'NUMERIC'
						),
						array(
							'relation' => 'OR',
							array(
								'key'     	=> 'lesson_id',
								'value'   	=> 0,
								'compare' 	=> '=',
								'type'		=>	'NUMERIC'
							),
							array(
								'key'     	=> 'lesson_id',
								'compare' 	=> 'NOT EXISTS',
							),
						)
					)
				);
				$quiz_steps_query = new WP_Query( $quiz_steps_query_args );
				if ( ( $quiz_steps_query instanceof WP_Query ) && ( property_exists( $quiz_steps_query, 'posts' ) ) && ( !empty( $quiz_steps_query->posts) ) ) {
					foreach( $quiz_steps_query->posts as $quiz_id ) {
						$steps['sfwd-quiz'][$quiz_id] = array();
					}
				} else {
					$steps['sfwd-quiz'] = array();
				}
			}

			return $steps;
		}

		function get_item_parent_steps( $post_id = 0, $post_type = '' ) {
			$item_ancestor_steps = array();
			
			if ( !empty( $post_id ) ) {
				if ( empty( $post_type ) ) {
					$post_type = get_post_type( $post_id );
				}

				if ( !empty( $post_type ) ) {
					$this->load_steps();
					$steps_key = $post_type .':'. $post_id;
					if ( isset( $this->steps['r'][$steps_key] ) ) {
						$item_ancestor_steps = $this->steps['r'][$steps_key];
					}
				} 
			} 
			
			return $item_ancestor_steps;
		}

		function get_parent_step_id( $step_post_id = 0, $ancestor_step_type = '' ) {
			if ( !empty( $step_post_id ) ) {
				$step_ancestor_item = $this->get_item_parent_steps( $step_post_id );
				if ( !empty( $step_ancestor_item ) ) {
					foreach( $step_ancestor_item as $parent_steps_value ) {
						//error_log('parent_steps_value<pre>'. print_r($parent_steps_value, true) .'</pre>');
						if ( ( is_string( $parent_steps_value ) ) && ( !empty( $parent_steps_value ) ) ) {
							list( $s_post_type, $s_post_id ) = explode(':', $parent_steps_value );
							if ( !empty( $ancestor_step_type ) ) {
								if ( $ancestor_step_type == $s_post_type ) {
									return intval( $s_post_id );
								}
							} else {
								return intval( $s_post_id );
							}
						}
					}
				}
			}
		}

		function get_children_steps( $post_id = 0, $post_type = '' ) {
			$item_children_steps = array();
			
			if ( !empty( $post_id ) ) {
				$this->load_steps();
				$steps_h = $this->get_steps('h');

				$ancestor_steps = $this->get_item_parent_steps( $post_id );
				if ( !empty( $ancestor_steps ) ) {
					$ancestor_steps = array_reverse( $ancestor_steps );
				}
				$ancestor_steps[] = get_post_type( $post_id ) .':'. $post_id;
				foreach( $ancestor_steps as $ancestor_step ) {
					if ( ( is_string( $ancestor_step ) ) && ( !empty( $ancestor_step ) ) ) {
						list( $ancestor_step_post_type, $ancestor_step_post_id ) = explode(':', $ancestor_step );
						if ( isset( $steps_h[$ancestor_step_post_type][$ancestor_step_post_id] ) ) {
							$steps_h = $steps_h[$ancestor_step_post_type][$ancestor_step_post_id];
						}
					} 
				}

				if ( !empty( $steps_h ) ) {
					foreach( $steps_h as $steps_post_type => $steps_post_set ) {
						if (( empty( $post_type) ) || ( $post_type == $steps_post_type ) ) {
							$item_children_steps = array_merge( $item_children_steps, array_keys( $steps_post_set ) );
						}
					}
				}
			} 
			
			return $item_children_steps;
		}

		/*
		function load_lessons_list( ) {
			$lessons_ids = array();
			
			if ( !empty( $this->course_id ) ) {
				$this->lessons_query_args = array(
					'post_type'		=>	LearnDash_Lesson::get_post_type(),
					'orderby' 		=> 	$this->settings['course_lesson_orderby'], 
					'order' 		=> 	$this->settings['course_lesson_order'],
					'fields'		=>	'ids',
					'meta_key' 		=> 	'course_id', 
					'meta_value' 	=> 	$this->course_id,
					'nopaging'		=>	true
				);

				error_log('lessons_query<pre>'. print_r($this->lessons_query_args, true) .'</pre>');
				$this->lessons_query = new WP_Query( $this->lessons_query_args );
				//error_log('lessons_query<pre>'. print_r($lessons_query, true) .'</pre>');
				if ( ( $this->lessons_query instanceof WP_Query ) && ( property_exists( $this->lessons_query, 'posts' ) ) ) {
					$lessons_ids = $this->lessons_query->posts;
				}
			}
			
			return $lessons_ids;
		}
		*/
		
		
		static function steps_split_keys( $steps, $parent_type = '' ) {
			if ( $parent_type == 'sfwd-lessons' ) {
				$course_steps_split_keys = array(
					'sfwd-topic' 	=> 	array(),
					'sfwd-quiz'		=>	array()
				);
			} else if ( $parent_type == 'sfwd-topic' ) {
				$course_steps_split_keys = array(
					'sfwd-quiz'		=>	array()
				);
			} else if ( $parent_type == 'sfwd-quiz' ) {
				$course_steps_split_keys = array();
			} else if ( $parent_type == 'section-heading' ) {
				$course_steps_split_keys = array();
			} else if ( empty( $parent_type ) ) {
				$course_steps_split_keys = array(
					'sfwd-lessons' 	=> 	array(),
					'sfwd-quiz'		=>	array()
				);
			}
			
			if ( !empty( $steps ) ) {
				foreach( $steps as $step_idx => $step_set ) {
					list( $step_post_type, $step_id ) = explode(':', $step_idx );
					if ( ( !empty( $step_post_type ) ) && ( !empty( $step_id ) ) ) {
						if ( !isset( $course_steps_split_keys[$step_post_type] ) )
							$course_steps_split_keys[$step_post_type] = array();
						$course_steps_split_keys[$step_post_type][$step_id] = self::steps_split_keys( $step_set, $step_post_type );
					}
				}
			}
			return $course_steps_split_keys;
		}
		
	}
}
