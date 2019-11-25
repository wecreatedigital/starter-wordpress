<?php
/**
 * Action to add custom content before section title (outside wrapper)
 *
 * @since 3.0
 */
do_action( 'learndash-nav-before-section-heading', $section, $course_id, $user_id ); ?>
<div class="ld-lesson-item-section-heading ld-lesson-item-section-heading-<?php echo esc_attr($section->ID); ?>">
    <?php
    /**
     * Action to add custom content before section title (inside wrapper)
     *
     * @since 3.0
     */
    do_action( 'learndash-nav-before-inner-section-heading', $section, $course_id, $user_id ); ?>
    <span class="ld-lesson-section-heading" role="heading" aria-level="3"><?php echo esc_html($section->post_title); ?></span>
    <?php
    /**
     * Action to add custom content after section title (inside wrapper)
     *
     * @since 3.0
     */
    do_action( 'learndash-nav-after-inner-section-heading', $section, $course_id, $user_id ); ?>
</div>
<?php
/**
 * Action to add custom content after section title (outside wrapper)
 *
 * @since 3.0
 */
do_action( 'learndash-nav-after-section-heading', $section, $course_id, $user_id );
