<?php
class WpProQuiz_Controller_Quiz extends WpProQuiz_Controller_Controller {
	private $view;
		
	public function route($get = null, $post = null) {
		if(empty($get))
			$get = $_GET;
		$action = isset($get['action']) ? $get['action'] : 'show';
		
		switch ($action) {
			case 'show':
				$this->showAction();
				break;
			case 'addEdit':
				$this->addEditQuiz($get, $post);
				break;
			case 'getEdit':
				return $this->getEditQuiz($get, $post);
				break;	
			case 'addUpdateQuiz':
				return $this->addUpdateQuiz($get, $post);
				break;	


// 			case 'add':
// 				$this->createAction();
// 				break;
// 			case 'edit':
// 				if(isset($_GET['id']))
// 					$this->editAction($get['id']);
// 				break;
			case 'delete':
				if( isset( $get['id'] ) )
					$this->deleteAction( intval( $get['id'] ) );
				
				break;
			case 'reset_lock':
				if( isset($get['id'] ) )
					$this->resetLock( intval( $get['id'] ) );
				
				break;
		}
	}
	
	private function addEditQuiz($get = null, $post = null) {	

		if(empty($get))
			$get = $_GET;
		if(!empty($post))
			$this->_post = $post;
		
		$quizId = isset($get['quizId']) ? (int)$get['quizId'] : 0;
		
		if($quizId) {
			if(!current_user_can('wpProQuiz_edit_quiz')) {
				wp_die(__('You do not have sufficient permissions to access this page.'));
			}
		} else {
			if(!current_user_can('wpProQuiz_add_quiz')) {
				wp_die(__('You do not have sufficient permissions to access this page.'));
			}
		}
		
		$prerequisiteMapper	= new WpProQuiz_Model_PrerequisiteMapper();
		$quizMapper 		= new WpProQuiz_Model_QuizMapper();
		$formMapper 		= new WpProQuiz_Model_FormMapper();
		$templateMapper 	= new WpProQuiz_Model_TemplateMapper();
		
		$quiz = new WpProQuiz_Model_Quiz();
		$forms = null;
		$prerequisiteQuizList = array();
		
		if(!empty($get["post_id"])) {
			$quiz_post = get_post($get["post_id"]);
			if ( ( !empty( $quiz_post ) ) && ( is_a( $quiz_post, 'WP_Post' ) ) ) {
				$this->_post["name"] = $quiz_post->post_title;
			}
		}

		if($quizId && $quizMapper->exists($quizId) == 0) {
			WpProQuiz_View_View::admin_notices( sprintf( esc_html_x('%s not found', 'Quiz not found', 'learndash'), LearnDash_Custom_Label::get_label( 'quiz' ) ), 'error');
			
			return;
		}
		if(isset($this->_post['template']) || (isset($this->_post['templateLoad']) && isset($this->_post['templateLoadId']))) {
			if(isset($this->_post['template']))
				$template = $this->saveTemplate();
			else
				$template = $templateMapper->fetchById($this->_post['templateLoadId']);
		
			$data = $template->getData();
			
			if($data !== null) {
				$quiz = $data['quiz'];
				$quiz->setId($quizId);
				$quiz->setName($this->_post["name"]);
				$quiz->setText("AAZZAAZZ");
				$quizMapper->save($quiz);
				if(empty($quizId) && !empty($get["post_id"])) {
					learndash_update_setting($get["post_id"], "quiz_pro", $quiz->getId());
				}
				$quizId = $quiz->getId();
				
				$forms = $data['forms'];
				$prerequisiteQuizList = $data['prerequisiteQuizList'];
			}
		} else if(isset($this->_post['form'])) {
			if(isset($this->_post['resultGradeEnabled'])) {
				$this->_post['result_text'] = $this->filterResultTextGrade($this->_post);
			}
			
			// Patch to only set Statistics on if post from form save. 
			// LEARNDASH-1434 & LEARNDASH-1481
			if ( !isset( $this->_post['statisticsOn'] ) ) {
				$this->_post['statisticsOn'] = '0';
				$this->_post['viewProfileStatistics'] = '0';
			}
				
			$quiz = new WpProQuiz_Model_Quiz($this->_post);
			$quiz->setId($quizId);
				
			if($this->checkValidit($this->_post)) {
				//if($quizId)
				//	WpProQuiz_View_View::admin_notices( sprintf( esc_html_x('%s edited', 'Quiz edited', 'learndash'), LearnDash_Custom_Label::get_label( 'quiz' )), 'info');
				//else
				//	WpProQuiz_View_View::admin_notices( sprintf( esc_html_x('%s created', 'Quiz created', 'learndash'), LearnDash_Custom_Label::get_label( 'quiz' )), 'info');
		
				$quiz->setText("AAZZAAZZ");
								
				$quizMapper->save($quiz);
				if ( empty( $quizId ) && !empty( $get["post_id"] ) ) {
					learndash_update_setting($get["post_id"], "quiz_pro", $quiz->getId());
				}
				
				//if ( ( isset( $get["post_id"] ) ) && ( !empty( $get["post_id"] ) ) ) {
				//	if ( isset( $this->_post['viewProfileStatistics'] ) ) {
				//		$quiz->setViewProfileStatistics( true );
				//		update_post_meta( $get["post_id"], '_viewProfileStatistics', 1 );
				//	} else {
				//		$quiz->setViewProfileStatistics( false );
				//		update_post_meta( $get["post_id"], '_viewProfileStatistics', 0 );
				//	}
				//}
				
				$quizId = $quiz->getId();

				$prerequisiteMapper->delete($quizId);
					
				if($quiz->isPrerequisite() && !empty($this->_post['prerequisiteList'])) {
					$prerequisiteMapper->save($quizId, $this->_post['prerequisiteList']);
					$quizMapper->activateStatitic($this->_post['prerequisiteList'], 1440);
				}
		
				if(!$this->formHandler($quiz->getId(), $this->_post)) {
					$quiz->setFormActivated(false);
					$quiz->setText("AAZZAAZZ");
					$quizMapper->save($quiz);
				}
				
				$forms = $formMapper->fetch($quizId);
				$prerequisiteQuizList = $prerequisiteMapper->fetchQuizIds($quizId);
				
			} else {
				//WpProQuiz_View_View::admin_notices( sprintf( esc_html_x('%s title or %s description are not filled', 'Quiz title or quiz description are not filled', 'learndash'), LearnDash_Custom_Label::get_label( 'quiz' ), learndash_get_custom_label_lower( 'quiz' )));
			}
		} else if($quizId) {
			$quiz = $quizMapper->fetch($quizId);
			$forms = $formMapper->fetch($quizId);
			$prerequisiteQuizList = $prerequisiteMapper->fetchQuizIds($quizId);
		}
		
		$this->view = new WpProQuiz_View_QuizEdit();
		
		$this->view->quiz 					= $quiz;
		$this->view->forms 					= $forms;
		$this->view->prerequisiteQuizList 	= $prerequisiteQuizList;
		$this->view->templates 				= $templateMapper->fetchAll(WpProQuiz_Model_Template::TEMPLATE_TYPE_QUIZ, false);
		$this->view->quizList 				= $quizMapper->fetchAllAsArray(array('id', 'name'), $quizId ? array($quizId) : array());
		$this->view->captchaIsInstalled 	= class_exists('ReallySimpleCaptcha');
		
		$this->view->header = $quizId ? sprintf( esc_html_x('Edit %s', 'Edit quiz', 'learndash'), learndash_get_custom_label_lower( 'quiz' ) ) : sprintf( esc_html_x('Create %s', 'Create quiz', 'learndash'), learndash_get_custom_label_lower( 'quiz' ));

		$this->view->show($get);
	}
	
	private function addUpdateQuiz( $get = null, $post = null ) {
		if( empty( $get ) )
			$get = $_GET;
		if( ! empty( $post ) )
			$this->_post = $post;
		
		$quizId = isset( $get['quizId'] ) ? (int)$get['quizId'] : 0;
		
		if( $quizId ) {
			if(!current_user_can('wpProQuiz_edit_quiz')) {
				wp_die(__('You do not have sufficient permissions to access this page.'));
			}
		} else {
			if(!current_user_can('wpProQuiz_add_quiz')) {
				wp_die(__('You do not have sufficient permissions to access this page.'));
			}
		}
		
		$prerequisiteMapper	= new WpProQuiz_Model_PrerequisiteMapper();
		$quizMapper 		= new WpProQuiz_Model_QuizMapper();
		$formMapper 		= new WpProQuiz_Model_FormMapper();
		$templateMapper 	= new WpProQuiz_Model_TemplateMapper();
		
		$quiz = new WpProQuiz_Model_Quiz();
		$forms = null;
		$prerequisiteQuizList = array();
		
		if(!empty($get["post_id"])) {
			$quiz_post = get_post($get["post_id"]);
			if ( ( !empty( $quiz_post ) ) && ( is_a( $quiz_post, 'WP_Post' ) ) ) {
				$this->_post["name"] = $quiz_post->post_title;
			}
		}

		if( $quizId && $quizMapper->exists( $quizId ) == 0 ) {
			WpProQuiz_View_View::admin_notices( sprintf( esc_html_x('%s not found', 'Quiz not found', 'learndash'), LearnDash_Custom_Label::get_label( 'quiz' ) ), 'error');

			return;
		}

		if ( ( ( isset( $this->_post['templateSaveList'] ) ) && ( intval( $this->_post['templateSaveList'] ) > 0 ) ) || ( ( isset( $this->_post['templateName'] ) ) && ( ! empty( $this->_post['templateName'] ) ) ) ) {
			$template = $this->saveTemplate();
		}

		//if ( ( isset( $this->_post['templateName'] ) ) && ( ! empty( $this->_post['templateName'] ) ) ) {
		//	$template = $this->saveTemplate();
		//}

		if( isset( $this->_post['form'] ) ) {
			if( isset( $this->_post['resultGradeEnabled'] ) ) {
				$this->_post['result_text'] = $this->filterResultTextGrade( $this->_post );
			}
			
			// Patch to only set Statistics on if post from form save. 
			// LEARNDASH-1434 & LEARNDASH-1481
			if ( !isset( $this->_post['statisticsOn'] ) ) {
				$this->_post['statisticsOn'] = '0';
				$this->_post['viewProfileStatistics'] = '0';
			}
				
			$quiz = new WpProQuiz_Model_Quiz( $this->_post );
			$quiz->setId( $quizId );
			if ( ! empty( $get['post_id'] ) ) {
				$quiz_post = get_post( $get['post_id'] );
				if ( ( ! empty( $quiz_post ) ) && ( is_a( $quiz_post, 'WP_Post' ) ) ) {
					$quiz->setPostId = $quiz_post->ID;
				}
			}
				
			if( $this->checkValidit( $this->_post ) ) {
				//if( $quizId )
				//	WpProQuiz_View_View::admin_notices( sprintf( esc_html_x('%s edited', 'Quiz edited', 'learndash' ), LearnDash_Custom_Label::get_label( 'quiz' ) ), 'info');
				//else
				//	WpProQuiz_View_View::admin_notices( sprintf( esc_html_x('%s created', 'Quiz created', 'learndash'), LearnDash_Custom_Label::get_label( 'quiz' )), 'info');
		
				$quiz->setText("AAZZAAZZ");
								
				$quiz2 = $quizMapper->save($quiz);

				if ( ( empty( $quizId ) ) && ( isset( $get["post_id"] ) ) && ( !empty( $get["post_id"] ) ) ) {
					learndash_update_setting($get["post_id"], "quiz_pro", $quiz->getId());

					$quiz_id_primary_new = absint( learndash_get_quiz_primary_shared( $quiz->getId(), false ) );
					if ( empty( $quiz_id_primary_new ) ) {
						update_post_meta( $get["post_id"], 'quiz_pro_primary_'. $quiz->getId(), $quiz->getId() );
					}
				}
				
				//if ( ( isset( $get["post_id"] ) ) && ( !empty( $get["post_id"] ) ) ) {
				//	if ( isset( $this->_post['viewProfileStatistics'] ) ) {
				//		$quiz->setViewProfileStatistics( true );
				//		update_post_meta( $get["post_id"], '_viewProfileStatistics', 1 );
				//	} else {
				//		$quiz->setViewProfileStatistics( false );
				//		update_post_meta( $get["post_id"], '_viewProfileStatistics', 0 );
				//	}
				//}
				
				$quizId = $quiz->getId();

				$prerequisiteMapper->delete($quizId);
					
				if($quiz->isPrerequisite() && !empty($this->_post['prerequisiteList'])) {
					$prerequisiteMapper->save($quizId, $this->_post['prerequisiteList']);
					$quizMapper->activateStatitic($this->_post['prerequisiteList'], 1440);
				}
		
				if(!$this->formHandler( $quiz->getId(), $this->_post ) ) {
					$quiz->setFormActivated( false );
					$quiz->setText(" AAZZAAZZ" );
					$quizMapper->save( $quiz );
				}
				
			} else {
				//WpProQuiz_View_View::admin_notices( sprintf( esc_html_x('%s title or %s description are not filled', 'Quiz title or quiz description are not filled', 'learndash'), LearnDash_Custom_Label::get_label( 'quiz' ), learndash_get_custom_label_lower( 'quiz' )));
				return false;
			}
		} 
	}

	private function getEditQuiz($get = null, $post = null) {	

		if(empty($get))
			$get = $_GET;
		if(!empty($post))
			$this->_post = $post;
		
		$quizId = isset($get['quizId']) ? (int)$get['quizId'] : 0;
		
		//if($quizId) {
		//	if(!current_user_can('wpProQuiz_edit_quiz')) {
		//		wp_die(__('You do not have sufficient permissions to access this page.'));
		//	}
		//} else {
		//	if(!current_user_can('wpProQuiz_add_quiz')) {
		//		wp_die(__('You do not have sufficient permissions to access this page.'));
		//	}
		//}
		
		$prerequisiteMapper	= new WpProQuiz_Model_PrerequisiteMapper();
		$quizMapper 		= new WpProQuiz_Model_QuizMapper();
		$formMapper 		= new WpProQuiz_Model_FormMapper();
		$templateMapper 	= new WpProQuiz_Model_TemplateMapper();
		
		$quiz = new WpProQuiz_Model_Quiz();
		$forms = null;
		$prerequisiteQuizList = array();
		
		if(!empty($get["post_id"])) {
			$quiz_post = get_post($get["post_id"]);
			if ( ( !empty( $quiz_post ) ) && ( is_a( $quiz_post, 'WP_Post' ) ) ) {
				$this->_post["name"] = $quiz_post->post_title;
			}
		}

		if($quizId && $quizMapper->exists($quizId) == 0) {
			WpProQuiz_View_View::admin_notices( esc_html__( 'Missing ProQuiz Associated Settings.', 'learndash' ), 'error' );
			
			$this->view = new WpProQuiz_View_QuizEdit();
			$this->view->quiz 					= $quiz;
			$this->view->forms 					= $forms;
			$this->view->prerequisiteQuizList 	= $prerequisiteQuizList;
			$this->view->templates 				= $templateMapper->fetchAll(WpProQuiz_Model_Template::TEMPLATE_TYPE_QUIZ, false);
			$this->view->quizList 				= $quizMapper->fetchAllAsArray(array('id', 'name'), $quizId ? array($quizId) : array());
			$this->view->captchaIsInstalled 	= class_exists('ReallySimpleCaptcha');
			return $this->view;
		}

		if ( ( isset( $get['templateLoad'] ) ) && ( ! empty( $get['templateLoad'] ) ) && ( isset( $get['templateLoadId'] ) ) && ( ! empty( $get['templateLoadId'] ) ) ) {
			$template = $templateMapper->fetchById( (int) $get['templateLoadId'] );
			if ( ( $template ) && is_a( $template, 'WpProQuiz_Model_Template' ) ) {
				$data = $template->getData();
			
				if ( $data !== null ) {
					$quiz = $data['quiz'];
					$quiz->setId( $quizId );
					$quiz->setName($this->_post["name"]);
					$quiz->setText("AAZZAAZZ");
					//$quizMapper->save( $quiz );
					//if ( empty( $quizId ) && ! empty( $get["post_id"] ) ) {
					//	learndash_update_setting($get["post_id"], "quiz_pro", $quiz->getId());
					//}
					//$quizId = $quiz->getId();
					
					$forms = $data['forms'];
					$prerequisiteQuizList = $data['prerequisiteQuizList'];
				}
			}
		} else {
			$quiz = $quizMapper->fetch($quizId);
			$forms = $formMapper->fetch($quizId);
			$prerequisiteQuizList = $prerequisiteMapper->fetchQuizIds($quizId);
		}	
		$this->view = new WpProQuiz_View_QuizEdit();
		
		$this->view->quiz 					= $quiz;
		$this->view->forms 					= $forms;
		$this->view->prerequisiteQuizList 	= $prerequisiteQuizList;
		$this->view->templates 				= $templateMapper->fetchAll(WpProQuiz_Model_Template::TEMPLATE_TYPE_QUIZ, false);
		$this->view->quizList 				= $quizMapper->fetchAllAsArray(array('id', 'name'), $quizId ? array($quizId) : array());
		$this->view->captchaIsInstalled 	= class_exists('ReallySimpleCaptcha');
		
		$this->view->header = $quizId ? sprintf( esc_html_x('Edit %s', 'Edit quiz', 'learndash'), learndash_get_custom_label_lower( 'quiz' ) ) : sprintf( esc_html_x('Create %s', 'Create quiz', 'learndash'), learndash_get_custom_label_lower( 'quiz' ));

		return $this->view;
	}

	public function checkLock() {
		
		
		if($userId > 0) {
			$quizIds = $prerequisiteMapper->getNoPrerequisite($quizId, $userId);
		} else {
			$checkIds = $prerequisiteMapper->fetchQuizIds($quizId);
			
			if(isset($this->_post['wpProQuiz_result'])) {
				$r = json_encode($this->_post['wpProQuiz_result'], true);
				
				if($r !== null && is_array($r)) {
					foreach($checkIds as $id) {
						if(!isset($r[$id]) || !$r[$id]) {
							$quizIds[] = $id;
						}
					}					
				}
			} else {
				$quizIds = $checkIds;
			}
		}
		
		$names = $quizMapper->fetchCol($quizIds, 'name');
		
	}
	
	public function isLockQuiz($quizId) {
		$quizId = (int)$this->_post['quizId'];
		$userId = get_current_user_id();
		$data = array();
		
		if ( learndash_is_admin_user( $userId ) ) {
			$bypass_course_limits_admin_users = LearnDash_Settings_Section::get_section_setting('LearnDash_Settings_Section_General_Admin_User', 'bypass_course_limits_admin_users' );
			if ( $bypass_course_limits_admin_users == 'yes' ) {
				return $data;
			}
		}

		$lockMapper = new WpProQuiz_Model_LockMapper();
		$quizMapper = new WpProQuiz_Model_QuizMapper();
		$prerequisiteMapper = new WpProQuiz_Model_PrerequisiteMapper();
	
		$quiz = $quizMapper->fetch($this->_post['quizId']);
	
		if($quiz === null || $quiz->getId() <= 0) {
			return null;
		}
	
		if($this->isPreLockQuiz($quiz)) {
			$lockIp = $lockMapper->isLock($this->_post['quizId'], $this->getIp(), $userId, WpProQuiz_Model_Lock::TYPE_QUIZ);
			$lockCookie = false;
			$cookieTime = $quiz->getQuizRunOnceTime();
				
			if(isset($this->_cookie['wpProQuiz_lock']) && $userId == 0 && $quiz->isQuizRunOnceCookie()) {
				$cookieJson = json_decode($this->_cookie['wpProQuiz_lock'], true);
					
				if($cookieJson !== false) {
					if(isset($cookieJson[$this->_post['quizId']]) && $cookieJson[$this->_post['quizId']] == $cookieTime) {
						$lockCookie = true;
					}
				}
			}
			
			$data['lock'] = array(
				'is' => ($lockIp || $lockCookie), 
				'pre' => true
			);
		}
	
		if($quiz->isPrerequisite()) {
			$quizIds = array();
			
			if($userId > 0) {
				$quizIds = $prerequisiteMapper->getNoPrerequisite($quizId, $userId);
			} else {
				$checkIds = $prerequisiteMapper->fetchQuizIds($quizId);
					
				if(isset($this->_cookie['wpProQuiz_result'])) {
					$r = json_decode($this->_cookie['wpProQuiz_result'], true);

					if($r !== null && is_array($r)) {
						foreach($checkIds as $id) {
							if(!isset($r[$id]) || !$r[$id]) {
								$quizIds[] = $id;
							}
						}
					}
				} else {
					$quizIds = $checkIds;
				}
			}
			
			if(!empty($quizIds)) {
				$post_quiz_ids = array();
				foreach( $quizIds as $pro_quiz_id ) {
					$post_quiz_id = learndash_get_quiz_id_by_pro_quiz_id( $pro_quiz_id );
					if ( ! empty( $post_quiz_id ) ) {
						$post_quiz_ids[$post_quiz_id] = $pro_quiz_id;
					}
				}
				if ( !empty( $post_quiz_ids ) ) {
					if ( ( isset( $this->_post['course_id'] ) ) && ( ! empty( $this->_post['course_id'] ) ) ) {
						$post_course_id = intval( $this->_post['course_id'] );
					} else {
						$post_course_id = 0;
					}
					$post_course_id = apply_filters( 
						'learndash_quiz_prerequisite_course_check', 
						$post_course_id, 
						$userId, 
						$post_quiz_ids
					);

					$post_quiz_ids = learndash_is_quiz_notcomplete($userId, $post_quiz_ids, true, -1 );
					if ( !empty( $post_quiz_ids ) ) {
						$quizIds = array_values( $post_quiz_ids );
					} else {
						$quizIds = array();
					}
				}
				
				if (!empty($quizIds)) {
					$names = $quizMapper->fetchCol($quizIds, 'name');
				
					if(!empty($names)) {
						$data['prerequisite'] = implode(', ', $names);
					}
				}
			}
		}
		
		if($quiz->isStartOnlyRegisteredUser()) {
			$data['startUserLock'] = (int)!is_user_logged_in();
		}
		
		return $data;
	}
	
	public function loadQuizData() {
		$quizId = (int)$_POST['quizId'];
		$userId = get_current_user_id();
		
		$quizMapper = new WpProQuiz_Model_QuizMapper();
		$toplistController = new WpProQuiz_Controller_Toplist();
		$statisticController = new WpProQuiz_Controller_Statistics();
		
		$quiz = $quizMapper->fetch($quizId);

		$data = array();
		
		if($quiz === null || $quiz->getId() <= 0) {
			return array();
		}
		
		$data['toplist'] = $toplistController->getAddToplist($quiz);
		
		if ( $quiz->isShowAverageResult() ) {
			$data['averageResult'] = $statisticController->getAverageResult($quizId);
		} else {
			$data['averageResult'] = 0;
		}
		
		/*
		$data['quiz_repeats'] = (int)0;
		$data['user_attempts_left'] = (int)0;
		$data['user_attempts_taken'] = (int)0;
		
		// We need the user quiz stats when they click the start quiz button
		$quiz_post_id = learndash_get_quiz_id_by_pro_quiz_id( $quizId );
		
		if (!empty($quiz_post_id)) {
			$quiz_post_meta = get_post_meta( $quiz_post_id, '_sfwd-quiz', true);
			
			if ( isset( $quiz_post_meta['sfwd-quiz_repeats'] ) ) {
				$data['quiz_repeats'] = intval( $quiz_post_meta['sfwd-quiz_repeats'] );
			}
		}
		if ( !empty( $userId ) ) {
			$usermeta = get_user_meta( $userId, '_sfwd-quizzes', true );
			$usermeta = maybe_unserialize( $usermeta );
			
			if ( ! is_array( $usermeta ) ) { 
				$usermeta = array();
			}
			
			if ( ! empty( $usermeta ) )	{
				foreach ( $usermeta as $k => $v ) {
					if ( $v['quiz'] == $quiz_post_id ) { 
						//error_log('match quiz<pre>'. print_r($v, true) .'</pre>');
						$data['user_attempts_taken'] += 1;
					}
				}
			}
			$data['user_attempts_left'] = (int)( $data['quiz_repeats'] == '' || $data['quiz_repeats'] >= $data['user_attempts_taken'] );
		}
		*/
		return $data;
	}
	
	private function resetLock($quizId) {
		
		if(!current_user_can('wpProQuiz_edit_quiz')) {
			exit;
		}
		
		$lm = new WpProQuiz_Model_LockMapper();
		$qm = new WpProQuiz_Model_QuizMapper();
		
		$q = $qm->fetch($quizId);
		
		if($q->getId() > 0) {

			$q->setQuizRunOnceTime(time());
			
			$qm->save($q);
			
			$lm->deleteByQuizId($quizId, WpProQuiz_Model_Lock::TYPE_QUIZ);
		}
		
		exit;
	}
	
	private function showAction() {
		if(!current_user_can('wpProQuiz_show')) {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}
		
		$this->view = new WpProQuiz_View_QuizOverall();
		
		$m = new WpProQuiz_Model_QuizMapper();
		$this->view->quiz = $m->fetchAll();
		
		$this->view->show();
	}
	
	private function editAction($id) {
		
		if(!current_user_can('wpProQuiz_edit_quiz')) {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}
		
		$prerequisiteMapper = new WpProQuiz_Model_PrerequisiteMapper();
		$quizMapper = new WpProQuiz_Model_QuizMapper();
		$formMapper = new WpProQuiz_Model_FormMapper();
		$templateMapper = new WpProQuiz_Model_TemplateMapper();
		$m = new WpProQuiz_Model_QuizMapper();
		
		$this->view = new WpProQuiz_View_QuizEdit();
		$this->view->header = sprintf( esc_html_x('Edit %s', 'Edit quiz', 'learndash'), learndash_get_custom_label_lower( 'quiz' ) );
		
		$forms = $formMapper->fetch($id);
		$prerequisiteQuizList = $prerequisiteMapper->fetchQuizIds($id);
		
		
		if($m->exists($id) == 0) {
			WpProQuiz_View_View::admin_notices( sprintf( esc_html_x('%s not found', 'Quiz not found', 'learndash'), LearnDash_Custom_Label::get_label( 'quiz' ) ), 'error');
			return;
		}
		
		if(isset($this->_post['submit'])) {
			
			if(isset($this->_post['resultGradeEnabled'])) {
				$this->_post['result_text'] = $this->filterResultTextGrade($this->_post);
			}
			
			$quiz = new WpProQuiz_Model_Quiz($this->_post);
			$quiz->setId($id);
					
			if($this->checkValidit($this->_post)) {
				
				//WpProQuiz_View_View::admin_notices( sprintf( esc_html_x('%s edited', 'Quiz edited', 'learndash'), LearnDash_Custom_Label::get_label( 'quiz' )), 'info');
				
				$prerequisiteMapper = new WpProQuiz_Model_PrerequisiteMapper();
				
				$prerequisiteMapper->delete($id);
				
				if($quiz->isPrerequisite() && !empty($this->_post['prerequisiteList'])) {
					$prerequisiteMapper->save($id, $this->_post['prerequisiteList']);
					$quizMapper->activateStatitic($this->_post['prerequisiteList'], 1440);
				}
				
				if(!$this->formHandler($quiz->getId(), $this->_post)) {
					$quiz->setFormActivated(false);
				}
				
				$quizMapper->save($quiz);
				
				$this->showAction();
				
				return;
			} else {
				//WpProQuiz_View_View::admin_notices( sprintf( esc_html_x('%1$s title or %2$s description are not filled', 'Quiz title or quiz description are not filled', 'learndash'), LearnDash_Custom_Label::get_label( 'quiz' ), learndash_get_custom_label_lower( 'quiz' )) );
			}
		} else if(isset($this->_post['template']) || isset($this->_post['templateLoad'])) {
			if(isset($this->_post['template']))
				$template = $this->saveTemplate();
			else 
				$template = $templateMapper->fetchById($this->_post['templateLoadId']);
			
			$data = $template->getData();
			
			if($data !== null) {
				$quiz = $data['quiz'];
				$forms = $data['forms'];
				$prerequisiteQuizList = $data['prerequisiteQuizList'];
			}
		} else {
			$quiz = $m->fetch($id);
		}
		
		$this->view->quiz = $quiz;
		$this->view->prerequisiteQuizList = $prerequisiteQuizList;
		$this->view->quizList = $m->fetchAllAsArray(array('id', 'name'), array($id));
		$this->view->captchaIsInstalled = class_exists('ReallySimpleCaptcha');
		$this->view->forms = $forms;
		$this->view->templates = $templateMapper->fetchAll(WpProQuiz_Model_Template::TEMPLATE_TYPE_QUIZ, false);
		$this->view->show();
	}
	
	private function createAction() {
		
		if(!current_user_can('wpProQuiz_add_quiz')) {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}
		
		$this->view = new WpProQuiz_View_QuizEdit();
		$this->view->header = sprintf( esc_html_x('Create %s', 'Create quiz', 'learndash'), learndash_get_custom_label_lower( 'quiz' ) );
		
		$forms = null;
		$prerequisiteQuizList = array();
		
		$m = new WpProQuiz_Model_QuizMapper();
		$templateMapper = new WpProQuiz_Model_TemplateMapper();
		
		if(isset($this->_post['submit'])) {
			
			if(isset($this->_post['resultGradeEnabled'])) {
				$this->_post['result_text'] = $this->filterResultTextGrade($this->_post);
			}
			
			$quiz = new WpProQuiz_Model_Quiz($this->_post);
			$quizMapper = new WpProQuiz_Model_QuizMapper();
			
			if($this->checkValidit($this->_post)) {
				//WpProQuiz_View_View::admin_notices( sprintf( esc_html_x('Create %s', 'Create quiz', 'learndash'), learndash_get_custom_label_lower( 'quiz' ) ), 'info');
				$quizMapper->save($quiz);
				
				$id = $quizMapper->getInsertId();
				$prerequisiteMapper = new WpProQuiz_Model_PrerequisiteMapper();
				
				if($quiz->isPrerequisite() && !empty($this->_post['prerequisiteList'])) {
					$prerequisiteMapper->save($id, $this->_post['prerequisiteList']);
					$quizMapper->activateStatitic($this->_post['prerequisiteList'], 1440);
				}
				
				if(!$this->formHandler($id, $this->_post)) {
					$quiz->setFormActivated(false);
					$quizMapper->save($quiz);
				}
				
				$this->showAction();
				return;
			} else {
				//WpProQuiz_View_View::admin_notices( sprintf( esc_html_x('%1$s title or %2$s description are not filled', 'Quiz title or quiz description are not filled', 'learndash'), LearnDash_Custom_Label::get_label( 'quiz' ), learndash_get_custom_label_lower( 'quiz' )) );
			}
		} else if(isset($this->_post['template']) || isset($this->_post['templateLoad'])) {
			if(isset($this->_post['template']))
				$template = $this->saveTemplate();
			else 
				$template = $templateMapper->fetchById($this->_post['templateLoadId']);
			
			$data = $template->getData();
			
			if($data !== null) {
				$quiz = $data['quiz'];
				$forms = $data['forms'];
				$prerequisiteQuizList = $data['prerequisiteQuizList'];
			}
		} else {
			$quiz = new WpProQuiz_Model_Quiz();
		}
		
		$this->view->quiz = $quiz;
		$this->view->prerequisiteQuizList = $prerequisiteQuizList;
		$this->view->quizList = $m->fetchAllAsArray(array('id', 'name'));
		$this->view->captchaIsInstalled = class_exists('ReallySimpleCaptcha');
		$this->view->forms = $forms;
		$this->view->templates = $templateMapper->fetchAll(WpProQuiz_Model_Template::TEMPLATE_TYPE_QUIZ, false);
		$this->view->show();
	}
	
	private function saveTemplate() {
		$templateMapper = new WpProQuiz_Model_TemplateMapper();
		
		if(isset($this->_post['resultGradeEnabled'])) {
			$this->_post['result_text'] = $this->filterResultTextGrade($this->_post);
		}
			
		$quiz = new WpProQuiz_Model_Quiz($this->_post);
			
		if($quiz->isPrerequisite() && !empty($this->_post['prerequisiteList']) && !$quiz->isStatisticsOn()) {
			$quiz->setStatisticsOn(true);
			$quiz->setStatisticsIpLock(1440);
		}
	
		$form = $this->_post['form'];
		
		unset($form[0]);
		
		$forms = array();
		
		foreach($form as $f) {
			if ( isset( $f['fieldname'] ) ) {
				$f['fieldname'] = trim( $f['fieldname'] );
				
				if ( empty( $f['fieldname'] ) ) {
					continue;
				}
				
				if ( absint( $f['form_id'] ) &&  absint( $f['form_delete'] ) ) {
					continue;
				}
				
				if( $f['type'] == WpProQuiz_Model_Form::FORM_TYPE_SELECT || $f['type'] == WpProQuiz_Model_Form::FORM_TYPE_RADIO ) {
					if(!empty($f['data'])) {
						$items = explode("\n", $f['data']);
						$f['data'] = array();
							
						foreach ($items as $item) {
							$item = trim($item);
			
							if(!empty($item))
								$f['data'][] = $item;
						}
					}
				}
				
				if(empty($f['data']) || !is_array($f['data']))
					$f['data'] = null;
					
				$forms[] = new WpProQuiz_Model_Form($f);
			}
		}
		
		//WpProQuiz_View_View::admin_notices(__('Template stored', 'learndash'), 'info');
		
		$data = array(
			'quiz' => $quiz,
			'forms' => $forms,
			'prerequisiteQuizList' => isset($this->_post['prerequisiteList']) ? $this->_post['prerequisiteList'] : array()
		);
		
		$quiz_post_id = $quiz->getPostId();
		if ( ! empty( $quiz_post_id ) ) {
			$data['_' . learndash_get_post_type_slug( 'quiz' ) ] = learndash_get_setting( $quiz_post_id );			
		}

		// Zero out the ProQuiz Post ID and the reference for the associated settings.
		$data['quiz']->setPostId(0);
		$data['_' . learndash_get_post_type_slug( 'quiz' ) ]['quiz_pro'] = 0;

		$template = new WpProQuiz_Model_Template();
		
		if($this->_post['templateSaveList'] == '0') {
			$template->setName(trim($this->_post['templateName']));
		} else {
			$template = $templateMapper->fetchById($this->_post['templateSaveList'], false);
		}
		
		$template->setType(WpProQuiz_Model_Template::TEMPLATE_TYPE_QUIZ);
		$template->setData($data);
		
		$templateMapper->save($template);
		
		return $template;
	}
	
	private function formHandler($quizId, $post) {
		if(!isset($post['form']))
			return false;
		
		$form = $post['form'];
		
		unset($form[0]);
		
		if(empty($form))
			return false;
		
		$formMapper = new WpProQuiz_Model_FormMapper();
		
		$deleteIds = array();
		$forms = array();
		$sort = 0;
		
		foreach($form as $f) {
						
			if ( ( !isset( $f['fieldname'] ) ) || ( empty( $f['fieldname'] ) ) )
				continue;
			
			$f['fieldname'] = trim($f['fieldname']);
			
			if((int) $f['form_id'] && (int) $f['form_delete']) {
				$deleteIds[] = (int) $f['form_id'];
				continue;				
			}
			
			$f['sort'] = $sort++;
			$f['quizId'] = $quizId;
			
			if($f['type'] == WpProQuiz_Model_Form::FORM_TYPE_SELECT || $f['type'] == WpProQuiz_Model_Form::FORM_TYPE_RADIO) {
				if(!empty($f['data'])) {
					$items = explode("\n", $f['data']);
					$f['data'] = array();
					
					foreach ($items as $item) {
						$item = trim($item);
						
						if(!empty($item))
							$f['data'][] = $item;
					}
				}
			}
			
			if(empty($f['data']) || !is_array($f['data']))
				$f['data'] = null;
			
			$forms[] = new WpProQuiz_Model_Form($f);
		}
		
		if(!empty($deleteIds))
			$formMapper->deleteForm($deleteIds, $quizId);
		
		$formMapper->update($forms);
		
		return !empty($forms);
	}
	
	private function deleteAction($id) {
		
		if(!current_user_can('wpProQuiz_delete_quiz')) {
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}
		
		$m = new WpProQuiz_Model_QuizMapper();
// 		$qm = new WpProQuiz_Model_QuestionMapper();
// 		$lm = new WpProQuiz_Model_LockMapper();
// 		$srm = new WpProQuiz_Model_StatisticRefMapper();
// 		$pm = new WpProQuiz_Model_PrerequisiteMapper();
// 		$tm = new WpProQuiz_Model_ToplistMapper();
		
// 		$m->delete($id);
// 		$qm->deleteByQuizId($id);
// 		$lm->deleteByQuizId($id);
// 		$srm->deleteAll($id);
// 		$pm->delete($id);
// 		$tm->delete($id);
		
		$m->deleteAll($id);
		
		//WpProQuiz_View_View::admin_notices(sprintf( esc_html_x('%s deleted', 'Quiz deleted', 'learndash'), LearnDash_Custom_Label::get_label( 'quiz' ) ), 'info');
		
		$this->showAction();
	}
	
	private function checkValidit($post) {
		
		if ( ( isset( $post['name'] ) ) && ( ! empty( $post['name'] ) ) && ( isset( $post['text'] ) ) && ( ! empty( $post['text'] ) ) )  {
			return true;
		}

		if ( ( isset( $post['post_ID'] ) ) && ( ! empty( $post['post_ID'] ) ) ) {
			if ( learndash_get_post_type_slug( 'quiz' ) === get_post_type( absint( $post['post_ID'] ) ) ) {
				return true;
			}
		}
	}
	
	private function filterResultTextGrade($post) {
		$result = array();
		
		$sorted = array();
		if ( isset( $post['resultTextGrade'] ) ) {
			$sorted = learndash_quiz_result_message_sort( $post['resultTextGrade'] );
			return $sorted;
		}
//		if ( ! empty( $sorted ) ) {
//			foreach ( $sorted as $item ) {
//				$result['text'][] = $item['text'];
//				$result['prozent'][] = $item['prozent'];
//			}
//		}

		return $result;				
	}
	
	private function setResultCookie(WpProQuiz_Model_Quiz $quiz) {
		$prerequisite = new WpProQuiz_Model_PrerequisiteMapper();
		
		if(get_current_user_id() == 0 && $prerequisite->isQuizId($quiz->getId())) {
			$cookieData = array();
			
			if(isset($this->_cookie['wpProQuiz_result'])) {
				$d = json_decode($this->_cookie['wpProQuiz_result'], true);
				
				if($d !== null && is_array($d)) {
					$cookieData = $d;
				}
			}
			
			$cookieData[$quiz->getId()] = 1;
			
			$url = parse_url(get_bloginfo( 'url' ));
		
			setcookie('wpProQuiz_result', json_encode($cookieData), time() + 60*60*24*300, empty($url['path']) ? '/' : $url['path']);
		}
	}
	
	public function completedQuiz() {		
		$lockMapper = new WpProQuiz_Model_LockMapper();
		$quizMapper = new WpProQuiz_Model_QuizMapper();
		$categoryMapper = new WpProQuiz_Model_CategoryMapper();
		
		$is100P = $this->_post['results']['comp']['result'] == 100;
		
		$userId = get_current_user_id();
		
		$quiz = $quizMapper->fetch($this->_post['quizId']);
		if ( ( isset( $this->_post['quiz'] ) ) && ( ! empty( $this->_post['quiz'] ) ) ) {
			if ( absint( $this->_post['quiz'] ) !== absint( $quiz->getPostId() ) ) {
				$quiz->setPostId( absint( $this->_post['quiz'] ) );
			}
		}

		if ( $quiz === null || $quiz->getId() <= 0 ) {
			exit;
		}

		$categories = $categoryMapper->fetchByQuiz($quiz);
		
		$this->setResultCookie( $quiz );
		
		$this->emailNote( $quiz, $this->_post['results']['comp'], $categories );
		
		if( ! $this->isPreLockQuiz( $quiz ) ) {
			$statistics = new WpProQuiz_Controller_Statistics();
			$statisticRefMapper_id = $statistics->save($quiz);
			do_action('wp_pro_quiz_completed_quiz', $statisticRefMapper_id) ;
			
			if ( $is100P )
				do_action( 'wp_pro_quiz_completed_quiz_100_percent' );
			
			exit;
		}
		
		$lockMapper->deleteOldLock(60*60*24*7, $this->_post['quizId'], time(), WpProQuiz_Model_Lock::TYPE_QUIZ, 0);
		
		$lockIp = $lockMapper->isLock($this->_post['quizId'], $this->getIp(), get_current_user_id(), WpProQuiz_Model_Lock::TYPE_QUIZ);
		$lockCookie = false;
		$cookieTime = $quiz->getQuizRunOnceTime();
		$cookieJson = null;
		
		if(isset($this->_cookie['wpProQuiz_lock']) && get_current_user_id() == 0 && $quiz->isQuizRunOnceCookie()) {
			$cookieJson = json_decode($this->_cookie['wpProQuiz_lock'], true);
			
			if($cookieJson !== false) { 
				if(isset($cookieJson[$this->_post['quizId']]) && $cookieJson[$this->_post['quizId']] == $cookieTime) {
					$lockCookie = true;
				}
			}
		}

		if(!$lockIp && !$lockCookie) {
			$statistics = new WpProQuiz_Controller_Statistics();
			$statisticRefMapper_id = $statistics->save($quiz);

			//do_action('wp_pro_quiz_completed_quiz');
			do_action('wp_pro_quiz_completed_quiz', $statisticRefMapper_id);
			
			if($is100P)
				do_action('wp_pro_quiz_completed_quiz_100_percent');

			if(get_current_user_id() == 0 && $quiz->isQuizRunOnceCookie()) {
				$cookieData = array();
				
				if($cookieJson !== null || $cookieJson !== false) {
					$cookieData = $cookieJson;
				}
				
				$cookieData[$this->_post['quizId']] = $quiz->getQuizRunOnceTime();
				$url = parse_url(get_bloginfo( 'url' ));
				
				setcookie('wpProQuiz_lock', json_encode($cookieData), time() + 60*60*24*60, empty($url['path']) ? '/' : $url['path']);
			}

			$lock = new WpProQuiz_Model_Lock();
			
			$lock->setUserId(get_current_user_id());
			$lock->setQuizId($this->_post['quizId']);
			$lock->setLockDate(time());
			$lock->setLockIp($this->getIp());
			$lock->setLockType(WpProQuiz_Model_Lock::TYPE_QUIZ);
			
			$lockMapper->insert($lock);
		}
		
		exit;
	}
	
	public function isPreLockQuiz(WpProQuiz_Model_Quiz $quiz) {
		$userId = get_current_user_id();
		
		if($quiz->isQuizRunOnce()) {
			switch ($quiz->getQuizRunOnceType()) {
				case WpProQuiz_Model_Quiz::QUIZ_RUN_ONCE_TYPE_ALL:
					return true;
				case WpProQuiz_Model_Quiz::QUIZ_RUN_ONCE_TYPE_ONLY_USER:
					return $userId > 0;
				case WpProQuiz_Model_Quiz::QUIZ_RUN_ONCE_TYPE_ONLY_ANONYM:
					return $userId == 0;
			}
		}
		
		return false;
	}
	
	private function getIp() {
		if(get_current_user_id() > 0)
			return '0';
		else
			return filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP);
	}
	
	private function emailNote(WpProQuiz_Model_Quiz $quiz, $result, $categories) {
		$globalMapper = new WpProQuiz_Model_GlobalSettingsMapper();
		
		$email_settings_admin = LearnDash_Settings_Section::get_section_settings_all( 'LearnDash_Settings_Quizzes_Admin_Email' );
		//error_log('email_settings_admin<pre>'. print_r($email_settings_admin, true) .'</pre>');

		$email_settings_user = LearnDash_Settings_Section::get_section_settings_all( 'LearnDash_Settings_Quizzes_User_Email' );
		//error_log('email_settings_user<pre>'. print_r($email_settings_user, true) .'</pre>');
		
		$user = wp_get_current_user();
		
		$r = array(
			'$userId' => $user->ID,
			'$username' => $user->display_name,
			'$quizname' => $quiz->getName(),
			'$result' => $result['result'].'%',
			'$points' => $result['points'],
			'$ip' => filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP),
			'$categories' => empty($result['cats']) ? '' : $this->setCategoryOverview($result['cats'], $categories)
		);
		
		if($user->ID == 0) {
			$r['$username'] = $r['$ip'];
		}
		
		if($quiz->isUserEmailNotification()) {
			//$msg = str_replace(array_keys($r), $r, $userEmail['message']);
			$msg = str_replace(array_keys($r), $r, $email_settings_user['user_mail_message']);
			
			$msg = apply_filters( 'learndash_quiz_email_note_user_message', $msg, $r, $quiz, $result, $categories );
				
			$headers = '';
				
			if ( ( isset( $email_settings_user['user_mail_from'] ) ) && ( !empty( $email_settings_user['user_mail_from'] ) ) && ( is_email( $email_settings_user['user_mail_from'] ) ) ) {
				if ( ( !isset( $email_settings_user['user_mail_from_name'] ) ) || ( empty( $email_settings_user['user_mail_from_name'] ) ) ) {
					$email_settings_user['user_mail_from_name'] = '';
				
					$email_user = get_user_by('emal', $email_settings_user['user_mail_from'] );
					if ( ( $email_user ) && ( is_a( $email_user, 'WP_User' ) ) ) {
						$email_settings_user['user_mail_from_name'] = $email_user->display_name;
					}
				}
				
				$headers .= 'From: ';
				if ( ( isset( $email_settings_user['user_mail_from_name'] ) ) && ( !empty( $email_settings_user['user_mail_from_name'] ) ) ) {
					$headers .= $email_settings_user['user_mail_from_name'] .' <'. $email_settings_user['user_mail_from'] .'>';
				} else {
					$headers .= $email_settings_user['user_mail_from'];
				}
			}

			if ( ( isset( $email_settings_user['user_mail_html'] ) ) && ( 'yes' === $email_settings_user['user_mail_html'] ) ) {
				add_filter( 'wp_mail_content_type', array( $this, 'htmlEmailContent' ) );
				$msg = wpautop( $msg );
			}

			$email_params = array(
							"email" => $user->user_email,
							"subject" => $email_settings_user['user_mail_subject'],
							"msg" => $msg,
							"headers" => $headers
						);

			$email_params = apply_filters("learndash_quiz_email", $email_params, $quiz);
			//error_log('Quiz User Email params<pre>'. print_r($email_params, true) .'</pre>');
			wp_mail( $email_params['email'], $email_params['subject'], $email_params['msg'], $email_params['headers'] );

			if ( ( isset( $email_settings_user['user_mail_html'] ) ) && ( 'yes' === $email_settings_user['user_mail_html'] ) ) {
				remove_filter( 'wp_mail_content_type', array( $this, 'htmlEmailContent' ) );
			}
		}
		
		if($quiz->getEmailNotification() == WpProQuiz_Model_Quiz::QUIZ_EMAIL_NOTE_ALL 
			|| (get_current_user_id() > 0 && $quiz->getEmailNotification() == WpProQuiz_Model_Quiz::QUIZ_EMAIL_NOTE_REG_USER)) {
			
			$msg = str_replace( array_keys( $r ), $r, $email_settings_admin['admin_mail_message'] );
			$msg = apply_filters( 'learndash_quiz_email_note_admin_message', $msg, $r, $quiz, $result, $categories );
			$headers = '';
			
			if ( ( ! isset( $email_settings_admin['admin_mail_from'] ) ) || ( empty( $email_settings_admin['admin_mail_from'] ) ) || ( !is_email( $email_settings_admin['admin_mail_from'] ) ) ) {
				$email_settings_admin['admin_mail_from'] = get_option( 'admin_email' );
			}
			
			if ( ( !isset( $email_settings_admin['admin_mail_from_name'] ) ) || ( empty( $email_settings_admin['admin_mail_from_name'] ) ) ) {
				$email_settings_admin['admin_mail_from_name'] = '';
				
				if ( ! empty( $email_settings_admin['admin_mail_from'] ) ) {
					$email_user = get_user_by('emal', $email_settings_admin['admin_mail_from'] );
					if ( ( $email_user ) && ( is_a( $email_user, 'WP_User' ) ) ) {
						$email_settings_admin['admin_mail_from_name'] = $email_user->display_name;
					}
				}
			}

			if ( ! empty( $email_settings_admin['admin_mail_from'] ) ) {
				$headers .= 'From: ';
				if ( ( isset( $email_settings_admin['admin_mail_from_name'] ) ) && ( !empty( $email_settings_admin['admin_mail_from_name'] ) ) ) {
					$headers .= $email_settings_admin['admin_mail_from_name'] .' <'. $email_settings_admin['admin_mail_from'] .'>';
				} else {
					$headers .= $email_settings_admin['admin_mail_from'];
				}
			}
			
			if ( ( isset( $email_settings_admin['admin_mail_html'] ) ) && ( $email_settings_admin['admin_mail_html'] ) ) {
				add_filter( 'wp_mail_content_type', array( $this, 'htmlEmailContent' ) );
				$msg = wpautop( $msg );

			}
			
			$email_params = array(
				'email' => $email_settings_admin['admin_mail_to'],
				'subject' => $email_settings_admin['admin_mail_subject'],
				'msg' => $msg,
				'headers' => $headers
			);

			$email_params = apply_filters( 'learndash_quiz_email_admin', $email_params, $quiz);
			//error_log('Quiz Admin Email params<pre>'. print_r($email_params, true) .'</pre>');
			wp_mail($email_params['email'], $email_params['subject'], $email_params['msg'], $email_params['headers'] );
			
			if ( ( isset( $email_settings_admin['admin_mail_html'] ) ) && ( 'yes' === $email_settings_admin['admin_mail_html'] ) ) {
				remove_filter( 'wp_mail_content_type', array( $this, 'htmlEmailContent' ) );
			}
		}
	}
	
	public function htmlEmailContent($contentType) {
		return 'text/html';
	}
	
	private function setCategoryOverview( $category_scores = array() , $question_cats = array() ) {
		if ( ( ! empty( $category_scores  ) ) && ( ! empty( $question_cats ) ) ) {
			$question_categories = array();

			foreach ( $question_cats as $cat ) {
				if ( ! $cat->getCategoryId() ) {
					$cat->setCategoryName( esc_html__( 'Not categorized', 'learndash' ) );
				}

				$question_categories[ $cat->getCategoryId() ] = $cat->getCategoryName();
			}
		}

		$output = SFWD_LMS::get_template(
			'quiz_result_categories_email.php',
			array(
				'question_categories' => $question_categories,
				'category_scores'     => $category_scores,
			)
		);
		return $output;

		//$cats = array();
		//
		//foreach($categories as $cat) {
		//	/* @var $cat WpProQuiz_Model_Category */
		//	
		//	if(!$cat->getCategoryId()) {
		//		$cat->setCategoryName(__('Not categorized', 'learndash'));
		//	}
		//	
		//	$cats[$cat->getCategoryId()] = $cat->getCategoryName();
		//}
		//
		//$a = esc_html__('Categories', 'learndash').":\n";
		//
		//foreach($catArray as $id => $value) {
		//	if(!isset($cats[$id]))
		//		continue;
		//	
		//	$a .= '* '.str_pad($cats[$id], 35, '.').((float)$value)."%\n";
		//}
		//
		//return $a;
	}
}