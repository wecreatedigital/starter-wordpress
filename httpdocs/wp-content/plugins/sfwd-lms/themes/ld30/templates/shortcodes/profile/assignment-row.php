<?php
$assignment_points = learndash_get_points_awarded_array( $assignment->ID ); ( $assignment->ID );  ?>

<div class="ld-table-list-item">
    <div class="ld-table-list-item-preview">
        <div class="ld-table-list-title">

            <a class="ld-item-icon" href='<?php echo esc_attr( get_post_meta( $assignment->ID, 'file_link', true ) ); ?>' target="_blank">
                <span class="ld-icon ld-icon-assignment" aria-label="<?php esc_html_e( 'Download Assignment', 'learndash' ); ?>"></span>
            </a>

            <?php
            $assignment_link = ( true === $assignment_post_type_object->publicly_queryable ? get_permalink( $assignment->ID ) : get_post_meta( $assignment->ID, 'file_link', true ) ); ?>

            <a class="ld-text" href="<?php echo esc_url($assignment_link); ?>"><?php echo esc_html( get_the_title($assignment->ID) ); ?></a>

        </div>
        <div class="ld-table-list-columns">
            <?php
            // Use an array so it can be filtered later
            $row_columns = array();

            /**
             * Comment count and link to assignment
             * @var [type]
             */

            if( true === $assignment_post_type_object->publicly_queryable ):

                /**
                 * Action to add custom content before assignment post link
                 *
                 * @since 3.0
                 */
                do_action( 'learndash-assignment-row-columns-before', $assignment, get_the_ID(), $course_id, $user_id );

                ob_start(); ?>

                <?php
                if( post_type_supports( 'sfwd-assignment', 'comments' ) && apply_filters( 'comments_open', $assignment->comment_status, $assignment->ID ) ):

                    /**
                     * Action to add custom content before assignment comment count & link
                     *
                     * @since 3.0
                     */
                    do_action( 'learndash-assignment-row-comments-before', $assignment, get_the_ID(), $course_id, $user_id ); ?>

                    <a href='<?php echo esc_attr( get_comments_link( $assignment->ID ) ); ?>' data-ld-tooltip="<?php 
                    echo sprintf(
                        // translators: placeholder: commentd count.
                        esc_html_x( '%d Comments', 'placeholder: commentd count', 'learndash'),
                        get_comments_number($assignment->ID)
                    ); ?>">
                        <?php
                        echo esc_html(get_comments_number( $assignment->ID )); ?>
                        <span class="ld-icon ld-icon-comments"></span>
                    </a>

                    <?php
                    // Add the markup to the array
                    $row_columns['comments'] = ob_get_clean(); ob_flush();

                    /**
                     * Action to add custom content after assignment comment count & link
                     *
                     * @since 3.0
                     */
                    do_action( 'learndash-assignment-row-comments-after', $assignment, get_the_ID(), $course_id, $user_id );

                endif;
            endif;

            if( !learndash_is_assignment_approved_by_meta($assignment->ID) && !$assignment_points ):

                ob_start(); ?>

                <span class="ld-status ld-status-waiting ld-tertiary-background">
                    <span class="ld-icon ld-icon-calendar"></span>
                    <span class="ld-text"><?php esc_html_e( 'Waiting Review', 'learndash' ); ?></span>
                </span> <!--/.ld-status-waiting-->

                <?php
                $row_columns['status'] = ob_get_clean(); ob_flush();

            elseif( $assignment_points || learndash_is_assignment_approved_by_meta($assignment->ID) ):

                ob_start(); ?>

                <span class="ld-status ld-status-complete">
                    <span class="ld-icon ld-icon-checkmark"></span>
                    <?php
                    if( $assignment_points ):
                        echo sprintf( esc_html__( '%s/%s Points Awarded ', 'learndash' ), $assignment_points['current'], $assignment_points['max'] ) . ' - ';
                    endif;

                    esc_html_e( 'Approved', 'learndash' ); ?>
                </span>

            <?php
                $row_columns['status'] = ob_get_clean(); ob_flush();

            endif;

            $row_columns['date'] = get_the_date( get_option('date_format'), $assignment->ID );

            // Apply a fitler so devs can add more info here later
            $row_columns = apply_filters( 'learndash-assignment-list-columns-content', $row_columns );
            if( !empty($row_columns) ): foreach( $row_columns as $slug => $content ):

                do_action('learndash-assignment-row-' . $slug . '-before', $assignment, get_the_ID(), $course_id, $user_id ); ?>
                <div class="<?php echo esc_attr( 'ld-table-list-column ld-' . $slug . '-column' ); ?>">
                    <?php
                    do_action('learndash-assignment-row-' . $slug . '-inside-before', $assignment, get_the_ID(), $course_id, $user_id );

                    echo wp_kses_post($content);

                    do_action('learndash-assignment-row-' . $slug . '-inside-after', $assignment, get_the_ID(), $course_id, $user_id ); ?>
                </div>
                <?php
                do_action('learndash-assignment-row-' . $slug . '-after', $assignment, get_the_ID(), $course_id, $user_id ); ?>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>
