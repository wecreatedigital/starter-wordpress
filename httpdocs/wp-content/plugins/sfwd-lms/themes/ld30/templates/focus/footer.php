                    <?php
                    /**
                     * Action to add custom content after the assignments upload message
                     *
                     * @since 3.0
                     */
                    do_action( 'learndash-focus-template-end', $course_id ); ?>
                </div> <!--/.ld-focus-->
            </div> <!--/.ld-learndash-wrapper-->

            <?php learndash_load_login_modal_html(); ?>
            <?php wp_footer(); ?>

    </body>
</html>
