<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * LearnDash Import CPT
 *
 * This file contains functions to handle import of the LearnDash Quiz Statistics
 *
 * @package LearnDash
 * @subpackage LearnDash
 * @since 1.0.0
 */

if ( ( !class_exists( 'LearnDash_Import_Quiz_Statistics' ) ) && ( class_exists( 'LearnDash_Import_Post' ) ) ) {
	class LearnDash_Import_Quiz_Statistics extends LearnDash_Import_Post {
		private $version			= '1.0';
		
	    function __construct() {
		}
		
		function startQuizStatisticsHeader() {
			$statisticRefModel = new WpProQuiz_Model_StatisticRefModel();

			return $statisticRefModel->get_object_as_array( );
		}

		function startQuizStatisticsQuestion() {
			$pro_quiz_statistic_import = new WpProQuiz_Model_Statistic();
			
			return $pro_quiz_statistic_import->get_object_as_array();
		}
		
		// $quiz_statistic_data should be an array of arrays. Each array item represents a single user question response
		function saveQuizStatisticSet( $quiz_statistic_header = array(), $quiz_statistic_details = array() ) {
			if ( ( !empty( $quiz_statistic_header ) ) && ( !empty( $quiz_statistic_details ) ) ) {
				
				$statisticRefModel = new WpProQuiz_Model_StatisticRefModel();
				$statisticRefModel->set_array_to_object( $quiz_statistic_header );
				
				$statistic_values = array();
				foreach( $quiz_statistic_details as $quiz_statistic_details ) {
					// Called to ensure we have a working Question Set ( WpProQuiz_Model_Question )
					$pro_quiz_statistic_import = new WpProQuiz_Model_Statistic();
					$pro_quiz_statistic_import->set_array_to_object( $quiz_statistic_details );
					$statistic_values[] = $pro_quiz_statistic_import;
				}
				
				$statisticRefMapper = new WpProQuiz_Model_StatisticRefMapper();
				$statisticRefMapper_id = $statisticRefMapper->statisticSave( $statisticRefModel, $statistic_values );
				return $statisticRefMapper_id;
			}
		}

		// $file_upload_full is the full path to the existin file. 
		// $question_id is needed when building the essay filename
		function migrate_file_upload_to_essay( $file_upload_full = '', $question_id = 0 ) {
			if ( !empty( $file_upload_full ) ) {
				
				// This logic was copied from LD core includes/quiz/ld-quiz-essay.php learndash_essay_fileupload_process()
				$filename = learndash_clean_filename( basename( $file_upload_full ) );
				
				//$question_id = $ld_quiz_question['_id'];
				
				// get file info
				// @fixme: wp checks the file extension....
				$filetype = wp_check_filetype( basename( $filename ), null );
				$filetitle = preg_replace( '/\.[^.]+$/', '', basename( $filename ) );
				$filename = sprintf( 'question_%d_%s.%s', $question_id, $filetitle, $filetype['ext'] );
				$filename = apply_filters( 'learndash_essay_upload_filename', $filename, $question_id, $filetitle, $filetype['ext'] );
				$upload_dir = wp_upload_dir();
				$upload_dir_base = $upload_dir['basedir'];
				$upload_url_base = $upload_dir['baseurl'];
				$upload_dir_path = $upload_dir_base . apply_filters( 'learndash_essay_upload_dirbase', '/essays', $filename, $upload_dir );
				$upload_url_path = $upload_url_base . apply_filters( 'learndash_essay_upload_urlbase', '/essays/', $filename, $upload_dir );

				if ( ! file_exists( $upload_dir_path ) ) {
					mkdir( $upload_dir_path );
				}

				/**
				 * Check if the filename already exist in the directory and rename the
				 * file if necessary
				 */
				$i = 0;

				while ( file_exists( $upload_dir_path . '/' . $filename ) ) {
					$i++;
					$filename = sprintf( 'question_%d_%s_%d.%s', $question_id, $filetitle, $i, $filetype['ext'] );
					$filename = apply_filters( 'learndash_essay_upload_filename_dup', $filename, $question_id, $filetitle, $i, $filetype['ext'] );
				}

				$filedest = $upload_dir_path . '/' . $filename;

				$copy_ret = copy( $file_upload_full, $filedest );
				if ( $copy_ret === true ) {
					return $upload_url_path . $filename;
					
					//update_post_meta( $essay_post_id, 'upload', $upload_url_path . $filename );
					//update_post_meta( $essay_post_id, '_ld_import_org', intval( $sensei_user_question_answers ) );
				}
			}
		}
		

		/*
		[20-May-2017 16:53:07 UTC] user_quizzes<pre>Array
		(
		    [0] => Array
		        (
		            [quiz] => 927
		            [score] => 5
		            [count] => 6
		            [question_show_count] => 6
		            [pass] => 0
		            [rank] => -
		            [time] => 1495293659
		            [pro_quizid] => 1
		            [points] => 25
		            [total_points] => 55
		            [percentage] => 45.45
		            [timespent] => 29.681
		            [has_graded] => 1
		            [statistic_ref_id] => 1
		            [started] => 1495293628
		            [completed] => 1495293658
		            [graded] => Array
		                (
		                    [1] => Array
		                        (
		                            [post_id] => 960
		                            [status] => graded
		                            [points_awarded] => 12
		                        )

		                    [2] => Array
		                        (
		                            [post_id] => 961
		                            [status] => not_graded
		                            [points_awarded] => 0
		                        )

		                )

		        )

		)
		</pre>
		*/
		
		function add_user_quiz_attempt( $user_id = 0, $quiz_attempt = array() ) {
			if ( ( !empty( $user_id ) ) && ( !empty( $quiz_attempt ) ) ) {

				$user_quiz_meta = get_user_meta( $user_id, '_sfwd-quizzes', true );
				if ( ! is_array( $user_quiz_meta ) ) {
					$user_quiz_meta = array();
				}

				$user_quiz_meta[] = $quiz_attempt;

				update_user_meta( $user_id, '_sfwd-quizzes', $user_quiz_meta );

			}
		}
		

		// End of functions
	}
}