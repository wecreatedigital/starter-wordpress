<?php
/**
 * Displays the Prerequisites
 *
 * Available Variables:
 * $current_post : (WP_Post Object) Current Post object being display. Equal to global $post in most cases.
 * $prerequisite_post : (WP_Post Object) Post object needed to be taken prior to $current_post
 * $prerequisite_posts_all : (WP_Post Object) Post object needed to be taken prior to $current_post
 * $content_type : (string) Will contain the singlar lowercase common label 'course', 'lesson', 'topic', 'quiz'
 * $course_settings : (array) Settings specific to current course
 *
 * @since 2.2.1.2
 *
 * @package LearnDash\Course
 */

 $post_links = '';
 $i = 0;
 if ( !empty( $prerequisite_posts_all ) ) {
     foreach( $prerequisite_posts_all as $pre_post_id => $pre_status ) {
         if ( $pre_status === false ) {
             $i++;
             if ( !empty( $post_links ) ) $post_links .= ', ';
             $post_links .= '<a href="'. get_the_permalink( $pre_post_id ) .'">'. get_the_title( $pre_post_id ) .'</a>';
         }
     }
 }

?>
<div class="learndash-wrapper">
    <?php
    $message = '<p>';

    $message .= sprintf( esc_html_x(
           'To take this %1$s, you need to complete the following %2$s first:',
           'placeholders: (1) will be Course, Lesson or Quiz sigular. (2) Course sigular label',
           'learndash'
       ),
       $content_type,
       _n( learndash_get_custom_label_lower( 'course' ), learndash_get_custom_label_lower( 'courses' ), $i, 'learndash' )
   );

   if ( !empty( $post_links ) ) {
       $message .= ' <span class="ld-text">' . $post_links . '</span>';
   }

   $message .= '</p>';

    learndash_get_template_part( 'modules/alert.php', array(
        'type'      =>  'warning',
        'icon'      =>  'alert',
        'message'   =>  $message
    ), true );

?>
</div>
