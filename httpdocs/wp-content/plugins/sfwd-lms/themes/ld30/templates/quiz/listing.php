<?php
// Extra sanity check that this lesson has quizzes
if( !empty($quizzes) ):

    /**
     * Action to add custom content before topic list
     *
     * @since 3.0
     */
    do_action( 'learndash-' . $context . '-quiz-list-before', get_the_ID(), $course_id, $user_id );
    $is_sample = false;
    if ( ( isset( $lesson_id ) ) && ( ! empty( $lesson_id ) ) ) {
        $is_sample = learndash_get_setting( $lesson_id, 'sample_lesson' );
    }
    $table_class     = 'ld-table-list ld-topic-list'
                    . ( isset( $is_sample ) && $is_sample == 'on' ? ' is_sample' : '' )
    ?>
    <div class="<?php echo $table_class; ?>">

        <div class="ld-table-list-header ld-primary-background">
            <?php
            /**
             * Action to add custom content before quiz listing header
             *
             * @since 3.0
             */
            do_action( 'learndash-' . $context . '-quiz-list-heading-before', get_the_ID(), $course_id, $user_id ); ?>

            <div class="ld-table-list-title"><?php echo LearnDash_Custom_Label::get_label( 'quizzes' ); ?></div>

            <?php
            /**
             * Action to add custom content before the lesson progress stats
             *
             * @since 3.0
             */
            do_action( 'learndash-' . $context . '-quiz-list-progress-before', get_the_ID(), $course_id, $user_id ); ?>

            <?php
            /**
             * TODO @37designs - need to create a function to count quizes complete / incomplete
             *
            <span><?php sprintf( '%s% Complete', $lesson_progress['percentage'] ); ?></span>
            <span><?php sprintf( '%s/%s Steps', $lesson_progress['complete'], $lesson_progress['total'] ); ?></span>
            */ ?>

            <div class="ld-table-list-lesson-details"></div>

                <?php
                /**
                 * Action to add custom content after the lesson progress stats
                 *
                 * @since 3.0
                 */
                do_action( 'learndash-' . $context . '-quiz-list-progress-after', get_the_ID(), $course_id, $user_id ); ?>

                <?php
                /**
                 * Action to add custom content after topic listing header
                 *
                 * @since 3.0
                 */
                do_action( 'learndash-' . $context . '-quiz-list-heading-after', get_the_ID(), $course_id, $user_id ); ?>

            </div> <!--/.ld-table-list-header-->

            <div class="ld-table-list-items">

                <?php
                // TODO @37designs Need to check pagination to see if we should show these - think there is a setting here too to disable quizzes in listing?

                foreach( $quizzes as $quiz ):
                    learndash_get_template_part( 'quiz/partials/row.php', array(
                        'quiz'      => $quiz,
                        'course_id' => $course_id,
                        'user_id'   => $user_id,
                        'context'   => $context,
                    ), true );
                endforeach; ?>

            </div> <!--/.ld-table-list-items-->

            <div class="ld-table-list-footer"></div>

    </div>

    <?php
    /**
     * Action to add custom content after topic list
     *
     * @since 3.0
     */
    do_action( 'learndash-' . $context . '-quiz-list-after', get_the_ID(), $course_id, $user_id ); ?>

<?php endif;
