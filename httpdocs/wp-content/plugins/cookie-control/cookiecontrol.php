<?php
/*
Plugin Name: Cookie Control 8
Plugin URI: https://www.civicuk.com/cookie-control
Description: Cookie Control 8 is a mechanism for controlling user consent for the use of cookies on their computer.
Version: 1.10
Author: Civic UK
Author URI: https://www.civicuk.com/
*/

defined( 'ABSPATH' ) or die( 'Direct access not permitted' );

//  defaults
$def_cookiecontrol_logConsent = 'false';
$def_cookiecontrol_initialState = 'CLOSE';
$def_cookiecontrol_notifyOnce = 'false';
$def_cookiecontrol_position = 'RIGHT';
$def_cookiecontrol_theme = 'DARK';
$def_cookiecontrol_layout = 'SLIDEOUT';
$def_cookiecontrol_toggleType = 'slider';
$def_cookiecontrol_closeStyle = 'icon';
$def_cookiecontrol_expiry = 90;

$def_cookiecontrol_fontColor = '#fff';
$def_cookiecontrol_fontFamily = 'Arial,sans-serif';
$def_cookiecontrol_fontSizeTitle = '1.2';
$def_cookiecontrol_fontSizeHeaders = '1';
$def_cookiecontrol_fontSize = '0.8';
$def_cookiecontrol_backgroundColor = '#313147';
$def_cookiecontrol_toggleText = '#fff';
$def_cookiecontrol_toggleColor = '#2f2f5f';
$def_cookiecontrol_toggleBackground = '#111125';
$def_cookiecontrol_buttonIcon = null;
$def_cookiecontrol_buttonIconWidth = '64';
$def_cookiecontrol_buttonIconHeight = '64';
$def_cookiecontrol_removeIcon = 'false';
$def_cookiecontrol_removeAbout = 'false';
$def_cookiecontrol_alertText = '#fff';
$def_cookiecontrol_alertBackground = '#111125';
$def_cookiecontrol_acceptTextColor = '';
$def_cookiecontrol_acceptBackground = '';

$def_cookiecontrol_titleText = 'This site uses cookies';
$def_cookiecontrol_introText = 'Some of these cookies are essential, while others help us to improve your experience by providing insights into how the site is being used.';
$def_cookiecontrol_privacyURL = '';
$def_cookiecontrol_privacyName = '';
$def_cookiecontrol_privacyDescription = '';
$def_cookiecontrol_privacyUpdateDate = '';
$def_cookiecontrol_necessaryTitle = 'Necessary Cookies';
$def_cookiecontrol_necessaryDescription = 'Necessary cookies enable core functionality. The website cannot function properly without these cookies, and can only be disabled by changing your browser preferences.';
$def_cookiecontrol_thirdPartyTitle = 'Warning: Some cookies require your attention';
$def_cookiecontrol_thirdPartyDescription = 'Consent for the following cookies could not be automatically revoked. Please follow the link(s) below to opt out manually.';
$def_cookiecontrol_offText = 'Off';
$def_cookiecontrol_onText = 'On';
$def_cookiecontrol_acceptText = 'Accept';
$def_cookiecontrol_settingsText = 'Cookie Preferences';
$def_cookiecontrol_notifyTitle = 'Your choice regarding cookies on this site';
$def_cookiecontrol_notifyDescription = 'We use cookies to optimise site functionality and give you the best possible experience.';
$def_cookiecontrol_acceptRecommended = 'Accept Recommended Settings';
$def_cookiecontrol_closeLabel = 'Close';
$def_cookiecontrol_accessibilityAlert = 'This site uses cookies to store information. Press accesskey C to learn more about your options.';

$def_cookiecontrol_accessKey = 'C';
$def_cookiecontrol_highlightFocus = 'false';
    
// define defaults
$cookiecontrol_defaults = apply_filters('cookiecontrol_defaults', array(
    'logConsent' => $def_cookiecontrol_logConsent,
    'notifyOnce' => $def_cookiecontrol_notifyOnce,
    'initialState' => $def_cookiecontrol_initialState,
    'position' => $def_cookiecontrol_position,
    'theme' => $def_cookiecontrol_theme,
    'layout' => $def_cookiecontrol_layout,
    'toggleType' => $def_cookiecontrol_toggleType,
    'closeStyle' => $def_cookiecontrol_closeStyle,
    'expiry' => $def_cookiecontrol_expiry,
    
    'fontColor' => $def_cookiecontrol_fontColor,
    'fontFamily' => $def_cookiecontrol_fontFamily,
    'fontSizeTitle' => $def_cookiecontrol_fontSizeTitle,
    'fontSizeHeaders' => $def_cookiecontrol_fontSizeHeaders,
    'fontSize' => $def_cookiecontrol_fontSize,
    'backgroundColor' => $def_cookiecontrol_backgroundColor,
    'toggleText' => $def_cookiecontrol_toggleText,
    'toggleColor' => $def_cookiecontrol_toggleColor,
    'toggleBackground' => $def_cookiecontrol_toggleBackground,
    'buttonIcon' => $def_cookiecontrol_buttonIcon,
    'buttonIconWidth' => $def_cookiecontrol_buttonIconWidth,
    'buttonIconHeight' => $def_cookiecontrol_buttonIconHeight,
    'removeIcon' => $def_cookiecontrol_removeIcon,
    'removeAbout' => $def_cookiecontrol_removeAbout,
    'alertText' => $def_cookiecontrol_alertText,
    'alertBackground' => $def_cookiecontrol_alertBackground,
    'alertText' => $def_cookiecontrol_alertText,
    'alertBackground' => $def_cookiecontrol_alertBackground,
    'acceptTextColor' => $def_cookiecontrol_acceptTextColor,
    'acceptBackground' => $def_cookiecontrol_acceptBackground,
  
    'titleText' => $def_cookiecontrol_titleText,
    'introText' => $def_cookiecontrol_introText,
    'privacyURL' => $def_cookiecontrol_privacyURL,
    'necessaryTitle' => $def_cookiecontrol_necessaryTitle,
    'necessaryDescription' => $def_cookiecontrol_necessaryDescription,
    'thirdPartyTitle' => $def_cookiecontrol_thirdPartyTitle,
    'thirdPartyDescription' => $def_cookiecontrol_thirdPartyDescription,
    'onText' => $def_cookiecontrol_onText,
    'offText' => $def_cookiecontrol_offText,
    'acceptText' => $def_cookiecontrol_acceptText,
    'settingsText' => $def_cookiecontrol_settingsText,
    'acceptRecommended' => $def_cookiecontrol_acceptRecommended,
    'notifyTitle' => $def_cookiecontrol_notifyTitle,
    'notifyDescription' => $def_cookiecontrol_notifyDescription,
    'closeLabel' => $def_cookiecontrol_closeLabel,
    'accessibilityAlert' => $def_cookiecontrol_accessibilityAlert,
  
    'accessKey' => $def_cookiecontrol_accessKey,
    'highlightFocus' => $def_cookiecontrol_highlightFocus,
));

// pull the settings from the db
// fallback default settings
$cookiecontrol_settings = wp_parse_args(get_option('cookiecontrol_settings'), $cookiecontrol_defaults);

// registers settings in the db
add_action('admin_init', 'cookiecontrol_register_settings');
function cookiecontrol_register_settings() {
    register_setting('cookiecontrol_settings', 'cookiecontrol_settings', 'cookiecontrol_settings_validate');
}

//	this function adds the settings page to the Appearance tab
add_action('admin_menu', 'add_cookiecontrol_menu');
function add_cookiecontrol_menu() {
    add_menu_page('Cookie Control 8', 'Cookie Control 8', 'administrator', 'cookiecontrol', 'cookiecontrol_admin_page');
}

/**
   * plugin_action_links()
   * Handler for the 'plugin_action_links' hook. Adds a "Settings" link to this plugin's entry
   * on the plugin list.
   *
   * @param array $links
   * @param string $file
   * @return array
   */
function add_action_links($links) {
    $links[] = '<a href="' . admin_url( 'admin.php?page=cookiecontrol' ) . '">' . __('Settings') . '</a>';
    $links[] = '<a href="https://www.civicuk.com/cookie-control/downloads/v8/wp/CC8-WP-manual-v1.pdf" target="_blank">' . __('Manual') . '</a>';
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'add_action_links' );

// options page styling
function custom_admin_styles() {
    //wp_register_style( 'style.css', site_url().'/wp-content/plugins/cookie-control/css/style.css', null, '1.0', 'screen' );
    wp_register_style( 'style.css', plugin_dir_url( __FILE__ ) .'css/style.css', null, '1.0', 'screen' );
    wp_enqueue_style( 'style.css' );
}

//TODO print_styles check
add_action( 'admin_print_styles', 'custom_admin_styles' );

function custom_admin_scripts($hook) { 
    if ( $hook == 'toplevel_page_cookiecontrol'){
      wp_enqueue_script('jquery-ui-tabs');
      wp_enqueue_script( 'script', plugin_dir_url( __FILE__ ) .'js/script.js' );
    }        
}
add_action( 'admin_enqueue_scripts', 'custom_admin_scripts' );

//Settings page
function cookiecontrol_admin_page() {
?>

<div class="ccc-container">

<header>
    <h1>
	<a href="https://www.civicuk.com/cookie-control" title="Cookie Control 8 by Civic" target="_blank">Cookie Control 8 by Civic</a>
    </h1>
</header>

<hr>

<div>
<p>With an elegant  user-interface that doesn't hurt the look and feel of your site, Cookie Control is a mechanism for controlling user consent for the use of cookies on their computer.</p>
<p>For more information, please visit Civic's Cookie Control pages at: <a href="https://www.civicuk.com/cookie-control" title="Cookie Control by Civic" target="_blank">https://www.civicuk.com/cookie-control</a></p>
<a class="civic" href="https://www.civicuk.com/cookie-control/v8/download" target="_blank">Get Your API Key</a>
</div>

<div class="wrap">
<?php cookiecontrol_settings_update_check(); ?>
<form method="post" action="options.php">
<?php settings_fields('cookiecontrol_settings'); ?>
<?php global $cookiecontrol_settings; $options = $cookiecontrol_settings;?>
	
    <!-- API -->
    <h2><?php _e('Your Cookie Control Product Information', 'cookie-control'); ?></h2>
    <table class="form-table">
      <tr>
        <th scope="row">
          <label for="cookiecontrol_settings[apikey]"><?php _e('API Key', 'cookie-control'); ?> <span>&#42;</span></label>
        </th>
        <td>
          <input type="text" name="cookiecontrol_settings[apiKey]" id="cookiecontrol_settings[apiKey]" value="<?php echo ( isset($options['apiKey']) ? $options['apiKey'] : '' ); ?>" size="50" />
        </td>
      </tr>
      <tr>
        <th scope="row">
          <label for="cookiecontrol_settings[product]"><?php _e('Product License Type', 'cookie-control'); ?> <span>&#42;</span></label>
        </th>
        <td>
          <input type="radio" class="first" name="cookiecontrol_settings[product]" value="COMMUNITY" <?php checked('COMMUNITY', ( isset($options['product']) ? $options['product'] : '' )); ?> /><?php _e('Community Edition', 'cookie-control'); ?>
          <input type="radio" name="cookiecontrol_settings[product]" value="PRO" <?php checked('PRO', ( isset($options['product']) ? $options['product'] : ' ' )); ?> /><?php _e('Pro Edition', 'cookie-control'); ?>
          <input type="radio" name="cookiecontrol_settings[product]" value="PRO_MULTISITE" <?php checked('PRO_MULTISITE', ( isset($options['product']) ? $options['product'] : '' )); ?> /><?php _e('Multisite Pro Edition', 'cookie-control'); ?>
        </td>
      </tr>
      <tr>
        <th scope="row">
          <label for="cookiecontrol_settings[logConsent]"><?php _e('Log Consent', 'cookie-control'); ?> <span>&#42;</span></label>
        </th>
        <td>
          <input type="radio" class="first" name="cookiecontrol_settings[logConsent]" value="false" <?php checked('false', ( isset($options['logConsent']) ? $options['logConsent'] : '' )); ?> /><?php _e('No', 'cookie-control'); ?>
          <input type="radio" name="cookiecontrol_settings[logConsent]" value="true" <?php checked('true', ( isset($options['logConsent']) ? $options['logConsent'] : '' )); ?> /><?php _e('Yes', 'cookie-control'); ?>
        </td>
      </tr>
    </table>
	
    <hr />
    
    <div id="cookie-tabs">
      <ul class="cookie-tabs">
        <li><h3><a href="#optional"><?php _e('Cookies', 'cookie-control'); ?></a></h3></li>
        <li><h3><a href="#necessary"><?php _e('Necessary Cookies', 'cookie-control'); ?></a></h3></li>
        <li><h3><a href="#appearance"><?php _e('Appearance', 'cookie-control'); ?></a></h3></li>
        <li><h3><a href="#branding"><?php _e('Branding', 'cookie-control'); ?></a></h3></li>
        <li><h3><a href="#regional"><?php _e('Regional', 'cookie-control'); ?></a></h3></li>
        <li><h3><a href="#accessibility"><?php _e('Accessibility', 'cookie-control'); ?></a></h3></li>
      </ul>
        
      <div id="optional">
        <h2><?php _e('Cookies Categories*', 'cookie-control'); ?></h2>
        <p>The module's core behaviour will be dependent on you accurately setting the optionalCookies option. This will inform the user of the different types of cookies the website may set, and protect any given type from being deleted should the user have consented to their use.</p>
        
        <div class="warning">
            <div class="dashicons dashicons-warning"><span class="screen-reader-text"><?php _e('warning', 'cookie-control'); ?></span></div>
            It is required to add at least a cookie category.
        </div>
        
        <div class="optionalCookiesTemplate">
          <table class="form-table">
            <tr>
              <th scope="row">
                <label for="cookiecontrol_settings[optionalCookiesName]"><?php _e('Cookie name', 'cookie-control'); ?></label>					
                <p>A unique identifier for the category, that the module will use to set an acceptance cookie for when user's opt in.</p>
              </th>
              <td>
                <input placeholder="analytics" type="text" name="cookiecontrol_settings[optionalCookiesName]"  id="cookiecontrol_settings[optionalCookiesName]" size="30" />
                <a href="#" class="remove removeRow" data-class="optionalCookies" id="removeoptionalCookies"><div class="dashicons dashicons-dismiss"><span class="screen-reader-text"><?php _e('Remove', 'cookie-control'); ?></span></div></a>
              </td>
            </tr>
            <tr>
              <th scope="row">
                <label for="cookiecontrol_settings[optionalCookiesLabel]"><?php _e('Cookie label', 'cookie-control'); ?></label>			
                <p>The descriptive title assigned to the category and displayed by the module.</p>
              </th>
              <td>
                <input placeholder="Analytical Cookies" type="text" name="cookiecontrol_settings[optionalCookiesLabel]"  id="cookiecontrol_settings[optionalCookiesLabel]" size="30" />
              </td>
            </tr>
            <tr>
              <th scope="row">
                <label for="cookiecontrol_settings[optionalCookiesDescription]"><?php _e('Cookie description', 'cookie-control'); ?></label>		
                <p>The full description assigned to the category and displayed by the module.</p>
              </th>
              <td>
                <input placeholder="Analytical cookies help us to improve our website by collecting and reporting information on its usage." type="text" name="cookiecontrol_settings[optionalCookiesDescription]"  id="cookiecontrol_settings[optionalCookiesDescription]" size="100" />
              </td>
            </tr>
            <tr>
              <th scope="row">
                <label for="cookiecontrol_settings[optionalCookiesArray]"><?php _e('Cookies', 'cookie-control'); ?></label>		
                <p>The name of the cookies that you wish to protect after a user opts in (comma seperated values ex. '_ga', '_gid', '_gat', '__utma').</p>
              </th>
              <td>
                <input type="text" name="cookiecontrol_settings[optionalCookiesArray]"  id="cookiecontrol_settings[optionalCookiesArray]" size="100" />
              </td>
            </tr>  
            <tr>
              <th scope="row">
                <label for="cookiecontrol_settings[optionalCookiesthirdPartyCookies]"><?php _e('Third party cookies', 'cookie-control'); ?></label>		
                <p>Only applicable if the category will set third party cookies on acceptance. Each object (multiple objects can be added, seperated by comma) will consist of the following key-value pairs:</p><ul><li>name : string,</li><li>optOutLink : url string</li></ul><p>Ex. {"name": "AddThis", "optOutLink": "http://www.addthis.com/privacy/opt-out"}</p>
              </th>
              <td>
                <input placeholder='{"name": "AddThis", "optOutLink": "http://www.addthis.com/privacy/opt-out"}' type="text" name="cookiecontrol_settings[optionalCookiesthirdPartyCookies]"  id="cookiecontrol_settings[optionalCookiesthirdPartyCookies]" size="100" />
              </td>
            </tr>     
            <tr>
                <th scope="row" colspan="2">
                <label for="cookiecontrol_settings[optionalCookiesonAccept]"><?php _e('On accept callback function', 'cookie-control'); ?></label>		
              </th>
            </tr>    
            <tr>
              <td>
                <textarea name="cookiecontrol_settings[optionalCookiesonAccept]" id="cookiecontrol_settings[optionalCookiesonAccept]" cols="100" rows="10"></textarea>
              </td>
              <td>
                <p>Callback function that will fire on user's opting into this cookie category.  For example: </p>
                <pre  style="font-size: 10px;">
(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

ga('create', 'UA-XXXXX-Y', 'auto');
ga('send', 'pageview');
                </pre>	
              </td>
            </tr>     
            <tr>
                <th scope="row" colspan="2">
                <label for="cookiecontrol_settings[optionalCookiesonRevoke]"><?php _e('On revoke callback function', 'cookie-control'); ?></label>		
              </th>
            </tr>    
            <tr>
              <td>
                <textarea name="cookiecontrol_settings[optionalCookiesonRevoke]" id="cookiecontrol_settings[optionalCookiesonRevoke]" cols="100" rows="10"></textarea>
              </td>
              <td>
                <p>Callback function that will fire on user's opting out of this cookie category. Should any thirdPartyCookies be set, the module will automatically display a warning that manual user action is needed.  For example: </p>
                <pre  style="font-size: 10px;">window['ga-disable-UA-XXXXX-Y'] = true;</pre>	
              </td>
            </tr>
            <tr>
              <th scope="row">
                <label for="cookiecontrol_settings[optionalCookiesinitialConsentState]"><?php _e('Recommended Consent State', 'cookie-control'); ?></label>
                <p>Defines whether or not this category should be accepted (opted in) as part of the user granting consent to the site's recommended settings. If set to "on", cookies will be allowed by default for this category. </p>
              </th>
              <td>
                <input type="radio" class="first" name="cookiecontrol_settings[optionalCookiesinitialConsentState]" id="cookiecontrol_settings[optionalCookiesinitialConsentState]" value="off"  checked />
                <?php _e('Off', 'cookie-control'); ?>
                <input type="radio" name="cookiecontrol_settings[optionalCookiesinitialConsentState]" id="cookiecontrol_settings[optionalCookiesinitialConsentState]" value="on"  />
                <?php _e('On', 'cookie-control'); ?>
              </td>
            </tr>
            <tr>
              <th scope="row">
                <label for="cookiecontrol_settings[optionalCookieslawfulBasis]"><?php _e('Lawful basis', 'cookie-control'); ?></label>
                <p>Defines whether this category requires explicit user consent, or if the category can be toggled on prior to any user interaction and justified under the more flexible lawful basis for processing: legitimate interest.</p>
                <p>Possible values are either consent or legitimate interest. If the latter, the UI will show the category toggled 'on', though no record of consent will exist.</p>
                <p>If you choose to rely on legitimate interest, you are taking on extra responsibility for considering and protecting people’s rights and interests; and must include details of your legitimate interests in your privacy statement.</p>
              </th>
              <td>
                <input type="radio" class="first" name="cookiecontrol_settings[optionalCookieslawfulBasis]" id="cookiecontrol_settings[optionalCookieslawfulBasis]" value="consent"  checked />
                <?php _e('Consent ', 'cookie-control'); ?>
                <input type="radio" name="cookiecontrol_settings[optionalCookieslawfulBasis]" id="cookiecontrol_settings[optionalCookieslawfulBasis]" value="legitimate interest"  />
                <?php _e('Legitimate interest', 'cookie-control'); ?>
              </td>
            </tr>
          </table>		
        </div>
        
        <div id="optionalCookiesContainer">
      <?php
            if ( !empty( $options['optionalCookiesName'] ) ):
                foreach ($options['optionalCookiesName'] as $key => $val ) :  ?>
                    <?php if ( trim($val) != '') : ?>
                        <div class="optionalCookies">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="cookiecontrol_settings[optionalCookiesName][<?php echo $key ?>]"><?php _e('Cookie name', 'cookie-control'); ?></label>					
                                        <p>A unique identifier for the category, that the module will use to set an acceptance cookie for when user's opt in.</p>
                                    </th>
                                    <td>
                                        <input type="text" name="cookiecontrol_settings[optionalCookiesName][<?php echo $key ?>]"  id="cookiecontrol_settings[optionalCookiesName][<?php echo $key ?>]" value="<?php echo $val ?>" size="30" />
                                        <a href="#" class="remove removeRow" data-class="optionalCookies" id="removeoptionalCookies"><div class="dashicons dashicons-dismiss"><span class="screen-reader-text"><?php _e('Remove', 'cookie-control'); ?></span></div></a>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="cookiecontrol_settings[optionalCookiesLabel][<?php echo $key ?>]"><?php _e('Cookie label', 'cookie-control'); ?></label>				
                                        <p>The descriptive title assigned to the category and displayed by the module.</p>	
                                    </th>
                                    <td>
                                        <input type="text" name="cookiecontrol_settings[optionalCookiesLabel][<?php echo $key ?>]"  id="cookiecontrol_settings[optionalCookiesLabel][<?php echo $key ?>]" value="<?php echo $options['optionalCookiesLabel'][$key]; ?>" size="30" />
                                   </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="cookiecontrol_settings[optionalCookiesDescription][<?php echo $key ?>]"><?php _e('Cookie description', 'cookie-control'); ?></label>			
                                        <p>The full description assigned to the category and displayed by the module.</p>
                                    </th>
                                    <td>
                                        <input type="text" name="cookiecontrol_settings[optionalCookiesDescription][<?php echo $key ?>]"  id="cookiecontrol_settings[optionalCookiesDescription][<?php echo $key ?>]" value="<?php echo $options['optionalCookiesDescription'][$key]; ?>" size="100" />
                                   </td>
                                </tr>
                                <tr>
                                  <th scope="row">
                                    <label for="cookiecontrol_settings[optionalCookiesArray][<?php echo $key ?>]"><?php _e('Cookies', 'cookie-control'); ?></label>	
                                    <p>The name of the cookies that you wish to protect after a user opts in (comma seperated values ex. '_ga', '_gid', '_gat', '__utma').</p>	
                                  </th>
                                  <td>
                                    <input type="text" name="cookiecontrol_settings[optionalCookiesArray][<?php echo $key ?>]"  id="cookiecontrol_settings[optionalCookiesArray][<?php echo $key ?>]" value="<?php echo $options['optionalCookiesArray'][$key]; ?>" size="100" />
                                  </td>
                                </tr> 
                                <tr>
                                  <th scope="row">
                                    <label for="cookiecontrol_settings[optionalCookiesthirdPartyCookies][<?php echo $key ?>]"><?php _e('Third party cookies', 'cookie-control'); ?></label>		
                                    <p>Only applicable if the category will set third party cookies on acceptance. Each object (multiple objects can be added, seperated by comma) will consist of the following key-value pairs:</p><ul><li>name : string,</li><li>optOutLink : url string</li></ul><p>Ex. {"name": "AddThis", "optOutLink": "http://www.addthis.com/privacy/opt-out"}</p>
                                  </th>
                                  <td>
                                    <input type="text" name="cookiecontrol_settings[optionalCookiesthirdPartyCookies][<?php echo $key ?>]"  id="cookiecontrol_settings[optionalCookiesthirdPartyCookies][<?php echo $key ?>]" value='<?php echo isset($options['optionalCookiesthirdPartyCookies'][$key]) ?  $options['optionalCookiesthirdPartyCookies'][$key] : ""; ?>' size="100" />
                                  </td>
                                </tr>  
                                <tr>
                                    <th scope="row" colspan="2">
                                    <label for="cookiecontrol_settings[optionalCookiesonAccept][<?php echo $key ?>]"><?php _e('On accept callback function', 'cookie-control'); ?></label>		
                                  </th>
                                </tr>   
                                <tr>
                                  <td>
                                    <textarea name="cookiecontrol_settings[optionalCookiesonAccept][<?php echo $key ?>]" id="cookiecontrol_settings[optionalCookiesonAccept][<?php echo $key ?>]" cols="100" rows="10"><?php echo $options['optionalCookiesonAccept'][$key]; ?></textarea>
                                  </td>
                                  <td>
                                    <p>Callback function that will fire on user's opting into this cookie category.  For example: </p>
                                    <pre  style="font-size: 10px;">(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

ga('create', 'UA-XXXXX-Y', 'auto');
ga('send', 'pageview');</pre>	
                                  </td>
                                </tr> 
                                <tr>
                                    <th scope="row" colspan="2">
                                    <label for="cookiecontrol_settings[optionalCookiesonRevoke[<?php echo $key ?>]"><?php _e('On revoke callback function', 'cookie-control'); ?></label>		
                                  </th>
                                </tr>   
                                <tr>
                                  <td>
                                    <textarea name="cookiecontrol_settings[optionalCookiesonRevoke][<?php echo $key ?>]" id="cookiecontrol_settings[optionalCookiesonRevoke][<?php echo $key ?>]" cols="100" rows="10"><?php echo $options['optionalCookiesonRevoke'][$key]; ?></textarea>
                                  </td>
                                  <td>
                                    <p>Callback function that will fire on user's opting out of this cookie category. Should any thirdPartyCookies be set, the module will automatically display a warning that manual user action is needed.  For example: </p>
                                    <pre  style="font-size: 10px;">window['ga-disable-UA-XXXXX-Y'] = true;</pre>	
                                  </td>
                                </tr>                                
                                <tr>
                                  <th scope="row">
                                    <label for="cookiecontrol_settings[optionalCookiesinitialConsentState][<?php echo $key ?>]"><?php _e('Recommended Consent State', 'cookie-control'); ?></label>
                                    <p>Defines whether or not this category should be accepted (opted in) as part of the user granting consent to the site's recommended settings. If set to "on", cookies will be allowed by default for this category.</p>
                                  </th>
                                  <td>
                                    <input type="radio" class="first" name="cookiecontrol_settings[optionalCookiesinitialConsentState][<?php echo $key ?>]" id="cookiecontrol_settings[optionalCookiesinitialConsentState][<?php echo $key ?>]" value="off"  <?php checked('off', $options['optionalCookiesinitialConsentState'][$key]); ?> />
                                    <?php _e('Off', 'cookie-control'); ?>
                                    <input type="radio" name="cookiecontrol_settings[optionalCookiesinitialConsentState][<?php echo $key ?>]" id="cookiecontrol_settings[optionalCookiesinitialConsentState][<?php echo $key ?>]" value="on"  <?php checked('on', $options['optionalCookiesinitialConsentState'][$key]); ?> />
                                    <?php _e('On', 'cookie-control'); ?>
                                  </td>
                                </tr>
                                <tr>
                                  <th scope="row">
                                    <label for="cookiecontrol_settings[optionalCookieslawfulBasis]"><?php _e('Lawful basis', 'cookie-control'); ?></label>
                                    <p>Defines whether this category requires explicit user consent, or if the category can be toggled on prior to any user interaction and justified under the more flexible lawful basis for processing: legitimate interest.</p>
                                    <p>Possible values are either consent or legitimate interest. If the latter, the UI will show the category toggled 'on', though no record of consent will exist.</p>
                                    <p>If you choose to rely on legitimate interest, you are taking on extra responsibility for considering and protecting people’s rights and interests; and must include details of your legitimate interests in your privacy statement.</p>
                                  </th>
                                  <td>
                                    <input type="radio" class="first" name="cookiecontrol_settings[optionalCookieslawfulBasis][<?php echo $key ?>]" id="cookiecontrol_settings[optionalCookieslawfulBasis][<?php echo $key ?>]" value="consent" <?php checked('consent', $options['optionalCookieslawfulBasis'][$key]); ?> />
                                    <?php _e('Consent ', 'cookie-control'); ?>
                                    <input type="radio" name="cookiecontrol_settings[optionalCookieslawfulBasis][<?php echo $key ?>]" id="cookiecontrol_settings[optionalCookieslawfulBasis][<?php echo $key ?>]" value="legitimate interest" <?php checked('legitimate interest', $options['optionalCookieslawfulBasis'][$key]); ?> />
                                    <?php _e('Legitimate interest', 'cookie-control'); ?>
                                  </td>
                                </tr>
                            </table>
                            <hr>
                        </div>		
                    <?php endif; ?>
                <?php endforeach; ?>
                <div id="last-used-key-optionalCookies" data-keyid="<?php echo $key ?>"></div>
      <?php else: ?>
                <div id="last-used-key-optionalCookies" data-keyid="0"></div>
      <?php endif; ?>
        </div>
        
        <button class="addRow civic" data-class="optionalCookies"><i class="icon-plus-sign icon-white"></i><?php _e('Add Cookie Category', 'cookie-control'); ?></button></br>
             
        <div class="warning">
            <div class="dashicons dashicons-warning"><span class="screen-reader-text"><?php _e('warning', 'cookie-control'); ?></span></div>
            We recommend that you add any plugins that may set third party cookies to the appropriate category's onAccept function, so that they only run after a user has given their consent. Similarly, the onRevoke function could be used to stop the plugin; though this would be dependent on the plugin offering such methods. How to do this specifically will depend on the plugin itself.
        </div>   
      </div> 
      
      <div id="necessary">
        <h2><?php _e('Necessary Cookies', 'cookie-control'); ?></h2>
        <p>This is a list of cookies that are necessary for your website's functionality, and you don't want to be deleted by Cookie Control. In most cases you won't have to set this option. Such cookies should be marked as Secure and HttpOnly and hence Cookie Control won't be able to delete them anyway.</p>
        <p>Note - it is possible to use the * wildcard at the end of a cookie name, if you want all cookies that start with this prefix to be protected.</p>
        
        
        <div class="necessaryCookiesTemplate">
          <table class="form-table">
            <tr>
              <th scope="row">
                <label for="cookiecontrol_settings[necessaryCookies]"><?php _e('Necessary Cookie', 'cookie-control'); ?></label>			
              </th>
              <td>
                <input placeholder="Cookie name ex. JSESSIONID" type="text" name="cookiecontrol_settings[necessaryCookies]"  id="cookiecontrol_settings[necessaryCookies]" size="30" />
                <a href="#" class="remove removeRow" data-class="necessaryCookies" id="removenecessaryCookies"><div class="dashicons dashicons-dismiss"><span class="screen-reader-text"><?php _e('Remove', 'cookie-control'); ?></span></div></a>
              </td>
            </tr>
          </table>		
        </div>
        <div id="necessaryCookiesContainer">
          <?php 
            if ( !empty( $options['necessaryCookies'] ) ):
                foreach ($options['necessaryCookies'] as $key => $val ) :  ?>
                    <?php if ( trim($val) != '') : ?>
                        <div class="necessaryCookies">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="cookiecontrol_settings[necessaryCookies][<?php echo $key ?>]"><?php _e('Necessary Cookie', 'cookie-control'); ?></label>		
                                    </th>
                                    <td>
                                        <input type="text" name="cookiecontrol_settings[necessaryCookies][<?php echo $key ?>]"  id="cookiecontrol_settings[necessaryCookies][<?php echo $key ?>]" value="<?php echo $val ?>" size="30" />
                                        <a href="#" class="remove removeRow" data-class="necessaryCookies" id="removenecessaryCookies"><div class="dashicons dashicons-dismiss"><span class="screen-reader-text"><?php _e('Remove', 'cookie-control'); ?></span></div></a>
                                    </td>
                                </tr>
                            </table>
                        </div>		
                    <?php endif; ?>
                <?php endforeach; ?>
              <div id="last-used-key-necessaryCookies" data-keyid="<?php echo $key ?>"></div>
      <?php else: ?>
            <div id="last-used-key-necessaryCookies" data-keyid="0"></div>
      <?php endif; ?>
        </div>
        <button class="addRow civic" data-class="necessaryCookies"><i class="icon-plus-sign icon-white"></i><?php _e('Add Necessary Cookie', 'cookie-control'); ?></button></br>
                
        <div class="warning">
            <div class="dashicons dashicons-warning"><span class="screen-reader-text"><?php _e('warning', 'cookie-control'); ?></span></div>
            Be careful not to overuse this option, as this might end in you protecting cookies that store personal identifying information, hence defeating the purpose of using Cookie Control.
        </div>
      </div>
        
      <div id="appearance">
        <h2><?php _e('Customising Appearance, Text And Behaviour', 'cookie-control'); ?></h2>
        <p>Cookie Control will load with its own preset styling and text configuration. You can customize your widget initial state, position, theme and text with the following options.</p>
      
        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="cookiecontrol_settings[initialState]"><?php _e('Initial State', 'cookie-control'); ?></label>
            </th>
            <td>
              <input type="radio" class="first" name="cookiecontrol_settings[initialState]" id="cookiecontrol_settings[initialState]" value="CLOSE" <?php checked('CLOSE', $options['initialState']); ?> />
              <?php _e('Close', 'cookie-control'); ?>
              <input type="radio" name="cookiecontrol_settings[initialState]" id="cookiecontrol_settings[initialState]" value="OPEN" <?php checked('OPEN', $options['initialState']); ?> />
              <?php _e('Open', 'cookie-control'); ?>
              <input type="radio" name="cookiecontrol_settings[initialState]" id="cookiecontrol_settings[initialState]" value="NOTIFY" <?php checked('NOTIFY', $options['initialState']); ?> />
              <?php _e('Notify (pro license)', 'cookie-control'); ?>
            </td>
          </tr>
          <tr>
          <th scope="row">
            <label for="cookiecontrol_settings[position]"><?php _e('Widget Position', 'cookie-control'); ?></label>
          </th>
          <td>
            <input type="radio" class="first" name="cookiecontrol_settings[position]" id="cookiecontrol_settings[position]" value="RIGHT" <?php checked('RIGHT', $options['position']); ?> />
            <?php _e('Right', 'cookie-control'); ?>
            <input type="radio" name="cookiecontrol_settings[position]" id="cookiecontrol_settings[position]" value="LEFT" <?php checked('LEFT', $options['position']); ?> />
            <?php _e('Left', 'cookie-control'); ?>
          </td>
          </tr>	
          <tr>
            <th scope="row">
              <label for="cookiecontrol_settings[theme]"><?php _e('Widget Theme', 'cookie-control'); ?></label>
            </th>
            <td>
              <input type="radio" class="first" name="cookiecontrol_settings[theme]" id="cookiecontrol_settings[theme]" value="DARK" <?php checked('DARK', $options['theme']); ?> />
              <?php _e('Dark', 'cookie-control'); ?>
              <input type="radio" name="cookiecontrol_settings[theme]" id="cookiecontrol_settings[theme]" value="LIGHT" <?php checked('LIGHT', $options['theme']); ?> />
              <?php _e('Light', 'cookie-control'); ?>
            </td>
          </tr>	
          <tr>
            <th scope="row">
              <label for="cookiecontrol_settings[layout]"><?php _e('Layout', 'cookie-control'); ?></label>
            </th>
            <td>
              <input type="radio" class="first" name="cookiecontrol_settings[layout]" id="cookiecontrol_settings[layout]" value="SLIDEOUT" <?php checked('SLIDEOUT', $options['layout']); ?> />
              <?php _e('Slideout', 'cookie-control'); ?>
              <input type="radio" name="cookiecontrol_settings[layout]" id="cookiecontrol_settings[layout]" value="POPUP" <?php checked('POPUP', $options['layout']); ?> />
              <?php _e('Popup (pro license)', 'cookie-control'); ?>
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="cookiecontrol_settings[notifyOnce]"><?php _e('Notify once', 'cookie-control'); ?></label>
            </th>
            <td>
              <input type="radio" class="first" name="cookiecontrol_settings[notifyOnce]" value="false" <?php checked('false', ( isset($options['notifyOnce']) ? $options['notifyOnce'] : '' )); ?> /><?php _e('No', 'cookie-control'); ?>
              <input type="radio" name="cookiecontrol_settings[notifyOnce]" value="true" <?php checked('true', ( isset($options['notifyOnce']) ? $options['notifyOnce'] : '' )); ?> /><?php _e('Yes', 'cookie-control'); ?>
            </td>   
          </tr>
          <tr class="description">
            <td colspan="2">
              <p>Determines whether the module only shows its initialState once, or if it continues to replay on subsequent page loads until the user has directly interacted with it - by either toggling on / off a category, accepting the recommended settings, or dismissing the module.</p>
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="cookiecontrol_settings[toggleType]"><?php _e('Toggle type', 'cookie-control'); ?></label>
            </th>
            <td>
              <input type="radio" class="first" name="cookiecontrol_settings[toggleType]" value="slider" <?php checked('slider', ( isset($options['toggleType']) ? $options['toggleType'] : '' )); ?> />
              <?php _e('Slider', 'cookie-control'); ?>
              <input type="radio" name="cookiecontrol_settings[toggleType]" value="checkbox" <?php checked('checkbox', ( isset($options['toggleType']) ? $options['toggleType'] : '' )); ?> />
              <?php _e('Checkbox', 'cookie-control'); ?>
            </td>   
          </tr>
          <tr class="description">
            <td colspan="2">
              <p>Determines the control toggle for each item within <em>optionalCookies</em></p>
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="cookiecontrol_settings[closeStyle]"><?php _e('Close style', 'cookie-control'); ?></label>
            </th>
            <td>
              <input type="radio" class="first" name="cookiecontrol_settings[closeStyle]" value="icon" <?php checked('icon', ( isset($options['closeStyle']) ? $options['closeStyle'] : '' )); ?> />
              <?php _e('Icon', 'cookie-control'); ?>
              <input type="radio" name="cookiecontrol_settings[closeStyle]" value="labelled" <?php checked('labelled', ( isset($options['closeStyle']) ? $options['closeStyle'] : '' )); ?> />
              <?php _e('Labelled', 'cookie-control'); ?>
              <input type="radio" name="cookiecontrol_settings[closeStyle]" value="button" <?php checked('button', ( isset($options['closeStyle']) ? $options['closeStyle'] : '' )); ?> />
              <?php _e('Button', 'cookie-control'); ?>
            </td>   
          </tr>
          <tr class="description">
            <td colspan="2">
              <p>Determines the control toggle for each item within <em>optionalCookies</em></p>
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="cookiecontrol_settings[expiry]"><?php _e('Consent cookie expiration(days)', 'cookie-control'); ?></label>              
            </th>
            <td>
              <input type="text" name="cookiecontrol_settings[expiry]" id="cookiecontrol_settings[expiry]" value="<?php echo $options['expiry'] ?>" size="8" />
            </td>
          </tr>
          <tr class="description">
            <td colspan="2">
              <p>Controls how many days the consent of the user will be remembered for. Defaults to 90 days. This setting will apply globally to all categories.</p>
            </td>
          </tr>
        </table>
        
        <div class="warning">
            <div class="dashicons dashicons-warning"><span class="screen-reader-text"><?php _e('warning', 'cookie-control'); ?></span></div>
            Please note, we do not store information of any kind until the user opts into one of your cookie categories. If this never happens and initialState is set to open, the module will re-appear on each subsequent page load.
        </div>   
        
        <hr />
        
        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="cookiecontrol_settings[titleText]"><?php _e('Title', 'cookie-control'); ?></label>
            </th>
            <td>
              <input type="text" name="cookiecontrol_settings[titleText]" id="cookiecontrol_settings[titleText]" value="<?php echo $options['titleText'] ?>" size="50" />
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="cookiecontrol_settings[introText]"><?php _e('Introductory Text', 'cookie-control'); ?></label>
            </th>
              <td>
                <textarea name="cookiecontrol_settings[introText]" id="cookiecontrol_settings[introText]" cols="100" rows="5"><?php echo $options['introText'] ?></textarea>
              </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="cookiecontrol_settings[privacyURL]"><?php _e('Privacy or cookie policy url', 'cookie-control'); ?></label>               
              <p>Use an absolute url</p>             
            </th>
            <td>
              <input type="text" name="cookiecontrol_settings[privacyURL]" id="cookiecontrol_settings[privacyURL]" value="<?php echo $options['privacyURL'] ?>" size="50" placeholder="http://www.yoursitename.com/privacy-policy" />
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="cookiecontrol_settings[privacyDescription]"><?php _e('Privacy or cookie policy intro text', 'cookie-control'); ?></label>              
            </th>
            <td>
              <input type="text" name="cookiecontrol_settings[privacyDescription]" id="cookiecontrol_settings[privacyDescription]" value="<?php echo $options['privacyDescription'] ?>" size="50" placeholder="For more detailed information, please check our" />
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="cookiecontrol_settings[privacyName]"><?php _e('Privacy or cookie policy url name', 'cookie-control'); ?></label>
            </th>
            <td>
              <input type="text" name="cookiecontrol_settings[privacyName]" id="cookiecontrol_settings[privacyName]" value="<?php echo $options['privacyName'] ?>" size="50" placeholder="Cookie and Privacy Statement" />
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="cookiecontrol_settings[privacyUpdateDate]"><?php _e('Privacy or cookie policy update date', 'cookie-control'); ?></label>              
            </th>
            <td>
              <input type="text" name="cookiecontrol_settings[privacyUpdateDate]" id="cookiecontrol_settings[privacyUpdateDate]" value="<?php echo $options['privacyUpdateDate'] ?>" size="50" placeholder="dd/mm/YYYY" />
            </td>
          </tr>
          <tr>
            <th scope="row" colspan="2">
              <div class="warning">
                <div class="dashicons dashicons-warning"><span class="screen-reader-text"><?php _e('warning', 'cookie-control'); ?></span></div>
                Please note this link is added at the end of your introductory text.
              </div>
            </th>    
          </tr>
          <tr>
            <th scope="row">
              <label for="cookiecontrol_settings[necessaryTitle]"><?php _e('Necessary cookies title', 'cookie-control'); ?></label>
            </th>
            <td>
              <input type="text" name="cookiecontrol_settings[necessaryTitle]" id="cookiecontrol_settings[necessaryTitle]" value="<?php echo $options['necessaryTitle'] ?>" size="50" />
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="cookiecontrol_settings[necessaryDescription]"><?php _e('Necessary cookies description', 'cookie-control'); ?></label>
            </th>
            <td>
              <textarea name="cookiecontrol_settings[necessaryDescription]" id="cookiecontrol_settings[necessaryDescription]" cols="100" rows="5"><?php echo $options['necessaryDescription'] ?></textarea>
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="cookiecontrol_settings[thirdPartyTitle]"><?php _e('Third party cookies title', 'cookie-control'); ?></label>
            </th>
            <td>
              <input type="text" name="cookiecontrol_settings[thirdPartyTitle]" id="cookiecontrol_settings[thirdPartyTitle]" value="<?php echo $options['thirdPartyTitle'] ?>" size="50" />
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="cookiecontrol_settings[thirdPartyDescription]"><?php _e('Third party cookies description', 'cookie-control'); ?></label>
            </th>
            <td>
              <textarea name="cookiecontrol_settings[thirdPartyDescription]" id="cookiecontrol_settings[thirdPartyDescription]" cols="100" rows="5"><?php echo $options['thirdPartyDescription'] ?></textarea>
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="cookiecontrol_settings[onText]"><?php _e('On text', 'cookie-control'); ?></label>
            </th>
            <td>
              <input type="text" name="cookiecontrol_settings[onText]" id="cookiecontrol_settings[onText]" value="<?php echo $options['onText'] ?>" size="50" />
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="cookiecontrol_settings[offText]"><?php _e('Off text', 'cookie-control'); ?></label>
            </th>
            <td>
              <input type="text" name="cookiecontrol_settings[offText]" id="cookiecontrol_settings[offText]" value="<?php echo $options['offText'] ?>" size="50" />
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="cookiecontrol_settings[notifyTitle]"><?php _e('Notify bar title', 'cookie-control'); ?></label>
            </th>
            <td>
              <input type="text" name="cookiecontrol_settings[notifyTitle]" id="cookiecontrol_settings[notifyTitle]" value="<?php echo $options['notifyTitle'] ?>" size="50" />
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="cookiecontrol_settings[notifyDescription]"><?php _e('Notify bar description', 'cookie-control'); ?></label>
            </th>
            <td>
              <textarea name="cookiecontrol_settings[notifyDescription]" id="cookiecontrol_settings[notifyDescription]" cols="100" rows="5"><?php echo $options['notifyDescription'] ?></textarea>
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="cookiecontrol_settings[acceptText]"><?php _e('Accept text', 'cookie-control'); ?></label>
            </th>
            <td>
              <input type="text" name="cookiecontrol_settings[acceptText]" id="cookiecontrol_settings[acceptText]" value="<?php echo $options['acceptText'] ?>" size="50" />
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="cookiecontrol_settings[settingsText]"><?php _e('Settings text', 'cookie-control'); ?></label>
            </th>
            <td>
              <input type="text" name="cookiecontrol_settings[settingsText]" id="cookiecontrol_settings[settingsText]" value="<?php echo $options['settingsText'] ?>" size="50" />
            </td>
          </tr>          
          <tr>
            <th scope="row">
              <label for="cookiecontrol_settings[acceptRecommended]"><?php _e('Accept recommended settings button text', 'cookie-control'); ?></label>
            </th>
            <td>
              <input type="text" name="cookiecontrol_settings[acceptRecommended]" id="cookiecontrol_settings[acceptRecommended]" value="<?php echo $options['acceptRecommended'] ?>" size="50" />
            </td>
          </tr>          
          <tr>
            <th scope="row">
              <label for="cookiecontrol_settings[closeLabel]"><?php _e('Close cookie window text', 'cookie-control'); ?></label>
            </th>
            <td>
              <input type="text" name="cookiecontrol_settings[closeLabel]" id="cookiecontrol_settings[closeLabel]" value="<?php echo $options['closeLabel'] ?>" size="50" />
            </td>
          </tr>          
          <tr>
            <th scope="row">
              <label for="cookiecontrol_settings[accessibilityAlert]"><?php _e('Accessibility alert text', 'cookie-control'); ?></label>
            </th>
            <td>
              <input type="text" name="cookiecontrol_settings[accessibilityAlert]" id="cookiecontrol_settings[accessibilityAlert]" value="<?php echo $options['accessibilityAlert'] ?>" size="100" />
            </td>
          </tr>
        </table>
        
      </div> 
        
      <div id="branding">
        <h2><?php _e('Branding', 'cookie-control'); ?></h2>
        <p>With <b>pro</b> and <b>pro_multisite</b> licenses, you are able to set all aspects of the module's styling, and remove any back links to CIVIC.</p>
        
        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="cookiecontrol_settings[fontColor]"><?php _e('Font color', 'cookie-control'); ?></label>
            </th>
            <td>
              <input type="text" name="cookiecontrol_settings[fontColor]" id="cookiecontrol_settings[fontColor]" value="<?php echo $options['fontColor'] ?>" size="8" />
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="cookiecontrol_settings[fontFamily]"><?php _e('Font family', 'cookie-control'); ?></label>
            </th>
            <td>
              <input type="text" name="cookiecontrol_settings[fontFamily]" id="cookiecontrol_settings[fontFamily]" value="<?php echo $options['fontFamily'] ?>" size="50" />
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="cookiecontrol_settings[fontSizeTitle]"><?php _e('Title font size', 'cookie-control'); ?></label>
            </th>
            <td>
              <input type="text" name="cookiecontrol_settings[fontSizeTitle]" id="cookiecontrol_settings[fontSizeTitle]" value="<?php echo $options['fontSizeTitle'] ?>" size="8" /> em
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="cookiecontrol_settings[fontSizeHeaders]"><?php _e('Headers font size', 'cookie-control'); ?></label>
            </th>
            <td>
              <input type="text" name="cookiecontrol_settings[fontSizeHeaders]" id="cookiecontrol_settings[fontSizeHeaders]" value="<?php echo $options['fontSizeHeaders'] ?>" size="8" /> em
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="cookiecontrol_settings[fontSize]"><?php _e('Body font size', 'cookie-control'); ?></label>
            </th>
            <td>
              <input type="text" name="cookiecontrol_settings[fontSize]" id="cookiecontrol_settings[fontSize]" value="<?php echo $options['fontSize'] ?>" size="8" /> em
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="cookiecontrol_settings[backgroundColor]"><?php _e('Background color', 'cookie-control'); ?></label>
            </th>
            <td>
              <input type="text" name="cookiecontrol_settings[backgroundColor]" id="cookiecontrol_settings[backgroundColor]" value="<?php echo $options['backgroundColor'] ?>" size="8" />
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="cookiecontrol_settings[toggleText]"><?php _e('Toggle text color', 'cookie-control'); ?></label>
            </th>
            <td>
              <input type="text" name="cookiecontrol_settings[toggleText]" id="cookiecontrol_settings[toggleText]" value="<?php echo $options['toggleText'] ?>" size="8" />
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="cookiecontrol_settings[toggleColor]"><?php _e('Toggle color', 'cookie-control'); ?></label>
            </th>
            <td>
              <input type="text" name="cookiecontrol_settings[toggleColor]" id="cookiecontrol_settings[toggleColor]" value="<?php echo $options['toggleColor'] ?>" size="8" />
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="cookiecontrol_settings[toggleBackground]"><?php _e('Toggle background color', 'cookie-control'); ?></label>
            </th>
            <td>
              <input type="text" name="cookiecontrol_settings[toggleBackground]" id="cookiecontrol_settings[toggleBackground]" value="<?php echo $options['toggleBackground'] ?>" size="8" />
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="cookiecontrol_settings[alertText]"><?php _e('Alert text color', 'cookie-control'); ?></label>
            </th>
            <td>
              <input type="text" name="cookiecontrol_settings[alertText]" id="cookiecontrol_settings[alertText]" value="<?php echo $options['alertText'] ?>" size="8" />
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="cookiecontrol_settings[alertBackground]"><?php _e('Alert background color', 'cookie-control'); ?></label>
            </th>
            <td>
              <input type="text" name="cookiecontrol_settings[alertBackground]" id="cookiecontrol_settings[alertBackground]" value="<?php echo $options['alertBackground'] ?>" size="8" />
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="cookiecontrol_settings[acceptTextColor]"><?php _e('Accept text color', 'cookie-control'); ?></label>
            </th>
            <td>
              <input type="text" name="cookiecontrol_settings[acceptTextColor]" id="cookiecontrol_settings[acceptTextColor]" value="<?php echo $options['acceptTextColor'] ?>" size="8" />
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="cookiecontrol_settings[acceptBackground]"><?php _e('Accept background color', 'cookie-control'); ?></label>
            </th>
            <td>
              <input type="text" name="cookiecontrol_settings[acceptBackground]" id="cookiecontrol_settings[acceptBackground]" value="<?php echo $options['acceptBackground'] ?>" size="8" />
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="cookiecontrol_settings[buttonIcon]"><?php _e('Button Icon (url)', 'cookie-control'); ?></label>
            </th>
            <td>
              <input type="text" name="cookiecontrol_settings[buttonIcon]" id="cookiecontrol_settings[buttonIcon]" value="<?php echo $options['buttonIcon'] ?>" size="50" />
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="cookiecontrol_settings[buttonIconWidth]"><?php _e('Button icon width', 'cookie-control'); ?></label>
              <p>Applicable only if custom icon used</p>
            </th>
            <td>
              <input type="text" name="cookiecontrol_settings[buttonIconWidth]" id="cookiecontrol_settings[buttonIconWidth]" value="<?php echo $options['buttonIconWidth'] ?>" size="8" /> px
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="cookiecontrol_settings[buttonIconHeight]"><?php _e('Button icon height', 'cookie-control'); ?></label>
              <p>Applicable only if custom icon used</p>
            </th>
            <td>
              <input type="text" name="cookiecontrol_settings[buttonIconHeight]" id="cookiecontrol_settings[buttonIconHeight]" value="<?php echo $options['buttonIconHeight'] ?>" size="8" /> px
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="cookiecontrol_settings[removeIcon]"><?php _e('Remove icon', 'cookie-control'); ?></label>
            </th>
            <td>
              <input type="radio" class="first" name="cookiecontrol_settings[removeIcon]" id="cookiecontrol_settings[removeIcon]" value="true" <?php checked('true', $options['removeIcon']); ?> /><?php _e('Yes', 'cookie-control'); ?>
              <input type="radio" name="cookiecontrol_settings[removeIcon]" id="cookiecontrol_settings[removeIcon]" value="false" <?php checked('false', $options['removeIcon']); ?> /><?php _e('No', 'cookie-control'); ?>
            </td>           
          </tr>
          <tr>
            <th scope="row">
              <label for="cookiecontrol_settings[removeAbout]"><?php _e('Remove about text', 'cookie-control'); ?></label>
            </th>
            <td>
              <input type="radio" class="first" name="cookiecontrol_settings[removeAbout]" id="cookiecontrol_settings[removeAbout]" value="true" <?php checked('true', $options['removeAbout']); ?> /><?php _e('Yes', 'cookie-control'); ?>
              <input type="radio" name="cookiecontrol_settings[removeAbout]" id="cookiecontrol_settings[removeAbout]" value="false" <?php checked('false', $options['removeAbout']); ?> /><?php _e('No', 'cookie-control'); ?>
            </td>           
          </tr>
       </table>   
        
        <div class="warning">
          <div class="dashicons dashicons-warning"><span class="screen-reader-text"><?php _e('warning', 'cookie-control'); ?></span></div>
          Please note, in changing the branding object you take responsibility for the module's accessibility standard. Should you set the removeIcon option to true, it is your responsibility to create your own ever present button that invokes CookieControl.toggle() so that users may still have consistent access to granting and revoking their consent.
        </div>
        
      </div> 
        
      <div id="regional">
        <h2><?php _e('Geolocation And Localisation', 'cookie-control'); ?></h2>
        <p>With <b>pro</b> and <b>pro_multisite</b> licenses, you are able to disable the module entirely for visitors outside of the EU, and offer alternative languages.</p>
        
        <div class="excludedCountriesTemplate">
          <table class="form-table">
            <tr>
              <th scope="row">
                <label for="cookiecontrol_settings[excludedCountries]"><?php _e('Excluded Country (ISO code)', 'cookie-control'); ?></label>						
              </th>
              <td>
                <input placeholder="Language code ex. US" type="text" name="cookiecontrol_settings[excludedCountries]"  id="cookiecontrol_settings[excludedCountries]" size="20" />
                <a href="#" class="remove removeRow" data-class="excludedCountries" id="removeexcludedCountries"><div class="dashicons dashicons-dismiss"><span class="screen-reader-text"><?php _e('Remove', 'cookie-control'); ?></span></div></a>
              </td>
            </tr>
          </table>		
        </div>
        <p>
          Either add the value <b>all</b>, or a 2 letter ISO code (ex. US) for the country you wish to disable the module for. 
          View full <b><a target="_blank" href="https://www.loc.gov/standards/iso639-2/php/code_list.php">list of languages codes.</a></b>
        <p>
        <div id="excludedCountriesContainer">
          <?php 
            if ( !empty( $options['excludedCountries'] ) )
                foreach ($options['excludedCountries'] as $key => $val ) :  ?>
                    <?php if ( trim($val) != '') : ?>
                        <div class="excludedCountries">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="cookiecontrol_settings[excludedCountries][<?php echo $key ?>]"><?php _e('Excluded Country (ISO code)', 'cookie-control'); ?></label>		
                                    </th>
                                    <td>
                                        <input type="text" name="cookiecontrol_settings[excludedCountries][<?php echo $key ?>]"  id="cookiecontrol_settings[excludedCountries][<?php echo $key ?>]" value="<?php echo $val ?>" size="20" />
                                        <a href="#" class="remove removeRow" data-class="excludedCountries" id="removeexcludedCountries"><div class="dashicons dashicons-dismiss"><span class="screen-reader-text"><?php _e('Remove', 'cookie-control'); ?></span></div></a>
                                    </td>
                                </tr>
                            </table>
                        </div>		
                    <?php endif ?>
                <?php endforeach; ?> 

        </div>
        <button class="addRow civic" data-class="excludedCountries"><i class="icon-plus-sign icon-white"></i><?php _e('Add Excluded Country', 'cookie-control'); ?></button></br>
                
        <div class="warning">
          <div class="dashicons dashicons-warning"><span class="screen-reader-text"><?php _e('warning', 'cookie-control'); ?></span></div>
          Please note, the excludedCountries option is ignored if the user accesses your website from within the EU, or their location cannot be identified.
        </div> 
        
        <hr />
        
        <h2><?php _e('Alternative languages', 'cookie-control'); ?></h2>
        
        <div class="altLanguagesTemplate">
          <table class="form-table">
            <tr>
              <td>
                <label for="cookiecontrol_settings[altLanguages]"><?php _e('Alternative language (ISO code)', 'cookie-control'); ?></label>		                			
                <input placeholder="Language code ex. el" type="text" name="cookiecontrol_settings[altLanguages]"  id="cookiecontrol_settings[altLanguages]" size="20" />
                <a href="#" class="remove removeRow" data-class="altLanguages" id="removealtLanguages"><div class="dashicons dashicons-dismiss"><span class="screen-reader-text"><?php _e('Remove', 'cookie-control'); ?></span></div></a>
              </td>
            </tr>
            <tr>
              <td colspan="2">
                <label for="cookiecontrol_settings[altLanguagesText]"><?php _e('Translation for the alternative language', 'cookie-control'); ?></label>		
                <p>The text object mirrors that of the default text object, and allows you to localise all values to this particular locale / language.</p>					
              </td>
            </tr>
            <tr>
              <td>
                <textarea name="cookiecontrol_settings[altLanguagesText]" id="cookiecontrol_settings[altLanguagesText]" cols="100" rows="10"></textarea>
              </td>
              <td>
                <p>Your input should contain all of the language strings you wish to translate<br>(Do not use single quotes ('') or apostrophe (') in your input).  For example: </p>
                <pre  style="font-size: 10px;">
text : {
  title: 'Αυτός ο ιστότοπος χρησιμοποιεί cookies για να αποθηκεύσει πληροφορίες στον υπολογιστή σας.',
  intro:  'Μερικά από αυτά είναι απαραίτητα, ενώ άλλα μας βοηθούν να βελτιώσουμε την εμπειρία σας δείχνοντάς μας πώς χρησιμοποιείται ο ιστότοπος. &lt;a href="/privacy-policy">Περισσότερα&lt;/a&gt;',
  necessaryTitle : 'Απαραίτητα Cookies',
  necessaryDescription : 'Τα απαραίτητα cookies καθιστουν δυνατή την λειτουργικότητα του ιστοτόπου, όπως για παράδειγμα την πλοήγηση και την πρόσβαση σε ασφαλείς περιοχές του ιστοτόπου. Ο ιστότοπος δεν μπορεί να λειτουργήσει χωρίς αυτά, και μπορούν να απενεργοποιηθούν μονο από τον φυλλομετρητή σας.',
  thirdPartyTitle : 'Προειδοποίηση: Μερικά cookies ζητούν την προσοχή σας',
  thirdPartyDescription : 'Η συγκατάθεση στα παρακάτω cookies δεν μπορεί να ανακληθεί αυτόματα. Παρακαλώ ακολουθηστε τον παρακάτω σύνδεσμο για να αποχωρήσετε από τη χρήση αυτών των υπηρεσιών.',
  acceptRecommended:'Αποδοχή προτεινόμενων ρυθμίσεων',
  on : 'On',
  off : 'Off',
  notifyTitle : 'Οι επιλογές σχετικά με τα cookies σεα αυτό το site',
	notifyDescription : 'Χρησιμοποιούμε cookies για να βελτιώσουμε την χρήση του site',
  accept : 'Αποδοχή',
  settings : 'Προτιμήσεις',
  optionalCookies:[
              {
                  label: 'Analytics',
                  description: 'Τα analytics cookies μας βοηθούν να βελτιώσουμε το website μας, παρακολουθώντας την επισκεψιμότητα και τη χρήση του.'
              }
          ]
}
                </pre>	
              </td>
            </tr>
          </table>		
        </div>
        <p>Accepts either a full locale (en_US), or two letter language code (en). Where both are present and matched with the current user's preference, the more specific locale will be used</p>	
        <div id="altLanguagesContainer">
          <?php 
            if ( !empty( $options['altLanguages'] ) ) :
                foreach ($options['altLanguages'] as $key => $val ) :  ?>
                    <?php if ( trim($val) != '') : ?>
                        <div class="altLanguages">
                            <table class="form-table">
                                <tr>
                                    <td>
                                      <label for="cookiecontrol_settings[altLanguages][<?php echo $key; ?>]"><?php _e('Alternative language (ISO code)', 'cookie-control'); ?></label>		
                                      <input type="text" name="cookiecontrol_settings[altLanguages][<?php echo $key;?>]"  id="cookiecontrol_settings[altLanguages][<?php echo $key; ?>]" value="<?php echo $val ?>" size="20" />
                                      <a href="#" class="remove removeRow" data-class="altLanguages" id="removealtLanguages"><div class="dashicons dashicons-dismiss"><span class="screen-reader-text"><?php _e('Remove', 'cookie-control'); ?></span></div></a>
                                    </td>
                                </tr>
                                <tr>
                                  <td colspan="2">
                                    <label for="cookiecontrol_settings[altLanguagesText][<?php echo $key; ?>]"><?php _e('Translation for the alternative language', 'cookie-control'); ?></label>		
                                    <p>The text object mirrors that of the default text object, and allows you to localise all values to this particular locale / language.</p>					
                                  </td>
                                </tr>
                                <tr>
                                  <td>
                                    <textarea name="cookiecontrol_settings[altLanguagesText][<?php echo $key; ?>]" id="cookiecontrol_settings[altLanguagesText][<?php echo $key; ?>]" cols="180" rows="10"><?php echo $options['altLanguagesText'][$key] ?></textarea>
                                  </td>
                                </tr>
                            </table>
                        </div>		
                    <?php endif ?>
                <?php endforeach; ?>          
                <div id="last-used-key-altLanguages" data-keyid="<?php echo $key ?>"></div>
      <?php else: ?>          
                <div id="last-used-key-altLanguages" data-keyid="0"></div>
      <?php endif; ?>

        </div>
        <button class="addRow civic" data-class="altLanguages"><i class="icon-plus-sign icon-white"></i><?php _e('Add Alternative Language', 'cookie-control'); ?></button></br>
        
        
      </div> 
        
      
      <div id="accessibility">
        <h2><?php _e('Accessibility', 'cookie-control'); ?></h2>
        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="cookiecontrol_settings[accessKey]"><?php _e('Access key', 'cookie-control'); ?></label>
            </th>
            <td>
              <input type="text" name="cookiecontrol_settings[accessKey]" id="cookiecontrol_settings[accessKey]" value="<?php echo $options['accessKey'] ?>" size="8" />
            </td>
          </tr>
          <tr class="description">
            <td colspan="2">
              <p>Remaps the accesskey that the module is assigned to.</p>
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="cookiecontrol_settings[highlightFocus]"><?php _e('Highlight focus', 'cookie-control'); ?></label>
            </th>
            <td>
              <input type="radio" class="first" name="cookiecontrol_settings[highlightFocus]" value="false" <?php checked('false', ( isset($options['highlightFocus']) ? $options['highlightFocus'] : '' )); ?> />
                <?php _e('No', 'cookie-control'); ?>
              <input type="radio" name="cookiecontrol_settings[highlightFocus]" value="true" <?php checked('true', ( isset($options['highlightFocus']) ? $options['highlightFocus'] : '' )); ?> />
                <?php _e('Yes', 'cookie-control'); ?>
            </td>
          </tr>
          <tr class="description">
            <td colspan="2">
              <p>Determines if the module should use more accentuated styling to highlight elements in focus, or use the browser's outline default.</p>  
              <p>If enabled, this property uses CSS filters to invert the module's colours. This should hopefully mean that a higher visual contrast is achieved, even with a custom branding.</p>
            </td>
          </tr>
        </table>
          
      </div>

    </div>    
    
    <!-- Submit -->
    <p class="submit">
      <input type="submit" class="button-primary" value="<?php _e('Save Settings', 'cookie-control') ?>" />
    </p>
</form>
    
<!-- The Reset Option -->
<form method="post" action="options.php">
    <?php settings_fields('cookiecontrol_settings'); ?>
    <?php global $cookiecontrol_defaults; // use the defaults ?>
    <?php foreach((array)$cookiecontrol_defaults as $key => $value) : ?>
    <input type="hidden" name="cookiecontrol_settings[<?php echo $key; ?>]" value="<?php echo $value; ?>" />
    <?php endforeach; ?>
    <input type="hidden" name="cookiecontrol_settings[update]" value="RESET" />
    <input type="submit" class="button" value="<?php _e('Reset Settings') ?>" />
</form>
<!-- End Reset Option -->

</div>
</div><!-- End of Plugin Option Page Container -->

<?php 
}

function cookiecontrol_settings_update_check() {
    global $cookiecontrol_settings;
    if(isset($cookiecontrol_settings['update'])) {
      echo '<div class="updated fade" id="message"><p>Cookie Control Settings <strong>'.$cookiecontrol_settings['update'].'</strong></p></div>';
      unset($cookiecontrol_settings['update']);
      update_option('cookiecontrol_settings', $cookiecontrol_settings);
    }
}

function cookiecontrol_settings_validate($input) {

    $input['logConsent'] = wp_filter_nohtml_kses($input['logConsent']);
    $input['notifyOnce'] = wp_filter_nohtml_kses($input['notifyOnce']);
    $input['position'] = wp_filter_nohtml_kses($input['position']);
    $input['theme'] = wp_filter_nohtml_kses($input['theme']);
    $input['layout'] = wp_filter_nohtml_kses($input['layout']);
    $input['initialState'] = wp_filter_nohtml_kses($input['initialState']);
    $input['toggleType'] = wp_filter_nohtml_kses($input['toggleType']);
    $input['closeStyle'] = wp_filter_nohtml_kses($input['closeStyle']);
    $input['expiry'] = (empty($input['expiry']) || !is_numeric($input['expiry'])) ? '' : $input['expiry'];
    
    $input['titleText'] = sanitize_text_field(wp_filter_nohtml_kses($input['titleText']));
    $input['introText'] = esc_js($input['introText']);
    $input['necessaryTitle'] = sanitize_text_field(wp_filter_nohtml_kses($input['necessaryTitle']));
    $input['necessaryDescription'] =  esc_js(strip_tags($input['necessaryDescription'], '<a>'));
    $input['thirdPartyTitle'] = sanitize_text_field(wp_filter_nohtml_kses($input['thirdPartyTitle']));
    $input['thirdPartyDescription'] =  esc_js(strip_tags($input['thirdPartyDescription'], '<a>'));
    $input['onText'] = sanitize_text_field(wp_filter_nohtml_kses($input['onText']));
    $input['offText'] = sanitize_text_field(wp_filter_nohtml_kses($input['offText']));
    $input['acceptText'] = sanitize_text_field(wp_filter_nohtml_kses($input['acceptText']));
    $input['settingsText'] = sanitize_text_field(wp_filter_nohtml_kses($input['settingsText']));
  	$input['privacyURL'] = sanitize_text_field($input['privacyURL']);
  	$input['privacyName'] = sanitize_text_field(wp_filter_nohtml_kses($input['privacyName']));
  	$input['privacyDescription'] = sanitize_text_field(wp_filter_nohtml_kses($input['privacyDescription']));
  	$input['privacyUpdateDate'] = sanitize_text_field($input['privacyUpdateDate']);
  	$input['acceptRecommended'] = sanitize_text_field(wp_filter_nohtml_kses($input['acceptRecommended']));
    $input['notifyTitle'] = sanitize_text_field(wp_filter_nohtml_kses($input['notifyTitle']));
    $input['notifyDescription'] = sanitize_text_field(wp_filter_nohtml_kses($input['notifyDescription']));
    $input['closeLabel'] = sanitize_text_field(wp_filter_nohtml_kses($input['closeLabel']));
    $input['accessibilityAlert'] = sanitize_text_field(wp_filter_nohtml_kses($input['accessibilityAlert']));
	
    $input['fontColor'] = (empty($input['fontColor']) || !preg_match('|^#([A-Fa-f0-9]{3}){1,2}$|', $input['fontColor'])) ? '' : $input['fontColor'];
    $input['fontSizeTitle'] = (empty($input['fontSizeTitle']) || !is_numeric($input['fontSizeTitle'])) ? '' : $input['fontSizeTitle'];
    $input['fontSizeHeaders'] = (empty($input['fontSizeHeaders']) || !is_numeric($input['fontSizeHeaders'])) ? '' : $input['fontSizeHeaders'];
    $input['fontSize'] = (empty($input['fontSize']) || !is_numeric($input['fontSize'])) ? '' : $input['fontSize'];
    $input['backgroundColor'] = (empty($input['backgroundColor']) || !preg_match('|^#([A-Fa-f0-9]{3}){1,2}$|', $input['backgroundColor'])) ? '' : $input['backgroundColor'];
    $input['toggleText'] = (empty($input['toggleText']) || !preg_match('|^#([A-Fa-f0-9]{3}){1,2}$|', $input['toggleText'])) ? '' : $input['toggleText'];
    $input['toggleColor'] = (empty($input['toggleColor']) || !preg_match('|^#([A-Fa-f0-9]{3}){1,2}$|', $input['toggleColor'])) ? '' : $input['toggleColor'];
    $input['toggleBackground'] = (empty($input['toggleBackground']) || !preg_match('|^#([A-Fa-f0-9]{3}){1,2}$|', $input['toggleBackground'])) ? '' : $input['toggleBackground'];
    $input['buttonIcon'] = (empty($input['buttonIcon'])) ? null : esc_url($input['buttonIcon']);
    $input['buttonIconWidth'] = (empty($input['buttonIconWidth']) || !is_numeric($input['buttonIconWidth'])) ? '64' : $input['buttonIconWidth'];
    $input['buttonIconHeight'] = (empty($input['buttonIconHeight']) || !is_numeric($input['buttonIconHeight'])) ? '64' : $input['buttonIconHeight'];
    //$input['removeIcon'] = (empty($input['removeIcon']) || !is_bool($input['removeIcon'])) ? false : $input['removeIcon'];
    //$input['removeAbout'] = (empty($input['removeAbout']) || !is_bool($input['removeAbout'])) ? false : $input['removeAbout'];
    $input['alertText'] = (empty($input['alertText']) || !preg_match('|^#([A-Fa-f0-9]{3}){1,2}$|', $input['alertText'])) ? '' : $input['alertText'];
    $input['alertBackground'] = (empty($input['alertBackground']) || !preg_match('|^#([A-Fa-f0-9]{3}){1,2}$|', $input['alertBackground'])) ? '' : $input['alertBackground'];
    $input['acceptTextColor'] = (empty($input['acceptTextColor']) || !preg_match('|^#([A-Fa-f0-9]{3}){1,2}$|', $input['acceptTextColor'])) ? '' : $input['acceptTextColor'];
    $input['acceptBackground'] = (empty($input['acceptBackground']) || !preg_match('|^#([A-Fa-f0-9]{3}){1,2}$|', $input['acceptBackground'])) ? '' : $input['acceptBackground'];
    
    if (!empty($input['optionalCookiesName']) && is_array($input['optionalCookiesName'])) {
      foreach ( $input['optionalCookiesName'] as $key => $val ) :
        $input['optionalCookiesName'][$key] = str_replace(' ', '_', sanitize_text_field($input['optionalCookiesName'][$key]));
        $input['optionalCookiesLabel'][$key] = esc_js(strip_tags($input['optionalCookiesLabel'][$key]));
        $input['optionalCookiesDescription'][$key] = esc_js(strip_tags($input['optionalCookiesDescription'][$key]));
        $input['optionalCookiesArray'][$key] = sanitize_text_field($input['optionalCookiesArray'][$key]);
        //$input['optionalCookiesinitialConsentState'][$key] = wp_filter_nohtml_kses($input['optionalCookiesinitialConsentState'][$key]);
        $input['optionalCookieslawfulBasis'][$key] = $input['optionalCookieslawfulBasis'][$key] !== 'legitimate interest' ? 'consent' : 'legitimate interest';
      endforeach;
    }
    
    if (!empty($input['necessaryCookies']) && is_array($input['necessaryCookies'])) {
      foreach ( $input['necessaryCookies'] as $key => $val ) :
        $input['necessaryCookies'][$key] = esc_js(sanitize_text_field($val));
      endforeach;
    }
    //add by default wp session cookies
    
    if (!empty($input['excludedCountries']) && is_array($input['excludedCountries'])) {
        foreach ( $input['excludedCountries'] as $key => $val ) :  
          $input['excludedCountries'][$key] = esc_js(sanitize_text_field($val)); 
        endforeach;
    }
    
    if (!empty($input['altLanguages']) && is_array($input['altLanguages'])) {
      $allowedHTML = array(
        'a' => array(
           'href' => array(),
           'title' => array()
        ),
       'br' => array(),
       'em' => array(),
       'strong' => array(),
       'p' => array(),
       'ul' => array(),
       'ol' => array(),
       'li' => array(),
      );

      foreach ( $input['altLanguages'] as $key => $val ) :
        $input['altLanguages'][$key] = esc_js(sanitize_text_field($val)); 
        $input['altLanguagesText'][$key] = wp_kses($input['altLanguagesText'][$key], $allowedHTML); 
      endforeach;
    }
    
    return $input;
}

add_action('wp_enqueue_scripts', 'cookiecontrol_scripts');
function cookiecontrol_scripts() {
    if(!is_admin()) {
        wp_enqueue_script('cookiecontrol', '//cc.cdn.civiccomputing.com/8/cookieControl-8.x.min.js', array(), '', true);
    }
}

add_action('wp_footer', 'cookiecontrol_args', 1500);
function cookiecontrol_args() {
	global $cookiecontrol_settings; 
  if ( $cookiecontrol_settings['apiKey'] != '' ):
	?>

<script type="text/javascript">
  var config = {
    apiKey: '<?php echo $cookiecontrol_settings['apiKey']; ?>',
    product: '<?php echo $cookiecontrol_settings['product']; ?>',
    logConsent : <?php echo $cookiecontrol_settings['logConsent']; ?>,    
    
    notifyOnce: <?php echo $cookiecontrol_settings['notifyOnce']; ?>,
    initialState: '<?php echo $cookiecontrol_settings['initialState']; ?>',
    position: '<?php echo $cookiecontrol_settings['position']; ?>',
    theme: '<?php echo $cookiecontrol_settings['theme']; ?>',
    layout: '<?php echo $cookiecontrol_settings['layout']; ?>',
    toggleType: '<?php echo $cookiecontrol_settings['toggleType']; ?>',
    closeStyle: '<?php echo $cookiecontrol_settings['closeStyle']; ?>',
    consentCookieExpiry: <?php echo $cookiecontrol_settings['expiry']; ?>,
    
    accessibility : {
      accessKey: '<?php echo $cookiecontrol_settings['accessKey']; ?>',
      highlightFocus: <?php echo $cookiecontrol_settings['highlightFocus']; ?>,      
    },
        
    text : {
      title: '<?php echo $cookiecontrol_settings['titleText']; ?>',
      intro:  '<?php echo $cookiecontrol_settings['introText']; ?>',
      necessaryTitle : '<?php echo $cookiecontrol_settings['necessaryTitle']; ?>',
      necessaryDescription : '<?php echo $cookiecontrol_settings['necessaryDescription']; ?>',
      thirdPartyTitle : '<?php echo $cookiecontrol_settings['thirdPartyTitle']; ?>',
      thirdPartyDescription : '<?php echo $cookiecontrol_settings['thirdPartyDescription']; ?>',
      on : '<?php echo $cookiecontrol_settings['onText']; ?>',
      off : '<?php echo $cookiecontrol_settings['offText']; ?>',
      accept : '<?php echo $cookiecontrol_settings['acceptText']; ?>',
      settings : '<?php echo $cookiecontrol_settings['settingsText']; ?>',
      acceptRecommended : '<?php echo $cookiecontrol_settings['acceptRecommended']; ?>',
      notifyTitle : '<?php echo $cookiecontrol_settings['notifyTitle']; ?>',
      notifyDescription : '<?php echo $cookiecontrol_settings['notifyDescription']; ?>',
      closeLabel : '<?php echo $cookiecontrol_settings['closeLabel']; ?>',
      accessibilityAlert : '<?php echo $cookiecontrol_settings['accessibilityAlert']; ?>',
    },
    
    <?php if ( $cookiecontrol_settings['product'] != 'COMMUNITY' ): ?>

    branding : {
      fontColor: '<?php echo $cookiecontrol_settings['fontColor']; ?>',
      fontFamily: '<?php echo $cookiecontrol_settings['fontFamily']; ?>',
      fontSizeTitle: '<?php echo $cookiecontrol_settings['fontSizeTitle']; ?>em',
      fontSizeHeaders: '<?php echo $cookiecontrol_settings['fontSizeHeaders']; ?>em',
      fontSize: '<?php echo $cookiecontrol_settings['fontSize']; ?>em',
      backgroundColor: '<?php echo $cookiecontrol_settings['backgroundColor']; ?>',
      toggleText: '<?php echo $cookiecontrol_settings['toggleText']; ?>',
      toggleColor: '<?php echo $cookiecontrol_settings['toggleColor']; ?>',
      toggleBackground: '<?php echo $cookiecontrol_settings['toggleBackground']; ?>',
      alertText: '<?php echo $cookiecontrol_settings['alertText']; ?>',
      alertBackground: '<?php echo $cookiecontrol_settings['alertBackground']; ?>',
      acceptText: '<?php echo $cookiecontrol_settings['acceptTextColor']; ?>',
      acceptBackground: '<?php echo $cookiecontrol_settings['acceptBackground']; ?>',
      <?php if (empty($cookiecontrol_settings['buttonIcon'])) : ?>
        buttonIcon: null,
      <?php else: ?>
        buttonIcon: '<?php echo $cookiecontrol_settings['buttonIcon']; ?>',
      <?php endif; ?>  
      buttonIconWidth: '<?php echo $cookiecontrol_settings['buttonIconWidth']; ?>px',
      buttonIconHeight: '<?php echo $cookiecontrol_settings['buttonIconHeight']; ?>px',
      removeIcon: <?php echo $cookiecontrol_settings['removeIcon']; ?>,
      removeAbout: <?php echo $cookiecontrol_settings['removeAbout']; ?>
    },

    <?php endif;?>

    <?php if ( is_array($cookiecontrol_settings['excludedCountries']) && sizeof($cookiecontrol_settings['excludedCountries']) > 0 && !empty($cookiecontrol_settings['excludedCountries']) ) : ?>
      <?php 
      $excludedCountriesVal = '';
      foreach ( $cookiecontrol_settings['excludedCountries'] as $key => $val ) :  
        $excludedCountriesVal .= $val != '' ? "'".$val."'," : ''; 
      endforeach;
      ?>
      <?php if ( $excludedCountriesVal != ''): ?>    
        excludedCountries: [ <?php echo substr($excludedCountriesVal, 0 , -1); ?> ],
      <?php endif; ?>  
    <?php endif; ?>
      
      
    <?php if ( is_array($cookiecontrol_settings['altLanguages']) && sizeof($cookiecontrol_settings['altLanguages']) > 0 && !empty($cookiecontrol_settings['altLanguages']) ) : ?>
    locales: [
      <?php foreach ( $cookiecontrol_settings['altLanguages'] as $key => $val ) :  ?>
          <?php if ( $val != '' && $cookiecontrol_settings['altLanguagesText'][$key] != '' ): ?> 
      {
          locale: '<?php echo $val; ?>',
          <?php echo $cookiecontrol_settings['altLanguagesText'][$key]; ?> 
      },       
          <?php endif; ?> 
      <?php  endforeach; ?> 
    ],
    <?php endif; ?>  
    
    <?php
      $necessaryCookiesVal = "'wordpress_*','wordpress_logged_in_*','CookieControl',";
    ?>
        
    <?php if ( is_array($cookiecontrol_settings['necessaryCookies']) && sizeof($cookiecontrol_settings['necessaryCookies']) > 0 && !empty($cookiecontrol_settings['necessaryCookies']) ) : ?>
      <?php 
      foreach ( $cookiecontrol_settings['necessaryCookies'] as $key => $val ) :  
        $necessaryCookiesVal .= $val != '' ? "'".$val."'," : ''; 
      endforeach; 
      ?>
    <?php endif; ?>
      
    <?php if ( $necessaryCookiesVal != ''): ?>    
      necessaryCookies: [ <?php echo substr($necessaryCookiesVal, 0 , -1); ?> ],
    <?php endif; ?>
            
    <?php if ( is_array($cookiecontrol_settings['optionalCookiesName']) && sizeof($cookiecontrol_settings['optionalCookiesName']) > 0 && !empty($cookiecontrol_settings['optionalCookiesName']) ) : ?>
    optionalCookies: [
      <?php foreach ( $cookiecontrol_settings['optionalCookiesName'] as $key => $val ) : ?>
          <?php if ( $val != '' ) : ?>
          {
            name: '<?php echo $val; ?>',
            label: '<?php echo $cookiecontrol_settings['optionalCookiesLabel'][$key]; ?>',
            description: '<?php echo $cookiecontrol_settings['optionalCookiesDescription'][$key]; ?>',
            cookies: [ <?php echo $cookiecontrol_settings['optionalCookiesArray'][$key]; ?> ],
            onAccept : function(){
              <?php echo $cookiecontrol_settings['optionalCookiesonAccept'][$key]; ?>
            },
            onRevoke : function(){
              <?php echo $cookiecontrol_settings['optionalCookiesonRevoke'][$key]; ?>
            },
            <?php if ( $cookiecontrol_settings['optionalCookiesthirdPartyCookies'][$key] && !empty($cookiecontrol_settings['optionalCookiesthirdPartyCookies'][$key]) ): ?>
              <?php if (substr($cookiecontrol_settings['optionalCookiesthirdPartyCookies'][$key], 0, 1) !== '{'): ?>
                <?php   $tempThirdPartyCookies = '{' . $cookiecontrol_settings['optionalCookiesthirdPartyCookies'][$key] . '}'; ?>
              <?php else: ?>
                <?php $tempThirdPartyCookies =  $cookiecontrol_settings['optionalCookiesthirdPartyCookies'][$key]; ?> 
              <?php endif; ?>   
              thirdPartyCookies: [<?php echo $tempThirdPartyCookies; ?>],
            <?php endif; ?> 
            recommendedState : '<?php echo $cookiecontrol_settings['optionalCookiesinitialConsentState'][$key]; ?>',
            lawfulBasis : '<?php echo isset($cookiecontrol_settings['optionalCookieslawfulBasis'][$key]) ? $cookiecontrol_settings['optionalCookieslawfulBasis'][$key] : 'consent'; ?>',
          },
          <?php endif; ?>
      <?php endforeach; ?>
    ],
    <?php endif; ?>

<?php if ( !empty($cookiecontrol_settings['privacyURL']) ): ?>
    statement:  {
      description: '<?php echo $cookiecontrol_settings['privacyDescription']; ?>',
      name: '<?php echo $cookiecontrol_settings['privacyName']; ?>',
      url: '<?php echo $cookiecontrol_settings['privacyURL']; ?>',
      updated: '<?php echo $cookiecontrol_settings['privacyUpdateDate']; ?>'
    },
<?php endif; ?>    
      
  };

  CookieControl.load( config );
</script>
<?php 
  endif;
}
