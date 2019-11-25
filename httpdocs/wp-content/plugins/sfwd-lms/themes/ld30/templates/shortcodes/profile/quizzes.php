<div class="ld-table-list ld-quiz-list">

    <div class="ld-table-list-header ld-primary-background">
        <div class="ld-table-list-title">
            <?php echo esc_html(LearnDash_Custom_Label::get_label('quizzes') ); ?>
        </div> <!--/.ld-table-list-title-->
        <div class="ld-table-list-columns">
        <?php
        $columns = apply_filters( 'learndash-profile-quiz-list-columns', array(
            array(
                'id' => 'certificate',
                'label' => __( 'Certificate', 'learndash' ),
            ),
            array(
                'id' => 'scores',
                'label' => __( 'Score', 'learndash' ),
            ),
            array(
                'id' => 'stats',
                'label' => __( 'Statistics', 'learndash' ),
            ),
            array(
                'id' => 'date',
                'label' => __( 'Date', 'learndash' )
            )
        ) );
        foreach( $columns as $column ): ?>
            <div class="<?php echo esc_attr( 'ld-table-list-column ld-column-' . $column['id'] ); ?>">
                <?php echo esc_html( $column['label'] ); ?>
            </div>
        <?php endforeach; ?>
        </div>
    </div> <!--/.ld-table-list-header-->

    <div class="ld-table-list-items">
        <?php
        foreach( $quiz_attempts[ $course_id ] as $k => $quiz_attempt ):

            learndash_get_template_part( 'shortcodes/profile/quiz-row.php', array(
                'user_id'       => $user_id,
                'quiz_attempt'  => $quiz_attempt,
                'course_id'     => $course_id
            ), true );

        endforeach; ?>
    </div> <!--/.ld-table-list-items-->

    <div class="ld-table-list-footer"></div>

</div> <!--/.ld-quiz-list-->
