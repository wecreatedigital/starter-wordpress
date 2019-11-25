<?php
/**
 * @var [type]
 */
$course       = get_post( $course_id);
$course_link  = get_permalink( $course_id );
$course_class = apply_filters( 'learndash-course-list-shortcode-course-class', '' ); ?>

<?php
/**
 * Action to add custom content before the course row
 *
 * @since 3.0
 */
do_action( 'learndash-course-row-before', $course_id, $user_id ); ?>

<div id="course-<?php echo esc_attr( $user_id ) . '-' . esc_attr( $course->ID ); ?>" class="<?php echo esc_attr($course_class); ?>">
    <div>

        <?php
        /**
         * Action to add custom content before the course row status
         *
         * @since 3.0
         */
        do_action( 'learndash-course-row-status-before', $course_id, $user_id ); ?>

        <?php
        /**
         * Action to add custom content before the course row link
         *
         * @since 3.0
         */
        do_action( 'learndash-course-row-link-before', $course_id, $user_id ); ?>

        <a href="<?php echo esc_attr( $course_link ); ?>">
            <?php echo wp_kses_post($course->post_title); ?>
        </a>

        <?php
        /**
         * Action to add custom content before the course row certificate
         *
         * @since 3.0
         */
        do_action( 'learndash-course-row-certificate-before', $course_id, $user_id ); ?>

        <?php
        /**
         * Action to add custom content before the course row link
         *
         * @since 3.0
         */
        do_action( 'learndash-course-row-expand-before', $course_id, $user_id ); ?>

        <?php
        /**
         * Action to add custom content before the course row link
         *
         * @since 3.0
         */
        do_action( 'learndash-course-row-expand-after', $course_id, $user_id ); ?>

    </div>
</div>
