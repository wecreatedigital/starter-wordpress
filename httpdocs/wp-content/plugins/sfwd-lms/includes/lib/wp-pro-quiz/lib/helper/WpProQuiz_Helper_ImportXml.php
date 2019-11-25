<?php
class WpProQuiz_Helper_ImportXml {
	private $_content = null;
	private $_error = false;
	public  $import_post_id = 0;

	public function setImportFileUpload($file) {
		if(!is_uploaded_file($file['tmp_name'])) {
			$this->setError(__('File was not uploaded', 'learndash'));
			return false;
		}
	
		$this->_content = file_get_contents($file['tmp_name']);
	
		return $this->checkCode();
	}
	
	public function setImportString($str) {
		$this->_content = gzuncompress(base64_decode($str));
		
		return true;
	}
	public function setString($str) {
		$this->_content = $str;
		
		return $this->checkCode();
	}
	private function checkCode() {
		$xml = @simplexml_load_string($this->_content);

		if($xml === false) {
			$this->_error = esc_html__('XML could not be loaded.', 'learndash');			
			return false;
		}
		
		return isset($xml->header);
	}
	
	public function getImportData() {
		$xml = @simplexml_load_string($this->_content, 'SimpleXMLElement', LIBXML_NOCDATA);
		$a = array('master' => array(), 'question' => array(), 'forms' => array());
		$i = 0;
		
		if($xml === false) {
			$this->_error = esc_html__('XML could not be loaded.', 'learndash');
			return false;
		}
		
		if(isset($xml->data) && isset($xml->data->quiz)) {
			foreach($xml->data->quiz as $quiz) {
				$quizModel = $this->createQuizModel($quiz);

				if($quizModel !== null) {
					$quizModel->setId($i++);
					
					$a['master'][] = $quizModel;
					
					if($quiz->forms->form) {
						foreach ($quiz->forms->form as $form) {
							$a['forms'][$quizModel->getId()][] = $this->createFormModel($form);
						}
					}
					
					if(isset($quiz->questions)) {
						foreach ($quiz->questions->question as $question) {
							$questionModel = $this->createQuestionModel($question);
							
							if($questionModel !== null)
								$a['question'][$quizModel->getId()][] = $questionModel;
						}
					}

					// We don't need to process the post content and post meta on the preview screen.
					if ( ( isset( $_POST['importSave'] ) ) && ( ! empty( $_POST['importSave'] ) ) ) {
						if ( isset( $quiz->post_meta ) ) {
							if ( ! isset( $a['post_meta'][$quizModel->getId()] ) ) {
								$a['post_meta'][$quizModel->getId()] = array();
							}

							foreach ( $quiz->post_meta as $post_meta ) {
								$meta_key   = trim( $post_meta->meta_key );
								if ( ! empty( $meta_key ) ) {
									$meta_value = trim( $post_meta->meta_value );

									//if ( ! isset( $a['post_meta'][$quizModel->getId()][ $meta_key ] ) ) {
									//	$a['post_meta'][ $quizModel->getId() ][ $meta_key ] = array();
									//}
									$a['post_meta'][ $quizModel->getId() ][ $meta_key ] = maybe_unserialize( $meta_value );
								}
							}
						}

						if ( isset( $quiz->post ) ) {
							if ( ! isset( $a['post'][$quizModel->getId()] ) ) {
								$a['post'][$quizModel->getId()] = array();
							}
							
							foreach ( $quiz->post as $post_items ) {
								foreach ( $post_items as $post_item_key => $post_item_value ) {
									$post_item_key = trim( $post_item_key );
									$post_item_value = trim( $post_item_value );
									$a['post'][$quizModel->getId()][ $post_item_key ] = $post_item_value;
								}
							}
						}
					}
				}
			}
		}

		return $a;
	}
	
	public function getContent() {
		return base64_encode(gzcompress($this->_content));
	}
	
	public function saveImport( $ids ) {
		$quizMapper     = new WpProQuiz_Model_QuizMapper();
		$questionMapper = new WpProQuiz_Model_QuestionMapper();
		$categoryMapper = new WpProQuiz_Model_CategoryMapper();
		$formMapper     = new WpProQuiz_Model_FormMapper();
		
		$data = $this->getImportData();
		$categoryArray = $categoryMapper->getCategoryArrayForImport();
		
		foreach ( $data['master'] as $quiz ) {
			if ( get_class($quiz) !== 'WpProQuiz_Model_Quiz' ) {
				continue;
			}

			$oldId = $quiz->getId();

			if ( $ids !== false && ! in_array( $oldId, $ids ) ) {
				continue;
			}

			$quiz->setId (0 );

			$quizMapper->save( $quiz );

			$user_id = get_current_user_id();
			$quiz_insert_data = array(
				'post_type'   => learndash_get_post_type_slug( 'quiz' ),
				'post_title' => $quiz->getName(),
				'post_status' => 'publish',
				'post_author' => $user_id,
			);

			if ( ( isset( $data['post'][ $oldId ] ) ) && ( ! empty( $data['post'][ $oldId ] ) ) ) {
				//$quiz_insert_data['post'] = $data['post'][ $oldId ];
				$post_import_keys = array( 'post_title', 'post_content' );
				$post_import_keys = apply_filters( 'learndash_quiz_import_post_keys', $post_import_keys );
				if ( ! empty( $post_import_keys ) ) {
					foreach( $post_import_keys as $import_key ) {
						if ( isset( $data['post'][ $oldId ][ $import_key ] ) ) {
							$quiz_insert_data[ $import_key ] = $data['post'][ $oldId ][ $import_key ];
						}
					}
				}
			}
			$quiz_insert_data = apply_filters( 'learndash_quiz_import_post_data', $quiz_insert_data, 'xml' );
			$quiz_post_id = wp_insert_post( $quiz_insert_data );
			if ( ! empty( $quiz_post_id ) ) {
				$this->import_post_id = $quiz_post_id;

				$post_meta_import_keys = array( '_' . get_post_type( $quiz_post_id ), '_viewProfileStatistics', '_timeLimitCookie' );
				$post_meta_import_keys = apply_filters( 'learndash_quiz_import_post_meta_keys', $post_meta_import_keys );
				if ( ! empty( $post_import_keys ) ) {

					if ( ( isset( $data['post_meta'][ $oldId ] ) ) && ( ! empty( $data['post_meta'][ $oldId ] ) ) ) {
						if ( ( isset( $data['post_meta'][ $oldId ] ) ) && ( ! empty( $data['post_meta'][ $oldId ] ) ) ) {
							foreach( $data['post_meta'][ $oldId ] as $_key => $_key_data ) {
								if ( ( empty( $_key ) ) || ( empty( $_key_data ) ) ) {
									continue;
								}
								
								if ( in_array( $_key, $post_meta_import_keys ) ) {
									foreach( $_key_data as $_data_set ) {
										$_data_set = maybe_unserialize( $_data_set );
										add_post_meta( $quiz_post_id, $_key, $_data_set );
									}
								}
							}
						}
					}
				}
				learndash_update_setting( $quiz_post_id, 'quiz_pro', $quiz->getId() );
			}

			if( isset( $data['forms'] ) && isset( $data['forms'][ $oldId ] ) ) {
				$sort = 0;

				foreach( $data['forms'][ $oldId ] as $form ) {
					$form->setQuizId( $quiz->getId() );
					$form->setSort( $sort++ );
				}

				$formMapper->update( $data['forms'][ $oldId ] );
			}

			$sort = 0;

			if ( ( isset( $data['question'][ $oldId ]  ) ) && ( ! empty( $data['question'][ $oldId ] ) ) ) {
				foreach( $data['question'][ $oldId ] as $question ) {
				
					if(get_class($question) !== 'WpProQuiz_Model_Question')
						continue;
							
					$question->setQuizId($quiz->getId());
					$question->setId(0);
					$question->setSort($sort++);
					$question->setCategoryId(0);
					if(trim($question->getCategoryName()) != '') {
						if(isset($categoryArray[strtolower($question->getCategoryName())])) {
							$question->setCategoryId($categoryArray[strtolower($question->getCategoryName())]);
						} else {
							$categoryModel = new WpProQuiz_Model_Category();
							$categoryModel->setCategoryName($question->getCategoryName());
							$categoryMapper->save($categoryModel);
							
							$question->setCategoryId($categoryModel->getCategoryId());
							
							$categoryArray[strtolower($question->getCategoryName())] = $categoryModel->getCategoryId();
						}
					}
					
					$question = $questionMapper->save( $question );
					
					$question_post_array = array(
						'post_type'    => learndash_get_post_type_slug( 'question' ),
						'post_title'   => $question->getTitle(),
						'post_content' => $question->getQuestion(),
						'post_status'  => 'publish',
						'post_author'  => $user_id,
						'menu_order'   => $sort,
					);
					$question_post_array = wp_slash( $question_post_array );
					$question_post_id = wp_insert_post( $question_post_array );
					if ( ! empty( $question_post_id ) ) {
						update_post_meta( $question_post_id, 'points', absint( $question->getPoints() ) );
						update_post_meta( $question_post_id, 'question_type', $question->getAnswerType() );
						update_post_meta( $question_post_id, 'question_pro_id', absint( $question->getId() ) );

						learndash_update_setting( $question_post_id, 'quiz', $quiz_post_id );
						add_post_meta( $question_post_id, 'ld_quiz_id', $quiz_post_id );
					}
				}
			}
		}
		
		return true;
	}
	public function saveImportSingle() {
		$quizMapper = new WpProQuiz_Model_QuizMapper();
		$questionMapper = new WpProQuiz_Model_QuestionMapper();
		$categoryMapper = new WpProQuiz_Model_CategoryMapper();
		$formMapper = new WpProQuiz_Model_FormMapper();
		
		$data = $this->getImportData();
		$categoryArray = $categoryMapper->getCategoryArrayForImport();
		
		foreach($data['master'] as $quiz) {
			if(get_class($quiz) !== 'WpProQuiz_Model_Quiz')
				continue;
			
			$oldId = $quiz->getId();
			
			if($oldId != 0)
				continue;
			
			$quiz->setId(0);
			
			$quizMapper->save($quiz);
			
			if(isset($data['forms']) && isset($data['forms'][$oldId])) {
				$sort = 0;
				
				foreach($data['forms'][$oldId] as $form) {
					$form->setQuizId($quiz->getId());
					$form->setSort($sort++);
				}
				
				$formMapper->update($data['forms'][$oldId]);
			}
			
			$sort = 0;
			
			foreach($data['question'][$oldId] as $question) {
			
				if(get_class($question) !== 'WpProQuiz_Model_Question')
					continue;
						
				$question->setQuizId($quiz->getId());
				$question->setId(0);
				$question->setSort($sort++);
				$question->setCategoryId(0);
				if(trim($question->getCategoryName()) != '') {
					if(isset($categoryArray[strtolower($question->getCategoryName())])) {
						$question->setCategoryId($categoryArray[strtolower($question->getCategoryName())]);
					} else {
						$categoryModel = new WpProQuiz_Model_Category();
						$categoryModel->setCategoryName($question->getCategoryName());
						$categoryMapper->save($categoryModel);
						
						$question->setCategoryId($categoryModel->getCategoryId());
						
						$categoryArray[strtolower($question->getCategoryName())] = $categoryModel->getCategoryId();
					}
				}
				
				$questionMapper->save($question);
			}
			return $quiz->getId();
		}
		
		return 0;
	}
	public function getError() {
		return $this->_error;
	}
	
	private function createFormModel($xml) {
		$form = new WpProQuiz_Model_Form();
		
		$attr = $xml->attributes();
		
		if($attr !== null) {
			$form->setType($attr->type);
			$form->setRequired($attr->required == 'true');
			$form->setFieldname($attr->fieldname);
		}
		
		if(isset($xml->formData)) {
			$d = array();
			
			foreach($xml->formData as $data) {
				$v = trim((string)$data);

				if($v !== '')
					$d[] = $v;
			}
			
			$form->setData($d);
		}
		
		return $form;
	}
	
	private function createQuizModel($xml) {
		$model = new WpProQuiz_Model_Quiz();
		
		$quizId = $xml->attributes()->id;

		$model->setName(trim($xml->title));
		$model->setText(trim($xml->text));
		$model->setTitleHidden($xml->title->attributes()->titleHidden == 'true');
		
		$model->setQuestionRandom($xml->questionRandom == 'true');
		$model->setAnswerRandom($xml->answerRandom == 'true');
		$model->setTimeLimit($xml->timeLimit);
		
		$model->setResultText($xml->resultText);
		$model->setResultGradeEnabled($xml->resultText);
		
		if(isset($xml->resultText)) {
			$attr = $xml->resultText->attributes();
			
			if($attr !== null) {
				$model->setResultGradeEnabled($attr->gradeEnabled == 'true');
				
				if($model->isResultGradeEnabled()) {
					$resultArray = array('text' => array(), 'prozent' => array());
					
					foreach($xml->resultText->text as $result) {
						$resultArray['text'][] = trim((string)$result);
						$resultArray['prozent'][] = $result->attributes() === null ? 0 : (int)$result->attributes()->prozent;
					}
					
					$model->setResultText($resultArray);
				} else {
					$model->setResultText(trim((string) $xml->resultText));
				}
			}
		}
		
		$model->setShowPoints($xml->showPoints == 'true');
		$model->setBtnRestartQuizHidden($xml->btnRestartQuizHidden == 'true');
		$model->setBtnViewQuestionHidden($xml->btnViewQuestionHidden == 'true');
		$model->setNumberedAnswer($xml->numberedAnswer == 'true');
		$model->setHideAnswerMessageBox($xml->hideAnswerMessageBox == 'true');
		$model->setDisabledAnswerMark($xml->disabledAnswerMark == 'true');
		
		if(isset($xml->statistic)) {
			$attr = $xml->statistic->attributes();
			
			if($attr !== null) {
				$model->setStatisticsOn($attr->activated == 'true');
				$model->setStatisticsIpLock($attr->ipLock);
			}
		}
		
		if(isset($xml->quizRunOnce)) {
			$model->setQuizRunOnce($xml->quizRunOnce == 'true');
			$attr = $xml->quizRunOnce->attributes();
			
			if($attr !== null) {
				$model->setQuizRunOnceCookie($attr->cookie == 'true');
				$model->setQuizRunOnceType($attr->type);
				$model->setQuizRunOnceTime($attr->time);
			}
		}
		
		if(isset($xml->showMaxQuestion)) {
			$model->setShowMaxQuestion($xml->showMaxQuestion == 'true');
			$attr = $xml->showMaxQuestion->attributes();
			
			if($attr !== null) {
				$model->setShowMaxQuestionValue($attr->showMaxQuestionValue);
				$model->setShowMaxQuestionPercent($attr->showMaxQuestionPercent == 'true');
			}
		}
		
		if(isset($xml->toplist)) {
			$model->setToplistActivated($xml->toplist->attributes()->activated == 'true');
			
			$model->setToplistDataAddPermissions($xml->toplist->toplistDataAddPermissions);
			$model->setToplistDataSort($xml->toplist->toplistDataSort);
			$model->setToplistDataAddMultiple($xml->toplist->toplistDataAddMultiple == 'true');
			$model->setToplistDataAddBlock($xml->toplist->toplistDataAddBlock);
			$model->setToplistDataShowLimit($xml->toplist->toplistDataShowLimit);
			$model->setToplistDataShowIn($xml->toplist->toplistDataShowIn);
			$model->setToplistDataCaptcha($xml->toplist->toplistDataCaptcha == 'true');
			$model->setToplistDataAddAutomatic($xml->toplist->toplistDataAddAutomatic == 'true');
		}
		
		$model->setShowAverageResult($xml->showAverageResult == 'true');
		$model->setPrerequisite($xml->prerequisite == 'true');
		$model->setQuizModus($xml->quizModus);
		$model->setShowReviewQuestion($xml->showReviewQuestion == 'true');
		$model->setQuizSummaryHide($xml->quizSummaryHide == 'true');
		$model->setSkipQuestionDisabled($xml->skipQuestionDisabled == 'true');
		$model->setEmailNotification($xml->emailNotification);
		$model->setUserEmailNotification($xml->userEmailNotification == 'true');
		$model->setShowCategoryScore($xml->showCategoryScore == 'true');
		$model->setHideResultCorrectQuestion($xml->hideResultCorrectQuestion == 'true');
		$model->setHideResultQuizTime($xml->hideResultQuizTime == 'true');
		$model->setHideResultPoints($xml->hideResultPoints == 'true');
		$model->setAutostart($xml->autostart == 'true');
		$model->setForcingQuestionSolve($xml->forcingQuestionSolve == 'true');
		$model->setHideQuestionPositionOverview($xml->hideQuestionPositionOverview == 'true');
		$model->setHideQuestionNumbering($xml->hideQuestionNumbering == 'true');
		
		//0.27
		$model->setStartOnlyRegisteredUser($xml->startOnlyRegisteredUser == 'true');
		$model->setSortCategories($xml->sortCategories == 'true');
		$model->setShowCategory($xml->showCategory == 'true');
		
		if(isset($xml->quizModus)) {
			$attr = $xml->quizModus->attributes();
				
			if($attr !== null) {
				$model->setQuestionsPerPage($attr->questionsPerPage);
			}
		}
		
		if(isset($xml->forms)) {
			$attr = $xml->forms->attributes();
			
			$model->setFormActivated($attr->activated == 'true');
			$model->setFormShowPosition($attr->position);
		}
		
		
		//Check
		if($model->getName() == '')
			return null;
		
		if($model->getText() == '')
			return null;
		
		return $model;
	}
	
	/**
	 * 
	 * @param DOMDocument $xml
	 * @return NULL|WpProQuiz_Model_Question
	 */
	private function createQuestionModel($xml) {
		$model = new WpProQuiz_Model_Question();
		
		$model->setTitle(trim($xml->title));
		$model->setQuestion(trim($xml->questionText));
		$model->setCorrectMsg(trim($xml->correctMsg));
		$model->setIncorrectMsg(trim($xml->incorrectMsg));
		$model->setAnswerType(trim($xml->attributes()->answerType));
		$model->setCorrectSameText($xml->correctSameText == 'true');
		
		$model->setTipMsg(trim($xml->tipMsg));
		
		if(isset($xml->tipMsg) && $xml->tipMsg->attributes() !== null)
			$model->setTipEnabled($xml->tipMsg->attributes()->enabled == 'true');
		
		$model->setPoints($xml->points);
		$model->setShowPointsInBox($xml->showPointsInBox == 'true');
		$model->setAnswerPointsActivated($xml->answerPointsActivated == 'true');
		$model->setAnswerPointsDiffModusActivated($xml->answerPointsDiffModusActivated == 'true');
		$model->setDisableCorrect($xml->disableCorrect == 'true');
		$model->setCategoryName(trim($xml->category));
		
		$answerData = array();
		
		if(isset($xml->answers)) {
			foreach($xml->answers->answer as $answer) {
				$answerModel = new WpProQuiz_Model_AnswerTypes();
				
				$attr = $answer->attributes();
				
				if($attr !== null) {
					$answerModel->setCorrect($attr->correct == 'true');
					$answerModel->setPoints($attr->points);

					if ( 'essay' === $model->getAnswerType() ) {
						$answerModel->setGraded('1');
						if ( isset( $attr->gradedType ) ) {
							$answerModel->setGradedType( $attr->gradedType );
						}

						if ( isset( $attr->gradingProgression ) ) {
							$answerModel->setGradingProgression( $attr->gradingProgression );
						}
					}
				}
				
				$answerModel->setAnswer(trim($answer->answerText));
				
				if($answer->answerText->attributes() !== null)
					$answerModel->setHtml($answer->answerText->attributes()->html);
				
				$answerModel->setSortString(trim($answer->stortText));
				
				if($answer->stortText->attributes() !== null)
					$answerModel->setSortStringHtml($answer->stortText->attributes()->html);
				
				$answerData[] = $answerModel;
			}
		}
		
		$model->setAnswerData($answerData);
		
		//Check
		if(trim($model->getAnswerType()) == '')
			return null;
		
		if(trim($model->getQuestion()) == '')
			return null;
		
		if(trim($model->getTitle()) == '')
			return null;
		
		if(count($model->getAnswerData()) == 0)
			return null;
	
		return $model;
	}
}