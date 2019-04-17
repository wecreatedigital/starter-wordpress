<?php
/*
  Plugin Name: Zedna WP Image Lazy Load
  Plugin URI: https://profiles.wordpress.org/zedna#content-plugins
  Text Domain: wp-image-lazy-load
  Domain Path: /languages
  Description: Image lazy load plugin to boost page load time and save bandwidth by removing all the images, background-images, responsive images, iframes and videos. Elements will load just when reach visible part of screen.
  Version: 1.6.2.2
  Author: Radek Mezulanik
  Author URI: https://cz.linkedin.com/in/radekmezulanik
  License: GPL3
*/

// CREATE WP Image Lazy Load options
//Skip iframes
add_option( 'wpimagelazyload_skipiframe', 'true', '', 'yes' );
//Skip iframe in element
add_option( 'wpimagelazyload_skipparent', '', '', 'yes' );
//Skip video in element
add_option( 'wpimagelazyload_skipvideo', 'false', '', 'yes' );
//Skip element in parent
add_option( 'wpimagelazyload_skipallparent', '', '', 'yes' );
//Load on custom position
add_option( 'wpimagelazyload_loadonposition', '0', '', 'yes' );

//Use animations
add_option( 'wpimagelazyload_animation', 'false', '', 'yes' );
//Animation duration
add_option( 'wpimagelazyload_animationduration', '5', '', 'yes' );
//Animation timing
add_option( 'wpimagelazyload_animationtiming', 'ease', '', 'yes' );

// #CREATE WP Image Lazy Load options

add_action( 'plugins_loaded', 'wpimagelazyload_load_textdomain' );
/**
 * Load plugin textdomain.
 *
 * @since 1.6.2
 */
function wpimagelazyload_load_textdomain() {
  load_plugin_textdomain( 'wp-image-lazy-load', false, basename( dirname( __FILE__ ) ) . '/languages' ); 
}

//get options from database and pass them to styles
$wpimagelazyload_animation = get_option( 'wpimagelazyload_animation' );
if($wpimagelazyload_animation === "true"){
add_action( 'wp_enqueue_scripts', 'wpimagelazyload_styles' );
}
function wpimagelazyload_styles() {
    wp_enqueue_style(
        'wpimagelazyloadstyle',
        plugins_url( '/image_lazy_load.css' , __FILE__ )
    );
        $wpimagelazyload_animationduration = get_option( 'wpimagelazyload_animationduration' );
        $wpimagelazyload_animationtiming = get_option( 'wpimagelazyload_animationtiming' );
        $wpimagelazyload_custom_css = "
                .fadein{
                  animation: fade {$wpimagelazyload_animationduration}s {$wpimagelazyload_animationtiming};
                }
                @keyframes fade {
                  0% {
                    opacity: 0;
                  }
                  100% {
                    opacity: 1;
                  }
                }";
    wp_add_inline_style( 'wpimagelazyloadstyle', $wpimagelazyload_custom_css );
}

//get options from database and pass them to script
add_action('wp_enqueue_scripts', 'add_wpimagelazyload_scripts');
function add_wpimagelazyload_scripts() {

  // ensure is_plugin_active() exists (not on frontend)
  if( !function_exists('is_plugin_active') ) {
      
      include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
      
  }
  if (is_plugin_active('js_composer/js_composer.php')) {
    //Visual Composer is activated
    $importantVC = true;
  }else{
    $importantVC = false;
  }
  if(!is_admin()) {
        wp_enqueue_script('jquery');
    $script_vars = array(
      'wpimagelazyloadsetting_skipiframe' => get_option( 'wpimagelazyload_skipiframe' ),
      'wpimagelazyloadsetting_skipparent' => get_option( 'wpimagelazyload_skipparent' ),
      'wpimagelazyloadsetting_skipallparent' => get_option( 'wpimagelazyload_skipallparent' ),
      'wpimagelazyloadsetting_skipvideo' => get_option( 'wpimagelazyload_skipvideo' ),
      'wpimagelazyloadsetting_loadonposition' => get_option( 'wpimagelazyload_loadonposition' ),
      'wpimagelazyloadsetting_importantvc' => $importantVC
    );

wp_enqueue_script( 'wpimagelazyload', plugins_url( '/image_lazy_load.js' , __FILE__ ), array( 'jquery' ), true);
wp_localize_script( 'wpimagelazyload', 'wpimagelazyload_settings', $script_vars );
}
    }

add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'wpimagelazyload_links' );

function wpimagelazyload_links( $links ) {
   $links[] = '<a href="https://profiles.wordpress.org/zedna/#content-plugins" target="_blank">More plugins by Radek Mezulanik</a>';
   return $links;
}

//Add admin page
add_action('admin_menu', 'wpimagelazyload_setttings_menu');
 

if( !defined('ABSPATH') ) die('-1');


function wpimagelazyload_setttings_menu(){
        $pluginURI = get_option('siteurl').'/wp-content/plugins/'.dirname(plugin_basename(__FILE__)); 
        
    add_menu_page( __('WP Image Lazy Load Settings page','wp-image-lazy-load'), __('WP Image Lazy Load Settings','wp-image-lazy-load'), 'manage_options', 'wpimagelazyload', 'wpimagelazyload_init',$pluginURI.'/assets/wpill-ico.png'  );
  // Call update_wpimagelazyload function to update database
  add_action( 'admin_init', 'update_wpimagelazyload' );
}

// Create function to register plugin settings in the database
if( !function_exists("update_wpimagelazyload") )
{
function update_wpimagelazyload() {
  register_setting( 'wpimagelazyload-settings', 'wpimagelazyload_skipiframe' );
  register_setting( 'wpimagelazyload-settings', 'wpimagelazyload_skipparent' );
  register_setting( 'wpimagelazyload-settings', 'wpimagelazyload_skipallparent' );
  register_setting( 'wpimagelazyload-settings', 'wpimagelazyload_skipvideo' );
  register_setting( 'wpimagelazyload-settings', 'wpimagelazyload_loadonposition' );
  register_setting( 'wpimagelazyload-settings', 'wpimagelazyload_animation' );
  register_setting( 'wpimagelazyload-settings', 'wpimagelazyload_animationduration' );
  register_setting( 'wpimagelazyload-settings', 'wpimagelazyload_animationtiming' );
}
}
 
function wpimagelazyload_init(){
$skipiframe = (get_option('wpimagelazyload_skipiframe') != '') ? get_option('wpimagelazyload_skipiframe') : 'true';
$skipparent = (get_option('wpimagelazyload_skipparent') != '') ? get_option('wpimagelazyload_skipparent') : '';
$skipallparent = (get_option('wpimagelazyload_skipallparent') != '') ? get_option('wpimagelazyload_skipallparent') : '';
$skipvideo = (get_option('wpimagelazyload_skipvideo') != '') ? get_option('wpimagelazyload_skipvideo') : 'false';
$loadonposition = (get_option('wpimagelazyload_loadonposition') != '') ? get_option('wpimagelazyload_loadonposition') : '0';
$useanimation = (get_option('wpimagelazyload_animation') != '') ? get_option('wpimagelazyload_animation') : 'false';
$animationduration = (get_option('wpimagelazyload_animationduration') != '') ? get_option('wpimagelazyload_animationduration') : '3';
$animationtiming = (get_option('wpimagelazyload_animationtiming') != '') ? get_option('wpimagelazyload_animationtiming') : 'ease';
?>

<h1><?php print __('WP Image Lazy Load Settings','wp-image-lazy-load');?></h1>
<img src="<?php echo plugins_url( '/assets/banner-772x250.png' , __FILE__ ); ?>">
  <h3><?php print __('Setting of your WP Image Lazy Load plugin','wp-image-lazy-load');?></h3>
  <form method="post" action="options.php">
    <?php settings_fields( 'wpimagelazyload-settings' ); ?>
    <?php do_settings_sections( 'wpimagelazyload-settings' ); ?>
    <table class="form-table">
      <tr valign="top">
      <th scope="row"><?php print __('Skip iframes:','wp-image-lazy-load');?></th>
      <td>
      <select name='wpimagelazyload_skipiframe'>
      <?php $skipiframe = get_option('wpimagelazyload_skipiframe'); 
      if ($skipiframe == "false"){
       echo "<option value='false' selected=selected>".__('No','wp-image-lazy-load')."</option>
             <option value='true'>".__('Yes','wp-image-lazy-load')."</option>";
      }else{ 
      echo "<option value='false'>".__('No','wp-image-lazy-load')."</option>
            <option value='true' selected=selected>".__('Yes','wp-image-lazy-load')."</option>";
      }
      ?>
      </select>
      </td>
      </tr>
      <tr valign="top">
      <th scope="row"><?php print __('Skip videos:','wp-image-lazy-load');?></th>
      <td>
      <select name='wpimagelazyload_skipvideo'>
      <?php $skipiframe = get_option('wpimagelazyload_skipvideo'); 
      if ($skipiframe == "false"){
       echo "<option value='false' selected=selected>".__('No','wp-image-lazy-load')."</option>
             <option value='true'>".__('Yes','wp-image-lazy-load')."</option>";
      }else{ 
      echo "<option value='false'>".__('No','wp-image-lazy-load')."</option>
            <option value='true' selected=selected>".__('Yes','wp-image-lazy-load')."</option>";
      }
      ?>
      </select>
      </td>
      </tr>
      <tr valign="top">
          <th scope="row"><?php print __('Skip images in parent element (<strong>css class without dot "."</strong>):','wp-image-lazy-load');?></th>
          <td><input type="text" name="wpimagelazyload_skipallparent" value="<?php echo $skipallparent;?>" /> <?php print __('For no skipping, leave empty. Multiple classes split by semicolon (e.g. classOne;classTwo)','wp-image-lazy-load');?></td>
      </tr>
      <tr valign="top">
          <th scope="row"><?php print __('Skip iframes in parent element (<strong>css class without dot "."</strong>):','wp-image-lazy-load');?></th>
          <td><input type="text" name="wpimagelazyload_skipparent" value="<?php echo $skipparent;?>" /> <?php print __('For no skipping, leave empty. Multiple classes split by semicolon (e.g. classOne;classTwo)','wp-image-lazy-load');?></td>
      </tr>
      <tr valign="top">
      <th scope="row"><?php print __('Example of use parent element:','wp-image-lazy-load');?></th>
          <td>
<xmp>
<div class="parent">
    <img src="https://www.google.com/image.png">
    <iframe src="http://www.google.com">content</iframe>
</div>
</xmp>
          <p>
         <?php print __(' In options field insert <code class="html">parent</code>','wp-image-lazy-load');?>
                </p>
      </td>
      </tr>
      <tr valign="top">
      <th scope="row"><?php print __('Load elements on custom position:','wp-image-lazy-load');?></th>
      <td><input type="number" name="wpimagelazyload_loadonposition" value="<?php echo $loadonposition;?>" required/>
          <p>
          <img src="<?php echo plugins_url( '/assets/pixels.png' , __FILE__ ); ?>" width="300px"></td>
          </p>
          </td>
      </tr>
      <tr valign="top">
      <th scope="row"><?php print __('Use animations:','wp-image-lazy-load');?></th>
      <td>
      <select name='wpimagelazyload_animation'>
      <?php
      if ($useanimation == "false"){
       echo "<option value='false' selected=selected>".__('No','wp-image-lazy-load')."</option>
             <option value='true'>".__('Yes','wp-image-lazy-load')."</option>";
      }else{ 
      echo "<option value='false'>".__('No','wp-image-lazy-load')."</option>
            <option value='true' selected=selected>".__('Yes','wp-image-lazy-load')."</option>";
      }?>
      </select> <i><?php print __('(excluding background images)','wp-image-lazy-load');?></i>
      </td>
      </tr>
      <tr valign="top">
      <th scope="row"><?php print __('Animation duration:','wp-image-lazy-load');?></th>
      <td><input type="number" name="wpimagelazyload_animationduration" value="<?php echo $animationduration;?>" required/></td>
      </tr>
      <tr valign="top">
      <th scope="row"><?php print __('Animation timing:','wp-image-lazy-load');?></th>
      <td>
      <select name='wpimagelazyload_animationtiming'>
      <?php
      if ($animationtiming == "linear"){
       echo "<option value='linear' selected=selected>linear</option>
             <option value='ease'>ease</option>
             <option value='ease-in'>ease-in</option>
             <option value='ease-out'>ease-out</option>
             <option value='ease-in-out'>ease-in-out</option>";
      }else if ($animationtiming == "ease"){ 
      echo "<option value='linear'>linear</option>
             <option value='ease' selected=selected>ease</option>
             <option value='ease-in'>ease-in</option>
             <option value='ease-out'>ease-out</option>
             <option value='ease-in-out'>ease-in-out</option>";
      }else if ($animationtiming == "ease-in"){ 
      echo "<option value='linear'>linear</option>
             <option value='ease'>ease</option>
             <option value='ease-in' selected=selected>ease-in</option>
             <option value='ease-out'>ease-out</option>
             <option value='ease-in-out'>ease-in-out</option>";
      }else if ($animationtiming == "ease-out"){ 
      echo "<option value='linear'>linear</option>
             <option value='ease'>ease</option>
             <option value='ease-in'>ease-in</option>
             <option value='ease-out' selected=selected>ease-out</option>
             <option value='ease-in-out'>ease-in-out</option>";
      }else if ($animationtiming == "ease-in-out"){ 
      echo "<option value='linear'>linear</option>
             <option value='ease'>ease</option>
             <option value='ease-in'>ease-in</option>
             <option value='ease-out'>ease-out</option>
             <option value='ease-in-out selected=selected'>ease-in-out</option>";
      }
      ?>
      </select>
      </td>
      </tr>
    </table>
  <?php submit_button(); ?>
  </form>
<p><?php print __('If you like this plugin, please donate us for faster upgrade','wp-image-lazy-load');?></p>
  <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----
">
<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
</form>
<?php
}

?>