<?php
if( !empty($previous_item) && $previous_item instanceof WP_Post ) {

    $alert = array(
        'icon'    => 'alert',
        'message' => '',
        'type'    =>  'warning',
        'button'  => array(
            'url'   => learndash_get_step_permalink( $previous_item->ID, $course_id ),
            'class' => 'learndash-link-previous-incomplete',
            'label' => __( 'Back', 'learndash' ),
            'icon'  =>  'arrow-left',
            'icon-location' => 'left'
        )
    );

    switch( $previous_item->post_type ) {
        case( 'sfwd-quiz' ):
            $alert['message'] = sprintf( esc_html_x( 'Please go back and complete the previous %s.', 'placeholders: quiz label', 'learndash' ), learndash_get_custom_label_lower('quiz') );
            break;
        case( 'sfwd-topic' ):
            $alert['message'] = sprintf( esc_html_x( 'Please go back and complete the previous %s.', 'placeholders: topic label', 'learndash' ), learndash_get_custom_label_lower('topic') );
            break;
        default:
            $alert['message'] = sprintf( esc_html_x( 'Please go back and complete the previous %s.', 'placeholders: lesson label', 'learndash' ), learndash_get_custom_label_lower('lesson') );
            break;
    }

} else {

    $alert['message'] = sprintf( esc_html_x( 'Please go back and complete the previous %s.', 'placeholders lesson', 'learndash' ), learndash_get_custom_label_lower('lesson') );

}

$alert = apply_filters( 'learndash_' . $context . '_progress_alert', $alert, get_the_ID(), $course_id );

/**
 * Action to add custom content before the lesson progression alert
 *
 * @since 3.0
 */
do_action( 'learndash-' . $context . '-progession-alert-before', get_the_ID(), $course_id );

learndash_get_template_part( 'modules/alert.php', $alert, true );

/**
 * Action to add custom content after the lesson progression alert
 *
 * @since 3.0
 */
do_action( 'learndash-'  . $context . '-progession-alert-after', get_the_ID(), $course_id );
