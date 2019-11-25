<?php
class WpProQuiz_Controller_Preview extends WpProQuiz_Controller_Controller {
	
	public function route() {
		global $learndash_assets_loaded;
		
		wp_enqueue_script(
			'wpProQuiz_front_javascript', 
			plugins_url('js/wpProQuiz_front'. leardash_min_asset() .'.js', WPPROQUIZ_FILE),
			array('jquery', 'jquery-ui-sortable'),
			LEARNDASH_SCRIPT_VERSION_TOKEN
		);
		$learndash_assets_loaded['scripts']['wpProQuiz_front_javascript'] = __FUNCTION__;
		
		wp_localize_script('wpProQuiz_front_javascript', 'WpProQuizGlobal', array(
			'ajaxurl' => str_replace(array("http:", "https:"), array("",""), admin_url('admin-ajax.php')),
			'loadData' => esc_html__('Loading', 'learndash'),
			'questionNotSolved' => esc_html__('You must answer this question.', 'learndash'),
			'questionsNotSolved' => sprintf( esc_html_x('You must answer all questions before you can complete the %s.', 'You must answer all questions before you can complete the quiz.', 'learndash'), learndash_get_custom_label_lower( 'quiz' ) ),
			'fieldsNotFilled' => esc_html__('All fields have to be filled.', 'learndash')
		));
		
		//wp_enqueue_style(
		//	'wpProQuiz_front_style', 
		//	plugins_url('css/wpProQuiz_front'. leardash_min_asset() .'.css', WPPROQUIZ_FILE),
		//	array(),
		//	LEARNDASH_SCRIPT_VERSION_TOKEN
		//);
		//wp_style_add_data( 'wpProQuiz_front_style', 'rtl', 'replace' );
		//$learndash_assets_loaded['styles']['wpProQuiz_front_style'] = __FUNCTION__;
		
		$filepath = SFWD_LMS::get_template( 'learndash_quiz_front.css', null, null, true );
		if ( !empty( $filepath ) ) {
			wp_enqueue_style( 'learndash_quiz_front_css', learndash_template_url_from_path( $filepath ), array(), LEARNDASH_SCRIPT_VERSION_TOKEN );
			wp_style_add_data( 'learndash_quiz_front_css', 'rtl', 'replace' );
			$learndash_assets_loaded['styles']['learndash_quiz_front_css'] = __FUNCTION__;
		} 
		
		$this->showAction($_GET['id']);
	}
	
	public function showAction($id) {
		$view = new WpProQuiz_View_FrontQuiz();
		
		$quizMapper = new WpProQuiz_Model_QuizMapper();
		$questionMapper = new WpProQuiz_Model_QuestionMapper();
		$categoryMapper = new WpProQuiz_Model_CategoryMapper();
		$formMapper = new WpProQuiz_Model_FormMapper();
		
		$quiz = $quizMapper->fetch($id);
		
		if($quiz->isShowMaxQuestion() && $quiz->getShowMaxQuestionValue() > 0) {
				
			$value = $quiz->getShowMaxQuestionValue();
				
			if($quiz->isShowMaxQuestionPercent()) {
				$count = $questionMapper->count($id);
		
				$value = ceil($count * $value / 100);
			}
				
			$question = $questionMapper->fetchAll($quiz, true, $value);
				
		} else {
			$question = $questionMapper->fetchAll($id);
		}
		
		$view->quiz = $quiz;
		$view->question = $question;
		$view->category = $categoryMapper->fetchByQuiz( $quiz );
		$view->forms = $formMapper->fetch($quiz->getId());
		
		$view->show(true);
	}
}