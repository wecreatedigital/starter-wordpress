<?php
$assignment_post_type_object = get_post_type_object('sfwd-assignment'); ?>

<div class="ld-table-list ld-assignment-list">
    <div class="ld-table-list-header ld-primary-background">
        <div class="ld-table-list-title">
            <?php echo esc_html_e( 'Assignments', 'learndash' ); ?>
        </div> <!--/.ld-table-list-tittle-->
        <div class="ld-table-list-columns">
            <?php
            $cols = apply_filters( 'learndash-profile-assignment-cols', array(
                'comments'    =>  __( '', 'learndash' ),
                'status'    =>  __( 'Status', 'learndash' ),
                'date'      =>  __( 'Date', 'learndash' )
            ) );
            foreach( $cols as $slug => $label ): ?>
                <div class="ld-table-list-column <?php echo esc_attr( 'ld-column-' . $slug ); ?>">
                    <?php echo esc_html($label); ?>
                </div>
            <?php endforeach; ?>
        </div> <!--/.ld-table-list-columns-->
    </div> <!--/.ld-table-list-header-->
    <div class="ld-table-list-items">
         <?php
         if( $assignments->have_posts() ): while( $assignments->have_posts() ): $assignments->the_post();

            global $post;

            learndash_get_template_part( 'shortcodes/profile/assignment-row.php', array(
                'assignment_post_type_object'   => get_post_type_object('sfwd-assignment'),
                'assignment'                    => $post,
                'course_id'                     => $course_id,
                'user_id'                       => $user_id
            ), true );

        endwhile; else:
            // In theory this will never display, but fallback just in case.
            ?>
            <div class="ld-table-list-item">
                <div class="ld-table-list-item-preview">
                    <div class="ld-table-list-title"><?php esc_html_e( 'No assignments at this time', 'learndash' ); ?></div>
                </div> <!--/.ld-table-list-item-preview-->
            </div> <!--/.ld-table-list-item-->
        <?php endif; wp_reset_query(); ?>
    </div> <!--/.ld-table-list-items-->
    <div class="ld-table-list-footer">
        <?php //TODO @37designs check for pagination ?>
    </div>
</div> <!--/.ld-assignment-list-->
