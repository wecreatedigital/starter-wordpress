<?php
/**
 * Override the various message shown via the LearnDash WPProQuiz output
 *
 * Available Variables:
 * $quiz_post_id : (integer) Current Quiz Post ID being display. 
 * $context : A unique label to distunquish the message and is used below to match the message to the optional replacement message.
 * $message : This is the message to be displayed. THIS MUST BE RETURNED
 * $placeholders : Array of placeholder values used in message. If used by $message. $placeholders[0] is first placeholder value, $placeholders[1] second etc. 
 * 
 * @since 2.4
 * 
 * @package LearnDash\Course
 */

switch( $context ) {
	
	// ------------- Messages -------------
	// ------------------------------------
	
	// Default Message: "Quiz complete. Results are being recorded."
	case 'quiz_complete_message':
		// Add alternate message here
		//$message = 'Quiz complete. You Rock!';
		break;
	
	// Default Message: "<p><span class="wpProQuiz_correct_answer">0</span> of <span>0</span> questions answered correctly</p>"
	// Notes: The <span>0</span> placeholders are required and populated via JavaScript when the quiz is completed. 
	case 'quiz_questions_answered_correctly_message':
		// Add alternate message here
		break;
	
	// Default Message: "Your time: <span></span>"
	case 'quiz_your_time_message':
		// Add alternate message here
		break;

	// Default Message: "Time has elapsed"
	case 'quiz_time_has_elapsed_message':
		// Add alternate message here
		break;
		
	// Default Message: 'You have reached <span>0</span> of <span>0</span> point(s), (<span>0</span>)'	
	// Notes: The <span>0</span> placeholders are required and populated via JavaScript when the quiz is completed. 
	case 'quiz_have_reached_points_message':
		// Add alternate message here
		break;
	
	// Default Message: 'Earned Point(s): <span>0</span> of <span>0</span>, (<span>0</span>)'
	// Notes: The <span>0</span> placeholders are required and populated via JavaScript when the quiz is completed. 
	case 'quiz_earned_points_message':
		// Add alternate message here
		break;
		
	// Default Message: '<span>0</span> Essay(s) Pending (Possible Point(s): <span>0</span>)'
	// Notes: The <span>0</span> placeholders are required and populated via JavaScript when the quiz is completed. 
	case 'quiz_essay_possible_points_message':
		// Add alternate message here
		break;
	
	// Default Message: 'Average score'
	case 'quiz_average_score_message':
		// Add alternate message here
		break;
	
	// Default Message: 'Your score'
	case 'quiz_your_score_message':
		// Add alternate message here
		break;

	// Default Message: '<p>You have already completed the %s before. Hence you can not start it again.</p>'
	case 'quiz_locked_message':
		// Add alternate message here
		break;

	// Default Message: '<p>You must sign in or sign up to start the quiz.</p>'
	case 'quiz_only_registered_user_message':
		// Add alternate message here
		break;

	// Default Message: '<p>You must first complete the following: <span></span></p>'
	// Notes: The <span></span> at the end of the message is required and will be populated JavaScript with the prerequisite quizzes. 
	case 'quiz_prerequisite_message':
		// Add alternate message here
		break;

	// Default Message: '<p><span>0</span> of XXX questions completed</p>'
	// Notes: The <span>0</span> at the start of the message is required and will be populated JavaScript. The XXX
	// will be a number of the total questions from the quiz. 
	case 'quiz_checkbox_questions_complete_message':
		// Add alternate message here
		break;

	// Default Message: '<span style="font-weight: bold;">Your result has been entered into leaderboard</span>'
	case 'quiz_toplist_results_message':
		// Add alternate message here
		break;

	// Default Message: 'Not categorized'
	case 'learndash_not_categorized_message':
		// Add alternate message here
		break;
	
	// Default Message: '<span>X</span> Question'
	// Notes: This message contains 1 numbers represented by X and wrapped in span HTML
	case 'quiz_question_list_1_message':
		// Add alternate message here
		//$message = '<span>'. $placeholders[0] .'</span> Question XXX';
		break;

	// Default Message: 'Question <span>X</span> of <span>Y</span>'
	// Notes: This message contains 2 numbers represented by X and Y wrapped in span HTML
	case 'quiz_question_list_2_message':
		// Add alternate message here
		break;

	// Default Message: '<span>X</span> point(s)'
	// Notes: This message contains 1 numbers represented by X wrapped in span HTML
	case 'quiz_question_points_message':
		// Add alternate message here
		break;

	// Default Message: 'Category: <span>S</span>'
	// Notes: This message contains 1 string represented by S wrapped in span HTML
	case 'quiz_question_category_message':
		// Add alternate message here
		break;
	
	// Default Message: 'Type your response here'
	// Notes: This is shown on the Essay textarea as placeholder tesxt
	case 'quiz_essay_question_textarea_placeholder_message':
		// Add alternate message here
		break;
	
	// Default Message: '<p>Upload your answer to this question.</p>'
	// Notes: This is shown on the Essay textarea as placeholder tesxt
	case 'quiz_essay_question_upload_answer_message':
		// Add alternate message here
		break;
		
	// Default Message: 'This response will be awarded full points automatically, but it can be reviewed and adjusted after submission.'	
	case 'quiz_essay_question_graded_full_message':
		// Add alternate message here
		break;
	
	// Default Message: 'This response will be awarded full points automatically, but it will be reviewed and possibly adjusted after submission.'
	case 'quiz_essay_question_not_graded_full_message':
		// Add alternate message here
		break;
	
	// Default Message: 'This response will be reviewed and graded after submission.'	
	case 'quiz_essay_question_not_graded_none_message':	
		// Add alternate message here
		break;
	
	// Default Message: 'Grading can be reviewed and adjusted.'	
	case 'quiz_essay_question_graded_review_message':
		// Add alternate message here
		break;
	
	// Default Message: 'Uploading'	
	case 'quiz_essay_uploading':
		// Add alternate message here
		break;
	
	// Default Message: 'Success'	
	case 'quiz_essay_success':
		// Add alternate message here
		break;
		



	// Default Message: 'Correct'
	case 'quiz_question_answer_correct_message	':
		// Add alternate message here
		break;

	// Default Message: 'Incorrect'
	case 'quiz_question_answer_incorrect_message':
		// Add alternate message here
		break;
			
	// Default Message: 'Points'
	case 'quiz_question_answer_points_message':
		// Add alternate message here
		break;
	
	// Default Message: 'Answered'
	case 'quiz_quiz_answered_message':
		// Add alternate message here
		break;

	// Default Message: 'Review'
	case 'quiz_quiz_review_message':
		// Add alternate message here
		break;

	// Default Message: 'Time limit'
	case 'quiz_quiz_time_limit_message':
		// Add alternate message here
		break;

	// Default Message: 'Certificate Pending - Questions still need to be graded, please check your profile for the status.'
	case 'quiz_certificate_pending_message':
		// Add alternate message here
		break;

	// ------------- Headers -------------
	// -----------------------------------

	// Default Message: 'Quiz Summary'
	// Notes: This header is wrapped in <h4></h4>
	case 'quiz_quiz_summary_header':
		// Add alternate message here
		break;

	// Default Message: 'Sort elements'
	case 'quiz_question_sort_elements_header':
		// Add alternate message here
		break;

	// Default Message: 'Hint'
	// Notes: This header is wrapped in <h5></h5>
	case 'quiz_hint_header':
		// Add alternate message here
		break;
	
	// Default Message: 'Categories'
	// Notes: This header is wrapped in <h4></h4>
	case 'learndash_categories_header':
		// Add alternate message here
		break;
	

	// ------------- Buttons -------------
	// -----------------------------------
	
	// Default Message: 'Start Quiz'
	// Notes: This is a button label and should only contain simple text not HTML
	case 'quiz_start_button_label':
		// Add alternate message here
		break;

	// Default Message: 'Finish Quiz'
	// Notes: This is a button label and should only contain simple text not HTML
	case 'quiz_finish_button_label':
		// Add alternate message here
		break;

	// Default Message: 'Next'
	// Notes: This is a button label and should only contain simple text not HTML
	case 'quiz_next_button_label':
		// Add alternate message here
		break;
			
	// Default Message: 'Check'
	// Notes: This is a button label and should only contain simple text not HTML
	case 'quiz_check_button_label':
		// Add alternate message here
		break;
		
	// Default Message: 'Hint'
	// Notes: This is a button label and should only contain simple text not HTML
	case 'quiz_hint_button_label':
		// Add alternate message here
		break;
		
	// Default Message: 'Back'
	// Notes: This is a button label and should only contain simple text not HTML
	case 'quiz_back_button_label':
		// Add alternate message here
		break;
		
	// Default Message: 'Skip question'
	// Notes: This is a button label and should only contain simple text not HTML
	case 'quiz_skip_button_label':
		// Add alternate message here
		break;
				
	// Default Message: 'Restart Quiz'
	// Notes: This is a button label and should only contain simple text not HTML
	case 'quiz_restart_button_label':
		// Add alternate message here
		break;
		
	// Default Message: 'View questions'
	// Notes: This is a button label and should only contain simple text not HTML
	case 'quiz_view_questions_button_label':
		// Add alternate message here
		break;

	// Default Message: 'Show leaderboard'
	// Notes: This is a button label and should only contain simple text not HTML
	case 'quiz_show_leaderboard_button_label':
		// Add alternate message here
		break;
	
	// Default Message: 'Quiz Summary'
	// Notes: This is a button label and should only contain simple text not HTML
	case 'quiz_quiz_summary_button_label':
		// Add alternate message here
		break;
	
	// Default Message: 'Review question'
	// Notes: This is a button label and should only contain simple text not HTML
	case 'quiz_review_question_button_label':
		// Add alternate message here
		break;
				
	// Default Message: 'View Quiz Statistics'
	// Notes: This is a button label and should only contain simple text not HTML
	case 'quiz_view_statistics_button_label':
		// Add alternate message here
		break;

	// Default Message: 'PRINT YOUR CERTIFICATE'
	case 'quiz_certificate_button_label':
		// Add alternate message here
		break;
		
	// Not match on 'context'. 	
	default:
		break;
}

// Finally echo $message
echo $message;

