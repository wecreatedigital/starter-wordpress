<?php

$classes = array(
    'container'   =>  'ld-lesson-item' . ( $quiz['status'] == 'completed' ? ' learndash-complete' : ' learndash-incomplete' ),
    'wrapper'     =>  'ld-lesson-item-preview' . ( $quiz['post']->ID == get_the_ID() ? ' ld-is-current-item ' : '' ),
    'anchor'      =>  'ld-lesson-item-preview-heading ld-primary-color-hover',
    'title'       =>  'ld-lesson-title'
);

if( isset($context) && $context == 'lesson' ) {

    $classes['container'] = 'ld-table-list-item' . ( $quiz['status'] == 'completed' ? ' learndash-complete' : ' learndash-incomplete' );
    $classes['wrapper']   = 'ld-table-list-item-wrapper';
    $classes['anchor']    = 'ld-table-list-item-preview ld-primary-color-hover' . ( $quiz['post']->ID == get_the_ID() ? ' ld-is-current-item ' : '' );
    $classes['title']     = 'ld-topic-title';

} ?>

<div class="<?php echo esc_attr($classes['container']); ?>">
    <div class="<?php echo esc_attr($classes['wrapper']); ?>">
        <a class="<?php echo esc_attr($classes['anchor']); ?>" href="<?php echo esc_attr( learndash_get_step_permalink( $quiz['post']->ID, $course_id ) ); ?>">

            <?php
            learndash_status_icon( $quiz['status'], 'sfwd-quiz', null, true ); ?>

            <div class="<?php echo esc_attr($classes['title']); ?>"><?php echo wp_kses_post($quiz['post']->post_title); ?></div> <!--/.ld-lesson-title-->

        </a> <!--/.ld-lesson-item-preview-heading-->
    </div> <!--/.ld-lesson-item-preview-->
</div>
