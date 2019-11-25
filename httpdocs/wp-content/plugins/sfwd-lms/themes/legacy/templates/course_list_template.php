<?php
/**
 * This file contains the code that displays the course list.
 * 
 * @since 2.1.0
 * 
 * @package LearnDash\Course
 */
?>
<?php the_title( '<h2 class="ld-entry-title entry-title"><a href="' . learndash_get_step_permalink( get_the_ID(), $course_id ) . '" title="' . the_title_attribute( 'echo=0' ) . '" rel="bookmark">', '</a></h2>' ); ?>

<div class="ld-entry-content entry-content">
	<?php 
		if ( ( isset( $shortcode_atts['show_thumbnail'] ) ) && ( $shortcode_atts['show_thumbnail'] == 'true' ) ) {
			the_post_thumbnail(); 
		}
	?>
	<?php
		if ( ( isset( $shortcode_atts['show_content'] ) ) && ( $shortcode_atts['show_content'] == 'true' ) ) {
			global $more; $more = 0;
			the_content( __( 'Read more.', 'learndash' ) ); 
		}
	?>
</div>
