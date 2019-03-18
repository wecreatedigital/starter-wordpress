<?php

/**
 * Sample FORM - PLEASE UNCOMMENT function.php
 * <form method="POST" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
 * <input type="hidden" name="action" value="sample_form">
 * </form>
 */
function sample_form() {

  //Add to alert contacts
  if(
    isset($_POST['sample_name']) &&
    is_string($_POST['sample_name']) &&
    isset($_POST['sample_email']) &&
    filter_var($_POST['sample_email'], FILTER_VALIDATE_EMAIL)
  ){

    $sample_email = wp_strip_all_tags(sanitize_email($_POST['sample_email']));
    $sample_name = wp_strip_all_tags(sanitize_text_field($_POST['sample_name']));
    $success = '?success=true';

  } else {
    $success = '?success=false';
  }

  if ( wp_get_referer() ){
    $url = strtok(wp_get_referer(), '?');
    wp_safe_redirect( $url.$success );
  } else {
    wp_safe_redirect( get_home_url() );
  }

}
add_action( 'admin_post_nopriv_sample_form', 'sample_form' ); //Logged in
add_action( 'admin_post_sample_form', 'sample_form' ); //Not logged in
