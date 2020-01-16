<?php
/**
 * Sample AJAX - PLEASE UNCOMMENT function.php
 * <form action="<?php print admin_url( 'admin-ajax.php' ) ?>" method="post">
 * <input type="hidden" name="action" value="sample_ajax_call">
 * </form>
 */

add_action('wp_sample_ajax_call', 'sample_ajax_call');
function sample_ajax_call()
{
    if (isset($_POST['sample_field'])) {
        update_field('sample_field', wp_strip_all_tags(sanitize_text_field($_POST['sample_field'])));
        echo json_encode(['status' => 'success']);
    }

    wp_die();
}

add_action('wp_ajax_wp_sample_ajax_call', 'wp_sample_ajax_call'); //Logged in
add_action('wp_ajax_nopriv_wp_sample_ajax_call', 'wp_sample_ajax_call'); //Not logged in
