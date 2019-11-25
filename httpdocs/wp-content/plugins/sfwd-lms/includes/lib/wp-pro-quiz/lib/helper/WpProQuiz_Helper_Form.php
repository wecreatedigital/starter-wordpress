<?php
class WpProQuiz_Helper_Form {
	
	/**
	 * 
	 * @param WpProQuiz_Model_Form $form
	 * @param mixed $data
	 * 
	 * @return bool
	 */
	public static function valid($form, $data) {
		if(is_string($data))
			$data = trim($data);
		
		if($form->isRequired() && (trim($data) == ""))
			return false;
		
		switch ($form->getType()) {
			case WpProQuiz_Model_Form::FORM_TYPE_TEXT:
			case WpProQuiz_Model_Form::FORM_TYPE_TEXTAREA:
				return true;
			case WpProQuiz_Model_Form::FORM_TYPE_CHECKBOX:
				return empty($data) ? true : $data == '1';
			case WpProQuiz_Model_Form::FORM_TYPE_EMAIL:
				return empty($data) ? true : filter_var($data, FILTER_VALIDATE_EMAIL) !== false;
			case WpProQuiz_Model_Form::FORM_TYPE_NUMBER:
				return empty($data) ? true : is_numeric($data);
			case WpProQuiz_Model_Form::FORM_TYPE_RADIO:
			case WpProQuiz_Model_Form::FORM_TYPE_SELECT:
				return empty($data) ? true : in_array($data, $form->getData());
			case WpProQuiz_Model_Form::FORM_TYPE_YES_NO:
				return ($data !== 0 && $data !== 1 && $data !== "0" && $data !== "1") ? true : ($data == 0 || $data == 1);
			case WpProQuiz_Model_Form::FORM_TYPE_DATE:
				return true;
				
		}
	}
	
	/**
	 * 
	 * @param WpProQuiz_Model_Form $form
	 * @param array $data
	 */
	public static function validData($form, $data) {
		if($form->isRequired() && empty($data))
			return null;
		
		$check = 0;
		$format = $data['day'].'-'.$data['month'].'-'.$data['year'];
		
		if($data['day'] > 0 && $data['day'] <= 31) {
			$check++;
		}
		
		if($data['month'] > 0 && $data['month'] <= 12) {
			$check++;
		}

		if($data['year'] >= 1900 && $data['year'] <= date('Y')) {
			$check++;
		}
		
		if($form->isRequired()) {
			if($check == 3)
				return $format;
			
			return null;
		}
		
		if($check == 0)
			return '';
		
		if($check == 3)
			return $format;
		
		return null;
	}
}