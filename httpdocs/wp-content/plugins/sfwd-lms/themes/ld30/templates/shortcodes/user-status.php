<?php
/**
  * $course_info - array
  */

  global $pagenow;

  $shortcode_atts_json = htmlspecialchars( json_encode( $shortcode_atts ) );
  $class = 'ld-course-info ld-user-status ' . ( isset($context) && $context === 'widget' ? 'ld-is-widget' : '' );

  $heading = ( isset($context) && $context === 'widget' ? array( '<h4>', '</h4>' ) : array( '<h2>', '</h2>' ) );

  if ( $pagenow != 'profile.php' && $pagenow != 'user-edit.php' && $course_info['courses_registered'] && !empty($course_info['courses_registered']) ): ?>

  <div class="learndash-wrapper">
      <div class="<?php echo esc_attr($class); ?>" data-shortcode-atts="<?php echo $shortcode_atts_json; ?>">
          <div class="ld-item-list">
              <div class="ld-section-heading">
                  <?php echo wp_kses_post( $heading[0] . __( 'Registered Courses', 'learndash' ) . $heading[1] ); ?>
              </div>
              <div class="ld-item-list-items">
                  <?php
                  foreach( $course_info['courses_registered'] as $course_id ):
                      learndash_get_template_part( 'shortcodes/user-status/course-row.php',
                          array(
                              'user_id'            => $course_info['user_id'],
                              'courses_registered' => $course_info['courses_registered'],
                              'shortcode_atts'     => $shortcode_atts,
                              'course_progress'    => $course_info['course_progress'],
                              'course_id'          => $course_id,
                          ), true );
                  endforeach; ?>
              </div> <!--/.ld-item-list-items-->
          </div> <!--/.ld-item-list-->

          <?php
          learndash_get_template_part( 'modules/pagination.php', array(
              'pager_results'   => $course_info['courses_registered_pager'],
              'pager_context'   => 'course_info_courses'
          ), true ); ?>

      </div> <!--/.ld-course-info-courses-->
  </div> <!--/.learn-wrapper-->
<?php endif; ?>
