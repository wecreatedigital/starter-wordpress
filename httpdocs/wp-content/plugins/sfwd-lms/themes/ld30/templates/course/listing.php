<?php
/**
 * Displays the listing of course content
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
 */ ?>

<?php
/**
 * Display lessons if they exist
 *
 * @var $lessons [array]
 * @since 3.0
 */

if ( !empty($lessons) || !empty($quizzes) ):

    $table_class = apply_filters( 'learndash_course_table_class', 'ld-item-list-items ' . ( isset($lesson_progression_enabled) && $lesson_progression_enabled ? 'ld-lesson-progression' : '' ) );

    /**
     * Display the expand button if lesson has topics
     *
     * @var $lessons [array]
     * @since 3.0
     */ ?>

        <div class="<?php echo esc_attr($table_class); ?>" id="<?php echo esc_attr( 'ld-item-list-' . $course_id ); ?>" data-ld-expand-list="true">
            <?php
            /**
             * Action to add custom content before the course listing
             *
             * @since 3.0
             */
            do_action( 'learndash-course-listing-before', $course_id, $user_id );

            if( $lessons && !empty($lessons) ):

                /**
                 * Loop through each lesson and output a row
                 *
                 * @var $lessons [array]
                 * @since 3.0
                 */

                $sections = learndash_30_get_course_sections($course_id);
                $i = 0;

                foreach( $lessons as $lesson ):
                    learndash_get_template_part('lesson/partials/row.php', array(
                        'count'          => $i,
                        'sections'       => $sections,
                        'lesson'         => $lesson,
                        'course_id'      => $course_id,
                        'user_id'        => $user_id,
                        'lesson_topics'  => @$lesson_topics,
                        'has_access'     => $has_access,
                        'course_pager_results' =>  $course_pager_results,
                    ), true );
                $i++; endforeach;

            endif;

            /**
             * Determine if we should show course quizzes at this point or not
             *
             * @var $show_course_quizzes boolean
             * @since 3.0
             */
            $show_course_quizzes = true;
            if( isset($course_pager_results['pager']) && !empty($course_pager_results['pager']) && $course_pager_results['pager']['total_pages'] !== 0 ):
                $show_course_quizzes = ( $course_pager_results['pager']['paged'] == $course_pager_results['pager']['total_pages'] ? true : false );
            endif;
            $show_course_quizzes = apply_filters( 'learndash-show-course-quizzes', $show_course_quizzes, $course_id, $user_id );

            if( $show_course_quizzes && !empty($quizzes) ): foreach( $quizzes as $quiz ):
                learndash_get_template_part( 'quiz/partials/row.php', array(
                    'course_id' => $course_id,
                    'user_id'   => $user_id,
                    'context'   => 'course',
                    'quiz'      => $quiz,
                    'has_access' => $has_access
                ), true );
            endforeach; endif;

            /**
             * Action to add custom content after the course listing
             *
             * @since 3.0
             */
            do_action( 'learndash-course-listing-after', $course_id, $user_id );

            /**
             * Action to add custom content before the course pagination
             *
             * @since 3.0
             */
            do_action( 'learndash-course-pagination-before', $course_id, $user_id );

            if( isset($course_pager_results['pager']) ):
                learndash_get_template_part(
                    'modules/pagination.php',
                    array(
                        'pager_results' => $course_pager_results['pager'],
                        'pager_context' => ( isset($context) ? $context : 'course_lessons' ),
                        'course_id'     => $course_id
                    ), true
                );
            endif;

            /**
             * Action to add custom content after the course pagination
             *
             * @since 3.0
             */
            do_action( 'learndash-course-pagination-after', $course_id, $user_id ); ?>
        </div> <!--/.ld-item-list-items-->
<?php
endif; ?>
