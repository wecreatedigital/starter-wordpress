<?php
/**
 * Migration functions to move from post meta to WP Pro Quiz
 * and other helper functions
 *
 * @since 2.1.0
 *
 * @package LearnDash\Quiz
 */



/**
 * Migrates the LearnDash quiz
 *
 * @since 2.1.0
 */
function learndash_quiz_migration() {
	$learndash_adv_quiz_migration_completed = ( ! empty( $_GET['learndash_adv_quiz_migration'] ) ) ? 0 : get_option( 'learndash_adv_quiz_migration_completed' );

	if ( empty( $learndash_adv_quiz_migration_completed) ) {
		learndash_create_quiz_for_all_adv_quiz();
		update_option( 'learndash_adv_quiz_migration_completed', 1 );
	}

	$learndash_quiz_migration_completed = ( ! empty( $_GET['force_learndash_quiz_migration'] ) ) ? 0 : get_option( 'learndash_quiz_migration_completed' );

	if ( $learndash_quiz_migration_completed ) {
		return;
	}

	if ( ! empty( $_GET['migrate_quiz_id'] ) ) {
		$posts = array(get_post( $_GET['migrate_quiz_id'] ));
	} else {
		$posts = get_posts(
			array(
				'post_type'      => 'sfwd-quiz',
				'post_status'    => 'any',
				'posts_per_page' => -1,
			)
		);
	}

	set_time_limit( 300 );

	global $wpdb;

	foreach ( $posts as $post ) {
		$quizdata = get_post_meta( $post->ID, '_quizdata', true );

		if ( ! empty( $_GET['force_learndash_quiz_migration'] ) && ( empty( $quizdata['workingJson'] ) || $quizdata['workingJson'] == 'false') ) {
			$quizdata = get_post_meta( $post->ID, '_quizdata_migrated', true );
		}

		if ( empty( $quizdata['workingJson'] ) || $quizdata['workingJson'] == 'false' ) {
			continue;
		}

		$simple_quiz_data = json_decode( $quizdata['workingJson'] );

		$LearnDash_Quiz_Migration = new LearnDash_Quiz_Migration();
		$xml = $LearnDash_Quiz_Migration->get_xml( $simple_quiz_data );

		if ( ! empty( $xml ) ) {
			$import = new WpProQuiz_Helper_ImportXml();
			$import->setString( $xml );
			$getImportData = $import->getImportData();
			$pro_quiz_id   = $import->saveImportSingle();
			learndash_update_setting( $post, 'quiz_pro', $pro_quiz_id );
		}

		$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->postmeta SET meta_key = '_quizdata_migrated' WHERE meta_key = '_quizdata' AND post_id = '%d' LIMIT 1", $post->ID ) );

	}

	update_option( 'learndash_quiz_migration_completed', 1 );
}

add_action( 'admin_init', 'learndash_quiz_migration' );



/**
 * Creates a quiz for each advanced quiz
 *
 * @since 2.1.0
 */
function learndash_create_quiz_for_all_adv_quiz() {
	$quizMapper = new WpProQuiz_Model_QuizMapper();
	$quizzes    = $quizMapper->fetchAll();

	foreach ( $quizzes as $key => $quiz ) {
		$quizId = $quiz->getId();
		//error_log('quizId['. $quizId .']');

		if ( empty( $quizId ) ) {
			continue;
		}

		$post_id = learndash_get_quiz_id_by_pro_quiz_id( $quizId );
		//error_log('post_id['. $post_id .']');
		//die();

		if ( empty( $post_id ) ) {
			$post_id = learndash_create_quiz_for_adv_quiz( $quizId );
		} else {
			learndash_migrate_content_from_pro_quiz_to_custom_post_type( $quiz, $post_id );
		}
	}
}



/**
 * Migrates the content from a pro quiz object to a custom post type
 *
 * @param  object $quiz  Pro Quiz to be migrated
 * @param  int $post_id
 */
function learndash_migrate_content_from_pro_quiz_to_custom_post_type( $quiz, $post_id ) {
	$quiz_desc = $quiz->getText();

	if ( ! empty( $quiz_desc ) && $quiz_desc != 'AAZZAAZZ' && ! empty( $post_id ) ) {
		$quiz_post = get_post( $post_id );
		$update_post['ID'] = $post_id;
		$update_post['post_content'] = $quiz_post->post_content . '<br>' . $quiz_desc;
		wp_update_post( $update_post );
		global $wpdb;
		$wpdb->query( $wpdb->prepare( 'UPDATE ' . LDLMS_DB::get_table_name( 'quiz_master' ) . " SET text = 'AAZZAAZZ' WHERE id = '%d'", $quiz->getId() ) );
	}
}



/**
 * Create a post type of sfwd-quiz for the input pro quiz ID
 *
 * @param  int $quizId Quiz ID
 * @return int Quiz Post ID
 */
function learndash_create_quiz_for_adv_quiz( $quizId ) {
	$quizMapper = new WpProQuiz_Model_QuizMapper();
	$quizzes    = $quizMapper->fetchAll();
	$quiz       = $quizMapper->fetch( $quizId );
	$quizId     = $quiz->getId();

	if ( empty( $quizId ) ) {
		return;
	}

	global $wpdb;
	$user_id = get_current_user_id();

	$quiz_post_id = wp_insert_post(
		array(
			'post_title' => $quiz->getName(),
			'post_type' => 'sfwd-quiz',
			'post_status' => 'publish',
			'post_author' => $user_id,
		)
	);

	if ( ! empty( $quiz_post_id ) ) {
		learndash_update_setting( $quiz_post_id, 'quiz_pro', $quiz->getId() );
	}

	return $quiz_post_id;
}



/**
 * Object that handles LearnDash Quiz Migrations
 */
class LearnDash_Quiz_Migration {

	/**
	 * Retrieves an XML representation of a simple quiz object
	 *
	 * @since 2.1.0
	 *
	 * @param  object $simple_quiz_data
	 * @return String                   XML representation of the simple quiz object
	 */
	public function get_xml( $simple_quiz_data ) {
		$title      = $simple_quiz_data->info->name;
		$maincopy   = $simple_quiz_data->info->main;
		$resultcopy = $simple_quiz_data->info->results;
		$questions  = $simple_quiz_data->questions;

		if ( empty( $title ) || empty( $questions ) || ! is_array( $questions ) ) {
			return '';
		}

		$questions_xml = '';
		$qno           = 1;

		foreach ( $questions as $question ) {
			$questionText = $question->q;
			$correct      = $question->correct;
			$incorrect    = $question->incorrect;
			$answers      = $question->a;

			if ( empty( $answers) || ! is_array( $answers ) ) {
				return '';
			}

			$answers_xml   = '';
			$correct_count = 0;

			foreach ( $answers as $answer ) {
				$answerText = $answer->option;
				$is_correct = ! empty( $answer->correct ) ? 'true' : 'false';

				if ( ! empty( $answer->correct) ) {
					$correct_count++;
				}

				$answers_xml .= $this->answer( $answerText, $is_correct );
			}

			$type = ( $correct_count > 1 ) ? 'multiple' : 'single';
			$questions_xml .= $this->question( $qno++, $questionText, $correct, $incorrect, $answers_xml, $type );
		}

		return $this->template( $title, $maincopy, $resultcopy, $questions_xml );
	}



	/**
	 * Uses the input string variables to fill an XML template.
	 *
	 * @since 2.1.0
	 *
	 * @param  string $title         XML text
	 * @param  string $maincopy      XML text
	 * @param  string $resultcopy    XML text
	 * @param  string $questions_xml XML text
	 * @return string                XML string
	 */
	public function template( $title, $maincopy, $resultcopy, $questions_xml ) {

		if ( empty( $maincopy ) ) {
			$maincopy = 'AAZZAAZZ';
		}

		return '<?xml version="1.0" encoding="UTF-8"?>
					<wpProQuiz>
						<header version="0.28" exportVersion="1" />
						<data>
							<quiz>
								<title titleHidden="false"><![CDATA[' . $title . ']]></title>
								<text><![CDATA[' . $maincopy . ']]></text>
								<resultText gradeEnabled="false"><![CDATA[' . $resultcopy . ']]></resultText>
								<btnRestartQuizHidden>false</btnRestartQuizHidden>
								<btnViewQuestionHidden>false</btnViewQuestionHidden>
								<questionRandom>false</questionRandom>
								<answerRandom>false</answerRandom>
								<timeLimit>0</timeLimit>
								<showPoints>false</showPoints>
								<statistic activated="false" ipLock="1440" />
								<quizRunOnce type="1" cookie="false" time="0">false</quizRunOnce>
								<numberedAnswer>false</numberedAnswer>
								<hideAnswerMessageBox>false</hideAnswerMessageBox>
								<disabledAnswerMark>false</disabledAnswerMark>
								<showMaxQuestion showMaxQuestionValue="1" showMaxQuestionPercent="false">false</showMaxQuestion>
								<toplist activated="false">
									<toplistDataAddPermissions>1</toplistDataAddPermissions>
									<toplistDataSort>1</toplistDataSort>
									<toplistDataAddMultiple>false</toplistDataAddMultiple>
									<toplistDataAddBlock>1</toplistDataAddBlock>
									<toplistDataShowLimit>1</toplistDataShowLimit>
									<toplistDataShowIn>0</toplistDataShowIn>
									<toplistDataCaptcha>false</toplistDataCaptcha>
									<toplistDataAddAutomatic>false</toplistDataAddAutomatic>
								</toplist>
								<showAverageResult>false</showAverageResult>
								<prerequisite>false</prerequisite>
								<showReviewQuestion>false</showReviewQuestion>
								<quizSummaryHide>false</quizSummaryHide>
								<skipQuestionDisabled>false</skipQuestionDisabled>
								<emailNotification>0</emailNotification>
								<userEmailNotification>false</userEmailNotification>
								<showCategoryScore>false</showCategoryScore>
								<hideResultCorrectQuestion>false</hideResultCorrectQuestion>
								<hideResultQuizTime>false</hideResultQuizTime>
								<hideResultPoints>false</hideResultPoints>
								<autostart>false</autostart>
								<forcingQuestionSolve>false</forcingQuestionSolve>
								<hideQuestionPositionOverview>false</hideQuestionPositionOverview>
								<hideQuestionNumbering>false</hideQuestionNumbering>
								<sortCategories>false</sortCategories>
								<showCategory>false</showCategory>
								<quizModus questionsPerPage="0">0</quizModus>
								<startOnlyRegisteredUser>false</startOnlyRegisteredUser>
								<forms activated="false" position="0" />
								<questions>
									' . $questions_xml . '
								</questions>
							</quiz>
						</data>
					</wpProQuiz>';
	}



	/**
	 * Returns the XML representation of the question based off of the input string variables
	 *
	 * @since 2.1.0
	 *
	 * @param  string $qno
	 * @param  string $questionText
	 * @param  string $correct
	 * @param  string $incorrect
	 * @param  string $answers_xml
	 * @param  string $type
	 * @return string XML string
	 */
	public function question( $qno, $questionText, $correct, $incorrect, $answers_xml, $type = 'single' ) {
		return '<question answerType="' . $type . '">
					<title><![CDATA[Question: ' . $qno . ']]></title>
					<points>1</points>
					<questionText><![CDATA[' . $questionText . ']]></questionText>
					<correctMsg><![CDATA[' . $correct . ']]></correctMsg>
					<incorrectMsg><![CDATA[' . $incorrect . ']]></incorrectMsg>
					<tipMsg enabled="false" />
					<category />
					<correctSameText>false</correctSameText>
					<showPointsInBox>false</showPointsInBox>
					<answerPointsActivated>false</answerPointsActivated>
					<answerPointsDiffModusActivated>false</answerPointsDiffModusActivated>
					<disableCorrect>false</disableCorrect>
					<answers>
						' . $answers_xml . '
					</answers>
				</question>';
	}



	/**
	 * Returns the XML representation of an answer based off of the input string variables
	 *
	 * @since 2.1.0
	 *
	 * @param  String $answerText
	 * @param  String $is_correct
	 * @return String XML string
	 */
	public function answer( $answerText, $is_correct) {
		return '<answer points="1" correct="' . $is_correct . '">
			<answerText html="false"><![CDATA[' . $answerText . ']]></answerText>
			<stortText html="false" />
		</answer>';
	}
}