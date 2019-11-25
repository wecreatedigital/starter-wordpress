<?php
/**
 * LearnDash Settings Page Courses Shortcodes.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ( class_exists( 'LearnDash_Settings_Page' ) ) && ( ! class_exists( 'LearnDash_Settings_Page_Courses_Shortcodes' ) ) ) {
	/**
	 * Class to create the settings page.
	 */
	class LearnDash_Settings_Page_Courses_Shortcodes extends LearnDash_Settings_Page {

		/**
		 * Public constructor for class
		 */
		public function __construct() {

			$this->parent_menu_page_url  = 'edit.php?post_type=sfwd-courses';
			$this->menu_page_capability  = LEARNDASH_ADMIN_CAPABILITY_CHECK;
			$this->settings_page_id      = 'courses-shortcodes';

			// translators: Course Shortcodes Label
			$this->settings_page_title   = esc_html_x( 'Shortcodes', 'Course Shortcodes Label', 'learndash' );
			$this->settings_columns      = 1;
			$this->show_quick_links_meta = false;

			parent::__construct();
		}

		/**
		 * Show settiings page output.
		 */

		public function show_settings_page() {
			?>
			<div  id='course-shortcodes'  class='wrap'>
				<h1>
				<?php
				printf(
					// translators: placeholder: Course Label.
					esc_html_x( '%s Shortcodes', 'placeholder: Course Label', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'course' )
				);
				?>
				</h1>
				<div class='sfwd_options_wrapper sfwd_settings_left'>
					<div class='postbox ' id='sfwd-course_metabox'>
						<div class='inside' style='margin: 11px 0; padding: 0 12px 12px;'>
						<?php
						echo '<b>' . esc_html__( 'Shortcode Options', 'learndash' ) . '</b>
							<p>' . sprintf(
								// translators: placeholders: course, lesson, quiz.
								esc_html_x( 'You may use shortcodes to add information to any page/%1$s/%2$s/%3$s. Here are built-in shortcodes for displaying relavent user information.', 'placeholders: course, lesson, quiz', 'learndash' ), learndash_get_custom_label_lower( 'course' ), learndash_get_custom_label_lower( 'lesson' ), learndash_get_custom_label_lower( 'quiz' ) ) . '</p>

							<p  class="ld-shortcode-header">[ld_profile]</p>
							<p>' . sprintf(
								// translators: placeholder: courses, course, quiz. 
								esc_html_x( 'Displays user\'s enrolled %1$s, %2$s progress, %3$s scores, and achieved certificates. This shortcode can take following parameters:', 'placeholder: courses, course, quiz', 'learndash' ), learndash_get_custom_label_lower( 'courses' ), learndash_get_custom_label_lower( 'course' ), learndash_get_custom_label_lower( 'quiz' ) ) . '</p>
							<ul>
								<li><b>order</b>: ' . sprintf( wp_kses_post(
									// translators: placeholders: courses, courses.
									_x( 'sets order of %1$s. Default value DESC. Possible values: <b>DESC</b>, <b>ASC</b>. Example: <b>[ld_profile order="ASC"]</b> shows %2$s in ascending order.', 'placeholders: courses, courses', 'learndash' ) ), learndash_get_custom_label_lower( 'courses' ), learndash_get_custom_label_lower( 'courses' ) ) . '</li>
								<li><b>orderby</b>: ' . sprintf( wp_kses_post(
									// translators: placeholders: courses.
									_x( 'sets what the list of ordered by. Default value ID. Possible values: <b>ID</b>, <b>title</b>. Example: <b>[ld_profile orderby="title" order="ASC"]</b> shows %s in ascending order by title.', 'placeholders: courses', 'learndash' ) ), learndash_get_custom_label_lower( 'courses' ) ) . '</li>
							</ul>
							
							<p>' . wp_kses_post( __( 'See <a target="_blank" href="https://codex.wordpress.org/Class_Reference/WP_Query#Order_.26_Orderby_Parameters">the full list of available orderby options here.</a>', 'learndash' ) ) . '</p><br/>
							
							<p class="ld-shortcode-header">[ld_course_list]</p>
							<p>' . sprintf(
							wp_kses_post(
								// translators: placeholders: courses, courses (URL slug).
								_x( 'This shortcode shows list of %1$s. You can use this shortcode on any page if you dont want to use the default <code>/%2$s</code> page. This shortcode can take following parameters:', 'placeholders: courses, courses (URL slug)', 'learndash' ) ),
							learndash_get_custom_label_lower( 'courses' ),
							LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Permalinks', 'courses' )
						) . '</p>
							<ul>
							<li><b>num</b>: ' . sprintf( wp_kses_post(
								// translators: placeholders: courses, courses.
								_x( 'limits the number of %1$s displayed. Example: <b>[ld_course_list num="10"]</b> shows 10 %2$s.', 'placeholders: courses, courses', 'learndash' ) ), learndash_get_custom_label_lower( 'courses' ), learndash_get_custom_label_lower( 'courses' ) ) . '</li>
							<li><b>order</b>: ' . sprintf( wp_kses_post( 
								// translators: placeholders: courses, courses.
								_x( 'sets order of %1$s. Possible values: <b>DESC</b>, <b>ASC</b>. Example: <b>[ld_course_list order="ASC"]</b> shows %2$s in ascending order.', 'placeholders: courses, courses', 'learndash' ) ), learndash_get_custom_label_lower( 'courses' ), learndash_get_custom_label_lower( 'courses' ) ) . '</li>
							<li><b>orderby</b>: ' . sprintf( wp_kses_post(
								// translators: placeholders: courses.
								_x( 'sets what the list of ordered by. Example: <b>[ld_course_list order="ASC" orderby="title"]</b> shows %s in ascending order by title.', 'placeholders: courses', 'learndash' ) ), learndash_get_custom_label_lower( 'courses' ) ) . '</li>
							<li><b>mycourses</b>: ' . sprintf( wp_kses_post(
								// translators: placeholders: courses, courses.
								_x( 'show current user\'s %1$s. Example: <b>[ld_course_list mycourses="true"]</b> shows %2$s the current user has access to.', 'placeholders: courses, courses', 'learndash' ) ), learndash_get_custom_label_lower( 'courses' ), learndash_get_custom_label_lower( 'courses' ) ) . '</li>
							<li><b>col</b>: ' . wp_kses_post( __( 'number of columns to show when using course grid addon. Example: <b>[ld_course_list col="2"]</b> shows 2 columns.', 'learndash' ) ) . '</li>';

						if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Taxonomies', 'wp_post_category' ) == 'yes' ) {
							echo '<li><b>cat</b>: ' . sprintf( wp_kses_post( 
								// translators: placeholders: courses, courses.
								_x( 'shows %1$s with mentioned category id. Example: <b>[ld_course_list cat="10"]</b> shows %2$s having category with category id 10.', 'placeholders: courses, courses', 'learndash' ) ), learndash_get_custom_label_lower( 'courses' ), learndash_get_custom_label_lower( 'courses' ) ) . '</li>
									
								<li><b>category_name</b>: ' . sprintf( wp_kses_post( 
									// translators: placeholders: courses, courses.
									_x( 'shows %1$s with mentioned category slug. Example: <b>[ld_course_list category_name="math"]</b> shows %2$s having category slug math.', 'placeholders: courses, courses', 'learndash' ) ), learndash_get_custom_label_lower( 'courses' ), learndash_get_custom_label_lower( 'courses' ) ) . '</li>';
							echo '<li><b>categoryselector</b>: ' . wp_kses_post( __( 'shows a course category dropdown. Example: <b>[ld_course_list categoryselector="true"]</b>.', 'learndash' ) ) . '</li>';
						}

						if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Taxonomies', 'wp_post_tag' ) == 'yes' ) {
							echo '<li><b>tag</b>: ' . sprintf( wp_kses_post( 
								// translators: placeholders: courses.
								_x( 'shows %1$s with mentioned tag. Example: <b>[ld_course_list tag="math"]</b> shows %2$s having tag math.', 'placeholders: courses, courses', 'learndash' ) ), learndash_get_custom_label_lower( 'courses' ), learndash_get_custom_label_lower( 'courses' ) ) . '</li>
								<li><b>tag_id</b>: ' . sprintf( wp_kses_post( 
									// translators: placeholders: courses, courses.
									_x( 'shows %1$s with mentioned tag_id. Example: <b>[ld_course_list tag_id="30"]</b> shows %2$s having tag with tag_id 30.', 'placeholders: courses, courses', 'learndash' ) ), learndash_get_custom_label_lower( 'courses' ), learndash_get_custom_label_lower( 'courses' ) ) . '</li>';
						}

						if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Taxonomies', 'ld_course_category' ) == 'yes' ) {
							echo '<li><b>course_cat</b>: ' . sprintf( wp_kses_post( 
								// translators: placeholders: courses, courses.
								_x( 'shows %1$s with mentioned course category id. Example: <b>[ld_course_list course_cat="10"]</b> shows %2$s having course category with category id 10.', 'placeholders: courses, courses', 'learndash' ) ), learndash_get_custom_label_lower( 'courses' ), learndash_get_custom_label_lower( 'courses' ) ) . '</li>

								<li><b>course_category_name</b>: ' . sprintf( wp_kses_post( 
									// translators: placeholders: courses, courses.
									_x( 'shows %1$s with mentioned course category slug. Example: <b>[ld_course_list course_category_name="math"]</b> shows %2$s having course category slug math.', 'placeholders: courses, courses', 'learndash' ) ), learndash_get_custom_label_lower( 'courses' ), learndash_get_custom_label_lower( 'courses' ) ) . '</li>';

							echo '<li><b>course_categoryselector</b>: ' . wp_kses_post( __( 'shows a category dropdown. Example: <b>[ld_course_list course_categoryselector="true"]</b>.', 'learndash' ) ) . '</li>';
						}

						if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Taxonomies', 'ld_course_tag' ) == 'yes' ) {
							echo '<li><b>course_tag</b>: ' . sprintf( wp_kses_post( 
								// translators: placeholders: courses, courses
								_x( 'shows %1$s with mentioned course tag. Example: <b>[ld_course_list course_tag="math"]</b> shows %2$s having course tag math.', 'placeholders: courses, courses', 'learndash' ) ), learndash_get_custom_label_lower( 'courses' ), learndash_get_custom_label_lower( 'courses' ) ) . '</li>
							<li><b>course_tag_id</b>: ' . sprintf( wp_kses_post( 
								// translators: placeholders: courses, courses.
								_x( 'shows %1$s with mentioned course_tag_id. Example: <b>[ld_course_list course_tag_id="30"]</b> shows %2$s having course tag with tag_id 30.', 'placeholders: courses, courses', 'learndash' ) ), learndash_get_custom_label_lower( 'courses' ), learndash_get_custom_label_lower( 'courses' ) ) . '</li>';
						}
							echo '</ul></p>
							<p>' . wp_kses_post( __( 'See the full list of <a target="_blank" href="https://codex.wordpress.org/Class_Reference/WP_Query#Category_Parameters">Category</a> and <a target="_blank" href="https://codex.wordpress.org/Class_Reference/WP_Query#Tag_Parameters">Tag</a> filtering options.', 'learndash' ) ) . '</p><br />


							<p class="ld-shortcode-header">[ld_lesson_list]</p>
							<p>' . sprintf( wp_kses_post( 
								// translators: placeholders: lessons.
								_x( 'This shortcode shows list of %s. You can use this shortcode on any page. This shortcode can take following parameters: num, order, orderby, tag, tag_id, cat, category_name lesson_tag, lesson_tag_id, lesson_cat, lesson_category_name, lesson_categoryselector. See [ld_course_list] above details on using the shortcode parameters.', 'placeholders: lessons', 'learndash' ) ), learndash_get_custom_label_lower( 'lessons' ) ) . '</p><br>
							
							<p  class="ld-shortcode-header">[ld_topic_list]</p>
							<p>' . sprintf( wp_kses_post( 
								// translators: placeholders: topics.
								_x( 'This shortcode shows list of %s. You can use this shortcode on any page. This shortcode can take following parameters: num, order, orderby, tag, tag_id, cat, category_name, topic_tag, topic_tag_id, topic_cat, topic_category_name, topic_categoryselector. See [ld_course_list] above details on using the shortcode parameters.', 'placeholders: topics', 'learndash' ) ), learndash_get_custom_label_lower( 'topics' ) ) . '</p><br>
							
							<p  class="ld-shortcode-header">[ld_quiz_list]</p>
							<p>' . sprintf( wp_kses_post( 
								// translators: placeholders: quizzes.
								_x( 'This shortcode shows list of %s. You can use this shortcode on any page. This shortcode can take following parameters: num, order, orderby. See [ld_course_list] above details on using the shortcode parameters.', 'placeholders: quizzes', 'learndash' ) ), learndash_get_custom_label_lower( 'quizzes' ) ) . '</p><br>
							
							<p class="ld-shortcode-header">[learndash_course_progress]</p><p>' . sprintf( wp_kses_post( 
								// translators: placeholders: course, course, lesson, quiz.
								_x( 'This shortcode displays users progress bar for the %1$s in any %2$s/%3$s/%4$s pages.', 'placeholders: course, course, lesson, quiz', 'learndash' ) ), learndash_get_custom_label_lower( 'course' ), learndash_get_custom_label_lower( 'course' ), learndash_get_custom_label_lower( 'lesson' ), learndash_get_custom_label_lower( 'quiz' ) ) . '</p><br>
							
							<p class="ld-shortcode-header">[visitor]</p>
							<p>' . sprintf( wp_kses_post( 
								// translators: placeholders: course.
								_x( 'This shortcode shows the content if the user is not enrolled in the %s. The shortcode can be used on <strong>any</strong> page or widget area. This shortcode can take following parameters:', 'placeholders: course', 'learndash' ) ), learndash_get_custom_label_lower( 'course' ) ) . '</p>
							<ul>
							<li><b>course_id</b>: ' . sprintf( wp_kses_post( 
								// translators: placeholders: courses, courses.
								_x( 'Optional. Show content if the student does not have access to a specific %s. Example: <b>[visitor course_id="10"]insert any content[/visitor]</b>', 'placeholders: courses, courses', 'learndash' ) ), learndash_get_custom_label_lower( 'course' ) ) . '</li>
							</ul><br>
							
		                    <p class="ld-shortcode-header">[student]</p>
							<p>' . sprintf( wp_kses_post( 
								// translators: placeholders: course.
								_x( 'This shortcode shows the content if the user is enrolled in the %s. The shortcode can be used on <strong>any</strong> page or widget area. This shortcode can take following parameters:', 'placeholders: course', 'learndash' ) ), learndash_get_custom_label_lower( 'course' ) ) . '</p>
							<ul>
							<li><b>course_id</b>: ' . sprintf( wp_kses_post( 
								// translators: placeholders: courses, courses.
								_x( 'Optional. Show content if the student has access to a specific %s. Example: <b>[student course_id="10"]insert any content[/student]</b>', 'placeholders: courses, courses', 'learndash' ) ), learndash_get_custom_label_lower( 'course' ) ) . '</li>
							</ul><br>
							
							<p class="ld-shortcode-header">[course_complete]</p><p>' . sprintf( wp_kses_post(
								// translators: placeholders: course.
								_x( 'This shortcode shows the content if the user has completed the %s. The shortcode can be used on <strong>any</strong> page or widget area. This shortcode can take following parameters:', 'placeholders: course', 'learndash' ) ), learndash_get_custom_label_lower( 'course' ) ) . '</p>
							<ul>
							<li><b>course_id</b>: ' . sprintf( wp_kses_post( 
								// translators: placeholders: courses, courses.
								_x( 'Optional. Show content if the student has access to a specific %s. Example: <b>[course_complete course_id="10"]insert any content[/course_complete]</b>', 'placeholders: courses, courses', 'learndash' ) ), learndash_get_custom_label_lower( 'course' ) ) . '</li>
							<li><b>user_id</b>: ' . wp_kses_post( __( 'Optional. If not provided will use current logged in user. Example: <b>[course_complete course_id="10" user_id="456"]insert any content[/course_complete]</b>', 'learndash' ) ) . '</li>
							</ul><br />

							
							<p class="ld-shortcode-header">[course_inprogress]</p>
							<p>' . sprintf( wp_kses_post( 
								// translators: placeholders: course.
								_x( 'This shortcode shows the content if the user has started but not completed the %s. The shortcode can be used on <strong>any</strong> page or widget area. This shortcode can take following parameters:', 'placeholders: course', 'learndash' ) ), learndash_get_custom_label_lower( 'course' ) ) . '</p>
							<ul>
							<li><b>course_id</b>: ' . sprintf( wp_kses_post( 
								// translators: placeholders: courses, courses.
								_x( 'Optional. Show content if the student has access to a specific %s. Example: <b>[course_inprogress course_id="10"]insert any content[/course_inprogress]</b>', 'placeholders: courses, courses', 'learndash' ) ), learndash_get_custom_label_lower( 'course' ) ) . '</li>
							<li><b>user_id</b>: ' . __( 'Optional. If not provided will use current logged in user. Example: <b>[course_inprogress course_id="10" user_id="456"]insert any content[/course_inprogress]</b>', 'learndash' ) . '</li>
							</ul><br />
							
							<p class="ld-shortcode-header">[course_notstarted]</p>
							<p>' . sprintf( wp_kses_post( 
								// translators: placeholders: course.
								_x( 'This shortcode shows the content if the user has access to the %s but not yet started. The shortcode can be used on <strong>any</strong> page or widget area. This shortcode can take following parameters:', 'placeholders: course', 'learndash' ) ), learndash_get_custom_label_lower( 'course' ) ) . '</p>
							<ul>
							<li><b>course_id</b>: ' . sprintf( wp_kses_post( 
								// translators: placeholders: courses, courses.
								_x( 'Optional. Show content if the student has access to a specific %s. Example: <b>[course_notstarted course_id="10"]insert any content[/course_notstarted]</b>', 'placeholders: courses, courses', 'learndash' ) ), learndash_get_custom_label_lower( 'course' ) ) . '</li>
							<li><b>user_id</b>: ' . wp_kses_post( __( 'Optional. If not provided will use current logged in user. Example: <b>[course_notstarted course_id="10" user_id="456"]insert any content[/course_notstarted]</b>', 'learndash' ) ) . '</li>
							</ul><br />

							<p class="ld-shortcode-header">[ld_course_info]</p>
							<p>' . sprintf( wp_kses_post( 
								// translators: placeholders: course, course.
								_x( 'This shortcode shows the %1$s for the user. This shortcode can take following parameters: user_id if not provided will assume current user. Example usage: <strong>[ld_course_info user_id="123"]</strong> will show the %2$s for the user 123', 'placeholders: course, course', 'learndash' ) ), learndash_get_custom_label_lower( 'courses' ), learndash_get_custom_label_lower( 'courses' ) ) . '</p><br />

							<p class="ld-shortcode-header">[ld_user_course_points]</p>
							<p>' . sprintf( wp_kses_post( 
								// translators: placeholders: course, course.
								_x( 'This shortcode shows the earned %s points for the user. This shortcode can take following parameters: user_id if not provided will assume current user. Example usage: <strong>[ld_user_course_points]</strong></strong>', 'placeholders: course, course', 'learndash' ) ), learndash_get_custom_label_lower( 'course' ) ) . '</p><br />

							<p class="ld-shortcode-header">[user_groups]</p>
							<p>' . esc_html__( 'This shortcode displays the list of groups users are assigned to as users or leaders.', 'learndash' ) . '</p><br/ >

		                    <p class="ld-shortcode-header">[ld_group]</p><p>' . __( 'This shortcode shows the content if the user is enrolled in a specific group. Example usage: <strong>[ld_group]</strong>Welcome to the Group!<strong>[/ld_group]</strong> This shortcode takes the following parameters:', 'learndash' ) . '</p>
							<ul>
							<li><b>group_id</b>: ' . wp_kses_post( __( 'Required. Show content if the student has access to a specific group. Example: <b>[ld_group group_id="16"]insert any content[/ld_group]</b>', 'learndash' ) ) . '</li>
							</ul><br />

		                    <p id="shortcode_ld_video" class="ld-shortcode-header">[ld_video]</p><p>' . sprintf( wp_kses_post(
								// translators: placeholders: Lessons, Topics
								_x( 'This shortcode is used on %1$s and %2$s where Video Progression is enabled. The video player will be added above the content. This shortcode allows positioning the player elsewhere within the content. This shortcode does not take any parameters.', 'placeholders: Lessons, Topics', 'learndash' ) ), LearnDash_Custom_Label::get_label( 'lessons' ), LearnDash_Custom_Label::get_label( 'topics' ) ) . '</p><br />

							
							<p class="ld-shortcode-header">[learndash_payment_buttons]</p>
							<p>' . sprintf( wp_kses_post(
								// translators: placeholders: course, Courses.
								_x( 'This shortcode can show the payment buttons on any page. Example: <strong>[learndash_payment_buttons course_id="123"]</strong> shows the payment buttons for %1$s with %2$s ID: 123', 'placeholders: course, Courses','learndash' ) ), learndash_get_custom_label_lower( 'course' ), LearnDash_Custom_Label::get_label( 'courses' ) ) . '</p><br>
							
							<p class="ld-shortcode-header">[course_content]</p>
							<p>' . sprintf( wp_kses_post(
								// translators: placeholders: Course, lessons, topics, quizzes, course, course, course.
								_x( 'This shortcode displays the %1$s Content table (%2$s, %3$s, and %4$s) when inserted on a page or post. Example: <strong>[course_content course_id="123"]</strong> shows the %5$s content for %6$s with %7$s ID: 123', 'placeholders: Course, lesson, topics, quizzes, course, course, Course', 'learndash' ) ), 
							LearnDash_Custom_Label::get_label( 'course' ), learndash_get_custom_label_lower( 'lessons' ), learndash_get_custom_label_lower( 'topics' ), learndash_get_custom_label_lower( 'quizzes' ), learndash_get_custom_label_lower( 'course' ), learndash_get_custom_label_lower( 'course' ), LearnDash_Custom_Label::get_label( 'course' ) ) . '</p><br>

							<p class="ld-shortcode-header">[ld_course_expire_status]</p>
							<p>' . sprintf( wp_kses_post( 
								// translators: placeholders: course, Course, Course.
								_x( 'This shortcode displays the user %1$s access expire date. Example: <strong>[ld_course_expire_status course_id="111" user="222" label_before="%2$s access will expire on:" label_after="%3$s access expired on:" format="F j, Y g:i a"]</strong>.', 'placeholders: course, Course, Course', 'learndash' ) ), learndash_get_custom_label_lower( 'course' ), LearnDash_Custom_Label::get_label( 'course' ), LearnDash_Custom_Label::get_label( 'course' ) ) . '</p>					
							<ul>
							<li><b>course_id</b>: ' . sprintf( wp_kses_post( 
								// translators: plaeholders: course
								_x( 'The ID of the %s to check. If not provided will attempt to user current post. Example: <b>[ld_course_expire_status course_id="111"]</b> ', 'plaeholders: course', 'learndash' ) ), LearnDash_Custom_Label::get_label( 'course' ) ) . '</li>
							<li><b>user_id</b>: ' . wp_kses_post( __( 'The ID of the user to check. If not provided the current logged in user ID will be used. Example: <b>[ld_course_expire_status user_id="222"]</b>', 'learndash' ) ) . '</li>
							<li><b>label_before</b>: ' . sprintf( wp_kses_post( 
								// translators: placeholders: Course, course
								_x( 'The label prefix shown before the access expires. Default label is "%1$s access will expire on:" Example: <b>[ld_course_expire_status label_before="Your access to this %2$s will expire on:"]</b>', 'placeholders: Course, course', 'learndash' ) ), LearnDash_Custom_Label::get_label( 'course' ), learndash_get_custom_label_lower( 'course' ) ) . '</li>
							<li><b>label_after</b>: ' . sprintf( wp_kses_post(
								// translators: placeholders: Course, course
								_x( 'The label prefix shown after access has expired. Default label is "%1$s access expired on:" Example: <b>[ld_course_expire_status label_after="Your access to this %2$s expired on:"]</b>', 'placeholders: Course, course', 'learndash' ) ), LearnDash_Custom_Label::get_label( 'course' ), learndash_get_custom_label_lower( 'course' ) ) . '</li>
							<li><b>format</b>: ' . wp_kses_post( __( 'This parameter controls the format of the date/time value shown to the user. If not provided the date/time format from your WordPress sytem will be used. Example: <b>[ld_course_expire_status format="F j, Y g:i a"]</b>', 'learndash' ) ) . '</li>
							</ul>
							';
							?>
						</div>
					</div>
				</div>
			</div>
			<?php
		}
	}
}
add_action(
	'learndash_settings_pages_init',
	function() {
		LearnDash_Settings_Page_Courses_Shortcodes::add_page_instance();
	}
);
