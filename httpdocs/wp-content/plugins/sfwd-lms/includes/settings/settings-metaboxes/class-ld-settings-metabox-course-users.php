<?php
/**
 * LearnDash Settings Metabox for Course Access Settings.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Metabox' ) ) && ( ! class_exists( 'LearnDash_Settings_Metabox_Course_Users_Settings' ) ) ) {
	/**
	 * Class to create the settings section.
	 */
	class LearnDash_Settings_Metabox_Course_Users_Settings extends LearnDash_Settings_Metabox {

		/**
		 * Public constructor for class
		 */
		public function __construct() {
			// What screen ID are we showing on.
			$this->settings_screen_id = 'sfwd-courses';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_metabox_key = 'learndash-course-users-settings';

			// Section label/header.
			$this->settings_section_label = sprintf(
				// translators: placeholder: Course.
				esc_html_x( '%s Users', 'placeholder: Course', 'learndash' ),
				learndash_get_custom_label( 'course' )
			);

			/*
			$this->settings_section_description = sprintf(
				// translators: placeholder: course.
				esc_html_x( 'Controls how users will gain access to the %s', 'placeholder: course', 'learndash' ),
				learndash_get_custom_label_lower( 'course' )
			);
			*/

			parent::__construct();
		}


		/**
		 * Initialize the metabox settings values.
		 */
		public function load_settings_values() {
			parent::load_settings_values();
			if ( true === $this->settings_values_loaded ) {

			}
		}

		/**
		 * Initialize the metabox settings fields.
		 */
		public function load_settings_fields() {
			parent::load_settings_fields();
		}

		protected function show_settings_metabox_fields( $metabox = null ) {
			if ( ( is_object( $metabox ) ) && ( is_a( $metabox, 'LearnDash_Settings_Metabox' ) ) && ( $metabox->settings_metabox_key === $this->settings_metabox_key ) ) {
				if ( ( isset( $metabox->post ) ) && ( is_a( $metabox->post, 'WP_Post ' ) ) ) {
					$course_id = $metabox->post->ID;
				} else {
					$course_id = get_the_ID();
				}

				if ( ( ! empty( $course_id ) ) && ( get_post_type( $course_id ) === learndash_get_post_type_slug( 'course' ) ) ) {

					$course_price_type = learndash_get_setting( $course_id, 'course_price_type' );
					if ( 'open' !== $course_price_type ) {
						$selected_user_ids   = array();
						$metabox_description = '';

						$course_access_users = learndash_get_course_users_access_from_meta( $course_id );
						if ( ! empty( $course_access_users )) {
							$course_access_users = learndash_convert_course_access_list( $course_access_users, true );
						} else {
							$course_access_users = array();
						}

						$course_users_binary_args = array(
							'html_title'            => '',
							'course_id'             => $course_id,
							'search_posts_per_page' => 100,
							'selected_ids'          => $course_access_users
						);

						// Use nonce for verification.
						wp_nonce_field( 'learndash_course_users_nonce_' . $course_id, 'learndash_course_users_nonce' );

						if ( ! empty( $metabox_description ) ) {
							$metabox_description .= ' ';
						}

						$metabox_description .= sprintf(
							// translators: placeholder: Course.
							esc_html_x( 'Users enrolled via Groups using this %s are excluded from the listings below and should be manage via the Group admin screen.', 'placeholder: Course', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'course' )
						);
						?>
						<div id="learndash_course_users_page_box" class="learndash_course_users_page_box">
						<?php
						if ( ! empty( $metabox_description ) ) {
							echo wpautop( wp_kses_post( $metabox_description ) );
						}
						$ld_binary_selector_course_users = new Learndash_Binary_Selector_Course_Users( $course_users_binary_args );
						$ld_binary_selector_course_users->show();
						?>
						</div>
						<?php
					} else {
						?>
						<p>
						<?php
						printf(
							// translators: placeholder: Course.
							esc_html_x( 'The %s price type is set to "open". This means ALL are automatically enrolled.', 'placeholder: Course', 'learndash' ),
							learndash_get_custom_label( 'course' )
						);
						?>
						</p>
						<?php
					}
				}
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
			if ( ( isset( $_POST['learndash_course_users_nonce'] ) ) && ( wp_verify_nonce( $_POST['learndash_course_users_nonce'], 'learndash_course_users_nonce_' . $post_id ) ) ) {
				if ( ( isset( $_POST['learndash_course_users'] ) ) && ( isset( $_POST['learndash_course_users'][ $post_id ] ) ) && ( ! empty( $_POST['learndash_course_users'][ $post_id ] ) ) ) {
					$course_users = (array) json_decode( stripslashes( $_POST['learndash_course_users'][ $post_id ] ) );
					learndash_set_users_for_course( $post_id, $course_users );
				}
			}
		}

		// End of functions.
	}

	add_filter(
		'learndash_post_settings_metaboxes_init_' . learndash_get_post_type_slug( 'course' ),
		function( $metaboxes = array() ) {
			if ( ( ! isset( $metaboxes['LearnDash_Settings_Metabox_Course_Users_Settings'] ) ) && ( class_exists( 'LearnDash_Settings_Metabox_Course_Users_Settings' ) ) ) {
				$metaboxes['LearnDash_Settings_Metabox_Course_Users_Settings'] = LearnDash_Settings_Metabox_Course_Users_Settings::add_metabox_instance();
			}

			return $metaboxes;
		},
		50,
		1
	);
}
