<?php
class WpProQuiz_Model_CategoryMapper extends WpProQuiz_Model_Mapper {
	
	public function fetchAll() {
		$r = array();
		
		$results = $this->_wpdb->get_results("SELECT * FROM {$this->_tableCategory}", ARRAY_A);
		
		foreach ($results as $row) {
			$r[] =  new WpProQuiz_Model_Category($row);
		}
		
		return $r;
	}
	
	public function fetchByQuiz( $quizId ) {
		$r = array();

		if ( is_a( $quizId, 'WpProQuiz_Model_Quiz' ) ) {
			$quiz = $quizId;
			$quizId = $quiz->getId();
			if ( empty( $quiz_post_id ) ) {
				$quiz_post_id = $quiz->getPostId();
			}
		}

		if ( ( ! empty( $quiz_post_id ) ) && ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Quizzes_Builder', 'enabled' ) === 'yes' ) && ( true === is_data_upgrade_quiz_questions_updated() ) ) {
			$ld_quiz_questions_object = LDLMS_Factory_Post::quiz_questions( intval( $quiz_post_id ) );
			if ( $ld_quiz_questions_object ) {
				$quiz_questions = $ld_quiz_questions_object->get_questions();

				if ( ! empty( $quiz_questions ) ) {
					$cat_sql_str = 'SELECT c.* FROM ' . $this->_tableCategory . ' AS c RIGHT JOIN ' . $this->_tableQuestion . ' AS q ON c.category_id = q.category_id WHERE q.id IN ('. implode( ',', $quiz_questions ) . ') GROUP BY q.category_id ORDER BY c.category_name';
					$results = $this->_wpdb->get_results( $cat_sql_str, ARRAY_A );
				}
			}
		} else {

			$results = $this->_wpdb->get_results($this->_wpdb->prepare('
				SELECT 
					c.*
				FROM
					'.$this->_tableCategory.' AS c
					RIGHT JOIN '.$this->_tableQuestion.' AS q
							ON c.category_id = q.category_id
				WHERE
					q.quiz_id = %d
				GROUP BY
					q.category_id
				ORDER BY
					c.category_name
			', $quizId), ARRAY_A);
		}

		foreach($results as $row) {
			$r[] = new WpProQuiz_Model_Category($row);
		}
		
		return $r;
	}
	
	public function save(WpProQuiz_Model_Category $category) {
		$data = array('category_name' => $category->getCategoryName());
		$format = array('%s');
		
		if($category->getCategoryId() == 0) {
			$this->_wpdb->insert($this->_tableCategory, $data, $format);
			$category->setCategoryId($this->_wpdb->insert_id);
		} else {
			$this->_wpdb->update(
				$this->_tableCategory, 
				$data, 
				array('category_id' => $category->getCategoryId()),
				$format,
				array('%d'));
		}
		
		return $category;
	}
	
	public function updateCatgoryName($categoryId, $name) {
		return $this->_wpdb->update(
			$this->_tableCategory, 
			array(
				'category_name' => $name
			), 
			array(
				'category_id' => $categoryId
			), 
			array('%s'), array('%d')
		);
	}

	public function delete($categoryId) {
		$this->_wpdb->update($this->_tableQuestion, array('category_id' => 0), array('category_id' => $categoryId), array('%d'), array('%d'));
		
		return $this->_wpdb->delete($this->_tableCategory, array('category_id' => $categoryId), array('%d'));
	}
	
	public function getCategoryArrayForImport() {
		$r = array();
		
		$results = $this->_wpdb->get_results("SELECT * FROM {$this->_tableCategory}", ARRAY_A);
		
		foreach ($results as $row) {
			$r[strtolower($row['category_name'])] = (int)$row['category_id'];
		}
		
		return $r;
	}

	public function fetchById($categoryId, $loadData = true) {
		$row = $this->_wpdb->get_row(
			$this->_wpdb->prepare(
				"SELECT * FROM {$this->_tableCategory} WHERE category_id = %d ", $categoryId
			),
			ARRAY_A
		);

		if ( $row !== null ) {
			$category = new WpProQuiz_Model_Category( $row );
			return $category;
		}
		
		return new WpProQuiz_Model_Category();
	}

	public function fetchByName( $categoryName = '' ) {
		if ( ! empty( $categoryName ) ) {
			$row = $this->_wpdb->get_row(
				$this->_wpdb->prepare(
					"SELECT * FROM {$this->_tableCategory} WHERE category_name = %s ", $categoryName
				),
				ARRAY_A
			);

			if ( $row !== null ) {
				$category = new WpProQuiz_Model_Category( $row );
				return $category;
			}
		}
		return new WpProQuiz_Model_Category();
	}
}