<?php
class WpProQuiz_Model_QuestionMapper extends WpProQuiz_Model_Mapper {
	private $_table;

	public function __construct() {
		parent::__construct();
		
		//$this->_table = $this->_prefix."question";
		$this->_table = $this->_tableQuestion;
	}
	
	public function delete($id) {
		$this->_wpdb->delete($this->_table, array('id' => $id), '%d');
	}
	
	public function deleteByQuizId($id) {
		$this->_wpdb->delete($this->_table, array('quiz_id' => $id), '%d');
	}
	
	public function getSort($questionId) {
		return $this->_wpdb->get_var($this->_wpdb->prepare("SELECT sort FROM {$this->_tableQuestion} WHERE id = %d", $questionId));
	}
	
	public function updateSort($id, $sort) {
		$this->_wpdb->update(
			$this->_table,
			array(
					'sort' => $sort),
			array('id' => $id),
			array('%d'),
			array('%d')
		);
		
		if ( true === is_data_upgrade_quiz_questions_updated() ) {		
			$question_post_id = learndash_get_question_post_by_pro_id( $id );
			if ( ! empty( $question_post_id ) ) {
				$update_post = array(
					'ID'           => $question_post_id,
					'menu_order'   => absint( $sort ),
				);
				wp_update_post( $update_post );
				learndash_set_question_quizzes_dirty( $question_post_id );
			}
		}
	}
	
	public function setOnlineOff($questionId) {
		return $this->_wpdb->update($this->_tableQuestion, array('online' => 0), array('id' => $questionId), null, array('%d'));
	}
	
	public function getQuizId($questionId) {
		return $this->_wpdb->get_var($this->_wpdb->prepare("SELECT quiz_id FROM {$this->_tableQuestion} WHERE id = %d", $questionId));
	}
	
	public function getMaxSort($quizId) {
		return $this->_wpdb->get_var($this->_wpdb->prepare(
			"SELECT MAX(sort) AS max_sort FROM {$this->_tableQuestion} WHERE quiz_id = %d AND online = 1", $quizId));
	}
	
	public function save(WpProQuiz_Model_Question $question, $auto = false) {
		$sort = null;
		
		if($auto && $question->getId()) {
			$statisticMapper = new WpProQuiz_Model_StatisticMapper();
			
			if($statisticMapper->isStatisticByQuestionId($question->getId())) {
				$this->setOnlineOff($question->getId());
				$question->setQuizId($this->getQuizId($question->getId()));
				$question->setId(0);
				$sort = $question->getSort();
			}
		}
		
		
		/**
		 * Convert emoji to HTML entities to allow saving in DB.
		 *
		 * @since 2.6.0.
		 */
		$question_title = $question->getTitle();
		$question_title = wp_encode_emoji( $question_title );
		
		$question_question = $question->getQuestion();
		$question_question = wp_encode_emoji( $question_question );

		if($question->getId() != 0) {
			$this->_wpdb->update(
					$this->_table, 
					array(
						'quiz_id' => $question->getQuizId(),
						'title' => $question_title,
						'points' => $question->getPoints(),
						'question' => $question_question,
						'correct_msg' => $question->getCorrectMsg(),
						'incorrect_msg' => $question->getIncorrectMsg(),
						'correct_same_text' => (int)$question->isCorrectSameText(),
						'tip_enabled' => (int)$question->isTipEnabled(),
						'tip_msg' => $question->getTipMsg(),
						'answer_type' => $question->getAnswerType(),
						'show_points_in_box' => (int)$question->isShowPointsInBox(),
						'answer_points_activated' => (int)$question->isAnswerPointsActivated(),
						'answer_data' => $question->getAnswerData(true),
						'category_id' => $question->getCategoryId(),
						'answer_points_diff_modus_activated' => (int)$question->isAnswerPointsDiffModusActivated(),
						'disable_correct' => (int)$question->isDisableCorrect(),
						'matrix_sort_answer_criteria_width' => $question->getMatrixSortAnswerCriteriaWidth()
					),
					array('id' => $question->getId()),
					array('%s', '%s', '%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%d', '%d', '%s', '%d', '%d', '%d', '%d'),
					array('%d'));
		} else {
			$this->_wpdb->insert($this->_table, array(
					'quiz_id' => $question->getQuizId(),
					'online' => 1,
					'sort' => $sort !== null ? $sort : ($this->getMaxSort($question->getQuizId()) + 1),
					'title' => $question_title,
					'points' => $question->getPoints(),
					'question' => $question_question,
					'correct_msg' => $question->getCorrectMsg(),
					'incorrect_msg' => $question->getIncorrectMsg(),
					'correct_same_text' => (int)$question->isCorrectSameText(),
					'tip_enabled' => (int)$question->isTipEnabled(),
					'tip_msg' => $question->getTipMsg(),
					'answer_type' => $question->getAnswerType(),
					'show_points_in_box' => (int)$question->isShowPointsInBox(),
					'answer_points_activated' => (int)$question->isAnswerPointsActivated(),
					'answer_data' => $question->getAnswerData(true),
					'category_id' => $question->getCategoryId(),
					'answer_points_diff_modus_activated' => (int)$question->isAnswerPointsDiffModusActivated(),
					'disable_correct' => (int)$question->isDisableCorrect(),
					'matrix_sort_answer_criteria_width' => $question->getMatrixSortAnswerCriteriaWidth()
				),
				array('%d', '%d', '%d', '%s', '%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%d', '%d', '%s', '%d', '%d', '%d', '%d')
			);
			
			$question->setId($this->_wpdb->insert_id);
		}
		
		if ( ( true === is_data_upgrade_quiz_questions_updated() ) && ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Quizzes_Builder', 'enabled' ) !== 'yes' ) ) {
			$question_post_id = learndash_get_question_post_by_pro_id( $question->getId() );
			if ( empty( $question_post_id ) ) {
				// We load fresh from DB. Don't use the $question object as it is not up to date.
				$question_pro = $this->fetchById( $question->getId() );
				if ( ( $question_pro ) && ( is_a( $question_pro, 'WpProQuiz_Model_Question' ) ) ) {
					$question_insert_post = array();
					$question_insert_post['post_type']    = learndash_get_post_type_slug( 'question' );
					$question_insert_post['post_status']  = 'publish';
					$question_insert_post['post_title']   = $question_pro->getTitle();
					$question_insert_post['post_content'] = $question_pro->getQuestion();
					$question_insert_post['menu_order']   = absint( $question_pro->getSort() );

					$question_insert_post = wp_slash( $question_insert_post );
					$question_insert_post_id = wp_insert_post( $question_insert_post );
					if ( false !== $question_insert_post_id ) {
						$quiz_pro_id = $question_pro->getQuizId();
						$quiz_pro_id = absint( $quiz_pro_id );
						$quiz_post_id = learndash_get_quiz_id_by_pro_quiz_id( $quiz_pro_id );
						learndash_update_setting( $question_insert_post_id, 'quiz', $quiz_post_id );
						learndash_proquiz_sync_question_fields( $question_insert_post_id, $question_pro );
						learndash_set_question_quizzes_dirty( $question_insert_post_id );
					}
				}
			}
		}

		return $question;
	}
	
	public function fetch($id) {
		
		$row = $this->_wpdb->get_row(
			$this->_wpdb->prepare(
				"SELECT
					*
				FROM
					". $this->_table. "
				WHERE
					id = %d AND online = 1",
				$id),
			ARRAY_A
		);
		
		$model = new WpProQuiz_Model_Question($row);
	
		return $model;
	}
	
	public function fetchById($id, $online = 1 ) {
		
		$ids = array_map('intval', (array)$id);
		$a = array();
		
		if(empty($ids))
			return null;
		
		$sql_str = 	"SELECT * FROM ". $this->_table. " WHERE id IN(". implode(', ', $ids) .") ";
		
		if ( ( $online === 1 ) || ( $online === 1 ) ) {
			$sql_str .= " AND online = ". $online;
		}
		
		$results = $this->_wpdb->get_results(
				$sql_str,
				ARRAY_A
		);
		
		foreach ($results as $row) {
			$a[] = new WpProQuiz_Model_Question($row);
			
		}
		
		return is_array($id) ? $a : (isset($a[0]) ? $a[0] : null);
	}
	
	public function fetchAll( $quizId = 0, $rand = false, $max = 0 ) {
		$quiz_post_id = 0;
		if ( is_a( $quizId, 'WpProQuiz_Model_Quiz' ) ) {
			$quiz = $quizId;
			$quizId = $quiz->getId();
			if ( empty( $quiz_post_id ) ) {
				$quiz_post_id = $quiz->getPostId();
			}
		} else {
			$quiz_post_id = learndash_get_question_post_by_pro_id( $quizId );
			if ( empty( $quiz_post_id ) ) {
				if ( ( isset( $_GET['post'] ) ) && ( ! empty( $_GET['post'] ) ) ) {
					$quiz_post_id = learndash_get_quiz_id( absint( $_GET['post'] ) );
				}
			}
		}

		if ( ( ! empty( $quiz_post_id ) ) && ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Quizzes_Builder', 'enabled' ) === 'yes' ) && ( true === is_data_upgrade_quiz_questions_updated() ) ) {
			$ld_quiz_questions_object = LDLMS_Factory_Post::quiz_questions( intval( $quiz_post_id ) );
			if ( $ld_quiz_questions_object ) {
				$pro_questions = $ld_quiz_questions_object->get_questions( 'pro_objects' );
				$pro_questions = apply_filters( 'learndash_fetch_quiz_questions', $pro_questions, $quizId, $rand, $max );
				if ( ! empty( $pro_questions ) ) {
					if ( $rand ) {
						//$pro_questions = array_rand( $pro_questions, intval( $max ) );
						shuffle( $pro_questions );

						$max = absint( $max );
						if ( $max > 0 ) {
							$pro_questions = array_slice( $pro_questions, 0, $max, true );
						}
					}
				}

				if ( ! empty( $pro_questions ) ) {
					$category_mapper = new WpProQuiz_Model_CategoryMapper();

					foreach( $pro_questions as $pro_question ) {
						$q_catId = $pro_question->getCategoryId();
						$q_catId = absint( $q_catId );
						if ( ! empty( $q_catId ) ) {
							$q_cat = $category_mapper->fetchById( $q_catId );
							if ( ( $q_cat ) && ( is_a( $q_cat, 'WpProQuiz_Model_Category' ) ) ) {
								$_catName = $q_cat->getCategoryName();
								if ( ! empty( $_catName ) ) {
									$pro_question->setCategoryName( $_catName );
								}
							}
						}
					}
				}
				return $pro_questions;
			}
		} else {
			if ( $rand ) {
				$orderBy = 'ORDER BY RAND()';
			} else {
				$orderBy = 'ORDER BY sort ASC';
			}

			$limit = '';
			
			if($max > 0) {
				$limit = 'LIMIT 0, '.((int)$max);
			}
			
			$a = array();
			$results = $this->_wpdb->get_results(
					$this->_wpdb->prepare(
								'SELECT 
									q.*,
									c.category_name 
								FROM 
									'. $this->_table.' AS q
									LEFT JOIN '.$this->_tableCategory.' AS c
										ON c.category_id = q.category_id
								WHERE
									quiz_id = %d AND q.online = 1
								'.$orderBy.' 
								'.$limit
							, $quizId),
					ARRAY_A);
			
			foreach($results as $row) {
				$model = new WpProQuiz_Model_Question($row);
				
				$a[] = $model;
			}
		}

		return $a;
	}
	
	public function fetchAllList($quizId, $list) {
		$quiz_post_id = 0;
		if ( is_a( $quizId, 'WpProQuiz_Model_Quiz' ) ) {
			$quiz = $quizId;
			$quizId = $quiz->getId();
			$quiz_post_id = $quiz->getPostId();
		}

		if ( ( ! empty( $quiz_post_id ) ) && ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Quizzes_Builder', 'enabled' ) === 'yes' ) && ( true === is_data_upgrade_quiz_questions_updated() ) ) {
			$ld_quiz_questions_object = LDLMS_Factory_Post::quiz_questions( intval( $quiz_post_id ) );
			if ( $ld_quiz_questions_object ) {
				$questions = $ld_quiz_questions_object->get_questions();
				if ( ! empty( $questions ) ) {
					$sql_str = "SELECT " . implode(', ', (array) $list ) . " FROM " . $this->_tableQuestion . " WHERE id IN (". implode(',', $questions) . ") AND online = 1";
					$results = $this->_wpdb->get_results( $sql_str, ARRAY_A );
					return $results;
				}
			}
		} else {
			$results = $this->_wpdb->get_results(
					$this->_wpdb->prepare(
							'SELECT
									'.implode(', ', (array)$list).'
								FROM
									'. $this->_tableQuestion.'
								WHERE
									quiz_id = %d AND online = 1'
							, $quizId),
					ARRAY_A);
			
			return $results;
		}
	}
	
	public function count($quizId) {
		return $this->_wpdb->get_var($this->_wpdb->prepare("SELECT COUNT(*) FROM {$this->_table} WHERE quiz_id = %d AND online = 1", $quizId));
	}
	
	public function exists($id) {
		return $this->_wpdb->get_var($this->_wpdb->prepare("SELECT COUNT(*) FROM {$this->_table} WHERE id = %d AND online = 1", $id));
	}
	
	public function existsAndWritable($id) {
		return $this->_wpdb->get_var($this->_wpdb->prepare("SELECT COUNT(*) FROM {$this->_table} WHERE id = %d AND online = 1", $id));
	}
	
	public function fetchCategoryPoints($quizId) {
		$results = $this->_wpdb->get_results(
				$this->_wpdb->prepare(
						'SELECT SUM( points ) AS sum_points , category_id
						FROM '.$this->_tableQuestion.'
						WHERE quiz_id = %d AND online = 1
						GROUP BY category_id', $quizId));
		
		$a = array();
		
		foreach($results as $result) {
			$a[$result['category_id']] = $result['sum_points'];
		}
		
		return $a;
	}
}