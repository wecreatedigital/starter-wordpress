<?php
class evScriptOptimizerBackend {

    static $saved = false;
    
    /**
     * init
     */
    static function init() {
		register_activation_hook(__FILE__,       array(__CLASS__, 'activation_hook'));
		add_action('admin_menu',                 array(__CLASS__, 'admin_menu'));
        add_action('admin_head',                 array(__CLASS__, 'ajax_javascript'));
        add_action('admin_head',                 array(__CLASS__, 'admin_css'));
        add_action('wp_ajax_spacker_inc_script', array(__CLASS__, 'wp_ajax_spacker_inc_script'));
        add_action('admin_notices',              array(__CLASS__, 'show_admin_messages'));
        
        if (is_admin()) {
            if (isset($_POST['spacker']) && is_array($_POST['spacker'])) {
                //evScriptOptimizer::$options = $_POST['spacker'];
                self::options_join(evScriptOptimizer::$options, $_POST['spacker']);
                
                evScriptOptimizer::$options['cache-js'] = array();
                evScriptOptimizer::$options['cache-css'] = array();
                //print_r(evScriptOptimizer::$options);

                evScriptOptimizer::save_options();
                evScriptOptimizer::check_cache_directory();
                self::$saved = true;
            }
        }
    }

    static function activation_hook() {
        if (! is_array(evScriptOptimizer::$options['inc-js'])) {
            evScriptOptimizer::$options['inc-js'] = array(
                                                        'jquery' => array(
                                                            'name' => 'jquery',
                                                            'url' => 'http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js'));
        }
    }


    static function show_admin_messages() {
        if (! evScriptOptimizer::check_cache_directory()) {
            // Only show to admins
            if (current_user_can('manage_options')) {
               ?>
               <div id="message" class="error"><p><strong><?php echo self::get_cache_dir_message(); ?></strong></p></div>
               <?php
            }
        }
    }
    
	static public function get_cache_dir_message() {
        return sprintf(__('Cannot create cache directory "%s". Please create this folder with a "write" (777) permissions.', 'spacker'), evScriptOptimizer::$options['cache-dir-path']);
    }
    
    /**
     * admin_menu action
     */
    static function admin_menu() {	
        add_options_page(__('JS & CSS Script Optimizer Options', 'spacker'), __('Script Optimizer', 'spacker'), 'manage_options', 'script-optimizer', array(__CLASS__, 'settings_section'));
    }

    static function is_option_tab($tab_name) {
        if (($tab_name == 'basic') && (!isset($_GET['tab']) || ($_GET['tab'] == 'basic')))
            return true;

        return (isset($_GET['tab']) && $_GET['tab'] == $tab_name);
    }

    static function settings_section() {
        if (!current_user_can('manage_options')) {
            wp_die( __('You do not have sufficient permissions to access this page.') );
        }
        ?>
        <div class="wrap">
            <div class="icon32" id="icon-tools"><br></div>
            <h2 class="spacker-backend-title"><?php _e('JS & CSS Script Optimizer Options', 'spacker') ?></h2>
            <ul class="subsubsub" id="spacker-menu">
                <li><a <?php if (self::is_option_tab('basic')) 		echo 'class="current"'; ?> href="<?php echo add_query_arg('tab', 'basic') ?>">Basic </a> |</li>
                <li><a <?php if (self::is_option_tab('inc_js')) 	echo 'class="current"'; ?> href="<?php echo add_query_arg('tab', 'inc_js') ?>">Include JS</a> |</li>
                <li><a <?php if (self::is_option_tab('inc_css')) 	echo 'class="current"'; ?> href="<?php echo add_query_arg('tab', 'inc_css') ?>">Include CSS</a> |</li>
				<li><a <?php if (self::is_option_tab('help')) 		echo 'class="current"'; ?> href="<?php echo add_query_arg('tab', 'help') ?>">Info</a></li>
            </ul>
            <br class="clear"/>

            <?php if (self::$saved) { ?>
            <div class="updated" id="message">
                <p><strong><?php _e('Options have been saved! Cache cleared.', 'spacker'); ?></strong></p>
            </div>
            <?php } ?>
            
            <form action="" method="post">
                <?php
                if (self::is_option_tab('inc_js')) self::options_tab_inc_js();
                elseif (self::is_option_tab('inc_css')) self::options_tab_inc_css();
				elseif (self::is_option_tab('help')) self::options_tab_help();
                else self::options_tab_basic();
                ?>
            </form>
            <br/>
        </div>
        <?php
    }
	
	// --- Admin Tabs: ---
	
    static function options_tab_basic() { ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php _e('Enable plugin', 'spacker'); ?></th>
                <td>
					<input name="spacker[enable-plugin]" type="hidden" value="0" />
                    <input name="spacker[enable-plugin]" type="checkbox" id="enable-plugin" value="1" <?php if (evScriptOptimizer::$options['enable-plugin']) echo 'checked'; ?> />
                    <label for="enable-plugin"><?php _e('Enable', 'spacker'); ?></label>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="cache-dir-url"><?php _e('Cache directory URL', 'spacker'); ?></label></th>
                <td>
                    <input type="text" name="spacker[cache-dir-url]" id="cache-dir-url" value="<?php echo esc_attr(evScriptOptimizer::$options['cache-dir-url']); ?>" style="width: 500px" /><br/>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="cache-dir-path"><?php _e('Cache directory path', 'spacker'); ?></label></th>
                <td>
                    <input type="text" name="spacker[cache-dir-path]" id="cache-dir-path" value="<?php echo esc_attr(evScriptOptimizer::$options['cache-dir-path']); ?>" style="width: 500px" /><br/>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="http-request-timeout"><?php _e('HTTP request timeout', 'spacker'); ?></label></th>
                <td>
                    <input type="text" name="spacker[http-request-timeout]" id="http-request-timeout" value="<?php echo esc_attr(evScriptOptimizer::$options['http-request-timeout']); ?>" style="width: 500px" /><br/>
                </td>
            </tr>
            <tr valign="top">
                <td colspan="2">
                    <h3><?php _e('JavaScript output options'); ?></h3>
                </td>
            </tr>
            <!--tr valign="top">
                <th scope="row"><?php _e('Strict ordering', 'spacker'); ?></th>
                <td>
					<input name="spacker[strict-ordering-beta]" type="hidden" value="0" />
                    <input name="spacker[strict-ordering-beta]" type="checkbox" id="strict-ordering-beta" value="1" <?php if (evScriptOptimizer::$options['strict-ordering-beta']) echo 'checked'; ?> />
                    <label for="strict-ordering-beta"><?php _e('Better compatibility with other plugins <sup>betta</sup>', 'spacker'); ?></label>
                </td>
            </tr-->
            <tr valign="top">
                <th scope="row"><?php _e('Pack JavaScripts', 'spacker'); ?></th>
                <td>
					<input name="spacker[packing-js]" type="hidden" value="0" />
                    <input onchange="spacker_enable_packing_options()" name="spacker[packing-js]" type="checkbox" id="packing-js" value="1" <?php if (evScriptOptimizer::$options['packing-js']) echo 'checked'; ?> />
                    <label for="packing-js"><?php _e('Compress (pack / minify) scripts', 'spacker'); ?></label>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('JavaScripts Minifier', 'spacker'); ?></th>
                <td>
					<input name="spacker[minifier]" type="hidden" value="<?php echo esc_attr( evScriptOptimizer::$options['minifier'] ); ?>" />
                    
                    <input name="spacker[minifier]" type="radio" id="js-minifier-deanedwards" value="deanedwards" <?php if (evScriptOptimizer::$options['minifier'] == 'deanedwards') echo 'checked'; ?> />
                    <label for="js-minifier-deanedwards"><?php _e('Dean Edwards Packer', 'spacker'); ?></label><br/>

                    <input name="spacker[minifier]" type="radio" id="js-minifier-minify" value="minify" <?php if (evScriptOptimizer::$options['minifier'] == 'minify') echo 'checked'; ?> />
                    <label for="js-minifier-minify"><?php _e('Minify by Steve Clay', 'spacker'); ?></label><br/>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('Ignore external JavaScript', 'spacker'); ?></th>
                <td>
					<input name="spacker[only-selfhosted-js]" type="hidden" value="0" />
                    <input name="spacker[only-selfhosted-js]" type="checkbox" id="only-selfhosted-js" value="1" <?php if (evScriptOptimizer::$options['only-selfhosted-js']) echo 'checked'; ?> />
                    <label for="only-selfhosted-js"><?php _e('For self-hosted only', 'spacker'); ?></label>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('Combine JavaScripts', 'spacker'); ?></th>
                <td>
                    <input name="spacker[combine-js]" type="radio" id="combine-js" value="combine" <?php if (evScriptOptimizer::$options['combine-js'] == 'combine') echo 'checked'; ?> />
                    <label for="combine-js"><?php _e('Combine all scripts into the two files (in the header & footer)', 'spacker'); ?></label><br/>

                    <input name="spacker[combine-js]" type="radio" id="move-bottom-js" value="move-bottom" <?php if (evScriptOptimizer::$options['combine-js'] == 'move-bottom') echo 'checked'; ?> />
                    <label for="move-bottom-js"><?php _e('Combine & Move all JavaScripts to the bottom', 'spacker'); ?></label><br/>

                    <input name="spacker[combine-js]" type="radio" id="no-combine-js" value="0" <?php if (! evScriptOptimizer::$options['combine-js']) echo 'checked'; ?> />
                    <label for="no-combine-js"><?php _e('Do not combine JavaScripts', 'spacker'); ?></label><br/>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="exclude-js"><?php _e('Ignore next JavaScripts', 'spacker'); ?></label></th>
                <td>
                    <textarea name="spacker[exclude-js]" id="exclude-js" cols="60"><?php echo esc_attr(evScriptOptimizer::$options['exclude-js']); ?></textarea><br/>
                    <?php _e('Comma separated handles or file names. For example: jquery, jquery-ui.js', 'spacker'); ?>
                </td>
            </tr>

            <tr valign="top">
                <td colspan="2">
                    <h3><?php _e('Style-sheets output options'); ?></h3>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="spacker-css"><?php _e('Enable CSS optimizer', 'spacker'); ?></label></th>
                <td>
					<input name="spacker[css]" type="hidden" value="0" />
                    <input onchange="spacker_enable_css_options()" name="spacker[css]" type="checkbox" id="spacker-css" value="1" <?php if (evScriptOptimizer::$options['css']) echo 'checked'; ?> />
                    <label for="spacker-css"><?php _e('Use plugin for CSS', 'spacker'); ?></label>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('Ignore external CSS', 'spacker'); ?></th>
                <td>
					<input name="spacker[only-selfhosted-css]" type="hidden" value="0" />
                    <input name="spacker[only-selfhosted-css]" type="checkbox" id="only-selfhosted-css" value="1" <?php if (evScriptOptimizer::$options['only-selfhosted-css']) echo 'checked'; ?> />
                    <label for="only-selfhosted-css"><?php _e('For self-hosted only', 'spacker'); ?></label>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('Combine CSS', 'spacker'); ?></th>
                <td>
					<input name="spacker[combine-css]" type="hidden" value="0" />
                    <input name="spacker[combine-css]" type="checkbox" id="combine-css" value="1" <?php if (evScriptOptimizer::$options['combine-css']) echo 'checked'; ?> />
                    <label for="combine-css"><?php _e('Combine all CSS scripts into the single file', 'spacker'); ?></label>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('Style-sheets packing', 'spacker'); ?></th>
                <td>
					<input name="spacker[packing-css]" type="hidden" value="0" />
                    <input disabled="disabled" name="spacker[packing-css]" type="checkbox" id="packing-css" value="1" <?php /* if (evScriptOptimizer::$options['packing-css']) */ echo 'checked'; ?> />
                    <label for="packing-css"><?php _e('Minify CSS files (remove comments, tabs, spaces, newlines)', 'spacker'); ?></label>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><label for="exclude-css"><?php _e('Ignore next CSS', 'spacker'); ?></label></th>
                <td>
                    <textarea name="spacker[exclude-css]" id="exclude-css" cols="60"><?php echo esc_attr(evScriptOptimizer::$options['exclude-css']); ?></textarea><br/>
                    <?php _e('Comma separated handles or file names. For example: jquery-ui.custom.css', 'spacker'); ?>
                </td>
            </tr>
            <tr valign="top">
                <th colspan="2"><input type="submit" class="button-primary" name="spacker[save]" value="<?php _e('Save options', 'spacker') ?>" class="aligment-right" /></th>
            </tr>

            <script type="text/javascript">
                function spacker_enable_packing_options() {
                    var spacker_css_el = document.getElementById('packing-js');
                    var ch = spacker_css_el.checked;

                    document.getElementById('js-minifier-deanedwards').disabled = ! ch;
                    document.getElementById('js-minifier-minify').disabled = ! ch;
                }
                
                function spacker_enable_css_options() {
                    var spacker_css_el = document.getElementById('spacker-css');
                    var ch = spacker_css_el.checked;

                    document.getElementById('only-selfhosted-css').disabled = ! ch;
                    document.getElementById('combine-css').disabled = ! ch;
                    //document.getElementById('packing-css').disabled = ! ch;
                    document.getElementById('exclude-css').disabled = ! ch;
                }
                
                spacker_enable_packing_options();
                spacker_enable_css_options();
            </script>
        </table>
        <?php
    }

    static function get_spacker_inc_js_table() { ?>
        <table cellspacing="0" class="widefat post fixed" style="width: 100%">
            <thead>
                <tr>
                    <th class="manage-column" style="width:110px"><?php _e('Name', 'spacker') ?></th>
                    <th class="manage-column"><?php _e('URL', 'spacker') ?></th>
                    <th class="manage-column" style="width:110px"><?php _e('Action', 'spacker') ?></th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <th class="manage-column"><?php _e('Name', 'spacker') ?></th>
                    <th class="manage-column"><?php _e('URL', 'spacker') ?></th>
                    <th class="manage-column"><?php _e('Action', 'spacker') ?></th>
                </tr>
            </tfoot>
            <tbody>
                <?php
                if (is_array(evScriptOptimizer::$options['inc-js'])) {
                    foreach(evScriptOptimizer::$options['inc-js'] as $script) { ?>
                <tr>
                    <td><?php echo $script['name']; ?></td>
                    <td><?php echo $script['url']; ?></td>
                    <td><a href="#" rel="<?php echo $script['name']; ?>" class="delete"><?php _e('Delete', 'spacker'); ?></a></td>
                </tr>
                <?php }
                } ?>
            </tbody>
        </table>
        <?php
    }

    static function get_spacker_inc_css_table() { ?>
        <table cellspacing="0" class="widefat post fixed" style="width: 100%">
            <thead>
                <tr>
                    <th class="manage-column" style="width:110px"><?php _e('Name', 'spacker') ?></th>
                    <th class="manage-column"><?php _e('URL', 'spacker') ?></th>
					<th class="manage-column" style="width:110px"><?php _e('Media', 'spacker') ?></th>
                    <th class="manage-column" style="width:110px"><?php _e('Action', 'spacker') ?></th>
					<th class="manage-column" style="width:130px"><?php _e('Known users only', 'spacker') ?></th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <th class="manage-column"><?php _e('Name', 'spacker') ?></th>
                    <th class="manage-column"><?php _e('URL', 'spacker') ?></th>
                    <th class="manage-column"><?php _e('Media', 'spacker') ?></th>
					<th class="manage-column"><?php _e('Action', 'spacker') ?></th>
					<th class="manage-column"><?php _e('Known users only', 'spacker')?></th>
                </tr>
            </tfoot>
            <tbody>
                <?php
                if (is_array(evScriptOptimizer::$options['inc-css'])) {
                    foreach(evScriptOptimizer::$options['inc-css'] as $script) { ?>
                <tr>
                    <td><?php echo $script['name']; ?></td>
                    <td><?php echo $script['url']; ?></td>
					<td><?php echo ucwords($script['media']); ?></td>
                    <td><a href="#" rel="<?php echo $script['name']; ?>" class="delete"><?php _e('Delete', 'spacker'); ?></a></td>
					<td><?php echo $script['admin'] ? 'yes' : 'no'; ?></td>
                </tr>
                <?php }
                } ?>
            </tbody>
        </table>
        <?php
    }

    static function options_tab_inc_js() { ?>
        <h3>
			<?php _e("Include next JavaScript", 'spacker'); ?>
			<span id="spacker-ajax-status"><?php _e('Loading...', 'spacker'); ?></span>
		</h3>
        <div id="spacker_inc_js_table">
            <?php self::get_spacker_inc_js_table(); ?>
        </div>
        <br class="clear"/>
        <div id="spacker_add_js_form">
            <label><span class="spacker_item_title"><?php _e('Name:', 'spacker'); ?></span> <input type="text" id="spacker_add_js_name"/></label>
            <br/>
            <label><span class="spacker_item_title"><?php _e('URL:', 'spacker'); ?></span> <input type="text" id="spacker_add_js_url"/></label>
            <br/>
            <input type="button" class="button-primary spacker-add-script-button" id="spacker-inc-add-js" value="<?php echo esc_attr(__('Add JavaScript', 'spacker')); ?>" />
        </div>
        <br class="clear"/>
        <?php
    }

    static function options_tab_inc_css() { ?>
        <h3>
			<?php _e('Include next StyleSheet', 'spacker'); ?>
			<span id="spacker-ajax-status"><?php _e('Loading...', 'spacker'); ?></span>
		</h3>
        <div id="spacker_inc_css_table">
            <?php self::get_spacker_inc_css_table(); ?>
        </div>
        <br class="clear"/>
        <div id="spacker_add_js_form">
            <label><span class="spacker_item_title"><?php _e('Name:', 'spacker'); ?></span> <input type="text" id="spacker_add_css_name"/></label>
            <br/>
            <label><span class="spacker_item_title"><?php _e('URL:', 'spacker'); ?></span> <input type="text" id="spacker_add_css_url"/></label>
            <br/>
            <label>
				<span class="spacker_item_title"><?php _e('Media:', 'spacker'); ?></span> 
				<select type="text" id="spacker_add_css_media">
					<option value="screen">Screen</option>
					<option value="print">Print</option>
					<option value="all">All</option>
				</select>			
			</label>
            <br/>
			<label><span class="spacker_item_title"><?php _e('If logged in:', 'spacker'); ?></span> <input type="checkbox" id="spacker_add_css_loggedIn"/> <span><?php _e('Display to known users only', 'spacker'); ?></span></label>
			<br/>
            <input type="button" class="button-primary spacker-add-script-button" id="spacker-inc-add-css" value="<?php echo esc_attr(__('Add CSS', 'spacker')); ?>" />
        </div>
        <br class="clear"/>
        <?php
    }
	
    static function options_tab_help() { ?>
        <div class="block-content">
            <h3>Plugin Features</h3>
            <ul>
                <li>WPMU / Network support</li>
                <li>Combine several scripts into the single file (to minimize http requests)</li>
                <li>Pack scripts using Dean Edwards's JavaScript Packer or Minify by Steve Clay</li>
                <li>You can move all JavaScripts to the bottom</li>
                <li>Combine all CSS scripts into the single files (with grouping by "media")</li>
                <li>Minify CSS files (remove comments, tabs, spaces, newlines)</li>
                <li>Ability to include JavaScript and CSS files</li>
                <li>If any script fails and shows error you can add it to exclude list</li>
            </ul>

            <h3>Requirements</h3>
            <ul>
                <li>This Plugin processes only those scripts that are included properly (using "wp_enqueue_script" or "wp_enqueue_style" function). <br/>So please read <a title="Permanent Link to How to properly add CSS in WordPress" rel="bookmark" href="https://developer.wordpress.org/themes/basics/including-css-javascript/">Including CSS & JavaScript</a>.</li>
                <li>Uploads directory should be writable</li>
            </ul>
            <p>For <b>more information</b> visit <a href="http://4coder.info/en/code/wordpress-plugins/js-css-script-optimizer/" title="This WordPress plugin home page">http://4coder.info/en/code/wordpress-plugins/js-css-script-optimizer/</a>.</p>
        </div>
        <div class="spacker-donate">

            <h4>Support "Script Optimizer"</h4>
            
            <p>Please <a target="_blank" href="http://wordpress.org/support/view/plugin-reviews/js-css-script-optimizer">write a review</a> to this plugin.</p>
            <?php /*
            <p>Donate using Visa/MasterCard:</p>
                
            <form action="https://www.liqpay.com/api/pay" accept-charset="utf-8" method="POST" target="_blank">
                <input type="hidden" value="i0060075195" name="public_key">
                <input type="hidden" value="10" name="amount">
                <input type="hidden" value="USD" name="currency">
                <input type="hidden" value="4Coder.Info website" name="description">
                <input type="hidden" value="donate" name="type">
                <input type="hidden" value="en" name="language">
                <input type="image" name="btn_text" src="//static.liqpay.com/buttons/d1en.radius.png">
            </form>

            <p>Donate using Moneybookers:</p>

            <form action="https://www.moneybookers.com/app/payment.pl" method="post" target="_blank">
                <fieldset>
                    <input type="hidden" name="pay_to_email" value="evgennniy@gmail.com">
                    <input type="hidden" name="return_url" value="http://4coder.info/donate-thanks/">

                    <input type="text" name="amount" value="5.00" size="5">

                    <select name="currency" size="1">
                        <option value="USD">$ USD</option>
                        <option value="EUR">€ EUR</option>
                        <option value="GBP">£ GBP</option>
                        <option value="JPY">¥ JPY</option>
                        <option value="CAD">$ CAD</option>
                        <option value="AUD">$ AUD</option>
                    </select>

                    <input type="submit" class="button-primary" alt="Click to make a donation" value="Donate!">
                    <input type="hidden" name="language" value="EN">
                    <input type="hidden" name="detail1_description" value="Donation mission">
                    <input type="hidden" name="detail1_text" value="Donation to evolve 4Coder.info">
                </fieldset>
            </form> 
            */ ?>
            <p>Thanks for using!</p>
        </div>
        <?php 
        /*
        <h3><?php _e("Help me to make my site more fast &rarr;", 'spacker'); ?></h3>

        <p><?php _e("We appreciate your attention to our plug-in and hope it works well for you.", 'spacker'); ?></p>
        <p><?php _e("If you need any support in the plug-in use or you need an advanced speed optimization please do not hesitate to contact us.", 'spacker'); ?></p>
        <p><?php _e("We will reply as soon as we can.", 'spacker'); ?></p>

		<div class="spacker-help">
			<?php _e("Email", 'spacker'); ?>: <a href="mailto:optimize@4coder.info">optimize@4coder.info</a>
		</div>
        <?php
        */
    }
	
	

    static function wp_ajax_spacker_inc_script() {
		// ----------------------------- Add JS --------------------------------
        if ($_POST['mode'] == 'add-js') { 
            if (! is_array(evScriptOptimizer::$options['inc-js'])) {
                evScriptOptimizer::$options['inc-js'] = array();
			}

            $name = trim($_POST['name']);
            $url  = trim($_POST['url']);
            $error_message = '';

            // Validate
            if (empty($name) || empty($url)) {
                $error_message = __('Fields "Name" and "URL" cannot be empty.', 'spacker');
            }
            elseif (isset(evScriptOptimizer::$options['inc-js'][$name])) {
                $error_message = __('This name is already used.', 'spacker');
            }
            else {
                // Add script
                evScriptOptimizer::$options['inc-js'][$name] = array('name'=> $name, 'url' => $url);
                evScriptOptimizer::save_options();
            }

            // Output
            if ($error_message) { ?>
                <div class="error settings-error">
                    <p><strong><?php echo $error_message; ?></strong></p>
                </div>
            <?php
            } else { ?>
                <div class="updated">
                    <p><strong><?php _e('Script has been added.', 'spacker'); ?></strong></p>
                </div>
            <?php
            }
            self::get_spacker_inc_js_table();
        }

        // ---------------------------- Delete JS ------------------------------
        elseif($_POST['mode'] == 'delete-js') { 
            $name = $_POST['name'];

            if (! isset(evScriptOptimizer::$options['inc-js'][$name])) {
                $error_message = __('Cannot find this script.', 'spacker');
            }
            else {
                // Delete script
                unset(evScriptOptimizer::$options['inc-js'][$name]);
                evScriptOptimizer::save_options();
            }

            // Output
            if ($error_message) { ?>
                <div class="error settings-error">
                    <p><strong><?php echo $error_message; ?></strong></p>
                </div>
            <?php
            } else { ?>
                <div class="updated">
                    <p><strong><?php _e('Script has been deleted.', 'spacker'); ?></strong></p>
                </div>
            <?php
            }
            self::get_spacker_inc_js_table();
        }

        // ----------------------------- Add CSS -------------------------------
        elseif ($_POST['mode'] == 'add-css') { 
            if (! is_array(evScriptOptimizer::$options['inc-css']))
                evScriptOptimizer::$options['inc-css'] = array();

            $name     = trim($_POST['name']);
            $url      = trim($_POST['url']);
			$media    = trim($_POST['media']);
			$loggedIn = $_POST['loggedIn'] ? 1 : 0;
            $error_message = '';

            // Validate
            if (empty($name) || empty($url)) {
                $error_message = __('Fields "Name" and "URL" cannot be empty.', 'spacker');
            }
            elseif (isset(evScriptOptimizer::$options['inc-css'][$name])) {
                $error_message = __('This Name is already used.', 'spacker');
            }
            else {
                // Add script
				evScriptOptimizer::$options['inc-css'][$name] = array('name'=> $name, 'url' => $url, 'media' => $media, 'loggedIn' => $loggedIn);
                evScriptOptimizer::save_options();
            }

            // Output
            if ($error_message) { ?>
                <div class="error settings-error">
                    <p><strong><?php echo $error_message; ?></strong></p>
                </div>
            <?php
            } else { ?>
                <div class="updated">
                    <p><strong><?php _e('Script has been added.', 'spacker'); ?></strong></p>
                </div>
            <?php
            }
            self::get_spacker_inc_css_table();
        }

        // ----------------------------- Delete CSS ----------------------------
        elseif($_POST['mode'] == 'delete-css') { 
            $name = $_POST['name'];

            if (! isset(evScriptOptimizer::$options['inc-css'][$name])) {
                $error_message = __('Cannot find this script.', 'spacker');
            }
            else {
                // Delete script
                unset(evScriptOptimizer::$options['inc-css'][$name]);
                evScriptOptimizer::save_options();
            }

            // Output
            if ($error_message) { ?>
                <div class="error settings-error">
                    <p><strong><?php echo $error_message; ?></strong></p>
                </div>
            <?php
            } else { ?>
                <div class="updated">
                    <p><strong><?php _e('Script has been deleted.', 'spacker'); ?></strong></p>
                </div>
            <?php
            }
            self::get_spacker_inc_css_table();
        }
        
        die();
    }
    
    static function ajax_javascript() {
        if (isset($_GET['page']) && ($_GET['page'] === 'script-optimizer')) { ?>
        <script type="text/javascript">
            function spacker_save_js(data){
                // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
                jQuery.post(ajaxurl, data, function(response) {
                    jQuery('#spacker_inc_js_table').html(response);
                });
            }

            function spacker_save_css(data){
                // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
                jQuery.post(ajaxurl, data, function(response) {
                    jQuery('#spacker_inc_css_table').html(response);
                });
            }

            jQuery(document).ready(function($) {
                jQuery('#spacker-inc-add-js').click(function() {
                    var data = {
                        action: 'spacker_inc_script'
                    };

                    data['mode'] = 'add-js';
                    data['name'] = $('#spacker_add_js_name').val();
                    data['url'] = $('#spacker_add_js_url').val();

                    spacker_save_js(data);
                });

                jQuery('#spacker_inc_js_table').delegate("a.delete", "click", function() {
                    var data = {
                        action: 'spacker_inc_script'
                    };

                    data['mode'] = 'delete-js';
                    data['name'] = jQuery(this).attr('rel');
                    spacker_save_js(data);
                    return false;
                });

                jQuery('#spacker-inc-add-css').click(function() {
                    var data = {
                        action: 'spacker_inc_script'
                    };

                    data['mode'] = 'add-css';
                    data['name'] = $('#spacker_add_css_name').val();
                    data['url'] = $('#spacker_add_css_url').val();
					data['media'] = $('#spacker_add_css_media').val();		
					data['loggedIn'] = $('#spacker_add_css_loggedIn').attr('checked') ? 1 : 0;

                    spacker_save_css(data);
                });

                jQuery('#spacker_inc_css_table').delegate("a.delete", "click", function() {
                    var data = {
                        action: 'spacker_inc_script'
                    };

                    data['mode'] = 'delete-css';
                    data['name'] = jQuery(this).attr('rel');
                    spacker_save_css(data);
                    return false;
                });

                // Loading status
                jQuery('#spacker-ajax-status').ajaxStart(function(){
                    jQuery(this).css('display', 'inline');
                });
                jQuery('#spacker-ajax-status').ajaxStop(function(){
                    jQuery(this).css('display', 'none');
                });
            });
        </script>
        <?php
        }
    }

    static function admin_css() {
        if (isset($_GET['page']) && ($_GET['page'] === 'script-optimizer')) { ?>
        <style type="text/css">
            #spacker_add_js_form {
                padding: 6px;
                float: left;
                margin: 2px 0 10px;
            }

            #spacker_add_js_form label span.spacker_item_title {
                display: inline-block;
                width: 76px;
            }

            #spacker_add_js_form label input[type="text"],
			#spacker_add_js_form label select{
                width: 400px;
            }

            .spacker-add-script-button {
                float: right;
                margin: 4px 0 0;
            }

            .spacker_add_script_h {
                font-size: 1em;
                font-weight: normal;
                margin: 10px 2px 0;
            }

            .spacker-backend-title sup a {
                text-decoration: none;
            }

            #spacker-ajax-status {
                display: none;
                float: right;
                font-style: italic;
                color: #999;
				font-size: 15px;
				font-weight:normal;
            }

			.spacker-help {
				background-color: #FFFBCC;
				border-color: #E6DB55;
				border-radius: 4px 4px 4px 4px;
				border-style: solid;
				border-width: 1px;
				color: #555555;
				float: left;
				font-size: 12px;
				font-weight: bold;
				line-height: 14px;
				margin: 0 7px 7px 0;
				padding: 8px 10px;
			}
			
			.spacker-backend-title {
				position: relative;
			}
			
			.spacker-donate {
				background-color: #FFFBCC;
				border-color: #E6DB55;
				border-radius: 6px 6px 6px 6px;
				border-style: solid;
				border-width: 1px;
				color: #555555;
				font-size: 15px;
				font-style: normal;
				line-height: 26px;
				padding: 0 13px 7px;
			}

            .block-content ul{
                margin: 0px 0 4px 20px;
                list-style: circle;
            }
			
			#screen-meta {
				z-index: 1;
			}
        </style>
        <?php
        }
    }

    static function options_join(&$options, &$merge) {
        foreach ($merge as $key => $m_val) {
            if (!is_array($m_val)){
                $options[$key] = $m_val;
            }
            else {
                nf_options_join($options[$key], $m_val);
            }
        }
    }
}
?>