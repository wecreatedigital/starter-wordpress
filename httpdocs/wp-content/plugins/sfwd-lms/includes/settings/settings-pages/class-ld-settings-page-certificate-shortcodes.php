<?php
/**
 * LearnDash Settings Page for Certificate Shortcodes.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Page' ) ) && ( ! class_exists( 'LearnDash_Settings_Page_Certificates_Shortcodes' ) ) ) {
	/**
	 * Class to create the settings page.
	 */
	class LearnDash_Settings_Page_Certificates_Shortcodes extends LearnDash_Settings_Page {

		/**
		 * Public constructor for class
		 */
		public function __construct() {
			$this->parent_menu_page_url = 'edit.php?post_type=sfwd-certificates';
			$this->menu_page_capability = LEARNDASH_ADMIN_CAPABILITY_CHECK;
			$this->settings_page_id     = 'learndash-lms-certificate_shortcodes';
			$this->settings_page_title  = esc_html__( 'Shortcodes', 'learndash' );
			$this->settings_columns     = 1;

			parent::__construct();
		}

		/**
		 * Custom function to show settings page output
		 *
		 * @since 2.4.0
		 */
		public function show_settings_page() {
			?>
			<div  id="certificate-shortcodes"  class="wrap">
				<h2><?php esc_html_e( 'Certificate Shortcodes', 'learndash' ); ?></h2>
				<div class='sfwd_options_wrapper sfwd_settings_left'>
					<div class='postbox ' id='sfwd-certificates_metabox'>
						<div class="inside" style="margin: 11px 0; padding: 0 12px 12px;">
						<?php
						echo wp_kses_post( __( '<b>Shortcode Options</b><p>You may use shortcodes to customize the display of your certificates. Provided is a built-in shortcode for displaying user information.</p><br />
							<p  class="ld-shortcode-header">[usermeta]</p>
						<p>This shortcode takes a parameter named field, which is the name of the user meta data field to be displayed.</p><p>Example: <b>[usermeta field="display_name"]</b> would display the user\'s Display Name.</p><p>See <a href="http://codex.wordpress.org/Function_Reference/get_userdata#Notes">the full list of available fields here</a>.', 'learndash' ) ) . '</p><br />
							
							<p  class="ld-shortcode-header">[quizinfo]</p>
							<p>' . sprintf(
								// translators: placeholder: quiz.
								esc_html_x( 'This shortcode displays information regarding %s attempts on the certificate. This shortcode can use the following parameters:', 'placeholders: quiz', 'learndash' ), 
								learndash_get_custom_label_lower( 'quiz' )
								) . '</p>
								<ul>
								<li><b>SHOW</b>: ' . sprintf( wp_kses_post( _x( 'This parameter determines the information to be shown by the shortcode. Possible values are:
									<ol class="cert_shortcode_parm_list">
										<li>score</li>
										<li>count</li>
										<li>pass</li>
										<li>timestamp</li>
										<li>points</li>
										<li>total_points</li>
										<li>percentage</li>
										<li>quiz_title</li>
										<li>course_title</li>
										<li>timespent</li>
									</ol>
									<br>Example: <b>[quizinfo show="percentage"]</b> shows the percentage score of the user in the %s.', 'placeholder: quiz', 'learndash' ) ), learndash_get_custom_label_lower( 'quiz' ) ) . '<br><br></li>
								<li><b>FORMAT</b>: ' . wp_kses_post( __( 'This can be used to change the timestamp format. Default: "F j, Y, g:i a" shows as <i>March 10, 2001, 5:16 pm</i>. <br>Example: <b>[quizinfo show="timestamp" format="Y-m-d H:i:s"]</b> will show as <i>2001-03-10 17:16:18</i>', 'learndash' ) ) . '</li>
								</ul>
								<p>' . wp_kses_post( __( 'See <a target="_blank" href="http://php.net/manual/en/function.date.php">the full list of available date formatting strings here.</a>', 	'learndash' ) ) . '</p><br />
								
								<p  class="ld-shortcode-header">[courseinfo]</p>
								<p>'. esc_html__( 'This shortcode displays course related information on the certificate. This shortcode can use the following parameters:', 'learndash' ) . '</p>
									<ul>
										<li><b>SHOW</b>: ' . sprintf( wp_kses_post( _x( 'This parameter determines the information to be shown by the shortcode. Possible values are:
											<ol class="cert_shortcode_parm_list">
												<li>course_title</li>
												<li>course_points</li>
												<li>user_course_points</li>
												<li>completed_on</li>
												<li>cumulative_score</li>
												<li>cumulative_points</li>
												<li>cumulative_total_points</li>
												<li>cumulative_percentage</li>
												<li>cumulative_timespent</li>
												<li>aggregate_percentage</li>
												<li>aggregate_score</li>
												<li>aggregate_points</li>
												<li>aggregate_total_points</li>
												<li>aggregate_timespent</li>
											</ol>
											<i>cumulative</i> is average for all %s of the %s.<br>
											<i>aggregate</i> is sum for all %s of the %s.<br>
										<br>Example: <b>[courseinfo show="cumulative_score"]</b> shows average points scored across all quizzes on the course.', 'placeholders: quizzes, course, quizzes, course', 'learndash' ) ), learndash_get_custom_label_lower( 'quizzes' ), learndash_get_custom_label_lower( 'course' ), learndash_get_custom_label_lower( 'quizzes' ), learndash_get_custom_label_lower( 'course' )) . '<br><br></li>
										<li><b>FORMAT</b>: ' . wp_kses_post( __( 'This can be used to change the date format. Default: "F j, Y, g:i a" shows as <i>March 10, 2001, 5:16 pm</i>. <br>Example: <b>[courseinfo show="completed_on" format="Y-m-d H:i:s"]</b> will show as <i>2001-03-10 17:16:18</i>', 'learndash' ) ) . '</li>
									</ul>
								<p>' . wp_kses_post( __( 'See <a target="_blank" href="http://php.net/manual/en/function.date.php">the full list of available date formatting strings here.</a>', 'learndash' ) ) . '</p>';
						?>
						</div>
					</div>
				</div>
			</div>
			<?php
		}
	}
}
add_action( 'learndash_settings_pages_init', function() {
	LearnDash_Settings_Page_Certificates_Shortcodes::add_page_instance();
} );
