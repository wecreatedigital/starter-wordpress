<?php
if( have_posts() ): while( have_posts() ): the_post();

    $cuser     = wp_get_current_user();
    $course_id = learndash_get_course_id();
    $user_id   = ( is_user_logged_in() ? $cuser->ID : false );

    do_action('learndash-focus-header-before', $course_id, $user_id );

    learndash_get_template_part( 'focus/header.php', array(
        'course_id' => $course_id,
        'user_id'   => $user_id,
        'context'   => 'focus'
    ), true );

    do_action('learndash-focus-sidebar-before', $course_id, $user_id );

    learndash_get_template_part( 'focus/sidebar.php', array(
        'course_id' => $course_id,
        'user_id'   => $user_id,
        'context'   => 'focus',
        'course_pager_results' => $course_pager_results
    ), true ); ?>

    <div class="ld-focus-main">

        <?php
        do_action('learndash-focus-masthead-before', $course_id, $user_id );

        learndash_get_template_part( 'focus/masthead.php', array(
            'course_id' => $course_id,
            'user_id'   => $user_id,
            'context'   => 'focus'
        ), true );

        do_action('learndash-focus-masthead-after', $course_id, $user_id ); ?>

        <div class="ld-focus-content">

                <?php do_action('learndash-focus-content-title-before', $course_id, $user_id ); ?>

                <h1><?php the_title(); ?></h1>

                <?php do_action('learndash-focus-content-content-before', $course_id, $user_id ); ?>

                <?php the_content(); ?>

                <?php do_action('learndash-focus-content-content-after', $course_id, $user_id ); ?>

                <?php
                if ( is_user_logged_in() && comments_open() ): 
                
                    learndash_get_template_part('focus/comments.php', array(
                        'course_id' => $course_id,
                        'user_id'   => $user_id,
                        'context'   => 'focus'
                    ), true);
                
                endif; 
                
                ?>

                <?php do_action('learndash-focus-content-comments-after', $course_id, $user_id ); ?>

        </div> <!--/.ld-focus-content-->

    </div> <!--/.ld-focus-main-->

    <?php
    do_action('learndash-focus-content-footer-before', $course_id, $user_id );

    learndash_get_template_part( 'focus/footer.php', array(
        'course_id' => $course_id,
        'user_id'   => $user_id,
        'context'   => 'focus'
    ), true );

    do_action('learndash-focus-content-footer-after', $course_id, $user_id ); 

endwhile; else:

        learndash_get_template_part( 'modules/alert.php', array(
            'type'      =>  'warning',
            'icon'      =>  'alert',
            'message'   =>  __( 'No content found at this address', 'learndash' )
        ), true );

endif; ?>
