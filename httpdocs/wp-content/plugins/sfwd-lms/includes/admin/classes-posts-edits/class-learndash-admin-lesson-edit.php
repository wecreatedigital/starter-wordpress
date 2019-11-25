<?php
/**
 * LearnDash Admin Lesson Edit Class.
 *
 * @package LearnDash
 * @subpackage Admin
 */

if ( ( class_exists( 'Learndash_Admin_Post_Edit' ) ) && ( ! class_exists( 'Learndash_Admin_Lesson_Edit' ) ) ) {
	/**
	 * Class for LearnDash Admin Lesson Edit.
	 */
	class Learndash_Admin_Lesson_Edit extends Learndash_Admin_Post_Edit {

		/**
		 * Public constructor for class.
		 */
		public function __construct() {
			$this->post_type = learndash_get_post_type_slug( 'lesson' );

			parent::__construct();
		}

		/**
		 * On Load handler function for this post type edit.
		 * This function is called by a WP action when the admin
		 * page 'post.php' or 'post-new.php' are loaded.
		 */
		public function on_load() {
			if ( $this->post_type_check() ) {
				require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/settings/settings-metaboxes/class-ld-settings-metabox-lesson-display-content.php';
				require_once LEARNDASH_LMS_PLUGIN_DIR . '/includes/settings/settings-metaboxes/class-ld-settings-metabox-lesson-access-settings.php';
				/**
				 * Keep for now in case we want to access the legacy metabox logic.
				 */
				//$cpt_instance = SFWD_CPT_Instance::$instances[ $this->post_type ];
				//error_log( 'cpt_instance<pre>'. print_r( $cpt_instance, true ) .'</pre>' );

				parent::on_load();
			}
		}

		/**
		 * Save metabox handler function.
		 *
		 * @param integer $post_id Post ID Question being edited.
		 * @param object  $post WP_Post Question being edited.
		 * @param boolean $update If update true, else false.
		 */
		public function save_post( $post_id = 0, $post = null, $update = false ) {
			if ( ! $this->post_type_check( $post ) ) {
				return false;
			}

			if ( ! parent::save_post( $post_id, $post, $update ) ) {
				return false;
			}

			if ( ! empty( $this->_metaboxes ) ) {
				foreach ( $this->_metaboxes as $_metaboxes_instance ) {
					$settings_fields = array();
					$settings_fields = array_merge( $settings_fields, $_metaboxes_instance->get_post_settings_field_updates( $post_id, $post, $update ) );
					$_metaboxes_instance->save_post_meta_box( $post_id, $post, $update, $settings_fields );
				}
			}
		}

		// End of functions.
	}
}
new Learndash_Admin_Lesson_Edit();
