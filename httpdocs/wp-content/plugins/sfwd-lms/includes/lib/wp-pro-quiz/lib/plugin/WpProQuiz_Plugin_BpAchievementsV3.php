<?php
class WpProQuiz_Plugin_BpAchievementsV3 extends DPA_Extension {
	
	public function __construct() {
		$this->actions = array(
			'wp_pro_quiz_completed_quiz' => sprintf( esc_html_x('The user completed a %s.', 'The user completed a quiz.', 'learndash'), learndash_get_custom_label_lower( 'quiz' ) ),
			'wp_pro_quiz_completed_quiz_100_percent' => sprintf( esc_html_x('The user completed a %s with 100 percent.', 'The user completed a quiz with 100 percent.', 'learndash'), learndash_get_custom_label_lower( 'quiz' )),
		);
		
		$this->contributors = array(
			array(
				'name'         => 'Julius Fischer',
				'gravatar_url' => 'http://gravatar.com/avatar/c3736cd18c273f32569726c93f76244d',
				'profile_url'  => 'http://profiles.wordpress.org/xeno010',
			)
		);
		
		$this->description     = sprintf( esc_html_x('A powerful and beautiful %s plugin for WordPress.', 'A powerful and beautiful quiz plugin for WordPress.', 'learndash'), learndash_get_custom_label_lower( 'quiz' ) );
		$this->id              = 'wp-pro-quiz';
		$this->image_url       = WPPROQUIZ_URL.'/img/wp_pro_quiz.jpg';
		$this->name            = esc_html__( 'WP-Pro-Quiz', 'learndash' );
		//$this->rss_url         = '';
		$this->small_image_url = WPPROQUIZ_URL.'/img/wp_pro_quiz_small.jpg';
		$this->version         = 5;
		$this->wporg_url       = 'http://wordpress.org/extend/plugins/wp-pro-quiz/';
	}
	
	public function do_update($current_version) { 
		$this->insertTerm();
	}
	
	public function insertTerm() {
		$taxId = dpa_get_event_tax_id();
		
		foreach ($this->actions as $actionName => $desc) {
			$e = term_exists($actionName, $taxId);
			
			if($e === 0 || $e === null) {
				wp_insert_term($actionName, $taxId, array('description' => $desc));
			}
		}
	}
}