<?php
/**
 * LearnDash Settings field Quiz / Question Templates.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Fields' ) ) && ( ! class_exists( 'LearnDash_Settings_Fields_Quiz_Templates_Save' ) ) ) {
	/**
	 * Class to create the settings field.
	 */
	class LearnDash_Settings_Fields_Quiz_Templates_Save extends LearnDash_Settings_Fields {

		/**
		 * Public constructor for class
		 */
		public function __construct() {
			$this->field_type = 'quiz-templates-save';

			parent::__construct();
		}

		/**
		 * Function to crete the settiings field.
		 *
		 * @since 2.4
		 *
		 * @param array $field_args An array of field arguments used to process the ouput.
		 * @return void
		 */
		public function create_section_field( $field_args = array() ) {
			$field_args = apply_filters( 'learndash_settings_field', $field_args );

			$html = apply_filters( 'learndash_settings_field_html_before', '', $field_args );

			$select_template_options = array();
			$template_type           = '';
			if ( isset( $field_args['template_type'] ) ) {
				$template_type = $field_args['template_type'];
			} else {
				global $post_type;
				if ( learndash_get_post_type_slug( 'quiz' ) === $post_type ) {
					$template_type = WpProQuiz_Model_Template::TEMPLATE_TYPE_QUIZ;
				} elseif ( learndash_get_post_type_slug( 'question' ) === $post_type ) {
					$template_type = WpProQuiz_Model_Template::TEMPLATE_TYPE_QUESTION;
				}
			}
			if ( ( isset( $template_type ) ) && ( '' !== $template_type ) ) {
				$template_mapper = new WpProQuiz_Model_TemplateMapper();
				$templates       = $template_mapper->fetchAll( $template_type, false );
				if ( ! empty( $templates ) ) {
					foreach ( $templates as $template ) {
						$select_template_options[ absint( $template->getTemplateId() ) ] = esc_html( $template->getName() );
					}
				}
			}

			$html .= '<span class="ld-select">';
			//$html .= '<select name="templateSaveList" class="learndash-section-field-select" data-ld-select2="1">';
			$html .= '<select autocomplete="off" ';
			$html .= $this->get_field_attribute_type( $field_args );
			//$html .= $this->get_field_attribute_name( $field_args );
			$html .= ' name="templateSaveList" ';
			$html .= $this->get_field_attribute_id( $field_args );
			$html .= $this->get_field_attribute_class( $field_args );

			if ( ( defined( 'LEARNDASH_SELECT2_LIB' ) ) && ( true === LEARNDASH_SELECT2_LIB ) ) {
				if ( ! isset( $field_args['attrs']['data-ld-select2'] ) ) {
					$html .= ' data-ld-select2="1" ';
				}
			}

			$html .= $this->get_field_attribute_misc( $field_args );
			$html .= $this->get_field_attribute_required( $field_args );
			$html .= $this->get_field_sub_trigger( $field_args );
			$html .= $this->get_field_inner_trigger( $field_args );

			$html .= ' >';

			if ( ( defined( 'LEARNDASH_SELECT2_LIB' ) ) && ( true === apply_filters( 'learndash_select2_lib', LEARNDASH_SELECT2_LIB ) ) ) {
				$html .= '   <option value="-1">';
			} else {
				$html .= '   <option value="">';
			}
			$html .= esc_html__( 'Select a templates to save or new', 'learndash' ) . '</option>';

			$html .= '   <option value="0">=== ' . esc_html__( 'Create new template', 'learndash' ) . ' === </option>';

			if ( ! empty( $select_template_options ) ) {
				foreach ( $select_template_options as $template_id => $template_name ) {
					$html .= '<option value="' . $template_id . '">' . $template_name . '</option>';
				}
			}
			
			$html .= '</select>';
			$html .= '</span><br />';
			$html .= '<input type="text" placeholder="' . esc_html__( 'new template name', 'learndash' ) . '" class="regular-text -medium" name="templateName">';

			$html = apply_filters( 'learndash_settings_field_html_after', $html, $field_args );

			echo $html;
		}

		/**
		 * Default validation function. Should be overriden in Field subclass.
		 *
		 * @since 2.4
		 *
		 * @param mixed  $val Value to validate.
		 * @param string $key Key of value being validated.
		 * @param array  $args Array of field args.
		 *
		 * @return mixed $val validated value.
		 */
		public function validate_section_field( $val, $key, $args = array() ) {
			if ( ( isset( $args['field']['type'] ) ) && ( $args['field']['type'] === $this->field_type ) ) {
				if ( ! empty( $val ) ) {
					$val = wp_check_invalid_utf8( $val );
					if ( ! empty( $val ) ) {
						$val = sanitize_post_field( 'post_content', $val, 0, 'db' );
					}
				}

				return $val;
			}

			return false;
		}
	}
}
add_action(
	'learndash_settings_sections_fields_init',
	function() {
		LearnDash_Settings_Fields_Quiz_Templates_Save::add_field_instance( 'quiz-templates-save' );
	}
);
