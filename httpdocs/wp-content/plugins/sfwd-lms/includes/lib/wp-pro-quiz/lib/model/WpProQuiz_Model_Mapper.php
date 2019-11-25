<?php
class WpProQuiz_Model_Mapper {
	/**
	 * Wordpress Datenbank Object 
	 * @var wpdb
	 */
	protected $_wpdb;
	
	/**
	 * @var string
	 */
	protected $_prefix;
	
	/**
	 * @var string
	 */
	protected $_tableQuestion;
	protected $_tableMaster;
	protected $_tableLock;
	protected $_tableStatistic;
	protected $_tableToplist;
	protected $_tablePrerequisite;
	protected $_tableCategory;
	protected $_tableStatisticRef;
	protected $_tableForm;
	protected $_tableTemplate;
	
	// Our reference between the ProQuiz and the Quiz (post_type)
	protected $_quiz_post_id;
	
	function __construct() {
		global $wpdb;
		
		$this->_wpdb = $wpdb;
		//$this->_prefix = $wpdb->prefix . 'wp_pro_quiz_';
		$this->_prefix = LDLMS_DB::get_table_prefix( 'wpproquiz' );
		
		$this->_tableQuestion = LDLMS_DB::get_table_name( 'quiz_question' );
		$this->_tableMaster = LDLMS_DB::get_table_name( 'quiz_master' );
		$this->_tableLock = LDLMS_DB::get_table_name( 'quiz_lock' );
		$this->_tableStatistic = LDLMS_DB::get_table_name( 'quiz_statistic' );
		$this->_tableToplist = LDLMS_DB::get_table_name( 'quiz_toplist' );
		$this->_tablePrerequisite = LDLMS_DB::get_table_name( 'quiz_prerequisite' );
		$this->_tableCategory = LDLMS_DB::get_table_name( 'quiz_category' );
		$this->_tableStatisticRef = LDLMS_DB::get_table_name( 'quiz_statistic_ref' );
		$this->_tableForm = LDLMS_DB::get_table_name( 'quiz_form' );
		$this->_tableTemplate = LDLMS_DB::get_table_name( 'quiz_template' );
	}
	
	public function getInsertId() {
		return $this->_wpdb->insert_id;
	}
}