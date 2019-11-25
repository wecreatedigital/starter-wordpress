<?php
/**
 * LearnDash Settings Metabox Abstract Class.
 *
 * @package LearnDash
 * @subpackage Settings
 */

//require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/settings/class-ld-settings-section-fields.php';

if ( ! class_exists( 'LearnDash_Settings_Metabox' ) ) {
	/**
	 * Absract for LearnDash Settings Sections.
	 */
	abstract class LearnDash_Settings_Metabox {

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
		//protected $settings_screen_id = '';

		/**
		 * Store for all the fields in this section
		 *
		 * @var array $setting_option_fields Array of section fields.
		 */
		protected $setting_option_fields = array();

		/**
		 * Store for all the sub fields in this section
		 *
		 * @var array $settings_sub_option_fields Array of section sub fields.
		 */
		protected $settings_sub_option_fields = array();

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
		 * This is used as the option_name when the settings
		 * are saved to the options table.
		 *
		 * @var string $settings_section_key
		 */
		protected $settings_metabox_key = '';

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
		 * Map internal settings field ID to legacy field ID.
		 *
		 * @var array $settings_fields_map
		 */
		protected $settings_fields_map = array();

		/**
		 * Legacy Settings fields.
		 *
		 * @var array $settings_fields_legacy
		 */
		protected $settings_fields_legacy = array();

		/**
		 * Legacy Settings values.
		 *
		 * @var array $settings_values_legacy
		 */
		protected $settings_values_legacy = array();

		protected $_post    = null;
		protected $_metabox = null;

		/**
		 * Public constructor for class
		 */
		public function __construct() {
			add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );

			add_filter(
				$this->settings_screen_id . '_display_settings',
				array( $this, 'display_settings_filter' ),
				100,
				3
			);
		}

		/**
		 * Get the instance of our class based on the metabox_key
		 *
		 * @since 2.6.0
		 *
		 * @param string $metabox_key Unique metabox key used to identify instance.
		 */
		final public static function get_metabox_instance( $metabox_key = '' ) {
			if ( ! empty( $metabox_key ) ) {
				if ( isset( self::$_instances[ $metabox_key ] ) ) {
					return self::$_instances[ $metabox_key ];
				}
			}
		}

		/**
		 * Add instance to static tracking array
		 *
		 * @since 2.4.0
		 */
		final public static function add_metabox_instance() {
			$metabox_key = get_called_class();
			if ( ! isset( self::$_instances[ $metabox_key ] ) ) {
				self::$_instances[ $metabox_key ] = new $metabox_key();
			}
			return self::$_instances[ $metabox_key ];
		}

		/**
		 * Initialize self
		 */
		public function init( $post = null ) {
			if ( ( ! $post ) || ( ! is_a( $post, 'WP_Post' ) ) ) {
				return false;
			}

			if ( $post->post_type !== $this->settings_screen_id ) {
				return false;
			}

			$this->_post = $post;

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

			$screen = get_current_screen();
			if ( $screen->id !== $this->settings_screen_id ) {
				return false;
			}
			if ( ( ! $this->_post ) || ( ! is_a( $this->_post, 'WP_Post' ) ) ) {
				return false;
			}

			if ( $this->_post->post_type !== $this->settings_screen_id ) {
				return false;
			}

			$setting_values = learndash_get_setting( $this->_post );

			if ( ! empty( $setting_values ) ) {
				foreach ( $this->settings_fields_map as $_internal => $_legacy ) {
					if ( isset( $setting_values[ $_legacy ] ) ) {
						$this->setting_option_values[ $_internal ]                                    = $setting_values[ $_legacy ];
						$this->settings_values_legacy[ $this->settings_screen_id . '_' . $_internal ] = $setting_values[ $_legacy ];
					} else {
						$this->setting_option_values[ $_internal ] = '';
					}
				}
			}

			$this->settings_values_loaded = true;

			return true;
		}

		protected function get_save_settings_fields_map_form_post_values( $post_values = array() ) {
			return $this->settings_fields_map;
		}

		/**
		 * Load the section settings fields.
		 */
		public function load_settings_fields() {
			$this->settings_fields_loaded = true;

			if ( ! empty( $this->setting_option_fields ) ) {
				foreach ( $this->setting_option_fields as $setting_option_key => &$setting_option_field ) {
					$setting_option_field = $this->load_settings_field( $setting_option_field );
				}
			}
		}

		public function load_settings_field( $setting_option_field = array() ) {
			if ( ! isset( $setting_option_field['type'] ) ) {
				return $setting_option_field;
			}

			$field_ref = LearnDash_Settings_Fields::get_field_instance( $setting_option_field['type'] );
			if ( ! $field_ref ) {
				return $setting_option_field;
			}

			$setting_option_field['setting_option_key'] = $this->settings_metabox_key;

			if ( ! isset( $setting_option_field['id'] ) ) {
				$setting_option_field['id'] = $setting_option_field['setting_option_key'] . '_' . $setting_option_field['name'];
			}

			if ( ! isset( $setting_option_field['label_for'] ) ) {
				$setting_option_field['label_for'] = $setting_option_field['id'];
			}

			if ( ! isset( $setting_option_field['label_none'] ) ) {
				$setting_option_field['label_none'] = false;
			}

			if ( ! isset( $setting_option_field['label_full'] ) ) {
				$setting_option_field['label_full'] = false;
			}

			if ( ! isset( $setting_option_field['input_show'] ) ) {
				$setting_option_field['input_show'] = true;
			}

			if ( ! isset( $setting_option_field['input_full'] ) ) {
				$setting_option_field['input_full'] = false;
			}

			if ( ! isset( $setting_option_field['default'] ) ) {
				$setting_option_field['default'] = '';
			}

			if ( ! isset( $setting_option_field['value'] ) ) {
				$setting_option_field['value'] = '';
			}
			if ( ( ! isset( $setting_option_field['value'] ) ) || ( empty( $setting_option_field['value'] ) ) ) {
				if ( 'radio' === $setting_option_field['type'] ) {
					if ( isset( $setting_option_field['default'] ) ) {
						$setting_option_field['value'] = $setting_option_field['default'];
					}
				}
			}

			if ( ! isset( $setting_option_field['name_wrap'] ) ) {
				$setting_option_field['name_wrap'] = true;
			}

			if ( ! isset( $setting_option_field['display_callback'] ) ) {
				$display_ref = $field_ref->get_creation_function_ref();
				if ( $display_ref ) {
					$setting_option_field['display_callback'] = $display_ref;
				}
			}

			if ( ! isset( $setting_option_field['validate_callback'] ) ) {
				$validate_ref = $field_ref->get_validation_function_ref();
				if ( $validate_ref ) {
					$setting_option_field['validate_callback'] = $validate_ref;
				}
			}

			if ( ! isset( $setting_option_field['value_callback'] ) ) {
				$value_ref = $field_ref->get_value_function_ref();
				if ( $value_ref ) {
					$setting_option_field['value_callback'] = $value_ref;
				}
			}

			if ( ( ! isset( $setting_option_field['value_type'] ) ) || ( empty( $setting_option_field['value_type'] ) ) ) {
				$setting_option_field['value_type'] = 'sanitize_text_field';
			}
			if ( ! is_callable( $setting_option_field['value_type'] ) ) {
				$setting_option_field['value_type'] = 'sanitize_text_field';
			}

			// Now we reorganize the field.
			if ( ! isset( $setting_option_field['args'] ) ) {
				$setting_option_field['args'] = array();
				foreach ( $setting_option_field as $field_key => $field_val ) {

					$setting_option_field['args'][ $field_key ] = $field_val;
					if ( 'label' === $field_key ) {
						$setting_option_field['title'] = $field_val;
					}

					if ( ! in_array( $field_key, array( 'id', 'name', 'args', 'callback' ) ) ) {
						unset( $setting_option_field[ $field_key ] );
					}
				}
			}
			return $setting_option_field;
		}

		/**
		 * Show Settings Section Description
		 */
		public function show_settings_section_description() {
			if ( ! empty( $this->settings_section_description ) ) {
				echo '<div class="ld-metabox-description">' . wpautop( wp_kses_post( $this->settings_section_description ) ) . '</div>';
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
				if ( apply_filters( 'learndash_show_metabox', true, $this->settings_metabox_key, $this->settings_screen_id ) ) {

					if ( ! isset( $learndash_metaboxes[ $this->settings_screen_id ] ) ) {
						$learndash_metaboxes[ $this->settings_screen_id ] = array();
					}
					$learndash_metaboxes[ $this->settings_screen_id ][ $this->settings_metabox_key ] = $this->settings_metabox_key;

					add_meta_box(
						$this->settings_metabox_key,
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
		public function show_meta_box( $post = null, $metabox = null ) {
			if ( $post ) {
				$this->init( $post );
				$this->show_metabox_nonce_field();
				$this->show_settings_metabox( $this );
			}
		}

		/**
		 * Output Metabox nonce field.
		 */
		public function show_metabox_nonce_field() {
			wp_nonce_field( $this->settings_metabox_key, $this->settings_metabox_key . '[nonce]' );
		}

		/**
		 * Verify Metabox nonce field POST value.
		 */
		public function verify_metabox_nonce_field() {
			if ( ( isset( $_POST[ $this->settings_metabox_key ]['nonce'] ) ) && ( ! empty( $_POST[ $this->settings_metabox_key ]['nonce'] ) ) && ( wp_verify_nonce( esc_attr( $_POST[ $this->settings_metabox_key ]['nonce'] ), $this->settings_metabox_key ) ) ) {
				return true;
			}
		}

		/**
		 * Save Settings Metabox
		 *
		 * @param integer $post_id $Post ID is post being saved.
		 * @param object  $saved_post WP_Post object being saved.
		 * @param boolean $update If update true, otherwise false.
		 * @param array   $settings_field_updates array of settings fields to update.
		 */
		public function save_post_meta_box( $post_id = 0, $saved_post = null, $update = null, $settings_field_updates = null ) {
			if ( true === $this->verify_metabox_nonce_field() ) {
				if ( is_null( $settings_field_updates ) ) {
					$settings_field_updates = $this->get_post_settings_field_updates( $post_id, $saved_post, $update );
				}
				if ( ( ! empty( $settings_field_updates ) ) && ( is_array( $settings_field_updates ) ) ) {
					foreach ( $settings_field_updates as $_key => $_val ) {
						learndash_update_setting( $saved_post, $_key, $_val );
					}
				}
			}
		}

		public function get_post_settings_field_updates( $post_id = 0, $saved_post = null, $update = null ) {
			$settings_field_updates = array();

			if ( ( $saved_post ) && ( is_a( $saved_post, 'WP_Post' ) ) && ( $saved_post->post_type === $this->settings_screen_id ) ) {
				if ( ( true === $this->verify_metabox_nonce_field() ) && ( isset( $_POST[ $this->settings_metabox_key ] ) ) ) {
					$post_values = $_POST[ $this->settings_metabox_key ];

					$this->init( $saved_post );

					$settings_fields_map = $this->get_save_settings_fields_map_form_post_values( $post_values );
					if ( ! empty( $settings_fields_map ) ) {

						// This valiadate_args array will be passed to the validation function for context.
						$validate_args = array(
							'settings_page_id'   => $this->settings_screen_id,
							'setting_option_key' => $this->settings_metabox_key,
							'post_fields'        => $settings_field_updates,
							'field'              => null,
						);

						foreach ( $settings_fields_map as $_internal => $_legacy ) {
							$settings_field = $this->get_settings_field_by_key( $_internal );
							if ( $settings_field ) {
								if ( isset( $post_values[ $_internal ] ) ) {
									$post_value = $post_values[ $_internal ];
								} else {
									$post_value = '';
								}

								$validate_args['field'] = $settings_field['args'];

								if ( ( isset( $settings_field['args']['value_callback'] ) ) && ( ! empty( $settings_field['args']['value_callback'] ) ) && ( is_callable( $settings_field['args']['value_callback'] ) ) ) {
									$post_value = call_user_func( $settings_field['args']['value_callback'], $post_value, $_internal, $validate_args, $post_values );
								} else {
									$post_value = esc_attr( $post_value );
								}

								if ( ( isset( $settings_field['args']['validate_callback'] ) ) && ( ! empty( $settings_field['args']['validate_callback'] ) ) && ( is_callable( $settings_field['args']['validate_callback'] ) ) ) {
									$post_value = call_user_func( $settings_field['args']['validate_callback'], $post_value, $_internal, $validate_args );
								} else {
									$post_value = esc_attr( $post_value );
								}
								$settings_field_updates[ $_legacy ] = $post_value;
							}
						}
					}
				}
			}

			$settings_field_updates = apply_filters( 'learndash_metabox_save_fields', $settings_field_updates, $this->settings_metabox_key, $this->settings_screen_id );
			$settings_field_updates = apply_filters( 'learndash_metabox_save_fields_' . $this->settings_metabox_key, $settings_field_updates, $this->settings_metabox_key, $this->settings_screen_id );

			return $settings_field_updates;
		}

		/**
		 * Show the meta box settings
		 *
		 * @param string $section Section to be shown.
		 */
		public function show_settings_metabox( $metabox = null ) {
			if ( ( $metabox ) && ( is_object( $metabox ) ) && ( isset( self::$_instances[ get_class( $metabox ) ] ) ) ) {
				// If this section defined its own display callback logic.
				if ( ( isset( $metabox->settings_fields_callback ) ) && ( ! empty( $metabox->settings_fields_callback ) ) && ( is_callable( $metabox->settings_fields_callback ) ) ) {
					call_user_func( $metabox->settings_fields_callback, $this->settings_metabox_key );
				} else {
					do_action( 'learndash_metabox_description_before', $metabox->settings_metabox_key );
					$this->show_settings_section_description();
					do_action( 'learndash_metabox_description_after', $metabox->settings_metabox_key );

					if ( learndash_get_post_type_slug( 'quiz' ) === $this->settings_screen_id ) {
						echo '<div class="wrap wpProQuiz_quizEdit">';
					}

					do_action( 'learndash_metabox_options_div_before', $metabox->settings_metabox_key );
					echo '<div class="sfwd sfwd_options ' . esc_attr( $metabox->settings_metabox_key ) . '">';
					do_action( 'learndash_metabox_options_div_inside_top', $metabox->settings_metabox_key );
					$this->show_settings_metabox_fields( $metabox );
					do_action( 'learndash_metabox_options_div_inside_bottom', $metabox->settings_metabox_key );
					echo '</div>';
					do_action( 'learndash_metabox_options_div_after', $metabox->settings_metabox_key );

					if ( learndash_get_post_type_slug( 'quiz' ) === $this->settings_screen_id ) {
						echo '</div>';
					}
				}
			}
		}

		/**
		 * Show Settings Section Fields.
		 *
		 * @param string $page Page shown.
		 * @param string $section Section shown.
		 */
		protected function show_settings_metabox_fields( $metabox = null ) {
			if ( $metabox ) {
				LearnDash_Settings_Fields::show_section_fields( $metabox->setting_option_fields );
			}
		}

		/**
		 * Filter the legacy settings fields when display to remove items
		 * handled by this metabox,
		 *
		 * @since 3.0
		 * @param array $settings_fields Array of settings fields.
		 * @param string $location Screen/Post Type location.
		 * @param array $settings_values Array of current field values.
		 */
		public function display_settings_filter( $settings_fields = array(), $location = '', $settings_values = array() ) {
			if ( ( $location === $this->settings_screen_id ) && ( ! empty( $settings_fields ) ) ) {

				$this->settings_fields_legacy = array();
				$this->settings_values_legacy = array();

				foreach ( $settings_fields as $setting_field_key => $setting_field ) {
					$settings_field_key_name = str_replace( $this->settings_screen_id . '_', '', $setting_field_key );

					$settings_key = array_search( $settings_field_key_name, $this->settings_fields_map );
					if ( false !== $settings_key ) {
						$this->settings_fields_legacy[ $settings_field_key_name ] = $setting_field;
						unset( $settings_fields[ $setting_field_key ] );
					}
				}

				foreach ( $settings_values as $setting_value_key => $setting_value ) {
					$settings_value_key_name = str_replace( $this->settings_screen_id . '_', '', $setting_value_key );

					$settings_key = array_search( $settings_value_key_name, $this->settings_fields_map );
					if ( false !== $settings_key ) {
						$this->settings_values_legacy[ $settings_value_key_name ] = $setting_value;
					}
				}
			}

			return $settings_fields;
		}

		public function get_settings_field_by_key( $field_key = '' ) {
			if ( ! empty( $field_key ) ) {
				if ( ! empty( $this->setting_option_fields ) ) {
					if ( isset( $this->setting_option_fields[ $field_key ] ) ) {
						return $this->setting_option_fields[ $field_key ];
					}
				}

				if ( ! empty( $this->settings_sub_option_fields ) ) {
					foreach ( $this->settings_sub_option_fields as $sub_option_key => $sub_option_fields ) {
						if ( isset( $sub_option_fields[ $field_key ] ) ) {
							return $sub_option_fields[ $field_key ];
						}
					}
				}
			}
		}

		public function init_quiz_edit( $post ) {
			static $pro_quiz_edit = array();

			$quiz_mapper         = new WpProQuiz_Model_QuizMapper();
			$prerequisite_mapper = new WpProQuiz_Model_PrerequisiteMapper();
			$form_mapper         = new WpProQuiz_Model_FormMapper();

			$pro_quiz_id = absint( learndash_get_setting( $post->ID, 'quiz_pro' ) );
			if ( ! isset( $pro_quiz_edit[ $pro_quiz_id ] ) ) {

				if ( ! empty( $pro_quiz_id ) ) {

					$pro_quiz_edit[ $pro_quiz_id ] = array(
						'quiz'                 => $quiz_mapper->fetch( $pro_quiz_id ),
						'prerequisiteQuizList' => $prerequisite_mapper->fetchQuizIds( $pro_quiz_id ),
						'forms'                => $form_mapper->fetch( $pro_quiz_id ),
					);
					$pro_quiz_edit[ $pro_quiz_id ]['quiz']->setPostId( absint( $post->ID ) );

				} else {
					$pro_quiz_edit[ $pro_quiz_id ] = array(
						'quiz'                 => $quiz_mapper->fetch( 0 ),
						'prerequisiteQuizList' => $prerequisite_mapper->fetchQuizIds( 0 ),
						'forms'                => $form_mapper->fetch( 0 ),
					);
				}

				if ( ( isset( $_GET['templateLoadId'] ) ) && ( ! empty( $_GET['templateLoadId'] ) ) ) {
					$template_id = absint( $_GET['templateLoadId'] );
					if ( ! empty( $template_id ) ) {
						$template_mapper = new WpProQuiz_Model_TemplateMapper();
						$template = $template_mapper->fetchById( $template_id );
						$data = $template->getData();
						if ( null !== $data ) {
							if ( ( isset( $data['quiz'] ) ) && ( is_a( $data['quiz'], 'WpProQuiz_Model_Quiz' ) ) ) {
								$data['quiz']->setId( $pro_quiz_edit[ $pro_quiz_id ]['quiz']->getId() );
								$data['quiz']->setPostId( $post->ID );
								$data['quiz']->setName( $pro_quiz_edit[ $pro_quiz_id ]['quiz']->getName() );
								$data['quiz']->setText( 'AAZZAAZZ' );
							} else {
								$data['quiz'] = $quiz_mapper->fetch( 0 );
							}

							if ( ! isset( $data['forms'] ) ) {
								$data['forms'] = array();
							}

							if ( ! isset( $data['prerequisiteQuizList'] ) ) {
								$data['prerequisiteQuizList'] = array();
							}

							if ( isset( $data['_' . learndash_get_post_type_slug( 'quiz' ) ] ) ) {
								$quiz_postmeta = $data['_' . learndash_get_post_type_slug( 'quiz' ) ];
								foreach( $this->settings_fields_map as $_key => $_val ) {
									if ( isset( $data['_' . learndash_get_post_type_slug( 'quiz' ) ][ $_key ] ) ) {
										$this->setting_option_values[ $_key ] = $data['_' . learndash_get_post_type_slug( 'quiz' ) ][ $_key ];
									}
								}
							} else {
								$quiz_postmeta = array();
							}

							$pro_quiz_edit[ $pro_quiz_id ] = array(
								'quiz'                 => $data['quiz'],
								'prerequisiteQuizList' => $data['prerequisiteQuizList'],
								'forms'                => $data['forms'],
								'quiz_postmeta'        => $quiz_postmeta,
							);
						}
					}
				}
			}

			return $pro_quiz_edit[ $pro_quiz_id ];
		}

		public function check_legacy_metabox_fields( $legacy_fields = array() ) {
			if ( ! empty( $legacy_fields ) ) {
				foreach ( $legacy_fields as $field_key => $field_value ) {

					//if ( isset( $this->settings_fields_map[ $field_key ] ) ) {
					if ( in_array( $field_key, $this->settings_fields_map ) ) {
						unset( $legacy_fields[ $field_key ] );
					}
				}
			}

			return $legacy_fields;
		}
		// End of functions.
	}
}
