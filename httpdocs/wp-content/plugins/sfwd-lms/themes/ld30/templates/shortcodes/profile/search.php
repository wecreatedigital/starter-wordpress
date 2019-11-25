<?php
$search_query = ( isset($_GET['ld-profile-search']) && !empty($_GET['ld-profile-search']) ? $_GET['ld-profile-search'] : false );
$is_expanded = ( $search_query !== false ? 'ld-expanded' : '' ); ?>

<div class="ld-item-search ld-expandable <?php echo esc_attr($is_expanded); ?>" id="ld-course-search">
    <div class="ld-item-search-wrapper">

        <div class="ld-closer"><?php echo esc_html_e( 'close', 'learndash' ); ?></div>

        <h4><?php printf( esc_html_x( 'Search Your %s', 'Profile Search Courses', 'learndash' ), esc_attr( LearnDash_Custom_Label::get_label( 'courses' ) ) ); ?></h4>

        <form method="get" action="" class="ld-item-search-fields">

            <div class="ld-item-search-name">
                <label for="course_name_field"><?php printf( esc_html_x( '%s Name', 'Profile Course Label', 'learndash' ), esc_attr( LearnDash_Custom_Label::get_label( 'course' ) ) ); ?></label>
                <input type="text" id="course_name_field" value="<?php echo esc_attr($search_query); ?>" class="ld-course-nav-field" name="ld-profile-search">
                <?php if( $search_query !== false ): ?>
                    <a href="<?php the_permalink(); ?>" class="ld-reset-button"><?php esc_html_e( 'reset', 'learndash' ); ?></a>
                <?php endif; ?>
                <input type="hidden" name="ld-profile-page" value="1">
            </div> <!--/.ld-course-search-name-->
            <?php /*
                   * Shortcode doesn't support search by status at this time
                   *
            <div class="ld-item-search-status">
                <label for="course_status"><?php echo esc_html_e( 'Status', 'learndash' ); ?></label>
                <div class="ld-select-field">
                    <select name="course_status">
                        <?php
                        $options = apply_filters( 'learndash_course_search_statues', array(
                            array(
                                'value' =>  'progress',
                                'title' =>  __( 'In Progress', 'learndash' )
                            ),
                            array(
                                'value' =>  'completed',
                                'title' =>  __( 'Completed', 'learndash' )
                            ),
                        ) );
                        foreach( $options as $option ): ?>
                            <option value="<?php echo esc_attr($option['value']); ?>"><?php echo esc_html($option['title']); ?></option>
                        <?php
                        endforeach; ?>
                    </select>
                </div>
            </div> <!--/.ld-course-search-status-->
            */ ?>

            <div class="ld-item-search-submit">
                <input type="submit" class="ld-button" value="<?php esc_html_e( 'Search', 'learndash' ); ?>" name="submit">
            </div> <!--/.ld-course-search-submit-->

        </form> <!--/.ld-course-search-fields-->
    </div> <!--/.ld-course-search-wrapper-->
</div> <!--/.ld-course-search-->
<?php
if( isset($_GET['ld-profile-search']) && !empty($_GET['ld-profile-search']) ):
    learndash_get_template_part( 'shortcodes/profile/search-results.php', array(), true );
endif; ?>
