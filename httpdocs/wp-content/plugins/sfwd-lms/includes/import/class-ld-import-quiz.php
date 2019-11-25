<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * LearnDash Import CPT
 *
 * This file contains functions to handle import of the LearnDash CPT Topic
 *
 * @package LearnDash
 * @subpackage LearnDash
 * @since 1.0.0
 */

if ( ( !class_exists( 'LearnDash_Import_Quiz' ) ) && ( class_exists( 'LearnDash_Import_Post' ) ) ) {
	class LearnDash_Import_Quiz extends LearnDash_Import_Post {
		private $version			= '1.0';
		
		protected $dest_post_type 	= 'sfwd-quiz';
		protected $source_post_type = 'sfwd-quiz';

	    function __construct() {
		}
		
		function duplicate_post( $source_post_id = 0, $force_copy = false ) {
			$new_post = parent::duplicate_post( $source_post_id, $force_copy );
			
			return $new_post;
		}

		function duplicate_post_tax_term( $source_term, $create_parents = false ) {
			$new_term = parent::duplicate_post( $source_term, $create_parents );
			
			return $new_term;
		}

		function fetchAllQuizQuestions( $ld_quiz_id = 0 ) {
			if ( !empty( $ld_quiz_id ) ) {
				$ld_pro_quiz_id = get_post_meta( $ld_quiz_id, 'quiz_pro_id', true );
				if ( !empty( $ld_pro_quiz_id ) ) {
					$questionMapper = new WpProQuiz_Model_QuestionMapper();
					$ld_quiz_questions = $questionMapper->fetchAll( $ld_pro_quiz_id );
					if ( !empty( $ld_quiz_questions ) ) {
						foreach( $ld_quiz_questions as $q_idx => $ld_quiz_question ) {
							$ld_quiz_questions[$q_idx] = $ld_quiz_question->get_object_as_array();
							
							if ( ( isset( $ld_quiz_questions[$q_idx]['_answerData'] ) ) && ( !empty( $ld_quiz_questions[$q_idx]['_answerData'] ) ) ) {
								foreach( $ld_quiz_questions[$q_idx]['_answerData'] as $a_idx => $answer_item ) {
									$ld_quiz_questions[$q_idx]['_answerData'][$a_idx] = $answer_item->get_object_as_array();
								}
							}
						}
						
						return $ld_quiz_questions;
					}
				}
			}
		}
		

		function startQuizSet() {
			$pro_quiz_import = new WpProQuiz_Model_Quiz();
			
			return $pro_quiz_import->get_object_as_array();
		}

		function saveQuizSet( $quiz_data = array() ) {
			if ( !empty( $quiz_data ) ) {
				
				$quiz_import = new WpProQuiz_Model_Quiz();
				$quiz_import->set_array_to_object( $quiz_data );
				
				$quizMapper = new WpProQuiz_Model_QuizMapper();
				$quizMapper->save( $quiz_import );
				
				$quiz_id = $quiz_import->getId();
				
				return $quiz_id;
			}
		}

		// End of functions
	}
}