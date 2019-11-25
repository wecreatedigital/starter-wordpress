<?php

/**
 * DEPRICATED
 * @var [type]
 */

$certificateLink = null;

/**
 * Identify the quiz status and certification
 *
 */
if( isset($quiz_attempt['has_graded']) && true === $quiz_attempt['has_graded'] && true === LD_QuizPro::quiz_attempt_has_ungraded_question($quiz_attempt) ):
    $status = 'pending';
else:
    $certificateLink = @$quiz_attempt['certificate']['certificateLink'];
    $status = empty( $quiz_attempt['pass'] ) ? 'failed' : 'passed';
endif;

/**
 * Set the quiz title and link
 *
 */
$quiz_title = ! empty( $quiz_attempt['post']->post_title ) ? $quiz_attempt['post']->post_title : @$quiz_attempt['quiz_title'];
$quiz_link = ! empty( $quiz_attempt['post']->ID ) ? learndash_get_step_permalink( intval($quiz_attempt['post']->ID), $course_id ) : '#';

/**
 * Only display the quiz if we've found a title
 *
 * @var [string] $quiz_title
 */
if( !empty($quiz_title) ): ?>

    <div class="<?php echo esc_attr($status); ?>">

        <?php echo $status; ?>

        <a href="<?php echo esc_attr($quiz_link); ?>"><?php echo esc_html($quiz_title); ?></a>

        <?php
        if( !empty($certificateLink) ): ?>
            <a href="<?php echo esc_attr($certificateLink); ?>&time=<?php echo esc_attr($quiz_attempt['time']) ?>" target="_blank">
                <?php esc_html_e( 'Certificate', 'learndash' ); ?>
            </a>
        <?php
        else:
            echo '-';
        endif; ?>

        <div class="scores">
            <?php
            if( isset($quiz_attempt['has_graded']) && true === $quiz_attempt['has_graded'] && true === LD_QuizPro::quiz_attempt_has_ungraded_question($quiz_attempt) ):
                echo esc_html_x('Pending', 'Pending Certificate Status Label', 'learndash');
            else:
                echo round( $quiz_attempt['percentage'], 2 ) .'%';
            endif; ?>
        </div>

        <div class="statistics">
            <?php
            if( $user_id == get_current_user_id() || learndash_is_admin_user() || learndash_is_group_leader_user() ):

                if( !isset($quiz_attempt['statistic_ref_id']) || empty($quiz_attempt['statistic_ref_id']) ):
                    $quiz_attempt['statistic_ref_id'] = learndash_get_quiz_statistics_ref_for_quiz_attempt( $user_id, $quiz_attempt );
                endif;

                if( isset($quiz_attempt['statistic_ref_id']) && !empty($quiz_attempt['statistic_ref_id']) ):
                    /**
                     *	 @since 2.3
                     * See snippet on use of this filter https://bitbucket.org/snippets/learndash/5o78q
                     */
                    if( apply_filters( 'show_user_profile_quiz_statistics', get_post_meta( $quiz_attempt['post']->ID, '_viewProfileStatistics', true ), $user_id, $quiz_attempt, basename( __FILE__ ) ) ): ?>
                        <a class="user_statistic" data-statistic_nonce="<?php echo wp_create_nonce( 'statistic_nonce_'. $quiz_attempt['statistic_ref_id'] .'_'. get_current_user_id() . '_'. $user_id ); ?>" data-user_id="<?php echo $user_id ?>" data-quiz_id="<?php echo $quiz_attempt['pro_quizid'] ?>" data-ref_id="<?php echo intval( $quiz_attempt['statistic_ref_id'] ) ?>" href="#"><div class="statistic_icon"></div></a>
                    <?php
                    endif;

                endif;

            endif; ?>
        </div>

        <div class="quiz_date"><?php echo learndash_adjust_date_time_display($quiz_attempt['time']); ?></div>

        <?php
        /**
         * TODO @37designs Need to query for essays related to this assignment
         */

        $quiz_essays = function_for_related_essays( $quiz_attempt['post']->ID );

        if( !empty($quiz_essays) ):
            foreach( $quiz_essays as $essay ):
                SFWD_LMS::get_template('quiz/partials/essay-row.php', array(
                    'essay'     => $essay,
                    'context'   => $context
                ) );
            endforeach;
        endif; ?>


    </div>
<?php endif; ?>
