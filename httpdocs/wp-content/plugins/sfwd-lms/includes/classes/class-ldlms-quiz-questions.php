<?php
/**
 * LearnDash Quiz Questions Class.
 *
 * @package LearnDash
 * @subpackage Quiz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LDLMS_Quiz_Questions' ) ) {
	/**
	 * Class for LearnDash Quiz Questions.
	 */
	class LDLMS_Quiz_Questions {

		/**
		 * Quiz ID for questions grouping.
		 *
		 * @var integer $quiz_id
		 */
		private $quiz_id = 0;

		/**
		 * ProQuiz Quiz ID.
		 *
		 * @var integer $quiz_pro_id
		 */
		private $quiz_pro_id = 0;

		/**
		 * Quiz ID Primary for settings.
		 * When sharing via Associated Settiings.
		 *
		 * @var integer $quiz_primary_id
		 */
		private $quiz_primary_id = 0;

		/**
		 * Init Called flag.
		 *
		 * @var boolean $init_called Set to false initially. Set to true init has
		 * been called.
		 */
		private $init_called = false;

		/**
		 * Questions Loaded flag.
		 *
		 * @var boolean $questions_loaded Set to false initially. Set to true once quiz
		 * questions have been loaded.
		 */
		private $questions_loaded = false;

		/**
		 * Questions are dirty.
		 *
		 * @var boolean $questions_dirty Set to false initially but can be set to true if the
		 * dirty meta is read in and it true.
		 */
		private $questions_dirty = false;

		/**
		 * Questions array.
		 *
		 * @var array $questions Array of question post IDs.
		 */
		protected $questions = false;

		/**
		 * Quiz post types
		 *
		 * @var array $quiz_post_types Quiz post types.
		 */
		protected $quiz_post_types = array();


		/**
		 * Public constructor for class.
		 *
		 * @since 2.6.0
		 * @param integer $quiz_id Quiz post ID to load questions for.
		 */
		public function __construct( $quiz_id = 0 ) {
			if ( ! empty( $quiz_id ) ) {
				$this->quiz_id = absint( $quiz_id );

				$this->quiz_post_types = LDLMS_Post_Types::get_post_types( 'quiz_questions' );
			}
		}

		/**
		 * Iniitialize object vars.
		 */
		public function init() {
			if ( false === $this->init_called ) {
				$this->init_called = true;

				$quiz_pro_id = learndash_get_setting( $this->quiz_id, 'quiz_pro' );
				$quiz_pro_id = absint( $quiz_pro_id );
				$this->quiz_pro_id = $quiz_pro_id;

				$this->quiz_primary_id = learndash_get_quiz_primary_shared( $this->quiz_pro_id, $this->quiz_id );
				$this->quiz_primary_id = absint( $this->quiz_primary_id );

				if ( empty( $this->quiz_primary_id ) ) {
					$this->quiz_primary_id = $this->quiz_id;
				}
			}
		}


		/**
		 * Load Quiz Questions.
		 */
		public function load_questions() {
			global $pagenow;

			$this->init();

			if ( ( false === $this->questions_loaded ) || ( $this->is_questions_dirty() ) ) {
				$this->questions_loaded = true;

				if ( ! is_array( $this->questions ) ) {
					$this->questions = array();
				}

				// A new Quiz will not yet have questions.
				if ( ( is_admin() ) && ( 'post-new.php' === $pagenow ) ) {
					$this->questions['post_ids'] = array();
				} else {
					$this->questions['post_ids'] = get_post_meta( $this->quiz_primary_id, 'ld_quiz_questions', true );
				}

				if ( ! is_array( $this->questions['post_ids'] ) ) {
					$this->questions_dirty = true;
				}

				if ( $this->is_questions_dirty() ) {
					$this->questions['post_ids'] = $this->build_questions( 'post_ids' );
				}

				if ( $this->is_questions_dirty() ) {
					update_post_meta( $this->quiz_id, 'ld_quiz_questions', $this->questions['post_ids'] );
					$this->clear_questions_dirty();
				}
			}
		}

		/**
		 * Build the ProQuiz question objects from the post_ids
		 *
		 * @since 2.6.0
		 * @param mixed $question_type string or array of question types to build out.
		 * @return array of questions.
		 */
		private function build_questions( $question_type = '' ) {

			$questions = array();

			switch ( $question_type ) {
				case 'post_ids':
					if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Quizzes_Builder', 'shared_questions' ) === 'yes' ) {
						$questions_query_meta_query = array(
							'relation' => 'OR',
							array(
								'key'     => 'quiz_id',
								'value'   => $this->quiz_primary_id,
								'compare' => '=',
							),
							array(
								'key'     => 'ld_quiz_' . $this->quiz_primary_id,
								'value'   => $this->quiz_primary_id,
								'compare' => '=',
							),
						);
					} else {
						// We clear out the existing questions to force query fresh from question posts.
						$this->questions['post_ids'] = array();
						$questions_query_meta_query = array(
							array(
								'key'     => 'quiz_id',
								'value'   => $this->quiz_primary_id,
								'compare' => '=',
							),
						);
					}

					// First validate the existing post meta stored set.
					if ( ( isset( $this->questions['post_ids'] ) ) && ( ! empty( $this->questions['post_ids'] ) ) ) {
						$questions_query_args = array(
							'post_type'      => learndash_get_post_type_slug( 'question' ),
							'posts_per_page' => -1,
							'post_status'    => 'publish',
							'fields'         => 'ids',
							'orderby'        => 'post__in',
							'post__in'       => array_keys( $this->questions['post_ids'] ),
							'meta_query'     => $questions_query_meta_query,
						);

						$questions_query = new WP_Query( $questions_query_args );
						if ( ( is_a( $questions_query, 'WP_Query' ) ) && ( property_exists( $questions_query, 'posts' ) ) && ( ! empty( $questions_query->posts ) ) ) {
							foreach ( $questions_query->posts as $question_post_id ) {
								$question_pro_id = get_post_meta( $question_post_id, 'question_pro_id', true );
								if ( ! empty( $question_pro_id ) ) {
									$questions[ $question_post_id ] = (int) $question_pro_id;
								}
							}
						}

						// We replace the stored post meta with the updated found items.
						$this->questions['post_ids'] = $questions;
					}

					if ( ! empty( $this->quiz_primary_id ) ) {
						$questions_query_args = array(
							'post_type'      => learndash_get_post_type_slug( 'question' ),
							'posts_per_page' => -1,
							'post_status'    => 'publish',
							'fields'         => 'ids',
							'orderby'        => 'menu_order',
							'order'          => 'ASC',
							'meta_query'     => $questions_query_meta_query,
						);

						// Exlude the found items from the first step above.
						if ( ( isset( $this->questions['post_ids'] ) ) && ( ! empty( $this->questions['post_ids'] ) ) ) {
							$questions_query_args['post__not_in'] = array_keys( $this->questions['post_ids'] );
						}
						$questions_query = new WP_Query( $questions_query_args );
						if ( ( is_a( $questions_query, 'WP_Query' ) ) && ( property_exists( $questions_query, 'posts' ) ) && ( ! empty( $questions_query->posts ) ) ) {
							foreach ( $questions_query->posts as $question_post_id ) {
								if ( ! isset( $questions[ $question_post_id ] ) ) {
									$question_pro_id = get_post_meta( $question_post_id, 'question_pro_id', true );
									if ( ! empty( $question_pro_id ) ) {
										$questions[ $question_post_id ] = (int) $question_pro_id;
									}
								}
							}
						}
					}
					break;

				case 'pro_objects':
					if ( ! empty( $this->questions['post_ids'] ) ) {

						$question_mapper = new WpProQuiz_Model_QuestionMapper();

						$_questions_changed = false;
						foreach ( $this->questions['post_ids'] as $question_post_id => $question_pro_id ) {
							$question_post_id = absint( $question_post_id );
							$question_pro_id = absint( $question_pro_id );

							$question_pro_object = $question_mapper->fetchById( $question_pro_id );
							if ( is_null( $question_pro_object ) ) {
								// Changed in LD 3.0.7 we don't trust the $question_pro_id value.
								$question_pro_id_real = get_post_meta( $question_post_id, 'question_pro_id', true );
								$question_pro_id_real = absint( $question_pro_id_real );
								if ( ( ! empty( $question_pro_id_real ) ) && ( $question_pro_id_real !== $question_pro_id ) ) {
									$question_pro_object = $question_mapper->fetchById( $question_pro_id_real );
									if ( is_a( $question_pro_object, 'WpProQuiz_Model_Question' ) ) {
										$_questions_changed = true;
										$this->questions['post_ids'][ $question_post_id ] = $question_pro_id_real;
										$question_pro_id = $question_pro_id_real;
									}
								}
							}

							if ( is_a( $question_pro_object, 'WpProQuiz_Model_Question' ) ) {
								$question_pro_object->setQuestionPostId( $question_post_id );
								$question_pro_object->setQuizId( absint( $this->quiz_pro_id ) );
								$question_pro_object->setTitle( get_post_field( 'post_title', $question_post_id, 'raw' ) );
								$question_pro_object->setQuestion( get_post_field( 'post_content', $question_post_id, 'raw' ) );
								$questions[ $question_post_id ] = $question_pro_object;
							} else {
								unset( $this->questions['post_ids'][ $question_post_id ] );
							}
						}

						if ( true === $_questions_changed ) {
							update_post_meta( $this->quiz_primary_id, 'ld_quiz_questions', $this->questions['post_ids'] );
						}
					}
					break;
			}

			return $questions;
		}

		/**
		 * Sets the Quiz dirty flag and will force the questions to be
		 * reloaded from queries.
		 */
		public function set_questions_dirty() {
			$this->init();
			if ( ! empty( $this->quiz_primary_id ) ) {
				$this->questions_dirty = true;
				update_post_meta( $this->quiz_primary_id, 'ld_quiz_questions_dirty', $this->quiz_primary_id );
			}
		}

		/**
		 * Check if the quiz dirty flag is set.
		 */
		protected function is_questions_dirty() {
			// If the questions_dirty boolean has been previously set to try it save a call to postmeta.
			if ( false === $this->questions_dirty ) {
				if ( ! empty( $this->quiz_primary_id ) ) {
					$is_dirty = get_post_meta( $this->quiz_primary_id, 'ld_quiz_questions_dirty', true );
					if ( absint( $is_dirty ) === absint( $this->quiz_primary_id ) ) {
						$this->questions_dirty = true;
					}
				}
			}

			return $this->questions_dirty;
		}

		/**
		 * Clear the quiz dirty flag.
		 */
		protected function clear_questions_dirty() {
			if ( ! empty( $this->quiz_primary_id ) ) {
				$this->questions_dirty = false;
				delete_post_meta( $this->quiz_primary_id, 'ld_quiz_questions_dirty' );
			}
		}

		/**
		 * Get the count of valid questions in quiz.
		 */
		public function get_questions_count() {
			$this->init();

			$quiz_questions_count = 0;

			$this->load_questions();
			if ( isset( $this->questions['post_ids'] ) ) {
				$quiz_questions_count = count( $this->questions['post_ids'] );
			}

			return $quiz_questions_count;
		}

		/**
		 * Save Quiz Questions.
		 *
		 * @since 2.6.0
		 *
		 * @param array $questions Questions array to save.
		 */
		public function set_questions( $questions = array() ) {
			$this->init();

			if ( ! empty( $this->quiz_primary_id ) ) {
				$this->load_questions();
				$this->questions['post_ids'] = $questions;

				if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Quizzes_Builder', 'shared_questions' ) != 'yes' ) {
					$this->set_questions_to_quiz_legacy();
				} else {
					$this->set_questions_to_quiz();
				}
				/**
				 * Clear the questions loaded flag to force reload of questions on the
				 * next get_questions request.
				 */
				$this->questions_loaded = false;

				update_post_meta( $this->quiz_primary_id, 'ld_quiz_questions', $questions );
			}
		}

		/**
		 * This function sets a post_meta association for the various questions within the quiz.
		 * The new association is 'ld_quiz_XXX' where 'XXX' is the quiz ID.
		 */
		public function set_questions_to_quiz() {
			global $wpdb;

			$quiz_questions_new = array();

			if ( ( isset( $this->questions['post_ids'] ) ) && ( ! empty( $this->questions['post_ids'] ) ) ) {
				$quiz_questions_new = array_keys( $this->questions['post_ids'] );
			}
			if ( ! empty( $quiz_questions_new ) ) {
				sort( $quiz_questions_new, SORT_NUMERIC );
			}

			$sql_str = $wpdb->prepare( "SELECT post_id as post_id FROM ". $wpdb->postmeta ." WHERE meta_key LIKE %s", 'ld_quiz_'. $this->quiz_primary_id );
			$quiz_questions_old = $wpdb->get_col( $sql_str );
			if ( !empty( $quiz_questions_old ) ) {
				sort( $quiz_questions_old, SORT_NUMERIC );
			}

			$quiz_questions_intersect = array_intersect( $quiz_questions_new, $quiz_questions_old );

			// Add Questions
			$quiz_questions_add = array_diff( $quiz_questions_new, $quiz_questions_intersect );
			if ( !empty( $quiz_questions_add ) ) {
				$quiz_questions_add_chunks = array_chunk ( $quiz_questions_add, LEARNDASH_LMS_DEFAULT_CB_INSERT_CHUNK_SIZE );
				foreach( $quiz_questions_add_chunks as $insert_post_ids ) {
					$insert_sql_str = "";
					foreach( $insert_post_ids as $post_id ) {
						if ( !empty( $insert_sql_str ) ) $insert_sql_str .= ',';

						$insert_sql_str .= "(". $post_id .", 'ld_quiz_" . $this->quiz_primary_id . "', ". $this->quiz_primary_id . ")";
					}
					if ( !empty( $insert_sql_str ) ) {
						$insert_sql_str = "INSERT INTO ". $wpdb->postmeta ." (`post_id`, `meta_key`, `meta_value`) VALUES " . $insert_sql_str;
						$wpdb->query( $insert_sql_str );
					}
				}
			}

			// Remove Steps.
			$quiz_questions_remove = array_diff( $quiz_questions_old, $quiz_questions_intersect );
			if ( ! empty( $quiz_questions_remove ) ) {
				$delete_sql_str = "DELETE FROM " . $wpdb->postmeta . " WHERE meta_key LIKE 'ld_quiz_" . $this->quiz_primary_id . "' AND post_id IN (" . implode(',', $quiz_questions_remove ) . ")";
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
				AND posts.post_type = '" . learndash_get_post_type_slug( 'question' ) . "'
				AND postmeta.meta_key = %s 
				AND postmeta.meta_value = %d", 'quiz_id', $this->quiz_primary_id
			);
			//error_log('sql_str['. $sql_str .']');

			$quiz_questions_primary = $wpdb->get_col( $sql_str );
			if ( ! empty( $quiz_questions_primary ) ) {
				$quiz_questions_primary = array_map( 'intval', $quiz_questions_primary );
			}

			$quiz_questions_primary_intersect = array_intersect( $quiz_questions_new, $quiz_questions_primary );

			$quiz_questions_primary_remove = array_diff( $quiz_questions_primary, $quiz_questions_primary_intersect );
			if ( ! empty( $quiz_questions_primary_remove ) ) {
				$delete_sql_str = "DELETE FROM " . $wpdb->postmeta . " WHERE meta_key = 'quiz_id' AND post_id IN (". implode(',', $quiz_questions_primary_remove ) . ")";
				$wpdb->query( $delete_sql_str );
			}
		}

		/**
		 * This function sets a post_meta association for the various questions within the quiz.
		 * The new association is 'quiz'.
		 */
		public function set_questions_to_quiz_legacy() {
			global $wpdb;

			$quiz_questions_new = array();

			if ( ( isset( $this->questions['post_ids'] ) ) && ( ! empty( $this->questions['post_ids'] ) ) ) {
				$this->set_question_to_quiz_order( $this->questions['post_ids'] );
				$quiz_questions_new = array_keys( $this->questions['post_ids'] );
			}

			if ( ! empty( $quiz_questions_new ) ) {
				sort( $quiz_questions_new, SORT_NUMERIC );
			}

			$quiz_questions_query_args = array(
				'post_type'      => $this->quiz_post_types,
				'posts_per_page' => -1,
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'meta_query'     => array(
					'relation'   => 'AND',
					array(
						'key'     => 'quiz_id',
						'value'   => $this->quiz_primary_id,
						'compare' => '=',
						'type'    => 'NUMERIC'
					),
				),
			);

			$quiz_questions_query = new WP_Query( $quiz_questions_query_args );
			if ( ( $quiz_questions_query instanceof WP_Query ) && ( property_exists( $quiz_questions_query, 'posts' ) ) && ( ! empty( $quiz_questions_query->posts ) ) ) {
				$quiz_questions_old = $quiz_questions_query->posts;
				$quiz_questions_old = array_map( 'intval', $quiz_questions_old );
			} else {
				$quiz_questions_old = array();
			}
			$quiz_questions_intersect = array_intersect( $quiz_questions_new, $quiz_questions_old );

			// Add Steps.
			$quiz_questions_add = array_diff( $quiz_questions_new, $quiz_questions_intersect );
			if ( ! empty( $quiz_questions_add ) ) {
				foreach ( $quiz_questions_add as $question_post_id ) {
					learndash_update_setting( $question_post_id, 'quiz', $this->quiz_primary_id );
				}
			}

			// Remove Steps.
			$quiz_questions_remove = array_diff( $quiz_questions_old, $quiz_questions_intersect );
			if ( ! empty( $quiz_questions_remove ) ) {
				foreach ( $quiz_questions_remove as $question_post_id ) {
					learndash_update_setting( $question_post_id, 'quiz', 0 );
				}
			}
		}

		/**
		 * This function updates the postmeta 'question_order' for legacy questions.
		 *
		 * @since 2.6.0
		 * @param array $questions Array of Question post IDs.
		 */
		protected function set_question_to_quiz_order( $questions = array() ) {
			global $wpdb;

			if ( ! empty( $questions ) ) {
				$sql_str = '';

				$question_order = (int)0;
				foreach ( $questions as $question_post_id => $question_pro_id ) {
					$question_order++;
					$wpdb->update(
						$wpdb->posts,
						array( 'menu_order' => $question_order ),
						array( 'ID' => $question_post_id ),
						array( '%d' ),
						array( '%d' )
					);
					if ( ! empty( $this->quiz_pro_id ) ) {
						$answer_fields = array( 'quiz_id' => $this->quiz_pro_id, 'sort' => $question_order );
						$answer_types = array( '%d', '%d' );
					} else {
						$answer_fields = array( 'sort' => $question_order );
						$answer_types = array( '%d' );
					}
					$wpdb->update(
						LDLMS_DB::get_table_name( 'quiz_question' ),
						$answer_fields,
						array( 'ID' => $question_pro_id ),
						$answer_types,
						array( '%d' )
					);
				}
			}
		}

		/**
		 * Get Quiz Questions set by type.
		 *
		 * @since 2.6.0
		 *
		 * @param string $question_type Default is 'post_ids' or 'pro_objects'.
		 * @return array of question IDs.
		 */
		public function get_questions( $question_type = 'post_ids' ) {
			$this->load_questions();

			if ( 'pro_objects' === $question_type ) {
				// By default the WPProQuiz object 'pro_objects' are not loaded until needed.
				$this->questions['pro_objects'] = $this->build_questions( $question_type );
				return $this->questions['pro_objects'];
			} elseif ( isset( $this->questions[ $question_type ] ) ) {
				return $this->questions[ $question_type ];
			} else if ( 'all' === $question_type ) {
				return $this->questions;
			}

			return array();
		}

		/**
		 * Process the _POST data on Quiz save. This function reformats the questions
		 * data array into the internally used format of this class.
		 *
		 * @since 2.6.0
		 *
		 * @param array $questions_data array of question post IDs.
		 * @return array $questions.
		 */
		public static function questions_split_keys( $questions_data = array() ) {
			$questions = array();
			if ( ! empty( $questions_data ) ) {
				foreach ( $questions_data as $question_set => $question_dummy ) {
					list( $question_post_type, $question_post_id ) = explode( ':', $question_set );
					$question_post_id = absint( $question_post_id );
					if ( ( learndash_get_post_type_slug( 'question' ) === $question_post_type ) && ( ! empty( $question_post_id ) ) ) {
						$question_pro_id = get_post_meta( $question_post_id, 'question_pro_id', true );
						if ( ! empty( $question_pro_id ) ) {
							$questions[ $question_post_id ] = (int) $question_pro_id;
						}
					}
				}
			}

			return $questions;
		}

		// End of functions.
	}
}
