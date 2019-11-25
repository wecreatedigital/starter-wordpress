<?php
/**
 * Action to add custom content before section title (outside wrapper)
 *
 * @since 3.0
 */
do_action( 'learndash-before-section-heading', $section, $course_id, $user_id ); ?>
<div class="ld-item-list-section-heading ld-item-section-heading-<?php echo esc_attr($section->ID); ?>">
    <?php
    /**
     * Action to add custom content before section title (inside wrapper)
     *
     * @since 3.0
     */
    do_action( 'learndash-before-inner-section-heading', $section, $course_id, $user_id ); ?>
    <div class="ld-lesson-section-heading" aria-role="heading" aria-level="3"><?php echo esc_html($section->post_title); ?></div>
    <?php
    /**
     * Action to add custom content after section title (inside wrapper)
     *
     * @since 3.0
     */
    do_action( 'learndash-after-inner-section-heading', $section, $course_id, $user_id ); ?>
</div>
<?php
/**
 * Action to add custom content after section title (outside wrapper)
 *
 * @since 3.0
 */
do_action( 'learndash-after-section-heading', $section, $course_id, $user_id );
