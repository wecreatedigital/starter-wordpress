<?php
/**
 * This file contains the wrapper for a custom alert message
 *
 * @since 3.0
 *
 * @package LearnDash
 */

$class = apply_filters( 'ld-alert-class', 'ld-alert ' . ( isset($type) ? 'ld-alert-' . $type : '' ) );
$icon  = apply_filters( 'ld-alert-icon', 'ld-alert-icon ld-icon ' . ( isset($icon) ? 'ld-icon-' . $icon : false ) );

if( isset($message) && !empty($message) ):

    // Adjust the message conditionally
    $message = apply_filters( 'learndash_alert_message', $message );

    /**
     * Add content between before an alert
     *
     * @since 3.0
     */
    do_action( 'learndash-alert-before', $class, $icon, $message ); ?>

    <div class="<?php echo esc_attr($class); ?>">
        <div class="ld-alert-content">

            <?php
            /**
             * Add content between before an alert icon
             *
             * @since 3.0
             */
            do_action( 'learndash-alert-icon-before', $class, $icon, $message );

            if( isset($icon) && !empty($icon) ): ?>
                <div class="<?php echo esc_attr($icon); ?>"></div>
            <?php
            endif;

            /**
             * Add content after an alert icon
             *
             * @since 3.0
             */
            do_action( 'learndash-alert-icon-after', $class, $icon, $message );

            ?><div class="ld-alert-messages"><?php
            echo wp_kses_post( $message );
            ?></div><?php

            /**
             * Add content after an alert message
             *
             * @since 3.0
             */
            do_action( 'learndash-alert-message-after', $class, $icon, $message ); ?>
        </div>

        <?php
        /**
         * Add content between alert message and button
         *
         * @since 3.0
         */
        do_action( 'learndash-alert-between-message-button', $class, $icon, $message );

        if( isset($button) ):

            $button_target = ( isset($button['target']) ? 'target="' . esc_attr($button['target']) . '"' : '' );
            $button_class  = 'ld-button ' . ( isset($button['class']) ? $button['class'] : '' ); ?>

                <a class="<?php echo esc_attr($button_class); ?>" href="<?php echo esc_attr($button['url']); ?>" <?php echo $button_target; ?>>
                    <?php if( isset($button['icon']) ): ?>
                        <span class="<?php echo esc_attr( 'ld-icon ld-icon-' . $button['icon'] ); ?>"></span>
                    <?php endif; ?>
                    <?php echo esc_html($button['label']); ?>
                </a>
        <?php
        endif;

        /**
         * Add content after an alert button
         *
         * @since 3.0
         */
        do_action( 'learndash-alert-content-after', $class, $icon, $message ); ?>
    </div>

    <?php
    /**
     * Add content after an alert
     *
     * @since 3.0
     */
    do_action( 'learndash-alert-after', $class, $icon, $message );

endif; ?>
