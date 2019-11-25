<?php
class WpProQuiz_Model_Statistic extends WpProQuiz_Model_Model {

	protected $_statisticRefId = 0;
	protected $_questionId = 0;
	protected $_correctCount = 0;
	protected $_incorrectCount = 0;
	protected $_hintCount = 0;
	protected $_points = 0;
	protected $_questionTime = 0;
	protected $_answerData = null;

	public function setStatisticRefId($_statisticRefId) {
		$this->_statisticRefId = (int)$_statisticRefId;
		return $this;
	}

	public function getStatisticRefId() {
		return $this->_statisticRefId;
	}

	public function setQuestionId($_questionId) {
		$this->_questionId = (int)$_questionId;
		return $this;
	}

	public function getQuestionId() {
		return $this->_questionId;
	}

	public function setCorrectCount($_correctCount) {
		$this->_correctCount = (int)$_correctCount;
		return $this;
	}

	public function getCorrectCount() {
		return $this->_correctCount;
	}

	public function setIncorrectCount($_incorrectCount) {
		$this->_incorrectCount = (int)$_incorrectCount;
		return $this;
	}

	public function getIncorrectCount() {
		return $this->_incorrectCount;
	}

	public function setHintCount($_hintCount) {
		$this->_hintCount = (int)$_hintCount;
		return $this;
	}

	public function getHintCount() {
		return $this->_hintCount;
	}

	public function setPoints($_points) {
		$this->_points = (int)$_points;
		return $this;
	}

	public function getPoints() {
		return $this->_points;
	}

	public function setQuestionTime($_questionTime) {
		$this->_questionTime = (int)$_questionTime;
		return $this;
	}

	public function getQuestionTime() {
		return $this->_questionTime;
	}

	public function setAnswerData($_answerData) {
		$this->_answerData = $_answerData;
		return $this;
	}
	
	public function getAnswerData() {
		return $this->_answerData;
	}
	
	public function get_object_as_array() {

		$object_vars = array(
			'_statisticRefId'					=> $this->getStatisticRefId(),
			'_questionId' 						=> $this->getQuestionId(),
			'_correctCount'						=> $this->getCorrectCount(),
			'_incorrectCount'					=> $this->getIncorrectCount(),
			'_hintCount'						=> $this->getHintCount(),
			'_points'							=> $this->getPoints(),
			'_questionTime'						=> $this->getQuestionTime(),
			'_answerData'						=> $this->getAnswerData()
		);

		return $object_vars;
	}

	public function set_array_to_object( $array_vars = array() ) {

		foreach( $array_vars as $key => $value ) {
			switch( $key ) {

				case '_statisticRefId':
					$this->setStatisticRefId( $value );
					break;
					
				case '_questionId':
					$this->setQuestionId( $value );
					break;
					
				case '_correctCount':
					$this->setCorrectCount( $value );
					break;
					
				case '_incorrectCount':
					$this->setIncorrectCount( $value );
					break;
					
				case '_hintCount':
					$this->setHintCount( $value );
					break;
					
				case '_points':
					$this->setPoints( $value );
					break;
					
				case '_questionTime':
					$this->setQuestionTime( $value );
					break;
					
				case '_answerData':
					$this->setAnswerData( $value );
			}	
		}
	}
	
}