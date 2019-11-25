<?php
/**
 * LearnDash Settings Page Abstract Class.
 *
 * @package LearnDash
 * @subpackage Settings
 */

//require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/settings/class-ld-settings-section-fields.php';

if ( ! class_exists( 'LearnDash_Settings_Section' ) ) {
	/**
	 * Absract for LearnDash Settings Sections.
	 */
	abstract class LearnDash_Settings_Section {

		/**
		 * Static array of section instances.
		 *
		 * @var array $_instances
		 */
		protected static $_instances = array();

		/**
		 * Match the WP Screen ID
		 *
		 * @var string $settings_screen_id Settings Screen ID.
		 */
		protected $settings_screen_id = '';

		/**
		 * Match the  Settings Page ID
		 *
		 * @var string $settings_page_id Settings Page ID.
		 */
		protected $settings_page_id = '';

		/**
		 * Store for all the fields in this section
		 *
		 * @var array $setting_option_fields Array of section fields.
		 */
		protected $setting_option_fields = array();

		/**
		 * Holds the values for the fields. Read in from the wp_options item.
		 *
		 * @var array $setting_option_values Array of section values.
		 */
		protected $setting_option_values = array();

		/**
		 * Flag for if settings values have been loaded.
		 *
		 * @var boolean $settings_values_loaded Flag.
		 */
		protected $settings_values_loaded = false;

		/**
		 * Flag for if settings fields have been loaded.
		 *
		 * @var boolean $settings_fields_loaded Flag.
		 */

		protected $settings_fields_loaded = false;

		/**
		 * This is the 'option_name' key used to store the section values.
		 *
		 * @var string setting_option_key
		 */
		protected $setting_option_key = '';

		/**
		 * This is the HTML form field prefix used.
		 *
		 * @var string setting_field_prefix
		 */
		protected $setting_field_prefix = '';

		/**
		 * This is used as the option_name when the settings
		 * are saved to the options table.
		 *
		 * @var string $settings_section_key
		 */
		protected $settings_section_key = '';

		/**
		 * Section label/header
		 * This setting is used to show in the title of the metabox or section.
		 *
		 * @var string $settings_section_label
		 */
		protected $settings_section_label = '';

		/**
		 * Used to show the section description above the fields. Can be empty.
		 *
		 * @var string $settings_section_description
		 */
		protected $settings_section_description = '';

		/**
		 * Used to transition settings from one class to another. Can be empty.
		 * Set at the class then combined into the $settings_deprecated array.
		 *
		 * @var string $settings_deprecated
		 */
		protected $settings_deprecated = array();

		/**
		 * Unique ID used for metabox on page. Will be derived from
		 * settings_option_key + setting_section_key
		 *
		 * @var string $metabox_key
		 */
		protected $metabox_key = '';

		/**
		 * Controls metabox context on page
		 * See WordPress add_meta_box() function 'context' parameter.
		 *
		 * @var string $metabox_context
		 */
		protected $metabox_context = 'normal';

		/**
		 * Controls metabox priority on page
		 * See WordPress add_meta_box() function 'priority' parameter.
		 *
		 * @var string $metabox_priority
		 */
		protected $metabox_priority = 'default';

		/**
		 * Lets the section define it's own display function instead of using the Settings API
		 *
		 * @var mixed $settings_fields_callback
		 */
		protected $settings_fields_callback = null;

		/**
		 * Used on submit metaboxes to display reset confirmation popup message.
		 *
		 * @var string $reset_confirm_message
		 */
		protected $reset_confirm_message = '';

		/**
		 * Static array of deprecated section and fields.
		 *
		 * @var array $settings_deprecated
		 */
		protected static $_settings_deprecated = array();

		/**
		 * Public constructor for class
		 */
		protected function __construct() {
			add_action( 'init', array( $this, 'init' ) );
			add_action( 'learndash_settings_page_init', array( $this, 'settings_page_init' ) );
			if ( defined( 'LEARNDASH_SETTINGS_SECTION_TYPE' ) && ( 'metabox' === LEARNDASH_SETTINGS_SECTION_TYPE ) ) {
				add_action( 'learndash_add_meta_boxes', array( $this, 'add_meta_boxes' ) );
			}

			add_filter( 'pre_update_option_' . $this->setting_option_key, array( $this, 'section_pre_update_option' ), 30, 3 );
			add_action( 'update_option_' . $this->setting_option_key, array( $this, 'section_update_option' ), 30, 3 );

			$this->metabox_key = $this->setting_option_key . '_' . $this->settings_section_key;

			if ( empty( $this->settings_screen_id ) ) {
				$this->settings_screen_id = 'admin_page_' . $this->settings_page_id;
			}

			if ( ! empty( $this->settings_deprecated ) ) {
				foreach ( $this->settings_deprecated as $old_class => $old_settings ) {
					if ( ! isset( self::$_settings_deprecated[ $old_class ] ) ) {
						if ( ! isset( $old_settings['class'] ) ) {
							$old_settings['class'] = get_called_class();
						}
						self::$_settings_deprecated[ $old_class ] = $old_settings;
					}
				}
			}
		}

		/**
		 * Get the instance of our class based on the section_key
		 *
		 * @since 2.4.0
		 *
		 * @param string $section_key Unique section key used to identify instance.
		 */
		final public static function get_section_instance( $section_key = '' ) {
			if ( ! empty( $section_key ) ) {
				if ( isset( self::$_instances[ $section_key ] ) ) {
					return self::$_instances[ $section_key ];
				}
			}
		}

		/**
		 * Add instance to static tracking array
		 *
		 * @since 2.4.0
		 */
		final public static function add_section_instance() {
			$section = get_called_class();

			if ( ! isset( self::$_instances[ $section ] ) ) {
				self::$_instances[ $section ] = new $section();
			}
		}

		/**
		 * Initialize self
		 */
		public function init() {
			if ( ! $this->settings_values_loaded ) {
				$this->load_settings_values();
			}

			if ( ! $this->settings_fields_loaded ) {
				$this->load_settings_fields();
			}
		}

		/**
		 * Load the section settings values.
		 */
		public function load_settings_values() {
			$this->settings_values_loaded = true;
			$this->setting_option_values  = get_option( $this->setting_option_key );
		}

		/**
		 * Load the section settings fields.
		 */
		public function load_settings_fields() {
			$this->settings_fields_loaded = true;

			foreach ( $this->setting_option_fields as &$setting_option_field ) {
				if ( ! isset( $setting_option_field['type'] ) ) {
					continue;
					//error_log('setting_option_field[type]['. $setting_option_field['type'] .']');
					//error_log('setting_option_field<pre>'. print_r($setting_option_field, true) .'</pre>');
				}
				
				$field_instance = LearnDash_Settings_Fields::get_field_instance( $setting_option_field['type'] );
				if ( ( $field_instance ) && ( 'LearnDash_Settings_Fields' === get_parent_class( $field_instance ) ) ) {
					$setting_option_field['setting_option_key'] = $this->setting_option_key;

					if ( ! isset( $setting_option_field['id'] ) ) {
						$setting_option_field['id'] = $setting_option_field['setting_option_key'] . '_' . $setting_option_field['name'];
					}

					if ( ! isset( $setting_option_field['label_for'] ) ) {
						$setting_option_field['label_for'] = $setting_option_field['id'];
					}

					if ( ! isset( $setting_option_field['name_wrap'] ) ) {
						$setting_option_field['name_wrap'] = true;
					}

					if ( ! isset( $setting_option_field['input_show'] ) ) {
						$setting_option_field['input_show'] = true;
					}

					if ( ! isset( $setting_option_field['display_callback'] ) ) {
						$display_ref = LearnDash_Settings_Fields::get_field_instance( $setting_option_field['type'] )->get_creation_function_ref();
						if ( $display_ref ) {
							$setting_option_field['display_callback'] = $display_ref;
						}
					}

					if ( ( ! isset( $setting_option_field['value_type'] ) ) || ( empty( $setting_option_field['value_type'] ) ) ) {
						$setting_option_field['value_type'] = 'sanitize_text_field';
					}
					if ( ! is_callable( $setting_option_field['value_type'] ) ) {
						$setting_option_field['value_type'] = 'sanitize_text_field';
					}

					if ( ! isset( $setting_option_field['validate_callback'] ) ) {
						$validate_ref = LearnDash_Settings_Fields::get_field_instance( $setting_option_field['type'] )->get_validation_function_ref();
						if ( $validate_ref ) {
							$setting_option_field['validate_callback'] = $validate_ref;
						}
					}

					if ( ! isset( $setting_option_field['value_callback'] ) ) {
						$value_ref = LearnDash_Settings_Fields::get_field_instance( $setting_option_field['type'] )->get_value_function_ref();
						if ( $value_ref ) {
							$setting_option_field['value_callback'] = $value_ref;
						}
					}
				}
			}
		}

		/**
		 * Save Section Settings values
		 */
		public function save_settings_values() {
			$this->settings_values_loaded = false;
			update_option( $this->setting_option_key, $this->setting_option_values );
		}

		/**
		 * Initialize the Settings page.
		 *
		 * @param string $settings_page_id ID of page being initialized.
		 */
		public function settings_page_init( $settings_page_id = '' ) {

			// Ensure settings_page_id is not empty and that it matches the page_id we want to display this section on.
			if ( ( ! empty( $settings_page_id ) ) && ( $settings_page_id === $this->settings_page_id ) && ( ! empty( $this->setting_option_fields ) ) ) {

				add_settings_section(
					$this->settings_section_key,
					$this->settings_section_label,
					array( $this, 'show_settings_section_description' ),
					$this->settings_page_id
				);

				foreach ( $this->setting_option_fields as $setting_option_field ) {
					if ( ! isset( $setting_option_field['name'] ) ) {
						continue;
					}
					
					add_settings_field(
						$setting_option_field['name'],
						$setting_option_field['label'],
						$setting_option_field['display_callback'],
						$this->settings_page_id,
						$this->settings_section_key,
						$setting_option_field
					);
				}

				register_setting(
					$this->settings_page_id,
					$this->setting_option_key,
					array( $this, 'settings_section_fields_validate' )
				);
			}
		}

		/**
		 * Show Settings Section Description
		 */
		public function show_settings_section_description() {

			if ( ! empty( $this->settings_section_description ) ) {
				echo '<div class="ld-metabox-description">' . wpautop( $this->settings_section_description ) . '</div>';
			}
		}

		/**
		 * Output Settings Section nonce field.
		 */
		public function show_settings_section_nonce_field() {
			wp_nonce_field( $this->setting_option_key, $this->setting_option_key . '_nonce' );
		}

		/**
		 * Intercept the WP options save logic and check that we have a valid nonce.
		 *
		 * @since 3.0
		 * @param array $value Array of section fields values.
		 * @param array $old_value Array of old values.
		 * @param string $section_key Section option key should match $this->setting_option_key.
		 */
		public function section_pre_update_option( $value, $old_value, $section_key = '' ) {
			if ( ( empty( $section_key ) ) || ( $section_key !== $this->setting_option_key ) ) {
				return $old_value;
			}

			if ( ! $this->verify_metabox_nonce_field() ) {
				$old_value;
			}

			return $value;
		}

		/**
		 * Called AFTER section settings are update.
		 *
		 * @since 3.0
		 * @param array $value Array of section fields values.
		 * @param array $old_value Array of old values.
		 * @param string $section_key Section option key should match $this->setting_option_key.
		 */
		public function section_update_option( $old_value = '', $value = '', $section_key = '' ) {
			if ( ( ! empty( $section_key ) ) && ( $section_key === $this->setting_option_key ) ) {
				// When a section values change we update our internal set and also trigger reload fresh.

				if ( ! defined( 'LEARNDASH_SETTINGS_UPDATING' ) ) {
					define( 'LEARNDASH_SETTINGS_UPDATING', true );
				}
				$this->setting_option_values = $value;
				$this->settings_values_loaded = false;
				$this->settings_fields_loaded = false;
				return true;
			}
		}

		/**
		 * Verify Settings Section nonce field POST value.
		 */
		public function verify_metabox_nonce_field() {
			if ( ( isset( $_POST[ $this->setting_option_key . '_nonce' ] ) ) && ( ! empty( $_POST[ $this->setting_option_key . '_nonce' ] ) ) && ( wp_verify_nonce( esc_attr( $_POST[ $this->setting_option_key . '_nonce' ] ), $this->setting_option_key ) ) ) {
				return true;
			}
		}

		/**
		 * Show Settings Section reset link
		 */
		public function show_settings_section_reset_confirm_link() {
			if ( ! empty( $this->reset_confirm_message ) ) {
				$reset_url = add_query_arg(
					array(
						'action'     => 'ld_reset_settings',
						'ld_wpnonce' => wp_create_nonce( get_current_user_id() . '-' . $this->setting_option_key ),
					)
				);
				?>
				<p class="delete-action sfwd_input">
					<a href="<?php echo esc_url( $reset_url ); ?>" class="button-secondary submitdelete deletion" data-confirm="<?php echo esc_html( $this->reset_confirm_message ); ?>"><?php esc_html_e( 'Reset Settings', 'learndash' ); ?></a>
				</p>
				<?php
			}
		}

		/**
		 * Added Settings Section meta box.
		 *
		 * @param string $settings_screen_id Settings Screen ID.
		 */
		public function add_meta_boxes( $settings_screen_id = '' ) {
			global $learndash_metaboxes;

			if ( $settings_screen_id === $this->settings_screen_id ) {
				if ( apply_filters( 'learndash_show_metabox', true, $this->metabox_key, $this->settings_screen_id ) ) {

					if ( ! isset( $learndash_metaboxes[ $this->settings_screen_id ] ) ) {
						$learndash_metaboxes[ $this->settings_screen_id ] = array();
					}
					$learndash_metaboxes[ $this->settings_screen_id ][ $this->metabox_key ] = $this->metabox_key;

					add_meta_box(
						$this->metabox_key,
						$this->settings_section_label,
						array( $this, 'show_meta_box' ),
						$this->settings_screen_id,
						$this->metabox_context,
						$this->metabox_priority
					);
				}
			}
		}

		/**
		 * Show Settings Section meta box.
		 */
		public function show_meta_box() {
			global $wp_settings_sections;

			$this->show_settings_section_nonce_field();

			if ( isset( $wp_settings_sections[ $this->settings_page_id ][ $this->settings_section_key ] ) ) {
				$this->show_settings_section( $wp_settings_sections[ $this->settings_page_id ][ $this->settings_section_key ] );
			} else {
				$this->show_settings_section();
			}
		}

		/**
		 * Show the meta box settings
		 *
		 * @param string $section Section to be shown.
		 */
		public function show_settings_section( $section = null ) {
			/**
			 * The 'callback' attribute is set if/when the section description is
			 * to be displayed. See the WP add_settings_section() argument #3.
			 */
			if ( ( ! is_null( $section ) ) && ( isset( $section['callback'] ) ) && ( ! empty( $section['callback'] ) ) && ( is_callable( $section['callback'] ) ) ) {
				call_user_func( $section['callback'] );
			}

			// If this section defined its own display callback logic.
			if ( ( isset( $this->settings_fields_callback ) ) && ( ! empty( $this->settings_fields_callback ) ) && ( is_callable( $this->settings_fields_callback ) ) ) {
				call_user_func( $this->settings_fields_callback, $this->settings_page_id, $this->settings_section_key );
			} else {
				/**
				 * Note here we are calling a custom version of the WP function
				 * do_settings_fields because we want to control the label and help icons
				 */

				do_action( 'learndash_section_before', $this->settings_section_key, $this->settings_screen_id );

				echo '<div class="sfwd sfwd_options ' . esc_attr( $this->settings_section_key ) . '">';

				do_action( 'learndash_section_fields_before', $this->settings_section_key, $this->settings_screen_id );
				$this->show_settings_section_fields( $this->settings_page_id, $this->settings_section_key );
				do_action( 'learndash_section_fields_after', $this->settings_section_key, $this->settings_screen_id );

				do_action( 'learndash_section_reset_before', $this->settings_section_key, $this->settings_screen_id );
				$this->show_settings_section_reset_confirm_link();
				do_action( 'learndash_section_reset_after', $this->settings_section_key, $this->settings_screen_id );

				echo '</div>';

				do_action( 'learndash_section_after', $this->settings_section_key, $this->settings_screen_id );
			}
		}

		/**
		 * Show Settings Section Fields.
		 *
		 * @param string $page Page shown.
		 * @param string $section Section shown.
		 */
		public function show_settings_section_fields( $page, $section ) {
			global $wp_settings_fields;

			if ( ! isset( $wp_settings_fields[ $page ][ $section ] ) ) {
				return;
			}

			LearnDash_Settings_Fields::show_section_fields( $wp_settings_fields[ $page ][ $section ] );
		}

		/**
		 * This validation function is set via the call to 'register_setting'
		 * and will be called for each section.
		 *
		 * @param array $post_fields Array of section fields.
		 */
		public function settings_section_fields_validate( $post_fields = array() ) {
			$setting_option_values = array();

			// This valiadate_args array will be passed to the validation function for context.
			$validate_args = array(
				'settings_page_id'   => $this->settings_page_id,
				'setting_option_key' => $this->setting_option_key,
				'post_fields'        => $post_fields,
				'field'              => null,
			);

			if ( ! empty( $post_fields ) ) {
				foreach ( $post_fields as $key => $val ) {
					if ( isset( $this->setting_option_fields[ $key ] ) ) {
						if ( ( isset( $this->setting_option_fields[ $key ]['validate_callback'] ) ) && ( ! empty( $this->setting_option_fields[ $key ]['validate_callback'] ) ) && ( is_callable( $this->setting_option_fields[ $key ]['validate_callback'] ) ) ) {
							$validate_args['field']        = $this->setting_option_fields[ $key ];
							$setting_option_values[ $key ] = call_user_func( $this->setting_option_fields[ $key ]['validate_callback'], $val, $key, $validate_args );
						}
					}
				}
			}
			return $setting_option_values;
		}

		/**
		 * Static function to get section setting.
		 *
		 * @param string $field_key Section field key.
		 * @param mixed  $default_return Default value if field not found.
		 */
		public static function get_setting( $field_key = '', $default_return = '' ) {
			return self::get_section_setting( get_called_class(), $field_key, $default_return );
		}

		/**
		 * Static function to get all section settings.
		 */
		public static function get_settings_all() {
			return self::get_section_settings_all( get_called_class() );
		}

		/**
		 * Static function to get section to get option label.
		 *
		 * @param string $field_key Section field key.
		 * @param string $option_key Section option key.
		 */
		public static function get_setting_select_option_label( $field_key = '', $option_key = '' ) {
			return self::get_section_setting_select_option_label( get_called_class(), $field_key, $option_key );
		}

		/**
		 * Static function to get a Section Setting value.
		 *
		 * @param string $section Settings Section.
		 * @param string $field_key Settings Section field key.
		 * @param mixed  $default_return Default value if field not found.
		 */
		public static function get_section_setting( $section = '', $field_key = '', $default_return = '' ) {
			if ( empty( $section ) ) {
				$section = get_called_class();
			}

			$field_key = self::check_deprecated_field_key( $field_key, $section );
			$section   = self::check_deprecated_class( $section );

			if ( isset( self::$_instances[ $section ] ) ) {
				self::$_instances[ $section ]->init();

				if ( isset( self::$_instances[ $section ]->setting_option_fields[ $field_key ] ) ) {
					$default_return = self::$_instances[ $section ]->setting_option_fields[ $field_key ]['value'];
				}
			}

			return $default_return;
		}

		/**
		 * Static function to set a Section Setting value.
		 *
		 * @param string $section Settings Section.
		 * @param string $field_key Settings Section field key.
		 * @param mixed  $new_value new value for field.
		 */
		public static function set_section_setting( $section = '', $field_key = '', $new_value = '' ) {
			if ( ( ! empty( $section ) ) && ( ! empty( $field_key ) ) ) {

				$field_key = self::check_deprecated_field_key( $field_key, $section );
				$section   = self::check_deprecated_class( $section );

				if ( isset( self::$_instances[ $section ] ) ) {
					self::$_instances[ $section ]->init();

					if ( isset( self::$_instances[ $section ]->setting_option_fields[ $field_key ] ) ) {
						self::$_instances[ $section ]->setting_option_fields[ $field_key ]['value'] = $new_value;
						self::$_instances[ $section ]->save_settings_values();
					}
				}
			}
		}

		/**
		 * Static function to get all Section fields.
		 *
		 * @param string $section Settings Section.
		 * @param string $field Settings Section field key.
		 */
		public static function get_section_settings_all( $section_org = '', $field = 'value' ) {
			if ( empty( $section_org ) ) {
				$section_org = get_called_class();
			}

			$section = self::check_deprecated_class( $section_org );
			if ( empty( $section ) ) {
				$section = $section_org;
			}

			if ( isset( self::$_instances[ $section ] ) ) {
				self::$_instances[ $section ]->init();
				$fields_values = wp_list_pluck( self::$_instances[ $section ]->setting_option_fields, $field );

				// If we are dealing with deprecated values we provide the old key/value sets as well so easy the logic on the caller.
				if ( $section_org !== $section ) {
					if ( ( isset( self::$_settings_deprecated[ $section_org ]['fields'] ) ) && ( ! empty( self::$_settings_deprecated[ $section_org ]['fields'] ) ) ) {
						foreach ( self::$_settings_deprecated[ $section_org ]['fields'] as $old_field => $new_field ) {
							if ( ( ! isset( $fields_values[ $old_field ] ) ) && ( isset( self::$_instances[ $section ]->setting_option_values[ $new_field ][ $field ] ) ) ) {
								$fields_values[ $old_field ] = self::$_instances[ $section ]->setting_option_fields[ $new_field ][ $field ];
							} elseif ( ( 'value' === $field ) && ( isset( self::$_instances[ $section ]->setting_option_values[ $new_field ] ) ) ) {
								$fields_values[ $old_field ] = self::$_instances[ $section ]->setting_option_values[ $new_field ];
							}
						}
					}
				}

				return $fields_values;
			}
		}

		/**
		 * From a section settings you can access the label used on a select by the option key.
		 *
		 * @param string $section Settings Section.
		 * @param string $field_key Settings Section field key.
		 * @param string $option_key Option key.
		 */
		public static function get_section_setting_select_option_label( $section = '', $field_key = '', $option_key = '' ) {
			if ( empty( $section ) ) {
				$section = get_called_class();
			}

			if ( ! empty( $field_key ) ) {

				$field_key = self::check_deprecated_field_key( $field_key, $section );
				$section   = self::check_deprecated_class( $section );

				if ( isset( self::$_instances[ $section ] ) ) {
					self::$_instances[ $section ]->init();

					// If the option_key was not passed we default to the current selected value.
					if ( empty( $option_key ) ) {
						$option_key = self::$_instances[ $section ]->get_setting( $field_key );
					}

					// Now we get the option fields by the field_key and then derive the option label from the option_key.
					if ( ( isset( self::$_instances[ $section ]->setting_option_fields[ $field_key ] ) ) && ( 'select' === self::$_instances[ $section ]->setting_option_fields[ $field_key ]['type'] ) && ( self::$_instances[ $section ]->setting_option_fields[ $field_key ]['options'] ) && ( isset( self::$_instances[ $section ]->setting_option_fields[ $field_key ]['options'][ $option_key ] ) ) ) {
						return self::$_instances[ $section ]->setting_option_fields[ $field_key ]['options'][ $option_key ];
					}
				}
			}
		}

		/**
		 * Transition settings from old class into current one.
		 *
		 * @since 3.0
		 */
		public function transition_deprecated_settings() {
			if ( ! empty( $this->settings_deprecated ) ) {
				foreach ( $this->settings_deprecated as $old_class => $old_class_settings ) {
					if ( ( isset( $old_class_settings['option_key'] ) ) && ( ! empty( $old_class_settings['option_key'] ) ) ) {
						$old_settings_values = get_option( $old_class_settings['option_key'] );
						if ( ( isset( $old_class_settings['fields'] ) ) && ( ! empty( $old_class_settings['fields'] ) ) ) {
							foreach ( $old_class_settings['fields'] as $old_setting_key => $new_setting_key ) {
								if ( isset( $old_settings_values[ $old_setting_key ] ) ) {
									$this->setting_option_values[ $new_setting_key ] = $old_settings_values[ $old_setting_key ];
								}
							}
						}
					}
				}
			}
		}

		/**
		 * Transition old Settiings Class to new one.
		 *
		 * @since 3.0
		 * @param string $section Old section class.
		 * @return string New section Class.
		 */
		public static function check_deprecated_class( $section = '' ) {
			if ( ! empty( $section ) ) {
				if ( isset( self::$_settings_deprecated[ $section ] ) ) {
					$section = self::$_settings_deprecated[ $section ]['class'];
				}
			}

			return $section;
		}

		/**
		 * Transition old Settiings Class field(s) to new one(s).
		 *
		 * @since 3.0
		 * @param string $field_key Old section field key.
		 * @param string $section Old section class.
		 * @return string new field key.
		 */
		public static function check_deprecated_field_key( $field_key = '', $old_section = '' ) {
			if ( ( ! empty( $old_section ) ) && ( isset( self::$_settings_deprecated[ $old_section ] ) ) ) {
				if ( isset( self::$_settings_deprecated[ $old_section ]['fields'] ) ) {
					$section_fields = self::$_settings_deprecated[ $old_section ]['fields'];
					if ( ! empty( $section_fields ) ) {
						if ( isset( $section_fields[ $field_key ] ) ) {
							$field_key = $section_fields[ $field_key ];
						}
					}
				}
			}

			return $field_key;
		}
	}
}

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Theme_Settings_Section' ) ) ) {
	/**
	 * Class to create the settings section.
	 */
	abstract class LearnDash_Theme_Settings_Section extends LearnDash_Settings_Section {
		/**
		 * Match Theme Key.
		 * This should match the theme_key set within the LearnDash_Theme_Register instance.
		 *
		 * @var string $settings_theme_key Settings Theme ID.
		 */
		protected $settings_theme_key = '';


		/**
		 * Protected constructor for class
		 */
		protected function __construct() {
			parent::__construct();

			if ( ! empty( $this->settings_theme_key ) ) {
				LearnDash_Theme_Register::register_theme_settings_section( $this->settings_theme_key, $this->settings_section_key, $this );
			}
			add_filter( 'learndash_show_metabox', array( $this, 'learndash_show_metabox' ), 1, 3 );
		}

		final public function learndash_show_metabox( $show_metabox = true, $metabox_key = '', $settings_screen_id = '' ) {
			if ( $metabox_key === $this->metabox_key ) {
				$show_metabox = false;
			}

			return $show_metabox;
		}
	}
}

