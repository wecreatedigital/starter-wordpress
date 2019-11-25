<div class="ld-progress">
    <div class="ld-progress-heading">
        <div class="ld-progress-label"><?php printf( esc_html_x( '%s Progress', 'Course Progress Overview Label', 'learndash' ), LearnDash_Custom_Label::get_label( 'course' ) ); ?></div>
        <div class="ld-progress-stats">
            <div class="ld-progress-percentage ld-secondary-color">
                <?php
                printf( esc_html_x( '%s%% Complete', 'Percentage of course complete', 'learndash' ), $progress['percentage'] ); ?>
            </div> <!--/.ld-course-progress-percentage-->
            <div class="ld-progress-steps"><?php echo sprintf( esc_html_x( '%1$d/%2$d Steps', 'placeholder: completed steps, total steps', 'learndash' ), $progress['completed'], $progress['total'] ); ?></div>
        </div> <!--/.ld-course-progress-stats-->
    </div> <!--/.ld-course-progress-heading-->

    <div class="ld-progress-bar">
        <div class="ld-progress-bar-percentage ld-secondary-background" style="width: <?php echo esc_attr( $progress['percentage'] ); ?>%;"></div>
    </div> <!--/.ld-course-progress-bar-->
</div> <!--/.ld-course-progress-->
