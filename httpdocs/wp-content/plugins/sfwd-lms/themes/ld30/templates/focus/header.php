<!DOCTYPE html>
<html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo( 'charset' ); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="profile" href="http://gmpg.org/xfn/11">
        <?php wp_head(); do_action( 'learndash-focus-head' ); ?>
    </head>
    <body <?php body_class(); ?>>

        <div class="<?php echo esc_attr( learndash_the_wrapper_class() ); ?>">
            <div class="ld-focus">
                <?php
                /**
                 * Action to add custom content to the start of the focus template
                 *
                 * @since 3.0
                 */
                do_action( 'learndash-focus-template-start', $course_id ); ?>
