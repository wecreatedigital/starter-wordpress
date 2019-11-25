<?php
/**
 * Quiz Builder Helpers.
 *
 * Used to provide proper data to Quiz Builder app.
 *
 * @package LearnDash
 */

namespace LearnDash\Admin\QuizBuilderHelpers;

/**
 * Provide Quiz Data to Builder.
 *
 * @param Object $data The data passed down to front-end.
 * @return Object
 */
function get_quiz_data( $data ) {
	global $pagenow, $typenow;

	$output_questions = [];

	if ( ( 'post.php' === $pagenow ) && ( learndash_get_post_type_slug( 'quiz' ) === $typenow ) ) {
		$quiz_id = get_the_ID();
		if ( ! empty( $quiz_id ) ) {
			// Get quiz's questions.
			$questions_ids    = array_keys( learndash_get_quiz_questions( $quiz_id ) );

			// Loop quiz's questions.
			foreach ( $questions_ids as $question_id ) {

				// Get answers from question.
				$question_pro_id = (int) get_post_meta( $question_id, 'question_pro_id', true );
				$question_mapper = new \WpProQuiz_Model_QuestionMapper();

				if ( ! empty( $question_pro_id ) ) {
					$question_model = $question_mapper->fetch( $question_pro_id );
				} else {
					$question_model = $question_mapper->fetch( null );
				}

				$question_data       = $question_model->get_object_as_array();
				$controller_question = new \WpProQuiz_Controller_Question();

				if ( $question_model && is_a( $question_model, 'WpProQuiz_Model_Question' ) ) {
					$answers_data = $controller_question->setAnswerObject( $question_model );
				} else {
					$answers_data = $controller_question->setAnswerObject();
				}

				// Store answers in our format used at FE.
				$processed_answers = [];

				foreach ( $answers_data as $answer_type => $answers ) {
					foreach ( $answers as $answer ) {
						$processed_answers[ $answer_type ][] = [
							'answer'             => $answer->getAnswer(),
							'html'               => $answer->isHtml(),
							'points'             => $answer->getPoints(),
							'correct'            => $answer->isCorrect(),
							'sortString'         => $answer->getSortString(),
							'sortStringHtml'     => $answer->isSortStringHtml(),
							'graded'             => $answer->isGraded(),
							'gradingProgression' => $answer->getGradingProgression(),
							'gradedType'         => $answer->getGradedType(),
							'type'               => 'answer',
						];
					}
				}

				// Output question's data and answers.
				$output_questions[] = [
					'ID'              => $question_id,
					'expanded'        => false,
					'post_title'      => $question_data['_title'],
					'post_content'    => $question_data['_question'],
					'edit_link'       => get_edit_post_link( $question_id, '' ),
					'type'            => get_post_type( $question_id ),
					'question_type'   => $question_data['_answerType'],
					'points'          => $question_data['_points'],
					'answers'         => $processed_answers,
					'correctMsg'      => $question_data['_correctMsg'],
					'incorrectMsg'    => $question_data['_incorrectMsg'],
					'correctSameText' => $question_data['_correctSameText'],
					'tipEnabled'      => $question_data['_tipEnabled'],
					'tipMsg'          => $question_data['_tipMsg'],
				];
			}
		}
	}
	
	// Output all the quiz's questions.
	$data['outline'] = [
		'questions' => $output_questions,
	];

	// Add labels and data to Quiz Builder at FE.
	$data['labels']['questions_types']             = $GLOBALS['learndash_question_types'];
	$data['questions_types_map']                   = [
		'single'             => 'classic_answer',
		'multiple'           => 'classic_answer',
		'sort_answer'        => 'sort_answer',
		'matrix_sort_answer' => 'matrix_sort_answer',
		'cloze_answer'       => 'cloze_answer',
		'free_answer'        => 'free_answer',
		'assessment_answer'  => 'assessment_answer',
		'essay'              => 'essay',
	];
	$data['labels']['points']                      = [
		'singular' => esc_html__( 'point', 'learndash' ),
		'plural'   => esc_html__( 'points', 'learndash' ),
	];
	$data['labels']['questions_types_description'] = [
		'free_answer'       => esc_html_x( 'correct answers (one per line) (answers will be converted to lower case)', 'Question type description for Free Answers', 'learndash' ),
		'sort_answer'       => esc_html_x( 'Please sort the answers in the right order with the "move" button. The answers will be displayed randomly.', 'Question type description for Sort Answers', 'learndash' ),
		'cloze_answer'      => [
			wp_kses_post( __( 'Use <strong class="description-red">{ }</strong> to mark a gap and correct answer:<br /> <strong>I <span class="description-red">{</span>play<span class="description-red">}</span> soccer.</strong>', 'learndash' ) ),
			wp_kses_post( __( 'Use <strong class="description-red">[ ]</strong> to mark multiple correct answers:<br /> <strong>I {<span class="description-red">[</span>love<span class="description-red">][</span>hate<span class="description-red">]</span>} soccer.</strong>', 'learndash' ) ),
		],
		'essay'             => [
			esc_html__( 'How should the user submit their answer?', 'learndash' ),
			sprintf(
				// translators: placeholders: course
				esc_html_x( 'This is a question that can be graded and potentially prevent a user from progressing to the next step of the %s.', 'placeholders: course', 'learndash' ),
				\learndash_get_custom_label_lower( 'course' )
			),
			esc_html__( 'The user can only progress if the essay is marked as "Graded" and if the user has enough points to move on.', 'learndash' ),
			sprintf(
				// translators: placeholders: quiz
				esc_html_x( 'How should the answer to this question be marked and graded upon %s submission?', 'placeholders: quiz', 'learndash' ),
				\learndash_get_custom_label_lower( 'quiz' )
			),
		],
		'assessment_answer' => [
			wp_kses_post( __( 'Use <strong class="description-red">{ }</strong> to mark an assessment:<br /> <strong>Less true <span class="description-red">{</span> [1] [2] [3] [4] [5] <span class="description-red">}</span> More true</strong>', 'learndash' ) ),
			wp_kses_post( __( 'Use <strong class="description-red">[ ]</strong> to mark selectable items:<br /> <strong>Less true { <span class="description-red">[</span>A<span class="description-red">]</span> <span class="description-red">[</span>B<span class="description-red">]</span> <span class="description-red">[</span>C<span class="description-red">]</span> } More true</strong>', 'learndash' ) ),
		],
	];

	return $data;
}
//add_filter( 'learndash_header_data', 'LearnDash\Admin\QuizBuilderHelpers\get_quiz_data', 100 );
