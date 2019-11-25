<?php
/**
 * LearnDash Settings Section for PayPal Metabox.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Section_PayPal' ) ) ) {
	/**
	 * Class to create the settings section.
	 */
	class LearnDash_Settings_Section_PayPal extends LearnDash_Settings_Section {

		/**
		 * Protected constructor for class
		 */
		protected function __construct() {
			$this->settings_page_id = 'learndash_lms_settings_paypal';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = 'learndash_settings_paypal';

			// This is the HTML form field prefix used.
			$this->setting_field_prefix = 'learndash_settings_paypal';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'settings_paypal';

			// Section label/header.
			$this->settings_section_label = esc_html__( 'PayPal Settings', 'learndash' );

			$this->reset_confirm_message = esc_html__( 'Are you sure want to reset the PayPal values?', 'learndash' );

			parent::__construct();
		}

		/**
		 * Initialize the metabox settings values.
		 */
		public function load_settings_values() {
			parent::load_settings_values();

			if ( false === $this->setting_option_values ) {
				$sfwd_cpt_options = get_option( 'sfwd_cpt_options' );

				if ( ( isset( $sfwd_cpt_options['modules']['sfwd-courses_options'] ) ) && ( ! empty( $sfwd_cpt_options['modules']['sfwd-courses_options'] ) ) ) {
					foreach ( $sfwd_cpt_options['modules']['sfwd-courses_options'] as $key => $val ) {
						$key = str_replace( 'sfwd-courses_', '', $key );
						if ( 'paypal_sandbox' === $key ) {
							if ( 'on' === $val ) {
								$val = 'yes';
							} else {
								$val = 'no';
							}
						}

						$this->setting_option_values[ $key ] = $val;
					}
				}
			}

			if ( ( isset( $_GET['action'] ) ) && ( 'ld_reset_settings' === $_GET['action'] ) && ( isset( $_GET['page'] ) ) && ( $_GET['page'] == $this->settings_page_id ) ) {
				if ( ( isset( $_GET['ld_wpnonce'] ) ) && ( ! empty( $_GET['ld_wpnonce'] ) ) ) {
					if ( wp_verify_nonce( $_GET['ld_wpnonce'], get_current_user_id() . '-' . $this->setting_option_key ) ) {
						if ( ! empty( $this->setting_option_values ) ) {
							foreach ( $this->setting_option_values as $key => $val ) {
								$this->setting_option_values[ $key ] = '';
							}
							$this->save_settings_values();
						}

						$reload_url = remove_query_arg( array( 'action', 'ld_wpnonce' ) );
						wp_redirect( $reload_url );
						die();
					}
				}
			}
		}

		/**
		 * Initialize the metabox settings fields.
		 */
		public function load_settings_fields() {
			global $wp_rewrite;

			if ( ( isset( $wp_rewrite ) ) && ( $wp_rewrite->using_permalinks() ) ) {
				$default_paypal_notifyurl = trailingslashit( get_home_url() ) . 'sfwd-lms/paypal';
			} else {
				$default_paypal_notifyurl = add_query_arg( 'sfwd-lms', 'paypal', get_home_url() );
			}

			$this->setting_option_fields = array(
				'paypal_email'     => array(
					'name'              => 'paypal_email',
					'type'              => 'text',
					'label'             => esc_html__( 'PayPal Email', 'learndash' ),
					'help_text'         => esc_html__( 'Enter your PayPal email here.', 'learndash' ),
					'value'             => ( ( isset( $this->setting_option_values['paypal_email'] ) ) && ( ! empty( $this->setting_option_values['paypal_email'] ) ) ) ? $this->setting_option_values['paypal_email'] : '',
					'class'             => 'regular-text',
					'validate_callback' => array( $this, 'validate_section_paypal_email' ),
				),
				'paypal_currency'  => array(
					'name'              => 'paypal_currency',
					'type'              => 'text',
					'label'             => esc_html__( 'PayPal Currency', 'learndash' ),
					'help_text'         => sprintf(
						// translators: placholder: Link to PayPal.
						esc_html_x( 'Enter the currency code for transactions. See PayPal %s Documentation', 'placeholder: URL to PayPal Currency Codes', 'learndash' ),
						'<a href="https://developer.paypal.com/docs/classic/api/currency_codes/" target="_blank">' . __( 'Currency Codes', 'learndash' ) . '</a>'
					),
					'value'             => ( ( isset( $this->setting_option_values['paypal_currency'] ) ) && ( ! empty( $this->setting_option_values['paypal_currency'] ) ) ) ? $this->setting_option_values['paypal_currency'] : 'USD',
					'class'             => 'regular-text',
					'validate_callback' => array( $this, 'validate_section_paypal_currency' ),
				),
				'paypal_country'   => array(
					'name'              => 'paypal_country',
					'type'              => 'text',
					'label'             => esc_html__( 'PayPal Country', 'learndash' ),
					'help_text'         => sprintf(
						// translators: placeholder: Link to PayPal Country Codes.
						esc_html_x( 'Enter your country code here. See PayPal %s Documentation', 'placeholder: URL to PayPal Country Codes.', 'learndash' ),
						'<a href="https://developer.paypal.com/docs/classic/api/country_codes/" target="_blank">' . __( 'Country Codes', 'learndash' ) . '</a>'
					),
					'value'             => ( ( isset( $this->setting_option_values['paypal_country'] ) ) && ( ! empty( $this->setting_option_values['paypal_country'] ) ) ) ? $this->setting_option_values['paypal_country'] : 'US',
					'class'             => 'regular-text',
					'validate_callback' => array( $this, 'validate_section_paypal_country' ),
				),
				'paypal_cancelurl' => array(
					'name'      => 'paypal_cancelurl',
					'type'      => 'text',
					'label'     => esc_html__( 'PayPal Cancel URL', 'learndash' ),
					'help_text' => esc_html__( 'Enter the URL used for purchase cancellations.', 'learndash' ),
					'value'     => ( ( isset( $this->setting_option_values['paypal_cancelurl'] ) ) && ( ! empty( $this->setting_option_values['paypal_cancelurl'] ) ) ) ? $this->setting_option_values['paypal_cancelurl'] : get_home_url(),
					'class'     => 'regular-text',
				),
				'paypal_returnurl' => array(
					'name'      => 'paypal_returnurl',
					'type'      => 'text',
					'label'     => esc_html__( 'PayPal Return ', 'learndash' ),
					'help_text' => esc_html__( 'Enter the URL used for completed purchases (typically a thank you page).', 'learndash' ),
					'value'     => ( ( isset( $this->setting_option_values['paypal_returnurl'] ) ) && ( ! empty( $this->setting_option_values['paypal_returnurl'] ) ) ) ? $this->setting_option_values['paypal_returnurl'] : get_home_url(),
					'class'     => 'regular-text',
				),
				'paypal_notifyurl' => array(
					'name'      => 'paypal_notifyurl',
					'type'      => 'text',
					'label'     => esc_html__( 'PayPal Notify URL', 'learndash' ),
					'help_text' => esc_html__( 'Enter the URL used for IPN notifications.', 'learndash' ),
					'value'     => ( ( isset( $this->setting_option_values['paypal_notifyurl'] ) ) && ( ! empty( $this->setting_option_values['paypal_notifyurl'] ) ) ) ? $this->setting_option_values['paypal_notifyurl'] : $default_paypal_notifyurl,
					'class'     => 'regular-text',
				),
				'paypal_sandbox'   => array(
					'name'      => 'paypal_sandbox',
					'type'      => 'checkbox',
					'label'     => esc_html__( 'Use PayPal Sandbox', 'learndash' ),
					'help_text' => esc_html__( 'Check to enable the PayPal sandbox.', 'learndash' ),
					'value'     => isset( $this->setting_option_values['paypal_sandbox'] ) ? $this->setting_option_values['paypal_sandbox'] : 'no',
					'options'   => array(
						'yes' => esc_html__( 'Yes', 'learndash' ),
					),
				),
			);

			$this->setting_option_fields = apply_filters( 'learndash_settings_fields', $this->setting_option_fields, $this->settings_section_key );

			parent::load_settings_fields();
		}

		/**
		 * Validate PayPal Email.
		 *
		 * @param string $val to be validated.
		 * @param string $key Settings key.
		 * @param array  $args Settings field args.
		 *
		 * @return string $val.
		 */
		public static function validate_section_paypal_email( $val, $key, $args = array() ) {
			$val = trim( $val );
			if ( ( ! empty( $val ) ) && ( ! is_email( $val ) ) ) {

				add_settings_error( $args['setting_option_key'], $key, esc_html__( 'PayPal Email must be a valid email.', 'learndash' ), 'error' );
			}

			return $val;
		}

		/**
		 * Validate Settings Country field.
		 *
		 * @param string $val to be validated.
		 * @param string $key Settings key.
		 * @param array  $args Settings field args.
		 *
		 * @return string $val.
		 */
		public static function validate_section_paypal_country( $val, $key, $args = array() ) {
			if ( ( isset( $args['post_fields']['paypal_email'] ) ) && ( ! empty( $args['post_fields']['paypal_email'] ) ) ) {
				$val = sanitize_text_field( $val );
				if ( empty( $val ) ) {
					add_settings_error( $args['setting_option_key'], $key, esc_html__( 'PayPal Country Code cannot be empty.', 'learndash' ), 'error' );
				} elseif ( strlen( $val ) > 2 ) {
					add_settings_error( $args['setting_option_key'], $key, esc_html__( 'PayPal Country Code should not be longer than 2 letters.', 'learndash' ), 'error' );
				}
			}

			return $val;
		}

		/**
		 * Validate Settings Currency field.
		 *
		 * @param string $val to be validated.
		 * @param string $key Settings key.
		 * @param array  $args Settings field args.
		 *
		 * @return string $val.
		 */
		public static function validate_section_paypal_currency( $val, $key, $args = array() ) {
			if ( ( isset( $args['post_fields']['paypal_email'] ) ) && ( ! empty( $args['post_fields']['paypal_email'] ) ) ) {
				$val = sanitize_text_field( $val );
				if ( empty( $val ) ) {
					add_settings_error( $args['setting_option_key'], $key, esc_html__( 'PayPal Currency Code cannot be empty.', 'learndash' ), 'error' );
				} elseif ( strlen( $val ) > 3 ) {
					add_settings_error( $args['setting_option_key'], $key, esc_html__( 'PayPal Currency Code should not be longer than 3 letters.', 'learndash' ), 'error' );
				}
			}

			return $val;
		}
	}
}
add_action(
	'learndash_settings_sections_init',
	function() {
		LearnDash_Settings_Section_PayPal::add_section_instance();
	}
);
