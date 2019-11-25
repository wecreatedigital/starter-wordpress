<?php
/**
 * Displays an informational bar
 *
 * This will have to be variable based on the current users context.
 * Different information is passed in based on if they are on a course, lesson,
 * topic etc...
 *
 * Having it in one place is advantagous over multiple instances of the status
 * bar for Guttenburg block placement.
 *
 * Available Variables:
 *
 * $course_status   : Course Status
 *
 *  $user_id         : Current User ID
 * $logged_in       : User is logged in
 * $current_user    : (object) Currently logged in user object
 *
 * @since 3.0
 *
 * @package LearnDash\Course
 */

/**
 * Thought process:
 *
 * Have some function that checks for the existance of a post type specific
 * variant of a template and falls back to a generic one if it doesn't
 * exist.
 *
 * e.g
 *
 * get_contextualized_template( $slug, $string );
 *
 * if( file_exists( $slug . '-' . $string . '.php' ) ) {
 *      return $slug . '-' . $string . '.php';
 * } else {
 *      return $slug . '-' . 'generic.php';
 * }
 *
 */

/**
 * Action to add custom content before the infobar (all locations)
 *
 * @since 3.0
 */
do_action( 'learndash-all-infobar-before', get_post_type(), $user_id );

SFWD::get_template_part( 'infobar', get_post_type() );

/**
 * Action to add custom content after the infobar (all locations)
 *
 * @since 3.0
 */
do_action( 'learndash-all-infobar-after', get_post_type(), $user_id );


if( $logged_in ):

    /**
     * User is logged in - can contextualize
     * @var [type]
     *
     * Some logic to determine if this is a course lesson, topic, quiz, etc...
     */

     // TODO: Needs to be a filterable template call with more elegant fallback



     if( file_exists('infobar-'.get_post_type().'.php') ) {
         incldue( 'infobar-' . get_post_type() . '.php' );
     } else {
         include( __DIR__ . '/infobar-generic.php' );
     }


else:

    /**
     * User isn't logged in - can't contextualize
     * @var [type]
     */

endif; ?>
