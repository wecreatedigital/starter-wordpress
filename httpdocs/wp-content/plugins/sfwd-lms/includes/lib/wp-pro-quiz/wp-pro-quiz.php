<?php
/*
Plugin Name: WP-Pro-Quiz
Plugin URI: http://wordpress.org/extend/plugins/wp-pro-quiz
Description: A powerful and beautiful quiz plugin for WordPress.
Version: 0.28
Author: Julius Fischer
Author URI: http://www.it-gecko.de
Text Domain: wp-pro-quiz
Domain Path: /languages
*/

define('WPPROQUIZ_VERSION', '0.28');

define('WPPROQUIZ_PATH', dirname(__FILE__));
define('WPPROQUIZ_URL', plugins_url('', __FILE__));
define('WPPROQUIZ_FILE', __FILE__);
//define('WPPROQUIZ_PPATH', dirname(plugin_basename(__FILE__)));
//define('WPPROQUIZ_PLUGIN_PATH', WPPROQUIZ_PATH.'/plugin');
//define('WPPROQUIZ_TEXT_DOMAIN', 'learndash' );

$uploadDir = wp_upload_dir();

define('WPPROQUIZ_CAPTCHA_DIR', $uploadDir['basedir'].'/wp_pro_quiz_captcha');
define('WPPROQUIZ_CAPTCHA_URL', $uploadDir['baseurl'].'/wp_pro_quiz_captcha');

spl_autoload_register('wpProQuiz_autoload');

//$WpProQuiz_Answer_types_labels = array();
//global $WpProQuiz_Answer_types_labels;

// This is never called. 
//register_activation_hook(__FILE__, array('WpProQuiz_Helper_Upgrade', 'upgrade'));

add_action('plugins_loaded', 'wpProQuiz_pluginLoaded');

if(is_admin()) {
	new WpProQuiz_Controller_Admin();
} else {
	new WpProQuiz_Controller_Front();
}

function wpProQuiz_autoload($class) {
	$c = explode('_', $class);

	if($c === false || count($c) != 3 || $c[0] !== 'WpProQuiz')
		return;

	$dir = '';

	switch ($c[1]) {
		case 'View':
			$dir = 'view';
			break;
		case 'Model':
			$dir = 'model';
			break;
		case 'Helper':
			$dir = 'helper';
			break;
		case 'Controller':
			$dir = 'controller';
			break;
		case 'Plugin':
			$dir = 'plugin';
			break;
		default:
			return;
	}

	if(file_exists(WPPROQUIZ_PATH.'/lib/'.$dir.'/'.$class.'.php'))
		include_once WPPROQUIZ_PATH.'/lib/'.$dir.'/'.$class.'.php';
}

function wpProQuiz_pluginLoaded() {

	if ( get_option( 'wpProQuiz_version' ) !== WPPROQUIZ_VERSION ) {
		WpProQuiz_Helper_Upgrade::upgrade();
	}
}

function wpProQuiz_achievementsV3() {
	achievements()->extensions->wp_pro_quiz = new WpProQuiz_Plugin_BpAchievementsV3();

	do_action('wpProQuiz_achievementsV3');
}

add_action('dpa_ready', 'wpProQuiz_achievementsV3');

/**
 * Format the Quiz Cloze type answers into an array to be used when comparing responses. 
 * @ since 2.5
 * copied from WpProQuiz_View_FrontQuiz
 */
function fetchQuestionCloze( $answer_text, $convert_to_lower = true ) {
	preg_match_all( '#\{(.*?)(?:\|(\d+))?(?:[\s]+)?\}#im', $answer_text, $matches, PREG_SET_ORDER );

	$data = array();

	foreach ( $matches as $k => $v ) {
		$text    = $v[1];
		$points  = ! empty( $v[2] ) ? (int) $v[2] : 1;
		$rowText = $multiTextData = array();
		$len     = array();

		if ( preg_match_all( '#\[(.*?)\]#im', $text, $multiTextMatches ) ) {
			foreach ( $multiTextMatches[1] as $multiText ) {
				$multiText_clean = trim( html_entity_decode( $multiText, ENT_QUOTES ) );
				
				if ( apply_filters('learndash_quiz_question_cloze_answers_to_lowercase', $convert_to_lower ) ) {
					if ( function_exists( 'mb_strtolower' ) )
						$x = mb_strtolower( $multiText_clean );
					else
						$x = strtolower( $multiText_clean );
				} else {
					$x = $multiText_clean;
				}
				
				$len[]           = strlen( $x );
				$multiTextData[] = $x;
				$rowText[]       = $multiText;
			}
		} else {
			$text_clean = trim( html_entity_decode( $text, ENT_QUOTES ) );
			if ( apply_filters('learndash_quiz_question_cloze_answers_to_lowercase', $convert_to_lower ) ) {
				if ( function_exists( 'mb_strtolower' ) )
					$x = mb_strtolower( trim( html_entity_decode( $text_clean, ENT_QUOTES ) ) );
				else
					$x = strtolower( trim( html_entity_decode( $text_clean, ENT_QUOTES ) ) );
			} else {
				$x = $text_clean;
			}
			
			$len[]           = strlen( $x );
			$multiTextData[] = $x;
			$rowText[]       = $text;
		}

		$a = '<span class="wpProQuiz_cloze"><input data-wordlen="' . max( $len ) . '" type="text" value=""> ';
		$a .= '<span class="wpProQuiz_clozeCorrect" style="display: none;"></span></span>';

		$data['correct'][] = $multiTextData;
		$data['points'][]  = $points;
		$data['data'][]    = $a;
	}

	$data['replace'] = preg_replace( '#\{(.*?)(?:\|(\d+))?(?:[\s]+)?\}#im', '@@wpProQuizCloze@@', $answer_text );

	return $data;
}


/**
 * This function will take an instance of a PHP stdClass and attempt to cast it to
 * the type of the specified $className.
 *
 * For example, we may pass 'Acme\Model\Product' as the $className.
 *
 * @param object $instance  an instance of the stdClass to convert
 * @param string $className the name of the class type to which we want to cals
 *
 * @return mixed a version of the incoming $instance casted as the specified className
 */
function learndash_cast_WpProQuiz_Model_AnswerTypes($instance, $className) {
    return unserialize(sprintf(
        'O:%d:"%s"%s',
        \strlen($className),
        $className,
        strstr(strstr(serialize($instance), '"'), ':')
    ));
}