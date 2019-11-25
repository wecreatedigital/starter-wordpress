<?php
/**
 * Displays a lesson content (topics and quizzes)
 *
 * Available Variables:
 *
 * $user_id     :   The current user ID
 * $course_id   :   The current course ID
 *
 * $lesson      :   The current lesson
 *
 * $topics      :   An array of the associated topics
 *
 * @since 3.0
 *
 * @package LearnDash\Course
 */

global $course_pager_results;

$lesson_progress = learndash_lesson_progress( $lesson['post'], $course_id );
$has_pagination  = ( isset($course_pager_results[ $lesson['post']->ID ]['pager']) ? true : false );
$table_class     = 'ld-table-list ld-topic-list'
                    . ( isset($is_sample) && $is_sample == 'on' ? ' is_sample' : '' )
                    . ( !$has_pagination ? ' ld-no-pagination' : '' );

/**
 * Action to add custom content before topic list
 *
 * @since 3.0
 */
do_action( 'learndash-topic-list-before', $lesson['post']->ID, $course_id, $user_id ); ?>

<div class="<?php echo esc_attr( apply_filters('ld-lesson-table-class', $table_class ) ); ?>" id="<?php echo esc_attr( 'ld-expand-' . $lesson['post']->ID ); ?>">

    <div class="ld-table-list-header ld-primary-background">

        <?php
        /**
         * Action to add custom content before topic listing header
         *
         * @since 3.0
         */
        do_action( 'learndash-topic-list-heading-before', $lesson['post']->ID, $course_id, $user_id ); ?>

        <div class="ld-table-list-title">
            <span class="ld-item-icon">
                <span class="ld-icon ld-icon-content"></span>
            </span>
            <span class="ld-text">
                <?php // translators: Course Status Label.
    			echo sprintf( esc_html_x( '%s Content', 'Lesson Content Label', 'learndash' ), esc_attr(LearnDash_Custom_Label::get_label('lesson')) ); ?>
            </span>
        </div> <!--/.ld-tablet-list-title-->
        <div class="ld-table-list-lesson-details">
            <?php
            /**
             * Action to add custom content before the lesson progress stats
             *
             * @since 3.0
             */
            do_action( 'learndash-topic-list-progress-before', $lesson['post']->ID, $course_id, $user_id ); ?>

            <?php if($lesson_progress): ?>
                <?php if ( true === apply_filters( 'learndash_show_lesson_list_progress', true, $lesson['post']->ID, $course_id, $user_id ) ) { ?>
                   <span class="ld-lesson-list-progress"><?php echo sprintf( esc_html_x( '%s%% Complete', 'Lesson Complete Percentage', 'learndash' ), $lesson_progress['percentage'] ); ?></span>
                <?php } ?>
                <?php if ( true === apply_filters( 'learndash_show_lesson_list_steps', true, $lesson['post']->ID, $course_id, $user_id ) ) { ?>
                    <span class="ld-lesson-list-steps"><?php echo sprintf( esc_html_x( '%1$d/%2$d Steps', 'Lesson Steps Complete', 'learndash' ), $lesson_progress['completed'], $lesson_progress['total'] ); ?></span>
                <?php } ?>
            <?php endif; ?>

            <?php
            /**
             * Action to add custom content after the lesson progress stats
             *
             * @since 3.0
             */
            do_action( 'learndash-topic-list-progress-after', $lesson['post']->ID, $course_id, $user_id ); ?>

            <?php if( 'sfwd-lesson' === get_post_type() ): ?>
                <span class="ld-expand-button" data-ld-expands="<?php echo esc_attr( 'ld-topic-list-' . $lesson['post']->ID ); ?>">
                    <span class="icon-simple-arrow-down ld-icon">
                    <span class="ld-text"><?php esc_html_e( 'Expand', 'learndash' ); ?></span>
                </span> <!--/.ld-expand-button-->
            <?php endif; ?>

        </div> <!--/.ld-table-list-lesson-details-->

        <?php
        /**
         * Action to add custom content after topic listing header
         *
         * @since 3.0
         */
        do_action( 'learndash-topic-list-heading-after', $lesson['post']->ID, $course_id, $user_id ); ?>

    </div> <!--/.ld-table-list-header-->

    <div class="ld-table-list-items" id="<?php echo esc_attr( 'ld-topic-list-' . $lesson['post']->ID ); ?>" data-ld-expand-list>

        <?php
        if( $topics && !empty($topics) ):
            foreach( $topics as $key => $topic ):
                learndash_get_template_part( 'topic/partials/row.php',
                    array(
                        'topic'     => $topic,
                        'user_id'   => $user_id,
                        'course_id' => $course_id,
                    ), true );
            endforeach;
        endif;

        $show_lesson_quizzes = true;
        if( isset($course_pager_results[ $lesson['post']->ID ]['pager']) && !empty($course_pager_results[ $lesson['post']->ID ]['pager'] ) ):
            $show_lesson_quizzes = ( $course_pager_results[ $lesson['post']->ID ]['pager']['paged'] == $course_pager_results[ $lesson['post']->ID ]['pager']['total_pages'] ? true : false );
        endif;
        $show_lesson_quizzes = apply_filters( 'learndash-show-lesson-quizzes', $show_lesson_quizzes, $lesson['post']->ID, $course_id, $user_id );


        if( !empty($quizzes) && $show_lesson_quizzes ): foreach( $quizzes as $quiz ):
            learndash_get_template_part( 'quiz/partials/row.php',
                array(
                    'quiz'      =>  $quiz,
                    'user_id'   =>  $user_id,
                    'course_id' =>  $course_id,
                    'lesson'    =>  $lesson,
                    'context'   =>  'lesson',
                ), true );
    	endforeach; endif; ?>


    </div> <!--/.ld-table-list-items-->

    <div class="ld-table-list-footer">
        <?php
        /**
         * Action to add custom content before the course pagination
         *
         * @since 3.0
         */
        do_action( 'learndash-lesson-pagination-before', $lesson['post']->ID, $course_id, $user_id );

        if ( isset( $course_pager_results[ $lesson['post']->ID ]['pager'] ) ) {
            learndash_get_template_part(
                'modules/pagination.php',
                array(
                    'pager_results' => $course_pager_results[ $lesson['post']->ID ]['pager'],
                    'pager_context' => 'course_topics',
                    'href_query_arg' => 'ld-topic-page',
                    'lesson_id'     => $lesson['post']->ID,
                    'course_id'     => $course_id,
                    'href_val_prefix' => $lesson['post']->ID . '-'
                ), true );
        }

        /**
         * Action to add custom content after the course pagination
         *
         * @since 3.0
         */
        do_action( 'learndash-lesson-pagination-after', $lesson['post']->ID, $course_id, $user_id ); ?>
    </div> <!--/.ld-table-list-footer-->

</div> <!--/.ld-table-list-->

<?php
/**
 * Action to add custom content after topic list
 *
 * @since 3.0
 */
do_action( 'learndash-topic-list-after', $lesson['post']->ID, $course_id, $user_id ); ?>
