<?php
foreach( $quizzes as $k => $v ):

    $quiz = get_post( $v['quiz'] );

    if ( !$quiz instanceof WP_Post || $quiz->post_type != 'sfwd-quiz' ) {
        if ( ( isset( $v['pro_quizid'] ) ) && ( !empty( $v['pro_quizid'] ) ) ) {
            $quiz_post_id = learndash_get_quiz_id_by_pro_quiz_id( intval( $v['pro_quizid'] ) );
            if ( !empty( $quiz_post_id ) ) {
                $quiz = get_post( $quiz_post_id );
            }
        }
    }


    if ( ( !( $quiz instanceof WP_Post ) ) || ( $quiz->post_type != 'sfwd-quiz' ) ) {
        continue;
    }

    $certificateLink = '';
    $certificate_threshold = 0;

    if (!isset($v['has_graded']))
        $v['has_graded'] = false;

    if ( true === $v['has_graded'] && true === LD_QuizPro::quiz_attempt_has_ungraded_question( $v ) ) {
        $certificateLink = '';
        $certificate_threshold = 0;
        $passstatus = 'red';
    } else {
        $c = learndash_certificate_details( $v['quiz'], $user_id );
        if ((isset($c['certificateLink'])) && (!empty($c['certificateLink'])))
            $certificateLink = $c['certificateLink'];

        if ((isset($c['certificate_threshold'])) && ('' !== $c['certificate_threshold']))
            $certificate_threshold = $c['certificate_threshold'];

        $passstatus = isset( $v['pass'] ) ? ( ( $v['pass'] == 1 ) ? 'green' : 'red' ) : '';
    }

    $quiz_title = ! empty( $quiz->post_title ) ? $quiz->post_title : @$v['quiz_title'];

    $quiz_course_id = 0;
    if ( isset( $v['course'] ) ) {
        $quiz_course_id = intval( $v['course'] );
    } else {
        $quiz_course_id = learndash_get_course_id( $quiz, true );
    }

    if ( ! empty( $quiz_title ) ) : ?>

        <div class="ld-item-list-item">
            <a class="ld-item-list-item-preview" href="<?php echo esc_attr( learndash_get_step_permalink( $quiz->ID, $course_id ) ); ?>">

                <span class="<?php echo esc_attr($quiz_icon_class); ?>">
                	<span class="ld-icon ld-icon-quiz ld-status-icon"></span>
                </span>
                <span class="ld-quiz-title"><?php echo wp_kses_post($quiz_title); ?></span> <!--/.ld-lesson-title-->

            </a> <!--/.ld-lesson-item-preview-heading-->
        </div>
    <?php endif;
endforeach; ?>
