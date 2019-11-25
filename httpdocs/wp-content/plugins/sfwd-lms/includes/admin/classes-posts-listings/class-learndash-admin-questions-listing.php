<?php
/**
 * LearnDash Quiz Questions (sfwd-question) Posts Listing Class.
 *
 * @package LearnDash
 * @subpackage admin
 */

if ( ( class_exists( 'Learndash_Admin_Posts_Listing' ) ) && ( ! class_exists( 'Learndash_Admin_Questions_Listing' ) ) ) {
	/**
	 * Class for LearnDash Quiz Questions Listing Pages.
	 */
	class Learndash_Admin_Questions_Listing extends Learndash_Admin_Posts_Listing {

		/**
		 * Public constructor for class
		 */
		public function __construct() {
			$this->post_type = 'sfwd-question';

			parent::__construct();
		}

		/**
		 * Call via the WordPress load sequence for admin pages.
		 */
		public function on_load_edit() {
			global $typenow, $post;

			if ( ( empty( $typenow ) ) || ( $typenow !== $this->post_type ) ) {
				return;
			}

			$this->columns = array(
				'question_type'   => esc_html__( 'Type', 'learndash' ),
				'question_points' => esc_html__( 'Points', 'learndash' ),
				'question_quiz'   => sprintf(
					// translators: placeholder: Quiz.
					esc_html_x( 'Assigned %s', 'placeholder: Quiz', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'quiz' )
				),
			);

			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Quizzes_Builder', 'shared_questions' ) == 'yes' ) {
				unset( $this->columns['question_quiz'] );
			}

			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Questions_Taxonomies', 'proquiz_question_category' ) == 'yes' ) {
				$this->columns['proquiz_question_category'] = sprintf(
					// translators: placeholder: Question.
					esc_html_x( '%s Category', 'placeholder: Question', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'question' )
				);
			}

			$this->post_type_selectors = array(
				'quiz_id' => array(
					'query_args'       => array(
						'post_type' => 'sfwd-quiz',
					),
					'query_arg'        => 'quiz_id',
					'selected'         => 0,
					'field_name'       => 'quiz_id',
					'field_id'         => 'quiz_id',
					'show_all_value'   => '',
					'show_all_label'   => sprintf(
						// translators: placeholder: Quizzes.
						esc_html_x( 'Show All %s', 'placeholder: Quizzes', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'quizzes' )
					),
					'show_empty_value' => 'empty',
					'show_empty_label' => sprintf(
						// translators: placeholder: Quiz.
						esc_html_x( '-- No %s --', 'placeholder: Quiz', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'quiz' )
					),
				),
			);

			add_action( 'admin_footer', array( $this, 'admin_footer' ) );

			parent::on_load_edit();
		}

		/**
		 * Hook into the WP admin footer logic to add custom JavaScript to replce the default page title.
		 */
		public function admin_footer() {
			global $post_type, $post_type_object;

			if ( ( ! is_admin() ) || ( $post_type !== $this->post_type ) ) {
				return;
			}
			if ( isset( $_GET['quiz_id'] ) ) {
				$quiz_id = absint( $_GET['quiz_id'] );
				if ( ! empty( $quiz_id ) ) {
					$quizzes_url = add_query_arg( 'post_type', learndash_get_post_type_slug( 'quiz' ), admin_url( 'edit.php' ) );

					$new_title     = '<a href="' . $quizzes_url . '">' . LearnDash_Custom_Label::get_label( 'quizzes' ) . '</a> &gt; <a href="' . get_edit_post_link( $quiz_id ) . '">' . get_the_title( $quiz_id ) . '</a> - ' . esc_html( $post_type_object->labels->name );
					$post_new_file = add_query_arg(
						array(
							'post_type' => $post_type,
							'quiz_id'   => $quiz_id,
						),
						'post-new.php'
					);
					$add_new_url   = admin_url( $post_new_file );
					?>
					<script>
						jQuery(window).ready(function() {
							jQuery( 'h1.wp-heading-inline' ).html('<?php echo $new_title; ?>' );
							jQuery( 'a.page-title-action' ).attr( 'href', '<?php echo $add_new_url; ?>' );
						});
					</script>
					<?php
				}
			}
		}

		/**
		 * Output custom column row data
		 *
		 * @since 2.6.0
		 *
		 * @param string  $column_name Column slug or row being displayed.
		 * @param integer $post_id Post ID of row being displayed.
		 */
		public function manage_column_rows( $column_name = '', $post_id = 0 ) {
			global $learndash_question_types;

			static $field_values = array();
			if ( ! isset( $field_values[ $post_id ] ) ) {
				$question_pro_id = get_post_meta( $post_id, 'question_pro_id', true );
				if ( ! empty( $question_pro_id ) ) {
					$field_values[ $post_id ] = leandash_get_question_pro_fields( $question_pro_id, array( 'points', 'answer_type', 'category_id', 'category_name' ) );
				} else {
					$field_values[ $post_id ] = array(
						'points'        => '',
						'answer_type'   => 'single',
						'category_id'   => 0,
						'category_name' => '',
					);
				}
			}

			if ( ( ! empty( $column_name ) ) && ( ! empty( $post_id ) ) ) {
				switch ( $column_name ) {
					case 'question_type':
						if ( ( ! isset( $field_values[ $post_id ]['answer_type'] ) ) || ( empty( $field_values[ $post_id ]['answer_type'] ) ) || ( ! isset( $learndash_question_types[ $field_values[ $post_id ]['answer_type'] ] ) ) ) {
							$field_values[ $post_id ]['answer_type'] = 'single';
						}
						echo esc_attr( $learndash_question_types[ $field_values[ $post_id ]['answer_type'] ] );
						break;

					case 'question_points':
						if ( ( ! isset( $field_values[ $post_id ]['points'] ) ) || ( empty( $field_values[ $post_id ]['points'] ) ) ) {
							$question_points = 1;
						}
						echo absint( $field_values[ $post_id ]['points'] );
						break;

					case 'question_quiz':
						$quiz_post_ids = get_post_meta( $post_id, 'quiz_id' );
						if ( ! empty( $quiz_post_ids ) ) {
							if ( 1 === count( $quiz_post_ids ) ) {
								$row_actions = array();
								foreach ( $quiz_post_ids as $quiz_post_id ) {
									$quiz_post_id = absint( $quiz_post_id );
									if ( ! empty( $quiz_post_id ) ) {
										$this->show_post_link( $quiz_post_id );
										$row_actions['edit']         = '<a href="' . get_edit_post_link( $quiz_post_id ) . '">' . esc_html__( 'edit', 'learndash' ) . '</a>';
										$row_actions['filter_posts'] = '<a href="' . add_query_arg( 'quiz_id', $post_id ) . '">' . esc_html__( 'filter', 'learndash' ) . '</a>';

										$this->show_row_actions( $row_actions );
									}
								}
							} else {
								printf(
									// translators: placeholder: Group Leaders Count.
									esc_html_x( 'Total %d', 'Quizzes Count', 'learndash' ),
									count( $quiz_post_ids )
								);
								$quiz_names = '';

								if ( count( $quiz_post_ids ) > 5 ) {
									$quiz_post_ids = array_slice( $quiz_post_ids, 0, 5 );
								}
								foreach ( $quiz_post_ids as $quiz_post_id ) {
									$quiz_post_id = absint( $quiz_post_id );
									if ( ! empty( $quiz_post_id ) ) {
										if ( ! empty( $quiz_names ) ) {
											$quiz_names .= ', ';
										}
										$quiz_names .= '<a href="' . get_edit_post_link( $quiz_post_id ) . '">' . get_the_title( $quiz_post_id ) . '</a>';
									}
								}
								if ( ! empty( $quiz_names ) ) {
									echo '<br />' . $quiz_names;
								}
							}
						}
						break;
					case 'proquiz_question_category':
						if ( ( isset( $field_values[ $post_id ]['category_id'] ) ) && ( ! empty( $field_values[ $post_id ]['category_id'] ) ) ) {
							$category_mapper = new WpProQuiz_Model_CategoryMapper();
							$cat             = $category_mapper->fetchById( $field_values[ $post_id ]['category_id'] );
							if ( ( $cat ) && ( is_a( $cat, 'WpProQuiz_Model_Category' ) ) ) {
								echo '<a href="' . add_query_arg(
									array(
										'post_type' => learndash_get_post_type_slug( 'question' ),
										'question_pro_category' => $cat->getCategoryId(),
									),
									admin_url( 'edit.php' )
								) . '">' . stripslashes( $cat->getCategoryName() ) . '</a>';
							}
						}
						break;
				}
			}
		}

		/**
		 * Function to show selectors before the post_type selectors.
		 */
		protected function show_early_selectors() {
			global $learndash_question_types;

			/**
			 * Filter selector for Question Types.
			 */
			if ( ! empty( $learndash_question_types ) ) {
				echo '<select name="question_type" id="question_type" class="postform">';
				echo '<option value="">' . sprintf(
					// translators: placeholder: Question.
					esc_html_x( 'Show all %s types', 'placeholder: Question', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'question' )
				);

				if ( ( isset( $_GET['question_type'] ) ) && ( ! empty( $_GET['question_type'] ) ) ) {
					$selected_question_type = esc_attr( $_GET['question_type'] );
				} else {
					$selected_question_type = '';
				}

				foreach ( $learndash_question_types as $q_type => $q_label ) {
					echo '<option value="' . esc_attr( $q_type ) . '" ' . selected( $q_type, $selected_question_type, false ) . '>' . esc_attr( $q_label ) . '</option>';
				}

				echo '</select>';
			}

			/**
			 * Filter selector for legacy ProQuiz Question Categories.
			 */
			$categoryMapper          = new WpProQuiz_Model_CategoryMapper();
			$question_pro_categories = $categoryMapper->fetchAll();
			if ( ! empty( $question_pro_categories ) ) {

				if ( ( isset( $_GET['question_pro_category'] ) ) && ( ! empty( $_GET['question_pro_category'] ) ) ) {
					$selected_question_pro_category = esc_attr( $_GET['question_pro_category'] );
				} else {
					$selected_question_pro_category = '';
				}
				echo '<select name="question_pro_category" id="question_pro_category" class="postform">';
				echo '<option value="">' . esc_html__( 'Show all ProQuiz Categories', 'learndash' );

				foreach ( $question_pro_categories as $question_pro_category ) {
					echo '<option value="' . absint( $question_pro_category->getCategoryId() ) . '" ' . selected( $question_pro_category->getCategoryId(), $selected_question_pro_category, false ) . '>' . esc_attr( $question_pro_category->getCategoryName() ) . '</option>';
				}

				echo '</select>';
			}
		}

		/**
		 * This function fill filter the table listing items based on filters selected.
		 * Called via 'parse_query' filter from WP.
		 *
		 * @since 2.6.0
		 * @param object $query WP_Query instance.
		 */
		public function parse_query_table_filter( $query ) {
			global $typenow, $post;

			if ( ( empty( $typenow ) ) || ( $typenow !== $this->post_type ) ) {
				return;
			}

			if ( ! $query->is_main_query() ) {
				return;
			}

			parent::parse_query_table_filter( $query );

			// Holds the included question ids.
			$questions_include = '';

			if ( ( isset( $_GET['quiz_id'] ) ) && ( ! empty( $_GET['quiz_id'] ) ) ) {
				$question_ids = array();

				if ( $this->post_type_selectors['quiz_id']['show_empty_value'] === $_GET['quiz_id'] ) {
					$query_args    = array(
						'post_type'      => learndash_get_post_type_slug( 'question' ),
						'posts_per_page' => -1,
						'post_status'    => 'publish',
						'fields'         => 'ids',
						'orderby'        => 'title',
						'order'          => 'ASC',
						'meta_query'     => array(
							'relation' => 'OR',
							array(
								'key'     => 'quiz_id',
								'compare' => 'NOT EXISTS',
							),
							array(
								'key'     => 'quiz_id',
								'value'   => '0',
								'compare' => '=',
							),
						),
					);
					$query_results = new WP_Query( $query_args );
					if ( ( is_a( $query_results, 'WP_Query' ) ) && ( property_exists( $query_results, 'posts' ) ) ) {
						if ( ! empty( $query_results->posts ) ) {
							$questions_include             = $query_results->posts;
							$query->query_vars['post__in'] = $query_results->posts;
							$query->query_vars['orderby']  = 'post__in';
						} else {
							$query->query_vars['post__in'] = array( 0 );
						}
					} else {
						$query->query_vars['post__in'] = array( 0 );
					}
				} else {
					$questions_include        = array();
					$ld_quiz_questions_object = LDLMS_Factory_Post::quiz_questions( absint( $_GET['quiz_id'] ) );
					$question_post_ids        = $ld_quiz_questions_object->get_questions();
					if ( ! empty( $question_post_ids ) ) {
						$questions_include = array_keys( $question_post_ids );
					}

					$questions_query_args = array(
						'post_type'      => learndash_get_post_type_slug( 'question' ),
						'posts_per_page' => -1,
						'fields'         => 'ids',
						'orderby'        => 'menu_order',
						'order'          => 'ASC',
						'meta_query'     => array(
							array(
								'key'     => 'quiz_id',
								'value'   => absint( $_GET['quiz_id'] ),
								'compare' => '=',
							),
						),
					);
					if ( ( isset( $question_post_ids ) ) && ( ! empty( $question_post_ids ) ) ) {
						$questions_query_args['post__not_in'] = $question_post_ids;
					}
					$questions_query = new WP_Query( $questions_query_args );
					if ( ( is_a( $questions_query, 'WP_Query' ) ) && ( property_exists( $questions_query, 'posts' ) ) && ( ! empty( $questions_query->posts ) ) ) {
						$questions_include = array_merge( $questions_include, $questions_query->posts );
					}

					if ( ! empty( $questions_include ) ) {
						$query->query_vars['post__in'] = $questions_include;
						$query->query_vars['orderby']  = 'post__in';
					} else {
						$query->query_vars['post__in'] = array( 0 );
					}
				}
			}

			if ( ( isset( $_GET['question_type'] ) ) && ( ! empty( $_GET['question_type'] ) ) ) {
				if ( ! isset( $query->query_vars['meta_query'] ) ) {
					$query->query_vars['meta_query'] = array();
				}

				$query->query_vars['meta_query'][] = array(
					'key'     => 'question_type',
					'value'   => esc_attr( $_GET['question_type'] ),
					'compare' => '=',
				);
			}

			if ( ( isset( $_GET['question_pro_category'] ) ) && ( ! empty( $_GET['question_pro_category'] ) ) ) {
				if ( ! isset( $query->query_vars['meta_query'] ) ) {
					$query->query_vars['meta_query'] = array();
				}

				$query->query_vars['meta_query'][] = array(
					'key'     => 'question_pro_category',
					'value'   => esc_attr( $_GET['question_pro_category'] ),
					'compare' => '=',
				);
			}
		}

		/**
		 * Initial hook for deleting a post.
		 *
		 * For the Questions post type we want to also remove the ProQuiz Question. So we grab
		 * the reference from the post meta for 'question_pro_id'.
		 *
		 * @since 2.6.5
		 * @param integer $post_id $Post ID to be deleted.
		 */
		public function before_delete_post( $post_id = 0 ) {
			global $post_type, $post_type_object;

			if ( ( ! is_admin() ) || ( $post_type !== $this->post_type ) ) {
				return;
			}

			$post_id = absint( $post_id );
			if ( ( ! empty( $post_id ) ) && ( current_user_can( 'delete_post', $post_id ) ) && ( ! isset( $this->posts_to_delete[ $post_id ] ) ) ) {
				$question_pro_id = get_post_meta( $post_id, 'question_pro_id', true );
				if ( ! empty( $question_pro_id ) ) {
					$this->posts_to_delete[ $post_id ] = absint( $question_pro_id );
				}
			}
		}

		/**
		 * Called after the post ha been deleted.
		 *
		 * Uses registered delete post ID
		 *
		 * @since 2.6.5
		 * @param integer $post_id $Post ID to be deleted.
		 */
		public function deleted_post( $post_id = 0 ) {
			global $post_type, $post_type_object;

			if ( ( ! is_admin() ) || ( $post_type !== $this->post_type ) ) {
				return;
			}

			if ( ( ! empty( $post_id ) ) && ( current_user_can( 'delete_post', $post_id ) ) && ( isset( $this->posts_to_delete[ $post_id ] ) ) ) {
				global $wpdb;

				$wpdb->delete(
					LDLMS_DB::get_table_name( 'quiz_question' ),
					array(
						'id' => $this->posts_to_delete[ $post_id ],
					),
					array( '%d' )
				);
				unset( $this->posts_to_delete[ $post_id ] );
			}
		}

		// End of functions.
	}
}
new Learndash_Admin_Questions_Listing();
