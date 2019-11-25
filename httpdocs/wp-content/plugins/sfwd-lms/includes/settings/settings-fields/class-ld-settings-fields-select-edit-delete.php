<?php
/**
 * LearnDash Settings administration field Select with Edit and Delete.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Fields' ) ) && ( ! class_exists( 'LearnDash_Settings_Fields_Select_Edit_Delete' ) ) ) {
	/**
	 * Class to create the settings field.
	 */
	class LearnDash_Settings_Fields_Select_Edit_Delete extends LearnDash_Settings_Fields {

		/**
		 * Public constructor for class
		 */
		public function __construct() {
			$this->field_type = 'select-edit-delete';

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
			$html       = apply_filters( 'learndash_settings_field_html_before', '', $field_args );

			if ( ( isset( $field_args['options'] ) ) && ( ! empty( $field_args['options'] ) ) ) {
				$field_id_base    = $this->get_field_attribute_id( $field_args, false );
				$field_class_base = $this->get_field_attribute_class( $field_args, false );

				$html .= '<select ';
				$html .= $this->get_field_attribute_type( $field_args );
				$html .= $this->get_field_attribute_name( $field_args );
				$html .= ' id="' . $field_id_base . '_select" ';
				$html .= $this->get_field_attribute_class( $field_args );
				$html .= $this->get_field_attribute_misc( $field_args );
				$html .= $this->get_field_attribute_required( $field_args );

				$html .= '" ';
				$html .= ' >';

				foreach ( $field_args['options'] as $option_key => $option_label ) {
					$html .= '<option value="' . $option_key . '" ' . selected( $option_key, $field_args['value'], false ) . '>' . $option_label . '</option>';
				}
				$html .= '</select>';

				$ajax_data = array(
					'action'      => $field_args['setting_option_key'],
					'field_key'   => $field_args['setting_option_key'],
					'field_nonce' => wp_create_nonce( $field_args['setting_option_key'] ),
				);

				$html .= '<input class="ajax_data" type="hidden" data-ajax="' . htmlspecialchars( json_encode( $ajax_data, JSON_FORCE_OBJECT ) ) . '" />';

				$html .= '<div class="ld-setting-field-sub">
					<input disabled="disabled" type="text" value="" id="' . $field_id_base . '_input" name="' . $field_id_base . '_input" class="medium-text ld-settings-field-input" />
				</div>';

				if ( ( isset( $field_args['buttons'] ) ) && ( ! empty( $field_args['buttons'] ) ) ) {
					$html .= '<div class="ld-setting-field-sub">';
					foreach ( $field_args['buttons'] as $button_key => $button_label ) {
						$html .= '<input type="button" disabled="disabled" value="' . $button_label . '" class="button-secondary ld-settings-fiels-button" data-action="' . $button_key . '" />';
					}

					// Add spinner field to be shown during the AJAX processing.
					$html .= '<span class="spinner"></span>';

					$html .= '</div>'; // end of setting-field-sub.

					// Add an update message holder. This will be filled in with the updated message after the AJAX processing.
					$html .= '<div class="message" style="display:none"></div>';
				}
			}

			$html = apply_filters( 'learndash_settings_field_html_after', $html, $field_args );

			echo $html;
		}
	}
}
add_action(
	'learndash_settings_sections_fields_init',
	function() {
		LearnDash_Settings_Fields_Select_Edit_Delete::add_field_instance( 'select-edit-delete' );
	}
);
