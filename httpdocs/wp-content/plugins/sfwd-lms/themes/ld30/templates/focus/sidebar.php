<?php
$has_access = sfwd_lms_has_access($course_id);
global $course_pager_results;

do_action('learndash-focus-sidebar-before', $course_id, $user_id ); ?>

<div class="ld-focus-sidebar">
    <div class="ld-course-navigation-heading">

        <?php do_action('learndash-focus-sidebar-trigger-wrapper-before', $course_id, $user_id ); ?>

        <span class="ld-focus-sidebar-trigger">
            <?php do_action('learndash-focus-sidebar-trigger-before', $course_id, $user_id ); ?>
            <?php if ( is_rtl() ) { ?>
			<span class="ld-icon ld-icon-arrow-right"></span>
			<?php } else { ?>
			<span class="ld-icon ld-icon-arrow-left"></span>
			<?php } ?>
            <?php do_action('learndash-focus-sidebar-trigger-after', $course_id, $user_id ); ?>
        </span>

        <?php do_action('learndash-focus-sidebar-trigger-wrapper-after', $course_id, $user_id ); ?>

        <?php do_action('learndash-focus-sidebar-heading-before', $course_id, $user_id ); ?>

        <h3>
            <a href="<?php echo esc_url( get_the_permalink($course_id) ); ?>" id="ld-focus-mode-course-heading">
                <span class="ld-icon ld-icon-content"></span>
                <?php echo esc_html( get_the_title($course_id) ); ?>
            </a>
        </h3>
        <?php do_action('learndash-focus-sidebar-heading-after', $course_id, $user_id ); ?>
    </div>
    <div class="ld-focus-sidebar-wrapper">
        <?php do_action('learndash-focus-sidebar-between-heading-navigation', $course_id, $user_id ); ?>
        <div class="ld-course-navigation">
            <div class="ld-course-navigation-list">
                <div class="ld-lesson-navigation">
                    <div class="ld-lesson-items" id="<?php echo esc_attr( 'ld-lesson-list-' . $course_id ); ?>">
                        <?php
                        do_action('learndash-focus-sidebar-nav-before', $course_id, $user_id );

                        $lessons = learndash_get_course_lessons_list( $course_id,  $user_id, learndash_focus_mode_lesson_query_args($course_id) );

                        $widget_instance = apply_filters( 'ld-focus-mode-navigation-settings', array(
                            'show_lesson_quizzes'   =>  true,
                            'show_topic_quizzes'    =>  true,
                            'show_course_quizzes'   =>  true,
                        ) );

                        learndash_get_template_part( 'widgets/navigation/rows.php', array(
                            'course_id'             => $course_id,
                            'widget_instance'       => $widget_instance,
                            'lessons'               => $lessons,
                            'has_access'            => $has_access,
                            'user_id'               => $user_id,
                            'course_pager_results'  => $course_pager_results
                        ), true );

                        do_action('learndash-focus-sidebar-nav-after', $course_id, $user_id ); ?>
                    </div> <!--/.ld-lesson-items-->
                </div> <!--/.ld-lesson-navigation-->
            </div> <!--/.ld-course-navigation-list-->
        </div> <!--/.ld-course-navigation-->
        <?php do_action('learndash-focus-sidebar-after-nav-wrapper', $course_id, $user_id ); ?>
    </div> <!--/.ld-focus-sidebar-wrapper-->
</div> <!--/.ld-focus-sidebar-->

<?php do_action('learndash-focus-sidebar-after', $course_id, $user_id ); ?>
