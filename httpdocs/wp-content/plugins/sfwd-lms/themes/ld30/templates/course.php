<?php
/**
 * Displays a course
 *
 * Available Variables:
 * $course_id       : (int) ID of the course
 * $course      : (object) Post object of the course
 * $course_settings : (array) Settings specific to current course
 *
 * $courses_options : Options/Settings as configured on Course Options page
 * $lessons_options : Options/Settings as configured on Lessons Options page
 * $quizzes_options : Options/Settings as configured on Quiz Options page
 *
 * $user_id         : Current User ID
 * $logged_in       : User is logged in
 * $current_user    : (object) Currently logged in user object
 *
 * $course_status   : Course Status
 * $has_access  : User has access to course or is enrolled.
 * $materials       : Course Materials
 * $has_course_content      : Course has course content
 * $lessons         : Lessons Array
 * $quizzes         : Quizzes Array
 * $lesson_progression_enabled  : (true/false)
 * $has_topics      : (true/false)
 * $lesson_topics   : (array) lessons topics
 *
 * @since 3.0
 *
 * @package LearnDash\Course
 */

$has_lesson_quizzes = learndash_30_has_lesson_quizzes( $course_id, $lessons ); ?>

 <div class="<?php echo esc_attr( learndash_the_wrapper_class() ); ?>">

    <?php
    global $course_pager_results;

    /**
     * Action to add custom content before the topic
     *
     * @since 3.0
     */
    do_action( 'learndash-course-before', get_the_ID(), $course_id, $user_id );

    /**
     * Action to add custom content before the course certificate link
     *
     * @since 3.0
     */
    do_action( 'learndash-course-certificate-link-before', $course_id, $user_id );

    /**
     * Certificate link
     *
     *
     */

    if( $course_certficate_link && !empty($course_certficate_link) ):

        learndash_get_template_part( 'modules/alert.php', array(
            'type'      =>  'success ld-alert-certificate',
            'icon'      =>  'certificate',
            'message'   =>  __( 'You\'ve earned a certificate!', 'learndash' ),
            'button'    =>  array(
                'url'    =>  $course_certficate_link,
                'icon'   =>  'download',
                'label'  =>  __( 'Download Certificate', 'learndash' ),
                'target' => '_new'
            )
        ), true );

    endif;

     /**
      * Action to add custom content after the course certificate link
      *
      * @since 3.0
      */
     do_action( 'learndash-course-certificate-link-after', $course_id, $user_id );


    /**
     * Course info bar
     *
     */
    learndash_get_template_part( 'modules/infobar.php', array(
            'context'       => 'course',
            'course_id'     => $course_id,
            'user_id'       => $user_id,
            'has_access'    => $has_access,
            'course_status' => $course_status,
            'post'          => $post
        ), true ); ?>

    <?php
    /**
     * Filter to add custom content after the Course Status section of the Course template output.
     *
     * @since 2.3
     * See https://bitbucket.org/snippets/learndash/7oe9K for example use of this filter.
     */
    echo apply_filters( 'ld_after_course_status_template_container', '', learndash_course_status_idx( $course_status ), $course_id, $user_id );

    /**
     * Content tabs
     *
     */
    learndash_get_template_part( 'modules/tabs.php', array(
         'course_id' => $course_id,
         'post_id'   => get_the_ID(),
         'user_id'   => $user_id,
         'content'   => $content,
         'materials' => $materials,
         'context'   => 'course'
     ), true );

    /**
     * Identify if we should show the course content listing
     * @var $show_course_content [bool]
     */
    $show_course_content = ( !$has_access && 'on' === $course_meta['sfwd-courses_course_disable_content_table'] ? false : true );

    if( $has_course_content && $show_course_content ): ?>

        <div class="ld-item-list ld-lesson-list">
            <div class="ld-section-heading">

                <?php
                /**
                 * Action to add custom content before the course heading
                 *
                 * @since 3.0
                 */
                do_action( 'learndash-course-heading-before', $course_id, $user_id ); ?>

                <h2><?php printf( esc_html_x( '%s Content', 'Course Content Label', 'learndash' ), esc_attr( LearnDash_Custom_Label::get_label( 'course' ) ) ); ?></h2>

                <?php
                /**
                 * Action to add custom content after the course heading
                 *
                 * @since 3.0
                 */
                do_action( 'learndash-course-heading-after', $course_id, $user_id ); ?>

                <div class="ld-item-list-actions" data-ld-expand-list="true">

                    <?php
                    /**
                     * Action to add custom content after the course content progress bar
                     *
                     * @since 3.0
                     */
                    do_action( 'learndash-course-expand-before', $course_id, $user_id ); ?>

                    <?php
                    // Only display if there is something to expand
                    if( $has_topics || $has_lesson_quizzes ): ?>
                        <div class="ld-expand-button ld-primary-background" id="<?php echo esc_attr( 'ld-expand-button-' . $course_id ); ?>" data-ld-expands="<?php echo esc_attr( 'ld-item-list-' . $course_id ); ?>" data-ld-expand-text="<?php echo esc_attr_e( 'Expand All', 'learndash' ); ?>" data-ld-collapse-text="<?php echo esc_attr_e( 'Collapse All', 'learndash' ); ?>">
                            <span class="ld-icon-arrow-down ld-icon"></span>
                            <span class="ld-text"><?php echo esc_html_e( 'Expand All', 'learndash' ); ?></span>
                        </div> <!--/.ld-expand-button-->
                        <?php
                        // TODO @37designs Need to test this
                        if ( apply_filters( 'learndash_course_steps_expand_all', false, $course_id, 'course_lessons_listing_main' ) ) {
                            ?>
                            <script>
                                jQuery(document).ready(function(){
                                    setTimeout(function(){
                                        jQuery("<?php echo '#ld-expand-button-' . $course_id; ?>").trigger('click');
                                    }, 1000);
                                });
                            </script>
                            <?php
                        }
                    endif;

                    /**
                     * Action to add custom content after the course content expand button
                     *
                     * @since 3.0
                     */
                    do_action( 'learndash-course-expand-after', $course_id, $user_id ); ?>

                </div> <!--/.ld-item-list-actions-->
            </div> <!--/.ld-section-heading-->

            <?php
            /**
             * Action to add custom content before the course content listing
             *
             * @since 3.0
             */
            do_action( 'learndash-course-content-list-before', $course_id, $user_id );

            /**
             * Content content listing
             *
             * @since 3.0
             *
             * ('listing.php');
             */

            learndash_get_template_part( 'course/listing.php', array(
                'course_id'     => $course_id,
                'user_id'       => $user_id,
                'lessons'       => $lessons,
                'lesson_topics' => @$lesson_topics,
                'quizzes'       => $quizzes,
                'has_access'    => $has_access,
                'course_pager_results' =>  $course_pager_results,
                'lesson_progression_enabled' => $lesson_progression_enabled,
            ), true );

            /**
             * Action to add custom content before the course content listing
             *
             * @since 3.0
             */
            do_action( 'learndash-course-content-list-after', $course_id, $user_id ); ?>

        </div> <!--/.ld-item-list-->

    <?php
    endif;

    /**
     * Action to add custom content before the topic
     *
     * @since 3.0
     */
    do_action( 'learndash-course-after', get_the_ID(), $course_id, $user_id );
    learndash_load_login_modal_html();
?>
</div>
