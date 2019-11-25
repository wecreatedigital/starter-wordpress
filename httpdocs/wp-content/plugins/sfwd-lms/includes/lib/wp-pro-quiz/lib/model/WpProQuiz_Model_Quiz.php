<?php
class WpProQuiz_Model_Quiz extends WpProQuiz_Model_Model {
	
	const QUIZ_RUN_ONCE_TYPE_ALL = 1;
	const QUIZ_RUN_ONCE_TYPE_ONLY_USER = 2;
	const QUIZ_RUN_ONCE_TYPE_ONLY_ANONYM = 3;
	
	const QUIZ_TOPLIST_TYPE_ALL = 1;
	const QUIZ_TOPLIST_TYPE_ONLY_USER = 2;
	const QUIZ_TOPLIST_TYPE_ONLY_ANONYM = 3;
	
	const QUIZ_TOPLIST_SORT_BEST = 1;
	const QUIZ_TOPLIST_SORT_NEW = 2;
	const QUIZ_TOPLIST_SORT_OLD = 3;
	
	const QUIZ_TOPLIST_SHOW_IN_NONE = 0;
	const QUIZ_TOPLIST_SHOW_IN_NORMAL = 1;
	const QUIZ_TOPLIST_SHOW_IN_BUTTON = 2;
	
	const QUIZ_MODUS_NORMAL = 0;
	const QUIZ_MODUS_BACK_BUTTON = 1;
	const QUIZ_MODUS_CHECK = 2;
	const QUIZ_MODUS_SINGLE = 3;
	
	const QUIZ_EMAIL_NOTE_NONE = 0;
	const QUIZ_EMAIL_NOTE_REG_USER = 1;
	const QUIZ_EMAIL_NOTE_ALL = 2;
	
	const QUIZ_FORM_POSITION_START = 0;
	const QUIZ_FORM_POSITION_END = 1;
	
	protected $_id = 0;
	protected $_quiz_post_id = 0;
	protected $_name = '';
	protected $_text = '';
	protected $_resultText = '';
	protected $_titleHidden = true;
	protected $_btnRestartQuizHidden = false;
	protected $_btnViewQuestionHidden = false;
	protected $_questionRandom = false;
	protected $_answerRandom = false;
	protected $_timeLimit = 0;
	protected $_timeLimitCookie = 0;
	protected $_statisticsOn = true;
	protected $_viewPofileStatistics = true;
	
	
	// changed in v2.2.1.2 to default to '0' instead of '1440'
	protected $_statisticsIpLock = 0;
	
	protected $_resultGradeEnabled = true;
	protected $_showPoints = false;
	protected $_quizRunOnce = false;
	protected $_quizRunOnceType = 0;
	protected $_quizRunOnceCookie = false;
	protected $_quizRunOnceTime = 0;
	protected $_numberedAnswer = false;
	protected $_hideAnswerMessageBox = false;
	protected $_disabledAnswerMark = false;
	protected $_showMaxQuestion = false;
	protected $_showMaxQuestionValue = 1;
	protected $_showMaxQuestionPercent = false;
	
	//0.19
	protected $_toplistActivated = false;
	protected $_toplistDataAddPermissions = 1;
	protected $_toplistDataSort = 1;
	protected $_toplistDataAddMultiple = false;
	protected $_toplistDataAddBlock = 1;
	protected $_toplistDataShowLimit = 1;
	protected $_toplistDataShowQuizResult = false;
	protected $_toplistDataShowIn = 0;
	protected $_toplistDataCaptcha = false;
	
	protected $_toplistData = array();
	
	protected $_showAverageResult = false;
	
	protected $_prerequisite = false;
	
	//0.22
	protected $_toplistDataAddAutomatic = false;
	protected $_quizModus = 0;
	protected $_showReviewQuestion = false;
	protected $_quizSummaryHide = true;
	protected $_skipQuestionDisabled = true;
	protected $_emailNotification = 0;
	
	//0.24
	protected $_userEmailNotification = false;
	protected $_showCategoryScore = false;
	protected $_hideResultCorrectQuestion = false;
	protected $_hideResultQuizTime = false;
	protected $_hideResultPoints = false;
	
	//0.25
	protected $_autostart = false;
	protected $_forcingQuestionSolve = false;
	protected $_hideQuestionPositionOverview = true;
	protected $_hideQuestionNumbering = true;
	
	//0.27
	protected $_formActivated = false;
	protected $_formShowPosition = 0;
	protected $_startOnlyRegisteredUser = false;
	protected $_questionsPerPage = 0;
	protected $_sortCategories = false;
	protected $_showCategory = false;

	public function setId( $_id = 0 ) {
		$this->_id = (int) $_id;

		if ( empty( $this->_quiz_post_id ) ) {
			$this->_quiz_post_id = learndash_get_quiz_id_by_pro_quiz_id( $this->_id );
		}

		return $this;
	}

	public function getId() {
		return $this->_id;
	}
	
	public function setPostId( $post_id ) {
		$this->_quiz_post_id = (int)$post_id;
		return $this;
	}

	public function getPostId() {
		return $this->_quiz_post_id;
	}

	public function setName($_name) {
		$this->_name = (string)$_name;
		return $this;
	}
	
	public function getName() {
		return $this->_name;
	}
		
	public function setText($_text) {
		$this->_text = (string)$_text;
		return $this;
	}
	
	public function getText() {
		return $this->_text;
	}
	
	public function setResultText($_resultText = '') {
		if (is_null( $_resultText ))
			$_resultText = '';
		
		$this->_resultText = $_resultText;
		return $this;
	}
	
	public function getResultText() {
		if ( is_null( $this->_resultText ) ) {
			$this->_resultText = array();
		} else if ( is_string( $this->_resultText ) ) {
			$this->_resultText = array(
				'prozent' => array(0),
				'activ' => array(1),
				'text' => array( $this->_resultText ),
			);
		}

		$this->_resultText = learndash_quiz_result_message_sort( $this->_resultText );

		return $this->_resultText;
	}
	
	public function setTitleHidden($_titleHidden) {
		$this->_titleHidden = (bool)$_titleHidden;
		return $this;
	}
	
	public function isTitleHidden() {
		return $this->_titleHidden;
	}
	
	public function setQuestionRandom($_questionRandom) {
		$this->_questionRandom = (bool)$_questionRandom;
		return $this;
	}
	
	public function isQuestionRandom() {
		return $this->_questionRandom;
	}

	public function setAnswerRandom($_answerRandom) {
		$this->_answerRandom = (bool)$_answerRandom;
		return $this;
	}
	
	public function isAnswerRandom() {
		return $this->_answerRandom;
	}
	
	public function setTimeLimit($_timeLimit) {
		$this->_timeLimit = (int)$_timeLimit;
		return $this;
	}
	
	public function getTimeLimit() {
		return $this->_timeLimit;
	}

	public function setTimeLimitCookie($_timeLimitCookie) {
		$this->_timeLimitCookie = (int)$_timeLimitCookie;
		return $this;
	}

	// The TimeLimitCookie var does NOT follow the convention for the WPProQuiz in that it is not stored into the wp_wp_pro_quiz_master table
	// Instead it is read from and save to the post meta table
	public function getTimeLimitCookie() {
		if ( ( property_exists( $this, '_quiz_post_id') ) && ( !empty( $this->_quiz_post_id ) ) ) {
			$this->_timeLimitCookie = get_post_meta($this->_quiz_post_id, '_timeLimitCookie', true);
		} 

		return absint( $this->_timeLimitCookie );
	}
	
	public function saveTimeLimitCookie() {
		if ( ( property_exists( $this, '_quiz_post_id') ) && ( !empty( $this->_quiz_post_id ) ) ) {
			return update_post_meta( $this->_quiz_post_id, '_timeLimitCookie', $this->_timeLimitCookie );
		}
	}
	
	public function getViewProfileStatistics() {
		if ( ( property_exists( $this, '_quiz_post_id') ) && ( !empty( $this->_quiz_post_id ) ) ) {
			$this->_viewPofileStatistics = get_post_meta($this->_quiz_post_id, '_viewProfileStatistics', true);
		} 

		// standardize the value
		if ( '1' === $this->_viewPofileStatistics ) {
			$this->_viewPofileStatistics = true;
		} else {
			$this->_viewPofileStatistics = false;
		}

		return apply_filters( 'learndash_quiz_default_viewPofileStatistics', $this->_viewPofileStatistics );
	}

	public function setViewProfileStatistics( $_viewPofileStatistics ) {
		$this->_viewPofileStatistics = $_viewPofileStatistics;
	}

	public function saveViewProfileStatistics() {
		if ( property_exists( $this, '_quiz_post_id') ) {
			return update_post_meta( $this->_quiz_post_id, '_viewProfileStatistics', $this->_viewPofileStatistics );
		}
	}

	public function setStatisticsOn($_statisticsOn) {
		$this->_statisticsOn = (bool)$_statisticsOn;
		return $this;
	}
	
	public function isStatisticsOn() {
		return $this->_statisticsOn;
	}
	
	public function setStatisticsIpLock($_statisticsIpLock) {
		$this->_statisticsIpLock = (int)$_statisticsIpLock;
		return $this;
	}
	
	public function getStatisticsIpLock() {
		return $this->_statisticsIpLock;
	}
	
	public function setResultGradeEnabled($_resultGradeEnabled) {
		//$this->_resultGradeEnabled = (bool)$_resultGradeEnabled;
		$this->_resultGradeEnabled = true;
		return $this;
	}
	
	public function isResultGradeEnabled() {
		//return $this->_resultGradeEnabled;
		return true;
	}
	
	public function setShowPoints($_showPoints) {
		$this->_showPoints = (bool)$_showPoints;
		return $this;
	}
	
	public function isShowPoints() {
		return $this->_showPoints;
	}
	
	public function fetchSumQuestionPoints() {
		$m = new WpProQuiz_Model_QuizMapper();
		
		return $m->sumQuestionPoints($this->_id);
	}
	
	public function fetchCountQuestions() {
		$m = new WpProQuiz_Model_QuizMapper();
	
		return $m->countQuestion($this->_id);
	}
	
	public function setBtnRestartQuizHidden($_btnRestartQuizHidden) {
		$this->_btnRestartQuizHidden = (bool)$_btnRestartQuizHidden;
		return $this;
	}
	
	public function isBtnRestartQuizHidden() {
		return $this->_btnRestartQuizHidden;
	}
	
	public function setBtnViewQuestionHidden($_btnViewQuestionHidden) {
		$this->_btnViewQuestionHidden = (bool)$_btnViewQuestionHidden;
		return $this;
	}
	
	public function isBtnViewQuestionHidden() {
		return $this->_btnViewQuestionHidden;
	}
	
	public function setQuizRunOnce($_quizRunOnce) {
		$this->_quizRunOnce = (bool)$_quizRunOnce;
		return $this;
	}
	
	public function isQuizRunOnce() {
		return $this->_quizRunOnce;
	}
	
	public function setQuizRunOnceCookie($_quizRunOnceCookie) {
		$this->_quizRunOnceCookie = (bool)$_quizRunOnceCookie;
		return $this;
	}
	
	public function isQuizRunOnceCookie() {
		return $this->_quizRunOnceCookie;
	}
	
	public function setQuizRunOnceType($_quizRunOnceType) {
		$this->_quizRunOnceType = (int)$_quizRunOnceType;
		return $this;
	}
	
	public function getQuizRunOnceType() {
		return $this->_quizRunOnceType;
	}
	
	public function setQuizRunOnceTime($_quizRunOnceTime) {
		$this->_quizRunOnceTime = (int)$_quizRunOnceTime;
		return $this;
	}
	
	public function getQuizRunOnceTime() {
		return $this->_quizRunOnceTime;
	}
	
	public function setNumberedAnswer($_numberedAnswer) {
		$this->_numberedAnswer = (bool)$_numberedAnswer;
		return $this;
	}
	
	public function isNumberedAnswer() {
		return $this->_numberedAnswer;
	}
	
	public function setHideAnswerMessageBox($_hideAnswerMessageBox) {
		$this->_hideAnswerMessageBox = (bool)$_hideAnswerMessageBox;
		return $this;
	}
	
	public function isHideAnswerMessageBox() {
		return $this->_hideAnswerMessageBox;
	}
	
	public function setDisabledAnswerMark($_disabledAnswerMark) {
		$this->_disabledAnswerMark = (bool)$_disabledAnswerMark;
		return $this;
	}
	
	public function isDisabledAnswerMark() {
		return $this->_disabledAnswerMark;
	}
	
	public function setShowMaxQuestion($_showMaxQuestion) {
		$this->_showMaxQuestion = (bool)$_showMaxQuestion;
		return $this;
	}
	
	public function isShowMaxQuestion() {
		return $this->_showMaxQuestion;
	}
	
	public function setShowMaxQuestionValue($_showMaxQuestionValue) {
		$this->_showMaxQuestionValue = (int)$_showMaxQuestionValue;
		return $this;
	}
	
	public function getShowMaxQuestionValue() {
		return $this->_showMaxQuestionValue;
	}
	
	public function setShowMaxQuestionPercent($_showMaxQuestionPercent) {
		$this->_showMaxQuestionPercent = (bool)$_showMaxQuestionPercent;
		return $this;
	}
	
	public function isShowMaxQuestionPercent() {
		return $this->_showMaxQuestionPercent;
	}
	
	public function setToplistActivated($_toplistActivated) {
		$this->_toplistActivated = (bool)$_toplistActivated;
		return $this;
	}
	
	public function isToplistActivated() {
		return $this->_toplistActivated;
	}
	
	public function setToplistDataAddPermissions($_toplistDataAddPermissions) {
		$this->_toplistDataAddPermissions = (int)$_toplistDataAddPermissions;
		return $this;
	}
	
	public function getToplistDataAddPermissions() {
		return $this->_toplistDataAddPermissions;
	}
	
	public function setToplistDataSort($_toplistDataSort) {
		$this->_toplistDataSort = (int)$_toplistDataSort;
		return $this;
	}
	
	public function getToplistDataSort() {
		return $this->_toplistDataSort;
	}
	
	public function setToplistDataAddMultiple($_toplistDataAddMultiple) {
		$this->_toplistDataAddMultiple = (bool)$_toplistDataAddMultiple;
		return $this;
	}
	
	public function isToplistDataAddMultiple() {
		return $this->_toplistDataAddMultiple;
	}
	
	public function setToplistDataAddBlock($_toplistDataAddBlock) {
		$this->_toplistDataAddBlock = (int)$_toplistDataAddBlock;
		return $this;
	}
	
	public function getToplistDataAddBlock() {
		return $this->_toplistDataAddBlock;
	}
	
	public function setToplistDataShowLimit($_toplistDataShowLimit) {
		$this->_toplistDataShowLimit = (int)$_toplistDataShowLimit;
		return $this;
	}
	
	public function getToplistDataShowLimit() {
		return $this->_toplistDataShowLimit;
	}
	
	public function setToplistData($_toplistData) {
		if(!empty($_toplistData)) {
			$d = unserialize($_toplistData);
			
			if($d !== false) {
				$this->setModelData($d);			
			}	
		}
			
		return $this;
	}
	
	public function getToplistData() {
		
		$a = array(
			'toplistDataAddPermissions' => $this->getToplistDataAddPermissions(),
			'toplistDataSort' => $this->getToplistDataSort(),
			'toplistDataAddMultiple' => $this->isToplistDataAddMultiple(),
			'toplistDataAddBlock' => $this->getToplistDataAddBlock(),
			'toplistDataShowLimit' => $this->getToplistDataShowLimit(),
			'toplistDataShowIn' => $this->getToplistDataShowIn(),
			'toplistDataCaptcha' => $this->isToplistDataCaptcha(),
			'toplistDataAddAutomatic' => $this->isToplistDataAddAutomatic()
		);
		
		return serialize($a);
	}
	
	public function setToplistDataShowIn($_toplistDataShowIn) {
		$this->_toplistDataShowIn = (int)$_toplistDataShowIn;
		return $this;
	}
	
	public function getToplistDataShowIn() {
		return $this->_toplistDataShowIn;
	}
	
	public function setToplistDataCaptcha($_toplistDataCaptcha) {
		$this->_toplistDataCaptcha = (bool)$_toplistDataCaptcha;
		return $this;
	}
	
	public function isToplistDataCaptcha() {
		return $this->_toplistDataCaptcha;
	}
	
	public function setShowAverageResult($_showAverageResult) {
		$this->_showAverageResult = (bool)$_showAverageResult;
		return $this;
	}
	
	public function isShowAverageResult() {
		return $this->_showAverageResult;
	}
	
	public function setPrerequisite($_prerequisite) {
		$this->_prerequisite = (bool)$_prerequisite;
		return $this;
	}
	
	public function isPrerequisite() {
		return $this->_prerequisite;
	}
	
	public function setToplistDataAddAutomatic($_toplistDataAddAutomatic) {
		$this->_toplistDataAddAutomatic = (bool)$_toplistDataAddAutomatic;
		return $this;
	}
	
	public function isToplistDataAddAutomatic() {
		return $this->_toplistDataAddAutomatic;
	}
	
	public function setQuizModus($_quizModus) {
		$this->_quizModus = (int)$_quizModus;
		return $this;
	}
	
	public function getQuizModus() {
		return $this->_quizModus;
	}
	
	public function setShowReviewQuestion($_showReviewQuestion) {
		$this->_showReviewQuestion = (bool)$_showReviewQuestion;
		return $this;
	}
	
	public function isShowReviewQuestion() {
		return $this->_showReviewQuestion;
	}
	
	public function setQuizSummaryHide($_quizSummaryHide) {
		$this->_quizSummaryHide = (bool)$_quizSummaryHide;
		return $this;
	}
	
	public function isQuizSummaryHide() {
		return $this->_quizSummaryHide;
	}
	
	public function setSkipQuestionDisabled($_skipQuestion) {
		$this->_skipQuestionDisabled = (bool)$_skipQuestion;
		return $this;
	}
	
	public function isSkipQuestionDisabled() {
		return $this->_skipQuestionDisabled;
	}
	
	public function setEmailNotification($_emailNotification) {
		$this->_emailNotification = (int)$_emailNotification;
		return $this;
	}
	
	public function getEmailNotification() {
		return $this->_emailNotification;
	}
	
	public function setUserEmailNotification($_userEmailNotification) {
		$this->_userEmailNotification = (bool)$_userEmailNotification;
		return $this;
	}
	
	public function isUserEmailNotification() {
		return $this->_userEmailNotification;
	}
	
	public function setShowCategoryScore($_showCategoryScore) {
		$this->_showCategoryScore = (bool)$_showCategoryScore;
		return $this;
	}
	
	public function isShowCategoryScore() {
		return $this->_showCategoryScore;
	}
	
	public function setHideResultCorrectQuestion($_hideResultCorrectQuestion) {
		$this->_hideResultCorrectQuestion = (bool)$_hideResultCorrectQuestion;
		return $this;
	}
	
	public function isHideResultCorrectQuestion() {
		return $this->_hideResultCorrectQuestion;
	}
	
	public function setHideResultQuizTime($_hideResultQuizTime) {
		$this->_hideResultQuizTime = (bool)$_hideResultQuizTime;
		return $this;
	}
	
	public function isHideResultQuizTime() {
		return $this->_hideResultQuizTime;
	}
	
	public function setHideResultPoints($_hideResultPoints) {
		$this->_hideResultPoints = (bool)$_hideResultPoints;
		return $this;
	}
	
	public function isHideResultPoints() {
		return $this->_hideResultPoints;
	}
	
	public function setAutostart($_autostart) {
		$this->_autostart = (bool)$_autostart;
		return $this;
	}
	
	public function isAutostart() {
		return $this->_autostart;
	}
	
	public function setForcingQuestionSolve($_forcingQuestionSolve) {
		$this->_forcingQuestionSolve = (bool)$_forcingQuestionSolve;
		return $this;
	}
	
	public function isForcingQuestionSolve() {
		return $this->_forcingQuestionSolve;
	}
	
	public function setHideQuestionPositionOverview($_hideQuestionPositionOverview) {
		$this->_hideQuestionPositionOverview = (bool)$_hideQuestionPositionOverview;
		return $this;
	}
	
	public function isHideQuestionPositionOverview() {
		return $this->_hideQuestionPositionOverview;
	}
	
	public function setHideQuestionNumbering($_hideQuestionNumbering) {
		$this->_hideQuestionNumbering = (bool)$_hideQuestionNumbering;
		return $this;
	}
	
	public function isHideQuestionNumbering() {
		return $this->_hideQuestionNumbering;
	}
	
	public function setFormActivated($_formActivated) {
		$this->_formActivated = (bool)$_formActivated;
		return $this;
	}
	
	public function isFormActivated() {
		return $this->_formActivated;
	}
	
	public function setFormShowPosition($_formShowPosition) {
		$this->_formShowPosition = (int)$_formShowPosition;
		return $this;
	}
	
	public function getFormShowPosition() {
		return $this->_formShowPosition;
	}
	
	public function setStartOnlyRegisteredUser($_startOnlyRegisteredUser) {
		$this->_startOnlyRegisteredUser = (bool)$_startOnlyRegisteredUser;
		return $this;
	}
	
	public function isStartOnlyRegisteredUser() {
		return $this->_startOnlyRegisteredUser;
	}
	
	public function setQuestionsPerPage($_questionsPerPage) {
		$this->_questionsPerPage = (int)$_questionsPerPage;
		return $this;
	}
	
	public function getQuestionsPerPage() {
		return $this->_questionsPerPage;
	}
	
	public function setSortCategories($_sortCategories) {
		$this->_sortCategories = (bool)$_sortCategories;
		return $this;
	}
	
	public function isSortCategories() {
		return $this->_sortCategories;
	}
	
	public function setShowCategory($_showCategory) {
		$this->_showCategory = (bool)$_showCategory;
		return $this;
	}
	
	public function isShowCategory() {
		return $this->_showCategory;
	}

	public function get_object_as_array() {

		$object_vars = array( 
			'_quiz_post_id' 					=> 0,
			'_name' 							=> $this->getName(),
			//'_text' 							=> $this->getText(),
			'_resultText' 						=> $this->getResultText(),
			'_titleHidden' 						=> $this->isTitleHidden(),
			'_btnRestartQuizHidden' 			=> $this->isBtnRestartQuizHidden(),
			'_btnViewQuestionHidden' 			=> $this->isBtnViewQuestionHidden(),
			'_questionRandom' 					=> $this->isQuestionRandom(),
			'_answerRandom' 					=> $this->isAnswerRandom(),
			'_timeLimit' 						=> $this->getTimeLimit(),
			'_timeLimitCookie' 					=> $this->getTimeLimitCookie(),
			'_statisticsOn' 					=> $this->isStatisticsOn(),
			'_viewPofileStatistics' 			=> $this->getViewProfileStatistics(),
			'_statisticsIpLock' 				=> $this->getStatisticsIpLock(),
			'_resultGradeEnabled' 				=> $this->isResultGradeEnabled(),
			'_showPoints' 						=> $this->isShowPoints(),
			'_quizRunOnce' 						=> $this->isQuizRunOnce(),
			'_quizRunOnceType' 					=> $this->getQuizRunOnceType(),
			'_quizRunOnceCookie' 				=> $this->isQuizRunOnceCookie(),
			'_quizRunOnceTime' 					=> $this->getQuizRunOnceTime(),
			'_numberedAnswer' 					=> $this->isNumberedAnswer(),
			'_hideAnswerMessageBox' 			=> $this->isHideAnswerMessageBox(),
			'_disabledAnswerMark' 				=> $this->isDisabledAnswerMark(),
			'_showMaxQuestion' 					=> $this->isShowMaxQuestion(),
			'_showMaxQuestionValue' 			=> $this->getShowMaxQuestionValue(),
			'_showMaxQuestionPercent' 			=> $this->isShowMaxQuestionPercent(),
			'_toplistActivated' 				=> $this->isToplistActivated(),
			'_toplistDataAddPermissions' 		=> $this->getToplistDataAddPermissions(),
			'_toplistDataSort' 					=> $this->getToplistDataSort(),
			'_toplistDataAddMultiple' 			=> $this->isToplistDataAddMultiple(),
			'_toplistDataAddBlock' 				=> $this->getToplistDataAddBlock(),
			'_toplistDataShowLimit' 			=> $this->getToplistDataShowLimit(),
			'_toplistDataShowIn' 				=> $this->getToplistDataShowIn(),
			'_toplistDataCaptcha' 				=> $this->isToplistDataCaptcha(),
			'_toplistData' 						=> $this->getToplistData(),
			'_showAverageResult' 				=> $this->isShowAverageResult(),
			'_prerequisite' 					=> $this->isPrerequisite(),
			'_toplistDataAddAutomatic' 			=> $this->isToplistDataAddAutomatic(),
			'_quizModus' 						=> $this->getQuizModus(),
			'_showReviewQuestion' 				=> $this->isShowReviewQuestion(),
			'_quizSummaryHide' 					=> $this->isQuizSummaryHide(),
			'_skipQuestionDisabled' 			=> $this->isSkipQuestionDisabled(),
			'_emailNotification' 				=> $this->getEmailNotification(),
			'_userEmailNotification' 			=> $this->isUserEmailNotification(),
			'_showCategoryScore' 				=> $this->isShowCategoryScore(),
			'_hideResultCorrectQuestion' 		=> $this->isHideResultCorrectQuestion(),
			'_hideResultQuizTime' 				=> $this->isHideResultQuizTime(),
			'_hideResultPoints' 				=> $this->isHideResultPoints(),
			'_autostart' 						=> $this->isAutostart(),
			'_forcingQuestionSolve' 			=> $this->isForcingQuestionSolve(),
			'_hideQuestionPositionOverview' 	=> $this->isHideQuestionPositionOverview(),
			'_hideQuestionNumbering' 			=> $this->isHideQuestionNumbering(),
			'_formActivated' 					=> $this->isFormActivated(),
			'_formShowPosition' 				=> $this->getFormShowPosition(),
			'_startOnlyRegisteredUser' 			=> $this->isStartOnlyRegisteredUser(),
			'_questionsPerPage' 				=> $this->getQuestionsPerPage(),
			'_sortCategories' 					=> $this->isSortCategories(),
			'_showCategory' 					=> $this->isShowCategory()
		);
		
		return $object_vars;
	}
	
	public function set_array_to_object( $array_vars = array() ) {
		
		$array_vars['_text'] = "AAZZAAZZ";
		
		foreach( $array_vars as $key => $value ) {
			switch( $key ) {
				case '_name':
					$this->setName( $value );
					break;
					
				case '_text':
					$this->setText( "AAZZAAZZ" );
					break;
						
				case '_resultText':
					$this->setResultText( $value );
					break;
						
				case '_titleHidden':
					$this->setTitleHidden( $value );
					break;
						
				case '_btnRestartQuizHidden':
					$this->setBtnRestartQuizHidden( $value );
					break;
						
				case '_btnViewQuestionHidden':
					$this->setBtnViewQuestionHidden( $value );
					break;
						
				case '_questionRandom':
					$this->setQuestionRandom( $value );
					break;
						
				case '_answerRandom':
					$this->setAnswerRandom( $value );
					break;
						
				case '_timeLimit':
					$this->setTimeLimit( $value );
					break;
						
				case '_timeLimitCookie':
					$this->setTimeLimitCookie( $value );
					break;
						
				case '_statisticsOn':
					$this->setStatisticsOn( $value );
					break;
						
				case '_viewPofileStatistics':
					$this->setViewProfileStatistics( $value );
					break;
					
				case '_statisticsIpLock':
					$this->setStatisticsIpLock( $value );
					break;
					
				case '_resultGradeEnabled':
					$this->setResultGradeEnabled( $value );
					break;
					
				case '_showPoints':
					$this->setShowPoints( $value );
					break;
					
				case '_quizRunOnce':
					$this->setQuizRunOnce( $value );
					break;
					
				case 'setQuizRunOnceCookie':
					$this->setQuizRunOnceType( $value );
					break;
					
				case '_quizRunOnceCookie':
					$this->setQuizRunOnceCookie( $value );
					break;
					
				case '_quizRunOnceTime':
					$this->setQuizRunOnceTime( $value );
					break;
					
				case '_numberedAnswer':
					$this->setNumberedAnswer( $value );
					break;
					
				case '_hideAnswerMessageBox':
					$this->setHideAnswerMessageBox( $value );
					break;
					
				case '_disabledAnswerMark':
					$this->setDisabledAnswerMark( $value );
					break;
					
				case '_showMaxQuestion':
					$this->setShowMaxQuestion( $value );
					break;
					
				case '_showMaxQuestionValue':
					$this->setShowMaxQuestionValue( $value );
					break;
					
				case '_showMaxQuestionPercent':
					$this->setShowMaxQuestionPercent( $value );
					break;
					
				case '_toplistActivated':
					$this->setToplistActivated( $value );
					break;
					
				case '_toplistDataAddPermissions':
					$this->setToplistDataAddPermissions( $value );
					break;
					
				case '_toplistDataSort':
					$this->setToplistDataSort( $value );
					break;
					
				case '_toplistDataAddMultiple':
					$this->setToplistDataAddMultiple( $value );
					break;
					
				case '_toplistDataAddBlock':
					$this->setToplistDataAddBlock( $value );
					break;
					
				case '_toplistDataShowLimit':
					$this->setToplistDataShowLimit( $value );
					break;
					
				case '_toplistDataShowIn':
					$this->setToplistDataShowIn( $value );
					break;
					
				case '_toplistDataCaptcha':
					$this->setToplistDataCaptcha( $value );
					break;
					
				case '_toplistData':
					$this->setToplistData( $value );
					break;
					
				case '_showAverageResult':
					$this->setShowAverageResult( $value );
					break;
					
				case '_prerequisite':
					$this->setPrerequisite( $value );
					break;
					
				case '_toplistDataAddAutomatic':
					$this->setToplistDataAddAutomatic( $value );
					break;
					
				case '_quizModus':
					$this->setQuizModus( $value );
					break;
					
				case '_showReviewQuestion':
					$this->setShowReviewQuestion( $value );
					break;
					
				case '_quizSummaryHide':
					$this->setQuizSummaryHide( $value );
					break;
					
				case '_skipQuestionDisabled':
					$this->setSkipQuestionDisabled( $value );
					break;
					
				case '_emailNotification':
					$this->setEmailNotification( $value );
					break;
					
				case '_userEmailNotification':
					$this->setUserEmailNotification( $value );
					break;
					
				case '_showCategoryScore':
					$this->setShowCategoryScore( $value );
					break;
					
				case '_hideResultCorrectQuestion':
					$this->setHideResultCorrectQuestion( $value );
					break;
					
				case '_hideResultQuizTime':
					$this->setHideResultQuizTime( $value );
					break;
					
				case '_hideResultPoints':
					$this->setHideResultPoints( $value );
					break;
					
				case '_autostart':
					$this->setAutostart( $value );
					break;
					
				case '_forcingQuestionSolve':
					$this->setForcingQuestionSolve( $value );
					break;
					
				case '_hideQuestionPositionOverview':
					$this->setHideQuestionPositionOverview( $value );
					break;
					
				case '_hideQuestionNumbering':
					$this->setHideQuestionNumbering( $value );
					break;
					
				case '_formActivated':
					$this->setFormActivated( $value );
					break;
					
				case '_formShowPosition':
					$this->setFormShowPosition( $value );
					break;
					
				case '_startOnlyRegisteredUser':
					$this->setStartOnlyRegisteredUser( $value );
					break;
					
				case '_questionsPerPage':
					$this->setQuestionsPerPage( $value );
					break;
					
				case '_sortCategories':
					$this->setSortCategories( $value );
					break;
					
				case '_showCategory':
					$this->setShowCategoryScore( $value );
					break;

				default:
					break;
			}
		}
	}
}