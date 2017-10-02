<?php
/*
  Plugin Name: JS & CSS Script Optimizer
  Plugin URI: http://4coder.info/en/code/wordpress-plugins/js-css-script-optimizer/
  Version: 0.3.3
  Author: Yevhen Kotelnytskyi
  Author URI: http://4coder.info/en/
  Description: Features: Combine all scripts into the single file, Pack scripts using Dean Edwards's JavaScript Packer or Minify by Steve Clay, Move all JavaScripts to the bottom, Combine all CSS scripts into the single file, Minify CSS files (remove comments, tabs, spaces, newlines).
*/

define('SO_REC_CACHE_DIR_PATH', str_replace(ABSPATH, '', WP_CONTENT_DIR . '/cache/scripts/'));
define('SO_REC_CACHE_DIR_URL', content_url()  . '/cache/scripts/');

class evScriptOptimizer {

    static $upload_url = '';
    static $plugin_path = '';
    static $cache_directory = '';
    static $cache_url = '';
    static $options = null;
    static $js_printed = false;
	static $css_printed = false;

	static $ordering_started = false;

    /**
     * init
     */
    static function init() {
        $is_logged_in = is_user_logged_in();

        // init some constants
        self::$plugin_path = dirname(__FILE__);
        
        // load plugin localizations
        load_plugin_textdomain( 'spacker', false, dirname(plugin_basename(__FILE__)) . '/lang/' );

        // load options
        self::$options = get_option('spacker-options');
		//self::$options = false;//***
        if (! is_array(self::$options)) {
            self::$options = array(
                'only-selfhosted-js'    => false,
                'combine-js'            => 'combine',
                'packing-js'            => true,
				'css'                   => true,
                'only-selfhosted-css'   => false,
                'combine-css'           => true,
                'packing-css'           => true,
                'inc-js'                => null,
                'inc-css'               => null,
                'exclude-js'            => null,
                'exclude-css'           => null,
                'cache'                 => array(),
                'cache-css'             => array(),
				'strict-ordering-beta'  => false,
				'cache-dir-path'        => SO_REC_CACHE_DIR_PATH,
				'cache-dir-url'         => SO_REC_CACHE_DIR_URL,
				'http-request-timeout'  => 5,
				'minifier'              => 'deanedwards',
			);
        }
        else {
            if (! isset(self::$options['minifier'])) {
				self::$options['minifier'] = 'deanedwards';
            }
                
            // Old cache directory
            if (! isset(self::$options['cache-dir-path'])) {
                $uploads = wp_upload_dir();        
                $upload_path = $uploads['basedir'] . '/';
                $upload_url = $uploads['baseurl'] . '/';       
                
                if (substr($upload_path, -1) != '/') $upload_path .= '/';
                if (substr($upload_url, -1) != '/') $upload_url .= '/';
                
                $cache_directory = $upload_path . 'spacker-cache/';
                $cache_url = $upload_url . 'spacker-cache/';

                self::$options['cache-dir-path'] = $cache_directory;
				self::$options['cache-dir-url']  = $cache_url;
                self::save_options();
            }
        }
		
		if (! isset(self::$options['strict-ordering-beta'])) {
			self::$options['enable-plugin'] = true;
			self::$options['strict-ordering-beta'] = false;
		}
		if (! isset(self::$options['combine-css']) AND !empty(self::$options['combining-css'])) {
			self::$options['combine-css'] = true;
		}
		if (! isset(self::$options['http-request-timeout']) 
              OR !is_int(self::$options['http-request-timeout']) 
              OR self::$options['http-request-timeout'] < 2 
              OR self::$options['http-request-timeout'] > 60) {
                
			self::$options['http-request-timeout'] = 5;
		}
        
        // Strict ordering temporary disabled 
        self::$options['strict-ordering-beta'] = false;
        self::$options['packing-css'] = true;
        
        // add actions and hooks
		if (! is_admin()) {
			if (self::is_on()) {
                global $wp_version;
                if ( version_compare( $wp_version, '2.8.0', '>' ) ) {          
                    add_action('wp_print_scripts',         array(__CLASS__, 'wp_print_scripts_action'), 0);
                    add_action('wp_print_footer_scripts',  array(__CLASS__, 'wp_print_scripts_action'), 0);
                    
                    if (self::$options['css']) {
                        add_action('wp_print_styles',         array(__CLASS__, 'wp_print_styles_action'), -10000);
                        add_action('wp_print_footer_scripts', array(__CLASS__, 'wp_print_styles_action'), 0);
                    }
                }
                else {
                    add_action('wp_print_scripts', array(__CLASS__, 'wp_print_scripts_action'), 200);
                    
                    if (self::$options['css']) {
                        add_action('wp_print_styles',  array(__CLASS__, 'wp_print_styles_action'), 200);
                    }
                }

				add_action('wp_footer', array(__CLASS__, 'footer'), 20000000);
				//add_action('wp_head', array(__CLASS__, 'head'), 20000000);

				// Include added scripts
				if (is_array(self::$options['inc-js'])) {
					foreach (self::$options['inc-js'] as $key => $js){
						if ($js['url']) {
							wp_deregister_script($key);
							wp_register_script($key, $js['url'], false);
						}
						wp_enqueue_script($key);
					}
				}
				
				if (is_array(self::$options['inc-css'])) {
					foreach (self::$options['inc-css'] as $key => $css){
						if (!$css['loggedIn'] || $is_logged_in)
							wp_enqueue_style($key, $css['url'], false, false, $css['media']);
					}
				}
				
				if (self::$options['strict-ordering-beta']) {
					self::ordering_start();
				}
			}
        }
        else {
            require_once('backend.php');
            evScriptOptimizerBackend::init();
        }
    }
    
	static function is_on() {
        if (! self::check_cache_directory()) {
            return false;
        }        
		if (! self::$options['enable-plugin']) {
            return false;
        }		
        if (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) {
            return false;
        }
        return true;
	}
    
    static private function get_full_path( $path ) {
        if (($path[0] == '/') or ($path[1] == ':')) {
            return $path;
        }
        
        return ABSPATH . '/' . $path;
    }

    static function check_cache_directory() {
        if (empty(self::$options['cache-dir-path']) 
          or empty(self::$options['cache-dir-url'])) {
            self::$options['cache-dir-path'] = SO_REC_CACHE_DIR_PATH;
            self::$options['cache-dir-url']  = SO_REC_CACHE_DIR_URL;
            self::save_options();
        }    
    
        if ((self::$options['cache-dir-path'][0] == '/')
          or (self::$options['cache-dir-path'][1] == ':')) {
            self::$options['cache-dir-path'] = str_replace(ABSPATH, '', self::$options['cache-dir-path']);
            self::save_options();
        }    
    
        if (is_writable(self::get_full_path(self::$options['cache-dir-path']))) {
            return true;
        }
        else {
            if (@mkdir(self::get_full_path(self::$options['cache-dir-path']), 0777, true) 
                && @chmod(self::get_full_path(self::$options['cache-dir-path']), 0777)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check exclude list
     */
    static function exclude_this_js($handle, $src) {
        static $exclude_js = false;
        if ($exclude_js === false) {
            $exclude_js = explode(',', self::$options['exclude-js']);
            foreach ($exclude_js as $_k => $_v) {
                $exclude_js[$_k] = trim($_v);
                if (! $exclude_js[$_k])
                    unset($exclude_js[$_k]);
            }
        }
        return (in_array($handle, $exclude_js) || in_array(basename($src), $exclude_js));
    }

    /**
     * Check exclude list for css
     */
    static function exclude_this_css($handle, $src) {
        static $exclude_css = false;
        if ($exclude_css === false) {
            $exclude_css = explode(',', self::$options['exclude-css']);
            foreach ($exclude_css as $_k => $_css) {
                $exclude_css[$_k] = trim($_css);
                if (! $exclude_css[$_k]) unset($exclude_css[$_k]);
            }
        }
        return (in_array($handle, $exclude_css) || in_array(basename($src), $exclude_css));
    }

    /**
     * wp_print_scripts action
     *
     * @global $wp_scripts, $auto_compress_scripts
     */
    static function wp_print_scripts_action() {
        /* if ( did_action('wp_print_footer_scripts') ) */
        
        if (is_admin()) return;

        global $wp_scripts, $auto_compress_scripts;
		if (! is_a($wp_scripts, 'WP_Scripts')) return;
		
        if (! is_array($auto_compress_scripts))
            $auto_compress_scripts = array();

        $queue = $wp_scripts->queue;
        $wp_scripts->all_deps($queue);
        
        /*
        echo '<h1>@queue</h1>';  
        print_r($wp_scripts->queue);
        
        echo '<h1>@to_do</h1>';  
        print_r($wp_scripts->to_do);
        */

		foreach( $wp_scripts->to_do as $key => $handle ) {
			if ( !in_array($handle, $wp_scripts->done, true) && isset($wp_scripts->registered[$handle]) ) {

				if ( ! $wp_scripts->registered[$handle]->src ) { // Defines a group.
					$wp_scripts->done[] = $handle;
					continue;
				}

                $src      = self::normalize_url($wp_scripts->registered[$handle]->src);
                $_exclude = self::exclude_this_js($handle, $src);  // Check exclude list
                $external = self::is_external_url($src);
                
                if ((! $_exclude) AND (! self::$options['only-selfhosted-js'] || ! $external)) {
                    
                    $_conditional = isset($wp_scripts->registered[$handle]->extra['conditional']) 
                                        ? $wp_scripts->registered[$handle]->extra['conditional'] : '';
                    
                    // Print scripts those added before
                    if ( $_conditional ) {
                        self::print_scripts();
                    } 
                
                    $auto_compress_scripts[$handle] = array(
                                                        'src'      => $src, 
                                                        'external' => $external,
                                                        'ver'      => $wp_scripts->registered[$handle]->ver,
                                                        'args'     => $wp_scripts->registered[$handle]->args,
                                                        'extra'    => $wp_scripts->registered[$handle]->extra,
                                                        'localize' => isset($wp_scripts->registered[$handle]->extra['data']) 
                                                                        ? $wp_scripts->registered[$handle]->extra['data'] : '',
                                                    );
                                                    
                    // Print script with "conditional"
                    if ( $_conditional ) {
                        self::print_scripts( $_conditional );
                    } 
                                                    
                    // Print scripts
                    if (self::$options['combine-js'] == '0') {
                        self::print_scripts();
                    }
                    
                    ob_start();
                    if ( $wp_scripts->do_item( $handle ) ) {
                        $wp_scripts->done[] = $handle;
                    }
                    ob_end_clean();
                }
                else {
                    // Print scripts those added before
                    self::print_scripts();
                    
                    // Standard way
                    if ( $wp_scripts->do_item( $handle ) ) {
                        $wp_scripts->done[] = $handle;
                    }
                }

				unset( $wp_scripts->to_do[$key] );
			}
		}

        // printing scripts hear or move to the bottom
        if ( self::$options['combine-js'] == 'combine' || self::$js_printed) {
            self::print_scripts();
        }        
    }

    /**
     * wp_print_styles action
     *
     * @global $wp_styles, $auto_compress_styles
     */
    static function wp_print_styles_action() {
        if (is_admin()) return;
		
        global $wp_styles, $auto_compress_styles;
		if (! is_object($wp_styles)) return;
			
        if (! is_array($auto_compress_styles))
            $auto_compress_styles = array();
		
        $queue = $wp_styles->queue;
        $wp_styles->all_deps($queue);
        
		foreach( $wp_styles->to_do as $key => $handle ) {

			if ( !in_array($handle, $wp_styles->done, true) && isset($wp_styles->registered[$handle]) ) {

				if ( ! $wp_styles->registered[$handle]->src ) { // Defines a group.
					$wp_styles->done[] = $handle;
					continue;
				}

                $src      = self::normalize_url($wp_styles->registered[$handle]->src);
                $_exclude = self::exclude_this_css($handle, $src);  // Check exclude list
                $media    = ($wp_styles->registered[$handle]->args ? $wp_styles->registered[$handle]->args : 'all');
                $external = self::is_external_url($src);

                if ((! $_exclude) AND (! self::$options['only-selfhosted-css'] || ! $external)) {
                    unset($wp_styles->to_do[$key]);
                    
                    $conditional = 'no-conditional';
                    if (isset($wp_styles->registered[$handle]->extra) 
                        && isset($wp_styles->registered[$handle]->extra['conditional'])) {
                        $conditional = $wp_styles->registered[$handle]->extra['conditional'];
                    }
                        
                    $auto_compress_styles[$media][$conditional][$handle] = array(
                                                                'src'      => $src, 
                                                                'media'    => $media, 
                                                                'external' => $external,
                                                                'ver'      => $wp_styles->registered[$handle]->ver,
                                                                'args'     => $wp_styles->registered[$handle]->args,
                                                                'extra'    => $wp_styles->registered[$handle]->extra,
                                                            );
                                                            
                    // printing CSS
                    if (! self::$options['combine-css']) {
                        self::print_styles();
                    }

                    ob_start();
                    if ( $wp_styles->do_item( $handle ) ) {
                        $wp_styles->done[] = $handle;
                    }
                    ob_end_clean();
                }
                else {
                    // printing scripts
                    self::print_styles();
                    
                    if ( $wp_styles->do_item( $handle ) ) {
                        $wp_styles->done[] = $handle;
                    }
                }

				unset( $wp_styles->to_do[$key] );
			}
		}
        
		// printing CSS
		if (self::$css_printed || self::$options['combine-css']) {
			 self::print_styles();
		}
    }
	
	static private function normalize_url($url) {
    
        if (substr($url, 0, 2) == '//') {
            if (isset($_SERVER['HTTPS']) )
                $url = 'https:' . $url;
            else
                $url = 'http:' . $url;
        }	    
        
        if (substr($url, 0, 1) == '/') {
            $url = site_url($url);
        }	
        
        return $url;
    }
    
	static private function is_external_url($url) {
        
        if (substr($url, 0, 4) != 'http') {
            $url = site_url($url);
            return false;
        }
        else {
            $home = get_option('home');
            if (substr($url, 0, strlen($home)) == $home) {
                return false;
            }
            else return true;
        }
    }

	static public function save_options() {
        update_option('spacker-options', self::$options);
    }
    
	static function print_styles() {
		global $auto_compress_styles;
        
        // TODO: Check ordering
        foreach ($auto_compress_styles as $media => $conditionals) {
            foreach ($conditionals as $conditional => $scripts) {
                if ($conditional == 'no-conditional') {
                    $conditional = false;
                }
                self::print_styles_by_media($scripts, $media, $conditional);
            }
        }
		//self::$css_printed = true;
        $auto_compress_styles = array();
	}
    
    static function minify_js( $js ) {        
        /*
        $contents = str_replace("\r", "\n", $contents);                        
        $pattern = '/([\}]+)(\s*\n+\s*)([a-z]+)/i';                                
        $replacement = "$1; $3 ";                        
        preg_match_all($pattern, $contents, $matches);
        echo '<pre>' . print_r($matches, true) . '</pre>';                        
        $contents = preg_replace($pattern, $replacement, $contents);                   
        */
        
        if (! empty(self::$options['minifier']) 
           && self::$options['minifier'] == 'minify') {
            require_once self::$plugin_path . '/JSMin.php';
            return JSMin::minify( $js );            
        }
        else {
            require_once self::$plugin_path . '/JavaScriptPacker.php';
            $packer = new \Tholu\Packer\Packer($js);
            return $packer->pack();
        }
    }
    
    
    static function print_scripts( $conditional = false ) {
        global $auto_compress_scripts;
        if (! is_array($auto_compress_scripts) || ! count($auto_compress_scripts))
            return;

        $home = get_option('siteurl').'/';
        if (! is_array(self::$options['cache-js']))
            self::$options['cache-js'] = array();

        if (self::$options['combine-js']) {
            $handles = array_keys($auto_compress_scripts);
            $handles = implode(', ', $handles);
			$localize_js = '';
            
            // Calc "modified tag"
            $fileId = 0;
            foreach ($auto_compress_scripts as $handle => $script) {
                if (! $script['external']) {
                    $path = self::get_path_by_url($script['src'], $home);
                    $fileId += @filemtime($path);
                }
				else {
					$fileId += $script['ver'].$script['src'];
				}
                
                if (! empty($script['localize'])) {
                    $localize_js .= "/* $handle */\n" . $script['localize'] . "\n";
                }
            }			
			
            $cache_name = md5(md5($handles).$fileId).'-'.self::$options['minifier'];
            $cache_file_path = self::get_full_path(self::$options['cache-dir-path']) . $cache_name . '.js';
            $cache_file_url = self::$options['cache-dir-url'] . $cache_name . '.js';
            // echo "$fileId<br>".self::$options['cache'][$cache_name]."<br>$cache_file_path<br>$cache_file_url<br>".is_readable($cache_file_path);
            
            // Find a cache
            if (self::get_cache($cache_name, $cache_file_path, 'js')) {
                
                // Include script 
                self::print_js_script_tag($cache_file_url, $conditional, true, $localize_js);
                
                $auto_compress_scripts = array();
                return;
            }

            // Build cache
            $scripts_text = '';
            foreach ($auto_compress_scripts as $handle => $script) {
				$src = html_entity_decode($script['src']);
                $scripts_text .= "/* $handle: ($src) */\n";
                
                // Get script contents
                $_remote_get = wp_remote_get(self::add_url_param($src, 'v', rand(1, 9999999)), 
                    array(
                        'timeout' => self::$options['http-request-timeout']
                    )
                );
                
                if (! is_wp_error($_remote_get) && $_remote_get['response']['code'] == 200) {
                    $contents = $_remote_get['body'];
                    /*
                    [headers][last-modified] => Thu, 15 Nov 2012 02:26:22 GMT
                    [headers][etag] => "be2599-16dda-4ce7f607fcf80"
                    */
                    
                    if ((self::$options['packing-js']) 
                       && (strpos($src, '.pack.') === false) 
                       && (strpos($src, '.min.') === false)) {

                        $contents = self::minify_js($contents) . ";\n";
                    }
                    else {
                        $contents = $contents.";\n\n";
                    }
                    $scripts_text .= $contents;
                }
                else {
                    $scripts_text .= "/*\nError loading script content: $src\n";
                    if (! is_wp_error($_remote_get)) 
                        $scripts_text .= "HTTP Code: {$_remote_get['response']['code']} ({$_remote_get['response']['message']})\n*/\n\n"; ///************************
                }
            }
            $scripts_text = "/*\nCache: ".$handles."\n*/\n" . $scripts_text;
            
            // Save cache
            self::save_cache($cache_file_path, $scripts_text, $cache_name, $fileId, 'js');

            // Include script 
            self::print_js_script_tag($cache_file_url, $conditional, false, $localize_js);

            $auto_compress_scripts = array();
            //--------------------------------------------------------------------------------
        }
        else {
            foreach ($auto_compress_scripts as $handle => $script) {
				$src = html_entity_decode($script['src']);
                $fileId = 0;
                if (! $script['external']) {
                    $path = self::get_path_by_url($script['src'], $home);
                    $fileId = @filemtime($path);
                }
				else {
					$fileId += $script['ver'].$script['src'];
				}
                $cache_name = md5(md5($handle).$fileId).'-'.self::$options['minifier'];
                $cache_file_path = self::get_full_path(self::$options['cache-dir-path']) . $cache_name . '.js';
                $cache_file_url = self::$options['cache-dir-url'] . $cache_name . '.js';

                // Find a cache
                if (self::get_cache($cache_name, $cache_file_path, 'js')) {

                    // Include script 
                    self::print_js_script_tag($cache_file_url, $conditional, true, $script['localize']);

                    continue;
                }

                // Get script contents
                $_remote_get = wp_remote_get(self::add_url_param($src, 'v', rand(1, 9999999)), 
                    array(
                        'timeout' => self::$options['http-request-timeout']
                    )
                );
                
                if (! is_wp_error($_remote_get) && $_remote_get['response']['code'] == 200) {
                    $scripts_text = $_remote_get['body'];
                    
                    if ((self::$options['packing-js'])
                       && (strpos($src, '.pack.') === false) 
                       && (strpos($src, '.min.') === false)) {
                        $scripts_text = self::minify_js($scripts_text) . ";\n";
                    }

                    $scripts_text = "/* $handle: ($src) */\n" . $scripts_text;
                    
                    // Save cache
                    self::save_cache($cache_file_path, $scripts_text, $cache_name, $fileId, 'js');

                    // Include script 
                    self::print_js_script_tag($cache_file_url, $conditional, false, $script['localize']);
                }
                else {
                    // Include script 
                    if (! is_wp_error($_remote_get)) 
                        $error_message = "/* Error loading script content: $src; HTTP Code: {$_remote_get['response']['code']} ({$_remote_get['response']['message']}) */";
                    else
                        $error_message = "/* Error loading script content: $src */";
                    
                    self::print_js_script_tag($src, $conditional, false, $script['localize'], $error_message);
                } 
            }
            self::save_options();
            $auto_compress_scripts = array();
        }
    }
		
    static function compress_css($css, $path) {
        // remove comments, tabs, spaces, newlines, etc.
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', ' ', $css);
        $css = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), ' ', $css);
		$css = str_replace(
             array(';}', ' {', '} ', ': ', ' !', ', ', ' >', '> '),
             array('}',  '{',  '}',  ':',  '!',  ',',  '>',  '>'), $css);
		
        // url
        $dir = dirname($path).'/';
	    $css = preg_replace('|url\(\'?"?(([\s])*[a-zA-Z0-9=\?\&\-_\.]+[a-zA-Z0-9=\?\&\-_\s\./]*)\'?"?\)|', "url(\"$dir$1\")", $css);

        return $css;
    }
	
	static function get_path_by_url($url, $home) {
		$path = ABSPATH . str_replace($home, '', $url);
		$_p = strpos($path, '?');
		if ($_p !== false) {
			$path = substr($path, 0, $_p);
		}
		return $path;
	}
    
    static private function save_cache($cache_file_path, $cache, $cache_name, $fileId, $type) {
        self::save_script($cache_file_path, $cache);
        self::$options['cache-'.$type][$cache_name] = $fileId;
        self::save_options();
    }   
    
    static private function get_cache($cache_name, $cache_file_path, $type) {
        return (!empty(self::$options['cache-'.$type][$cache_name]) && is_readable($cache_file_path));
    }

    static private function print_js_script_tag($url, $conditional, $is_cache, $localize = '', $error_message = '') {
        
        if ($localize) {
            echo "<script type='text/javascript'>\n/* <![CDATA[ */\n$localize\n/* ]]> */\n</script>\n";
        }
            
        if ($conditional) {
            echo "<!--[if " . $conditional . "]>\n";        
        }
        
        echo '<script type="text/javascript" src="' . $url . '">' . ($is_cache ? '/*Cache!*/' : '') . $error_message . '</script>' . "\n";

        if ($conditional) {
            echo "<![endif]-->" . "\n";
        }
    }    
    
    static private function print_css_link_tag($url, $media, $conditional, $is_cache) {
        if ($conditional)
            echo "<!--[if " . $conditional . "]>\n";
              
        echo '<link rel="stylesheet" href="' . $url . '" type="text/css" media="' . $media . '" />' . (($is_cache && ! $conditional) ? ' <!-- Cache! -->' : '') . "\n";
        
        if ($conditional) 
            echo "<![endif]-->" . (($is_cache && $conditional) ? ' <!-- Cache! -->' : '') . "\n";
    }
	
    /*
     * Print CSS
     */
    static function print_styles_by_media($scripts, $media, $conditional) {
        global $auto_compress_styles;
        if (! is_array($scripts) || ! count($scripts))
            return false;

        $home = get_option('siteurl').'/';
        if (! is_array(self::$options['cache-css']))
            self::$options['cache-css'] = array();

        if (self::$options['combine-css']) {
            $handles = array_keys($scripts);
			$handles = implode(', ', $handles);
			
            // Calc "modified tag"
            $fileId = 0;
            foreach ($scripts as $handle => $script) {
                if (! $script['external']) {
                    $path = self::get_path_by_url($script['src'], $home);
                    $fileId += @filemtime($path);
                }
				else {
					$fileId .= '-'.$script['ver'];
				}
            }
            if (empty($fileId)) 
                $fileId = 'nover';
			
            $cache_name = md5(md5($handles).$fileId);
            $cache_file_path = self::get_full_path(self::$options['cache-dir-path']) . $cache_name . '.css';
            $cache_file_url = self::$options['cache-dir-url'] . $cache_name . '.css';
			
            // Find a cache
            if (self::get_cache($cache_name, $cache_file_path, 'css')) {
                
                // Include script 
                self::print_css_link_tag($cache_file_url, $media, $conditional, true);

                $scripts = array();
                return true;
            }

            // Build cache
            $scripts_text = '';
            foreach ($scripts as $handle => $script) {
                $src = html_entity_decode($script['src']);
                $scripts_text .= "/* $handle: ($src) */\n";

                // Get script contents
                $_remote_get = wp_remote_get(self::add_url_param($src, 'v', rand(1, 9999999)), 
                    array(
                        'timeout' => self::$options['http-request-timeout']
                    )
                );

                if (! is_wp_error($_remote_get) && $_remote_get['response']['code'] == 200) {
                    $content = $_remote_get['body'];

                    if (self::$options['packing-css']) {
                        $content = self::compress_css($content, $src);
                    }
                    $scripts_text .= $content . "\n";                    
                }
                else {
                    if (! is_wp_error($_remote_get)) {
                        $error_message = "/* Error loading script content: $src; HTTP Code: {$_remote_get['response']['code']} ({$_remote_get['response']['message']}) */";
                    }
                    else
                        $error_message = "/* Error loading script content: $src */";
                        
                    $scripts_text .= "$error_message\n";
                    $scripts_text .= "@import url('" . $src . "'); \n\n";
                }
            }
            $scripts_text = "/*\nCache: ".$handles."\n*/\n" . $scripts_text;

            // Save cache
            self::save_cache($cache_file_path, $scripts_text, $cache_name, $fileId, 'css');
            
            // Include script 
            self::print_css_link_tag($cache_file_url, $media, $conditional, false);

            return true;
            //--------------------------------------------------------------------------------
        }
        else {
            foreach ($scripts as $handle => $script) {
                $src = html_entity_decode($script['src']);
                $fileId = 0;
                if (! $script['external']) {
                    $path = self::get_path_by_url($script['src'], $home);
                    $fileId = @filemtime($path);
                }
				else {
					$fileId = $script['ver'];
				}
                if (empty($fileId)) 
                    $fileId = 'nover';
                
                $cache_name = md5(md5($handle).$fileId);
                $cache_file_path = self::get_full_path(self::$options['cache-dir-path']) . $cache_name . '.css';
                $cache_file_url = self::$options['cache-dir-url'] . $cache_name . '.css';

                // Find a cache
                if (self::get_cache($cache_name, $cache_file_path, 'css')) {
                
                    // Include script 
                    self::print_css_link_tag($cache_file_url, $media, $conditional, true);
                    
                    continue;
                }

                // Get script contents
                $_remote_get = wp_remote_get(self::add_url_param($src, 'v', rand(1, 9999999)), 
                    array(
                        'timeout' => self::$options['http-request-timeout']
                    )
                );

                if (! is_wp_error($_remote_get) && $_remote_get['response']['code'] == 200) {
                    $scripts_text = $_remote_get['body'];
                    if (self::$options['packing-css']) {
                        $scripts_text = self::compress_css($scripts_text, $src);
                    }

                    $scripts_text = "/* $handle: ($src) */\n" . $scripts_text;

                    // Save cache                   
                    self::save_cache($cache_file_path, $scripts_text, $cache_name, $fileId, 'css');
                    
                    // Include script 
                    self::print_css_link_tag($cache_file_url, $media, $conditional, false);
                }
                else {
                    // Include script 
                    self::print_css_link_tag($src, $media, $conditional, true);      
                    if (! is_wp_error($_remote_get)) 
                        echo "<!-- Error loading script content: $src; HTTP Code: {$_remote_get['response']['code']} ({$_remote_get['response']['message']}) -->\n";
                    else
                        echo "<!-- Error loading script content: $src -->\n";
                }
            }
        }
        
        return true;
    }
	
    static function add_url_param($url, $name, $val) {
        if (strpos($url, '?') === false)
			return $url."?$name=$val";
			
		return $url."&$name=$val";
    }

    static function save_script($filename, $content) {
        if (is_writable(self::get_full_path(self::$options['cache-dir-path']))) {
            $fhandle = @fopen($filename, 'w+');
            if ($fhandle) fwrite($fhandle, $content, strlen($content));
        }
        return false;
    }

    static function head() {
        /*if (self::$options['combine-js'] == 'combine') {
            self::print_scripts();
        }
		if (self::$options['combine-css']) {
			self::print_styles();
		}
		*/
		if (self::$options['strict-ordering-beta']) {
            self::ordering_stop();
        }
    }

    static function footer() {
        if (self::$options['combine-js']) {
            self::$js_printed = true;
            self::print_scripts();
        }

        if (self::$options['combine-css']) {
            self::$css_printed = true;
            self::print_styles();
        }
    }
	
    static function ordering_start() {
		self::$ordering_started = true;
		ob_start();		
	}	
	
    static function ordering_stop() {
		if (! self::$ordering_started) return;
		self::$ordering_started = false;		
		$html = ob_get_contents();
		ob_end_clean();

		$html = self::order_scripts($html);
		echo $html;
	}
	
	static function order_scripts($html){
		$count = preg_match_all('/<!--\[if[^<]*<script.*>.*<\/script>.*if\]-->/imxsU', $html, $matches);
		$if_scripts = '';
		if ($count) {
			foreach ($matches[0] as $_script) {
				$if_scripts .= $_script."\n";
				$html = str_replace($_script, '', $html);
			}
		}
		
		$count = preg_match_all('/<script.*>(.*)<\/script>/imxsU', $html, $matches);	
		$scripts = '';
		if ($count) {
			for ($i = 0; $i < $count; $i++) {
				$script = $matches[0][$i];
				$content = trim($matches[1][$i]);
				if (empty($content) || $content == '/*Cache!*/') 
					continue; 
				
				$scripts .= $script."\n";
				$html = str_replace($script, '', $html);
			}
		}
		
		$html .= "\n".$scripts."\n";
		$html = str_replace('</title>', "</title>\n".$if_scripts, $html);

		return $html;
	}
}

add_action('init', array('evScriptOptimizer', 'init'));

/*
function wp_print_styles( $handles = false ) {
	if ( '' === $handles ) // for wp_head
		$handles = false;

	if ( ! $handles )  // <-------------------------------  TODO: Write to WP developers
		do_action( 'wp_print_styles' );

	...
}
*/