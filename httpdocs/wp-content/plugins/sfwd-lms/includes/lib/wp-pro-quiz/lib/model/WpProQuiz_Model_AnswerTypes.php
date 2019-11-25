<?php
class WpProQuiz_Model_AnswerTypes extends WpProQuiz_Model_Model {
	protected $_answer = '';
	protected $_html = false;
	protected $_points = LEARNDASH_LMS_DEFAULT_ANSWER_POINTS;
	
	protected $_correct = false;
	
	protected $_sortString = '';
	protected $_sortStringHtml = false;

	protected $_graded = false;
	protected $_gradingProgression = 'not-graded-none';
	protected $_gradedType = null;

	public function setAnswer($_answer) {
		$this->_answer = (string)$_answer;
		return $this;
	}
	
	public function getAnswer() {
		return $this->_answer;
	}
	
	public function setHtml($_html) {
		$this->_html = (bool)$_html;
		return $this;
	}
	
	public function isHtml() {
		return $this->_html;
	}
	
	public function setPoints($_points) {
		$this->_points = (int)$_points;
		return $this;
	}
	
	public function getPoints() {
		return $this->_points;
	}
	
	public function setCorrect($_correct) {
		$this->_correct = (bool)$_correct;
		return $this;
	}
	
	public function isCorrect() {
		return $this->_correct;
	}
	
	public function setSortString($_sortString) {
		$this->_sortString = (string)$_sortString;
		return $this;
	}
	
	public function getSortString() {
		return $this->_sortString;
	}
	
	public function setSortStringHtml($_sortStringHtml) {
		$this->_sortStringHtml = (bool)$_sortStringHtml;
		return $this;
	}
	
	public function isSortStringHtml() {
		return $this->_sortStringHtml;
	}

	public function setGraded($_graded) {
		$this->_graded = (string)$_graded;
		return $this;
	}

	public function isGraded() {
		return $this->_graded;
	}

	public function setGradedType($_gradedType) {
		$this->_gradedType = (string)$_gradedType;
		return $this;
	}

	public function getGradedType() {
		return $this->_gradedType;
	}

	public function setGradingProgression($_gradingProgression) {
		if ( ( is_null( $_gradingProgression ) ) || ( empty( $_gradingProgression ) ) ) {
			$_gradingProgression = 'not-graded-none';
		}
		$this->_gradingProgression = (string)$_gradingProgression;
		return $this;
	}

	public function getGradingProgression() {
		if ( ( is_null( $this->_gradingProgression ) ) || ( empty( $this->_gradingProgression ) ) ) {
			$this->_gradingProgression = 'not-graded-none';
		}
		return $this->_gradingProgression;
	}
	
	public function get_object_as_array() {
		$object_vars = array(
			'_answer' 					=> $this->getAnswer(),
			'_html' 					=> $this->isHtml(),
			'_points' 					=> $this->getPoints(),
			'_correct' 					=> $this->isCorrect(),
			'_sortString' 				=> $this->getSortString(),
			'_sortStringHtml' 			=> $this->isSortStringHtml(),
			'_graded' 					=> $this->isGraded(),
			'_gradingProgression' 		=> $this->getGradingProgression(),
			'_gradedType' 				=> $this->getGradedType()
		);
		
		return $object_vars;
	}

	public function set_array_to_object( $array_vars = array() ) {
		
		foreach( $array_vars as $key => $value ) {
			switch( $key ) {
	
				case '_answer':
					$this->setAnswer( $value );
					break;

				case '_html':
					$this->setHtml( $value );
					break;

				case '_points':
					$this->setPoints( $value );
					break;
	
				case '_correct':
					$this->setCorrect( $value );
					break;

				case '_sortString':
					$this->setSortString( $value );
					break;

				case '_sortStringHtml':
					$this->setSortStringHtml( $value );
					break;

				case '_graded':
					$this->setGraded( $value );
					break;

				case '_gradingProgression':
					$this->setGradingProgression( $value );
					break;

				case '_gradedType':
					$this->setGradedType( $value );
					break;

				default:
					break;
			}
		}

	}
}