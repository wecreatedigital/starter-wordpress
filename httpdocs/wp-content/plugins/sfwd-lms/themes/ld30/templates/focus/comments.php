<div class="ld-focus-comments">
    <?php
    do_action('learndash-focus-content-comments-before', $course_id, $user_id );

    $count = wp_count_comments(get_the_id());
    if ( ( $count->approved > 0) && ( ! isset( $_GET['replytocom'] ) ) ) { 
        ?>
        <div class="ld-focus-comments__heading">
            <div class="ld-focus-comments__header">
                <?php printf( _nx( '%s Comment', '%s Comments', $count->approved, 'comments', 'learndash' ), number_format_i18n( $count->approved ) ); ?>
            </div>
            <div class="ld-focus-comments__heading-actions">
                <div class="ld-expand-button ld-button-alternate ld-expanded" id="ld-expand-button-comments" data-ld-expands="ld-comments" data-ld-expand-text="<?php esc_html_e('Expand Comments', 'learndash'); ?>" data-ld-collapse-text="<?php esc_html_e('Collapse Comments', 'learndash'); ?>">
                <span class="ld-text"><?php _e('Collapse Comments', 'learndash'); ?></span>
                <span class="ld-icon-arrow-down ld-icon"></span>
                </div>
            </div>
        </div>
        <?php 
    } 
    ?>

    <div class="ld-focus-comments__comments ld-expanded" id="ld-comments">
        <div class="ld-focus-comments__comments-items" id="ld-comments-wrapper">
            <?php
            // If comments are open or we have at least one comment, load up the comment template.
            if ( comments_open() || get_comments_number() ) {
                // Add filter to direct comments to our template.
                add_filter( 'comments_template', function( $theme_template = '' ) {
                    $theme_template_alt = SFWD_LMS::get_template( 'focus/comments_list.php', null, null, true );
                    if ( ! empty( $theme_template_alt ) ) {
                        $theme_template = $theme_template_alt;
                    }

                    return $theme_template;
                }, 999, 1 );
                
                comments_template();

                if ( ! isset( $_GET['replytocom'] ) ) {
                    the_comments_navigation();
                }
            }
            ?>
        </div>
    </div>
    <?php 
    do_action('learndash-focus-content-comments-after', $course_id, $user_id );
    if ($count->approved == 0):
    ?>
    <div class="ld-expand-button ld-button-alternate" id="ld-comments-post-button">
        <span class="ld-icon-arrow-down ld-icon"></span>
        <span class="ld-text"><?php _e('Post a comment', 'learndash'); ?></span>
    </div>
    <?php 
    endif;
    $formState = ($count->approved == 0) ? ' ld-collapsed' : '';
    ?>
    <div class="ld-focus-comments__form-container<?php echo $formState; ?>" id="ld-comments-form">
        <?php $args = apply_filters( 'learndash_focus_mode_comment_form_args', array(
            'title_reply' => __( 'Leave a Comment', 'learndash' ),
        ) );

        comment_form($args); 
        ?>
    </div>
    <?php 
    do_action('learndash-focus-content-comment-form-after', $course_id, $user_id );
    ?>
</div> <!--/ld-focus-comments-->