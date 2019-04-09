<?php
/**
 * Plugin Name: Merge + Minify + Refresh
 * Plugin URI: https://wordpress.org/plugins/merge-minify-refresh
 * Description: Merge/Concatenate & Minify CSS & JS.
 * Version: 1.8.12
 * Author: Launch Interactive
 * Author URI: http://launchinteractive.com.au
 * License: GPL2

Copyright 2015  Marc Castles  (email : marc@launchinteractive.com.au)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class MergeMinifyRefresh {
	
	private $host = '';
	private $root = '';
	private $refreshed = false;
	
	private $mergecss = true;
	private $mergejs = true;
	private $cssmin = true;
	private $jsmin = true;
	private $http2push = false;
	private $outputbuffering = false;
	private $gzip = false;
	private $ignore = array();
	
	private $wordpressdir = '';
	
	private $scriptcount = 0;

	private $rootRelativeWPContentDir = '';

	public function __construct() {
		
		/*
		Valid Configs:
		
		MMR_CACHE_DIR + MMR_CACHE_URL
		MMR_CACHE_DIR + MMR_JS_CACHE_URL + MMR_CSS_CACHE_URL
		MMR_CACHE_DIR + MMR_CACHE_URL + MMR_JS_CACHE_URL + MMR_CSS_CACHE_URL // MMR_CACHE_URL becomes unnecessary
		MMR_CACHE_DIR + MMR_CACHE_URL + MMR_JS_CACHE_URL
		MMR_CACHE_DIR + MMR_CACHE_URL + MMR_CSS_CACHE_URL
		MMR_CACHE_URL
		MMR_JS_CACHE_URL + MMR_CSS_CACHE_URL
		MMR_CACHE_URL + MMR_JS_CACHE_URL + MMR_CSS_CACHE_URL // MMR_CACHE_URL becomes unnecessary
		MMR_CACHE_URL + MMR_JS_CACHE_URL
		MMR_CACHE_URL + MMR_CSS_CACHE_URL
		MMR_CSS_CACHE_URL
		MMR_JS_CACHE_URL	
		*/
		
		if(!defined('MMR_CACHE_DIR')) {
			define('MMR_CACHE_DIR', WP_CONTENT_DIR.'/mmr');
			
			if(!defined('MMR_CACHE_URL')) {
				define('MMR_CACHE_URL', WP_CONTENT_URL.'/mmr');
			}
		} else if(WP_DEBUG && !defined('MMR_CACHE_URL') && (!defined('MMR_JS_CACHE_URL') || !defined('MMR_CSS_CACHE_URL'))) {
			wp_die("You must specify MMR_CACHE_URL or MMR_JS_CACHE_URL & MMR_CSS_CACHE_URL");
		}
		
		if(!defined('MMR_JS_CACHE_URL')) {
			define('MMR_JS_CACHE_URL', MMR_CACHE_URL);
		}
		if(!defined('MMR_CSS_CACHE_URL')) {
			define('MMR_CSS_CACHE_URL', MMR_CACHE_URL);
		}

		if(!is_dir(MMR_CACHE_DIR)) {
			mkdir(MMR_CACHE_DIR);
		}
	
		/* Calculate Root Relative path to WP Content */
		if(defined('WP_CONTENT_URL')) {
			$this->rootRelativeWPContentDir = parse_url(WP_CONTENT_URL,PHP_URL_PATH);
		} else {
			$this->rootRelativeWPContentDir = str_replace($_SERVER['DOCUMENT_ROOT'],'', WP_CONTENT_DIR);
		}

		$this->min = defined('PHP_INT_MIN') ? PHP_INT_MIN : -9223372036854775808;
		$this->max = defined('PHP_INT_MAX') ? PHP_INT_MAX : 9223372036854775807;

		$this->root = $_SERVER["DOCUMENT_ROOT"];
		$this->wordpressdir = rtrim(parse_url(network_site_url(), PHP_URL_PATH),'/');

		if(is_admin()) {
		
			add_action( 'admin_menu', array($this,'admin_menu') );
		
			add_action( 'admin_enqueue_scripts', array($this,'load_admin_jscss') );
		
			add_action( 'wp_ajax_mmr_files', array($this,'mmr_files_callback') );
		
			add_action( 'admin_init', array($this,'mmr_register_settings') );
		
			register_deactivation_hook( __FILE__, array($this, 'plugin_deactivate') );
		
		} else if($this->should_mmr()) {

			$this->host = $_SERVER['HTTP_HOST'];
			//php < 5.4.7 returns null if host without scheme entered
			if(mb_substr($this->host, 0, 4) !== 'http') $this->host = 'http'.(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 's' : '').'://' . $this->host;
			$this->host = parse_url( $this->host, PHP_URL_HOST );
		
			$this->mergecss = !get_option('mmr-nomergecss');
			$this->mergejs = !get_option('mmr-nomergejs');
			$this->cssmin = !get_option('mmr-nocssmin');
			$this->jsmin = !get_option('mmr-nojsmin');
			$this->http2push = get_option('mmr-http2push');
			$this->outputbuffering = get_option('mmr-outputbuffering');
			$this->gzip = get_option('mmr-gzip');
			$this->ignore = array_map('trim',explode(PHP_EOL,get_option('mmr-ignore')));
		
			add_action( 'compress_css',array($this,'compress_css_action'), 10, 1 );
				add_action( 'compress_js', array($this,'compress_js_action'), 10, 1 );
		
			if($this->outputbuffering) {
				add_action( 'init', array($this,'start_buffer'), $this->min );
			}
		
			add_action( 'wp_print_scripts', array($this,'inspect_scripts'), $this->max );
			add_action( 'wp_print_styles', array($this,'inspect_styles'), $this->max );
		
			add_filter( 'style_loader_src', array($this,'remove_cssjs_ver'), 10, 2 );
			add_filter( 'script_loader_src', array($this,'remove_cssjs_ver'), 10, 2 );

			add_action( 'wp_print_footer_scripts', array($this,'inspect_stylescripts_footer'), 9.999999 ); //10 = Internal WordPress Output
			
			add_action('shutdown', array($this, 'refreshed'), 10);
		}
	}
	
	private function should_mmr() {
		if ( class_exists( 'Vc_Manager' ) && isset($_GET['vc_editable'] )) { // disable mrr when visual composer in use and in edit mode
			return false;
		}
		return true;
	}
	

	public function mmr_files_callback() {

		if(isset($_POST['purge']) && $_POST['purge'] == 'all') {
			$this->clear_scheduled();
			$this->rrmdir(MMR_CACHE_DIR); 
		} else if(isset($_POST['purge'])) {
			$this->clear_scheduled($_POST['purge']);
			array_map('unlink', glob(MMR_CACHE_DIR.'/'.$_POST['purge'].'*'));
		}

		$return = array('js'=>array(),'css'=>array(),'stamp'=>$_POST['stamp']);

		$files = glob(MMR_CACHE_DIR.'/*.log', GLOB_BRACE);

		if(count($files) > 0) {
			
			foreach($files as $file) {
					
				$script_path = substr($file, 0, -4); 
				
				$ext = pathinfo($script_path, PATHINFO_EXTENSION);
				
				$scheduled = false;

				if(wp_next_scheduled( 'compress_'.$ext, array($script_path) ) !== false) {
					$scheduled = true;
				}

				$log = file_get_contents($file);
				
				$error = false;
				if(strpos($log,'COMPRESSION FAILED') !== false) {
					$error = true;
				}

				$filename = basename($script_path);
				
				switch($ext) {
					case 'css':
						$minpath = substr($script_path,0,-4).'.min.css';
					break;
					case 'js':
						$minpath = substr($script_path,0,-3).'.min.js';
					break;
				}
				
				if(file_exists($minpath)) {
					$filename = basename($minpath);
				}
				
				$hash = substr($filename,0,strpos($filename,'-'));
				$accessed = 'Unknown';
				if( file_exists($script_path.'.accessed'))
				{
					$accessed = file_get_contents($script_path.'.accessed');
					if(strtotime('today') <= $accessed) {
						$accessed = 'Today';
					} else if(strtotime('yesterday') <= $accessed) {
						$accessed = 'Yesterday';
					} else if(strtotime('this week') <= $accessed) {
						$accessed = 'This Week';
					} else if(strtotime('this month') <= $accessed) {
						$accessed = 'This Month';
					} else {
						$accessed = date(get_option('date_format'), $accessed);
					}
				}
				
				array_push($return[$ext], array('hash'=>$hash,'filename'=>$filename,'scheduled'=>$scheduled,'log'=>$log, 'error'=>$error, 'accessed'=>$accessed) );							
			}
		}

		header('Content-Type: application/json');
		echo json_encode($return);

		wp_die(); // this is required to terminate immediately and return a proper response
	}
	
	private function clear_scheduled($hash = null) {
		
		if($hash != null) {
			$files = glob(MMR_CACHE_DIR.'/'.$hash.'*.log', GLOB_BRACE);
		} else {
			$files = glob(MMR_CACHE_DIR.'/*.log', GLOB_BRACE);
		}

		if(count($files) > 0) {
			foreach($files as $file) {
				$script_path = substr($file, 0, -4); 
				$ext = pathinfo($script_path, PATHINFO_EXTENSION);
				wp_clear_scheduled_hook( 'compress_'.$ext, array($script_path) );
			}
		}
		
	}

	public function plugin_deactivate() {	
		$this->clear_scheduled();
		if(is_dir(MMR_CACHE_DIR)) {
			$this->rrmdir(MMR_CACHE_DIR); 
		}
	}

	private function rrmdir($dir) { 
		foreach(glob($dir.'/{,.}*', GLOB_BRACE) as $file) { 
			if(basename($file) != '.' && basename($file) != '..') {
				if(is_dir($file)) $this->rrmdir($file); else unlink($file); 
			}
		}
		rmdir($dir); 
	}

	public function load_admin_jscss($hook) {
		if ( 'settings_page_merge-minify-refresh' != $hook ) {
			return;
		}
		wp_enqueue_style( 'merge-minify-refresh', plugins_url('admin.css', __FILE__) );
		wp_enqueue_script( 'merge-minify-refresh', plugins_url('admin.js', __FILE__), array(), false, true );
	}

	public function admin_menu() {
		add_options_page( 'Merge + Minify + Refresh Settings', 'Merge + Minify + Refresh', 'manage_options', 'merge-minify-refresh', array($this,'merge_minify_refresh_settings') );
	}

	public function mmr_register_settings() {
		register_setting( 'mmr-group', 'mmr-nomergecss' );
		register_setting( 'mmr-group', 'mmr-nomergejs' );
		register_setting( 'mmr-group', 'mmr-nocssmin' );
		register_setting( 'mmr-group', 'mmr-nojsmin' );
		register_setting( 'mmr-group', 'mmr-http2push' );
		register_setting( 'mmr-group', 'mmr-outputbuffering' );
		register_setting( 'mmr-group', 'mmr-gzip' );
		register_setting( 'mmr-group', 'mmr-ignore' );
	}
	
	public function merge_minify_refresh_settings() {
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		//echo '<pre>';var_dump(_get_cron_array()); echo '</pre>';

		$files = glob(MMR_CACHE_DIR.'/*.{js,css}', GLOB_BRACE);
		
		echo '<div id="merge-minify-refresh">
				<h2>Merge + Minify + Refresh Settings</h2>
				<p>When a CSS or JS file is modified MMR will automatically re-process the files. However, when a dependancy changes these files may become stale.</p>
		
				<div id="mmr_processed">
					<a href="#" class="button button-secondary purgeall">Purge All</a>
				
					<div id="mmr_jsprocessed">
						<h4>The following Javascript files have been processed:</h4>
						<ul class="processed"></ul>
					</div>
				
					<div id="mmr_cssprocessed">
						<h4>The following CSS files have been processed:</h4>
						<ul class="processed"></ul>
					</div>
				</div>
			
				<p id="mmr_noprocessed"><strong>No files have been processed</strong></p>
			
			</div>
		';

		echo '<form method="post" id="mmr_options" action="options.php">';
		settings_fields( 'mmr-group' ); 
		do_settings_sections( 'mmr-group' ); 
		echo '<p><label><input type="checkbox" name="mmr-nomergecss" value="1" '.checked( 1 == get_option('mmr-nomergecss') , true, false).'/> Don\'t Merge CSS</label>';
		echo '<label><input type="checkbox" name="mmr-nomergejs" value="1" '.checked( 1 == get_option('mmr-nomergejs') , true, false).'/> Don\'t Merge JS</label>';
		echo '<br/><em>Note: Selecting these will increase requests but may be required for some themes. e.g. Themes using @import</em></p>';

		echo '<p><label><input type="checkbox" name="mmr-nocssmin" value="1" '.checked( 1 == get_option('mmr-nocssmin') , true, false).'/> Disable CSS Minification</label>';

		echo '<label><input type="checkbox" name="mmr-nojsmin" value="1" '.checked( 1 == get_option('mmr-nojsmin') , true, false).'/> Disable JS Minification</label>';
		echo '<br/><em>Note: Disabling CSS/JS minification may require a "Purge All" to take effect.</em></p>';

		echo '<p><label><input type="checkbox" name="mmr-http2push" value="1" '.checked( 1 == get_option('mmr-http2push') , true, false).'/> Enable HTTP2 Server Push</label>';
		echo '<br/><em>Enables the server to send multiple responses (in parallel) for a single client request.</em></p>';

		echo '<p><label><input type="checkbox" name="mmr-outputbuffering" value="1" '.checked( 1 == get_option('mmr-outputbuffering') , true, false).'/> Enable Output Buffering</label>';
		echo '<br/><em>Output buffering may be required for compatibility with some plugins. If its disabled only the header requests will be be sent for HTTP2 Server Push.</em></p>';
		
		echo '<p><label><input type="checkbox" name="mmr-gzip" value="1" '.checked( 1 == get_option('mmr-gzip') , true, false).'/> Enable Gzip Encoding</label>';
		echo '<br/><em>Checking this option will generate additional .css.gz and .js.gz files. Your webserver may need to be configured to use these files.</em></p>';

		echo '<p><label class="textlabel">Ignore these files (one per line):<textarea name="mmr-ignore" placeholder="file paths (view logs to get paths)">'.get_option('mmr-ignore').'</textarea></label></p>';

		echo '<p><button type="submit" class="button">SAVE</button></p></form>';

	}

	public function remove_cssjs_ver( $src ) {
		if( strpos( $src, '?ver=' ) )
			$src = remove_query_arg( 'ver', $src );
		return $src;
	}

	public function http2push_reseource( $url, $type = '' ) {
		if( !$this->http2push || headers_sent() ) {
			return FALSE;
		}

		$http_link_header = array( "Link: <{$url}>; rel=preload" );

		if ( $type != '' ) {
			$http_link_header[] = "as={$type}";
		}

		if( isset($_SERVER['HTTP_REFERER']) &&
		    stristr($_SERVER['HTTP_REFERER'], get_site_url()) !== FALSE ) {
			$http_link_header[] = 'nopush';
		}
		
		header( implode('; ', $http_link_header), false);

	}

	private function host_match( $url ) {
		if( empty($url) ) {
			return false;
		}

		$url = $this->ensure_scheme($url);

		$url_host = parse_url( $url, PHP_URL_HOST );
		
		if(  !$url_host || $url_host == $this->host ) {
			return true;
		} else {
			return false;
		}
	}
	
	//php < 5.4.7 parse_url returns null if host without scheme entered
	private function ensure_scheme($url) {
		return preg_replace("/(http(s)?:\/\/|\/\/)(.*)/i", "http$2://$3", $url);
	}
	
	private function remove_scheme($url) {
		return preg_replace("/(http(s)?:\/\/|\/\/)(.*)/i", "//$3", $url);
	}

	public function start_buffer() {
		ob_start();
	}
	
	private function fix_wp_subfolder($file_path) {
		if(!is_main_site() && defined('SUBDOMAIN_INSTALL') && !SUBDOMAIN_INSTALL) { //WordPress site is within a subfolder
			$details = get_blog_details();
			$file_path = preg_replace('|^'.$details->path.'|', '/', $file_path);
		}	
		/* WordPress includes files relative to its core. This fixes paths when WordPress isn't in the document root. */
		if(
			$this->wordpressdir != '' && //WordPress core is within a subfolder
			substr($file_path, 0, strlen($this->wordpressdir) + 1) != $this->wordpressdir . '/' && //File is not in WordPress core directory
			substr($file_path, 0, strlen($this->rootRelativeWPContentDir) + 1) != $this->rootRelativeWPContentDir . '/' //File is not in the wp-content directory
		) {
			$file_path = $this->wordpressdir . $file_path;
		}
		return $file_path;
	}
	
	public function inspect_styles() {

		wp_styles(); //ensure styles is initialised

		global $wp_styles;	
		$this->process_scripts($wp_styles, 'css');	
	}

	public function inspect_scripts() {

		wp_scripts(); //ensure scripts is initialised

		global $wp_scripts;
		$this->process_scripts($wp_scripts, 'js');
	}
	
	public function inspect_stylescripts_footer() {

		global $wp_scripts;
		$this->process_scripts($wp_scripts, 'js', true);

		global $wp_styles;	
		$this->process_scripts($wp_styles, 'css', true);

		if($this->outputbuffering) {
			ob_end_flush();
		}
	}

	/**
	 * process_scripts function.
	 * 
	 * @access public
	 * @param mixed &$script_list - copy of the global wp list
	 * @param mixed $ext - type of script to check 'css' or 'js' 
	 * @param bool $in_footer (default: false)
	 * @return void
	 */
	public function process_scripts(&$script_list, $ext, $in_footer = false) {

		if($script_list) {
			
			$script_line_end = "\n";
			if($ext == 'js') {
				$script_line_end = ";\n";
			}

			$scripts = clone $script_list;

			$scripts->all_deps($scripts->queue);
			
			$handles = $this->get_handles($ext, $scripts, !$in_footer);
			
			$done = $scripts->done;

			//loop through header scripts and merge + schedule wpcron
			for($i=0,$l=count($handles);$i<$l;$i++) {

				if(!isset($handles[$i]['handle'])) {
					
					$done = array_merge($done, $handles[$i]['handles']);

					$hash = hash('adler32', get_home_url() . implode('', $handles[$i]['handles'])); //get_home_url() prevents multisite hash collisions			
				
					$file_path = '/'.$hash.'-'.$handles[$i]['modified'].'.' . $ext;
				
					$full_path = MMR_CACHE_DIR.$file_path;
				
					$min_path = '/'.$hash.'-'.$handles[$i]['modified'].'.min.' . $ext;
				
					$min_exists = file_exists(MMR_CACHE_DIR.$min_path);

					if(!file_exists($full_path) && !$min_exists) {

						$output = '';
						$log = "";
						$should_minify = true;
					
						foreach( $handles[$i]['handles'] as $handle ) {
					
							$log .= " - ".$handle." - ".$scripts->registered[$handle]->src;

							$script_path = parse_url($this->ensure_scheme($scripts->registered[$handle]->src), PHP_URL_PATH);
							$script_path = $this->fix_wp_subfolder($script_path);
						
							if(substr($script_path, -7) == '.min.' . $ext) {
								if(count($handles[$i]['handles']) > 1) { //multiple files default to not minified
									$nomin_path = substr($script_path, 0, -7) . '.' . $ext; 
									if(is_file($this->root.$nomin_path)) {
										$script_path = $nomin_path;
										$log .= " - unminified version used";
									}
								} else {						
									$should_minify = false; // single file is already minified
								}
							}
						
							$contents = '';
							
							if($ext == 'js' && isset($scripts->registered[$handle]->extra['before']) && count($scripts->registered[$handle]->extra['before']) > 0) {
								$contents .= implode($script_line_end,$scripts->registered[$handle]->extra['before']) . $script_line_end;
							}

							// Remove the BOM
							$contents .= preg_replace("/^\xEF\xBB\xBF/", '', file_get_contents($this->root.$script_path)) . $script_line_end;
							
							if(isset($scripts->registered[$handle]->extra['after']) && count($scripts->registered[$handle]->extra['after']) > 0) {
								$contents .= implode($script_line_end,$scripts->registered[$handle]->extra['after']) . $script_line_end;
							}
							
							if($ext == 'css') { 
								//convert relative paths to absolute & ignore data: or absolute paths (starts with /)
								$contents = preg_replace("/url\(\s*['\"]?(?!data:)(?!http)(?![\/'\"])(.+?)['\"]?\s*\)/i", "url(".dirname($script_path)."/$1)", $contents);
							}
							
							$output .= $contents; 

							$log .= "\n";
	
						}

						//remove existing expired files
						array_map('unlink', glob(MMR_CACHE_DIR.'/'.$hash.'-*.' . $ext));
					
						if($should_minify) {
					
							file_put_contents($full_path , $output);
							if(count($handles[$i]['handles']) > 1) {
								file_put_contents($full_path.'.log', date('c')." - MERGED:\n".$log);
							} else {
								file_put_contents($full_path.'.log', date('c')."\n".$log);
							}
	
							wp_clear_scheduled_hook('compress_'. $ext, array($full_path) );
							if($this->{$ext . 'min'}) {
								wp_schedule_single_event( time(), 'compress_' . $ext, array($full_path) );
							}
						} else {
							file_put_contents(substr($full_path, 0, -2) . 'min.' . $ext , $output);
							file_put_contents($full_path.'.log', date('c')." - ORIGINAL FILE USED:\n".$log);
							$min_exists = true;
						}
					} else {
						file_put_contents($full_path.'.accessed', current_time('timestamp'));
					}
	
	
					if($ext == 'js') {
						
						$data = '';
						
						foreach( $handles[$i]['handles'] as $handle ) {	
							if(isset($scripts->registered[$handle]->extra['data'])) {
								$data .= $scripts->registered[$handle]->extra['data'];
							}
						}
	
						if($min_exists) {
							$this->http2push_reseource(MMR_JS_CACHE_URL.$min_path, 'script');
							wp_register_script('js-'.$this->scriptcount, MMR_JS_CACHE_URL.$min_path, array(), false, $in_footer);
						} else {
							$this->http2push_reseource(MMR_JS_CACHE_URL.$file_path, 'script');
							wp_register_script('js-'.$this->scriptcount, MMR_JS_CACHE_URL.$file_path, array(), false, $in_footer);
						}
	
						//set any existing data that was added with wp_localize_script
						if($data != '') {							
							$script_list->registered['js-'.$this->scriptcount]->extra['data'] = $data;
						}
				
						wp_enqueue_script('js-'.$this->scriptcount);
					
					} else {
						
						if($min_exists) {
							$this->http2push_reseource(MMR_CSS_CACHE_URL.$min_path, 'style');
							wp_register_style('css-'.$this->scriptcount, MMR_CSS_CACHE_URL.$min_path,false,false,$handles[$i]['media']);
						} else {
							$this->http2push_reseource(MMR_CSS_CACHE_URL.$file_path, 'style');
							wp_register_style('css-'.$this->scriptcount, MMR_CSS_CACHE_URL.$file_path,false,false,$handles[$i]['media']);
						}
					
						wp_enqueue_style('css-'.$this->scriptcount);
					}

					$this->scriptcount++;
				
				} else { //external
					if($ext == 'js') {
						wp_dequeue_script($handles[$i]['handle']); //need to do this so the order of scripts is retained
						wp_enqueue_script($handles[$i]['handle']);
					} else {
						wp_dequeue_style($handles[$i]['handle']); //need to do this so the order of scripts is retained
						wp_enqueue_style($handles[$i]['handle']);
					}
				}
			}
			$script_list->done = $done;
		}
	}	
	
	/**
	 * get_handles function.
	 * 	Returns a list of the handles in $ourList in the order and grouping that mmr will need to merge them
	 * @access private
	 * @param mixed $type - type of script to check 'css' or 'js'
	 * @param mixed &$ourList - copy of the global wp list
	 * @param bool $ignoreFooterScripts (default: false) - whether to ignore scripts marked for the footer
	 * @return array() - MMR script handles list
	 */
	private function get_handles($type, &$ourList, $ignoreFooterScripts = false)
	{
		switch($type)
		{
			case 'js':
				$ext = 'js';
				$dontMerge = !$this->mergejs;
				$srcFilter = 'script_loader_src';
				$checkMedia = false;
				break;
				
			case 'css':
				$ext = 'css';
				$dontMerge = !$this->mergecss;
				$srcFilter = 'style_loader_src';
				$checkMedia = true;
				break;
				
			default: 
				return array();
		}
		
		$handles = array();
		$currentHandle = -1;
		foreach( $ourList->to_do as $handle ) {
			if(apply_filters( $srcFilter, $ourList->registered[$handle]->src, $handle) !== false) { //is valid src

				if($ignoreFooterScripts)
				{
					$is_footer = isset($ourList->registered[$handle]->extra['group']);
					if($is_footer)
					{
						//ignore this script, so go on to the next one
						continue;
					}
				}
				$script_path = parse_url($this->ensure_scheme($ourList->registered[$handle]->src), PHP_URL_PATH);
				$script_path = $this->fix_wp_subfolder($script_path);

				$extension = pathinfo($script_path, PATHINFO_EXTENSION);

				if($extension == $ext && $this->host_match($ourList->registered[$handle]->src) && !in_array($ourList->registered[$handle]->src, $this->ignore) && !isset($ourList->registered[$handle]->extra["conditional"])) { //is a local script
	
					$mediaMatches = true;
					if($checkMedia)
					{
						$media = isset($ourList->registered[$handle]->args) ? $ourList->registered[$handle]->args : 'all';
						$mediaMatches = $currentHandle != -1 && isset($handles[$currentHandle]['media']) && $handles[$currentHandle]['media'] == $media;
					}
					
					if($dontMerge || $currentHandle == -1 || isset($handles[$currentHandle]['handle']) || !$mediaMatches) {
						if($checkMedia)
						{
							array_push($handles, array('modified'=>0,'handles'=>array(),'media'=>$media));
						}
						else
						{
							array_push($handles, array('modified'=>0,'handles'=>array()));
						}
						$currentHandle++;
					}

					$modified = 0;
					
					if(is_file($this->root.$script_path)) {
						$modified = filemtime($this->root.$script_path);
					}

					array_push($handles[$currentHandle]['handles'], $handle);

					if($modified > $handles[$currentHandle]['modified']) {
						$handles[$currentHandle]['modified'] = $modified;
					}
				} else { //external script or not able to be processed
					array_push($handles, array('handle'=>$handle));
					$currentHandle++;
				}
			}
		}
		
		return $handles;
	}
	
	public function compress_css_action($full_path) {

		if(is_file($full_path)) {
			
			$this->refreshed = true;

			require_once('Minify/src/Minify.php');
			require_once('Minify/src/CSS.php');
			require_once('Minify/ConverterInterface.php');
			require_once('Minify/Converter.php');
			require_once('Minify/src/Exception.php');

			file_put_contents($full_path.'.log', date('c')." - COMPRESSING CSS\n",FILE_APPEND);

			$file_size_before = filesize($full_path);

			$minifier = new MatthiasMullie\Minify\CSS($full_path);

			$min_path = str_replace('.css','.min.css',$full_path);

			$minifier->minify($min_path);
			if($this->gzip_file($min_path)) {
				file_put_contents($full_path.'.log', date('c')." - GZIPPED - $min_path.gz\n",FILE_APPEND);
			}

			$file_size_after = filesize($min_path);

			file_put_contents($full_path.'.log', date('c')." - COMPRESSION COMPLETE - ".$this->human_filesize($file_size_before-$file_size_after)." saved\n",FILE_APPEND);
		}
	}
	
	public function compress_js_action($full_path) {

		if(is_file($full_path)) {
			
			$this->refreshed = true;

			$file_size_before = filesize($full_path);

			if(function_exists('exec') && exec('command -v java >/dev/null && echo "yes" || echo "no"') == 'yes' && exec('java -version 2>&1',$jvoutput) && preg_match("/version\ \"(1\.[7-9]{1}+|[7-9]|[0-9]{2,})/", $jvoutput[0])) {

				file_put_contents($full_path.'.log', date('c')." - COMPRESSING JS WITH CLOSURE\n",FILE_APPEND);

				$cmd = 'java -jar \''.WP_PLUGIN_DIR.'/merge-minify-refresh/closure-compiler.jar\' --warning_level QUIET --js \''.$full_path.'\' --js_output_file \''.$full_path.'.tmp\'';

				exec($cmd . ' 2>&1', $output);

				if(count($output) == 0) {
					$min_path = str_replace('.js','.min.js',$full_path);
					rename($full_path.'.tmp',$min_path);
					if($this->gzip_file($min_path)) {
						file_put_contents($full_path.'.log', date('c')." - GZIPPED - $min_path.gz\n",FILE_APPEND);
					}
					$file_size_after = filesize($min_path);
					file_put_contents($full_path.'.log', date('c')." - COMPRESSION COMPLETE - ".$this->human_filesize($file_size_before-$file_size_after)." saved\n",FILE_APPEND);
				} else {

					ob_start();
					var_dump($output);
					$error=ob_get_contents();
					ob_end_clean();

					file_put_contents($full_path.'.log', date('c')." - COMPRESSION FAILED\n".$error,FILE_APPEND);
					unlink($full_path.'.tmp');
				}
			} else {
				
				require_once('Minify/src/Minify.php');
				require_once('Minify/src/JS.php');
				
				file_put_contents($full_path.'.log', date('c')." - COMPRESSING WITH MINIFY (PHP exec not available or java not found)\n",FILE_APPEND);
				
				$minifier = new MatthiasMullie\Minify\JS($full_path);
			
				$min_path = str_replace('.js','.min.js',$full_path);
				
				$minifier->minify($min_path);
				if($this->gzip_file($min_path)) {
					file_put_contents($full_path.'.log', date('c')." - GZIPPED - $min_path.gz\n",FILE_APPEND);
				}
				
				$file_size_after = filesize($min_path);
				
				file_put_contents($full_path.'.log', date('c')." - COMPRESSION COMPLETE - ".$this->human_filesize($file_size_before-$file_size_after)." saved\n",FILE_APPEND);
			}
		}
	}
	
	//thanks to http://php.net/manual/en/function.filesize.php#106569
	private function human_filesize($bytes, $decimals = 2) {
		$sz = 'BKMGTP';
		$factor = floor((strlen($bytes) - 1) / 3);
		return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
	}
	
	//thanks to Marcus Svensson
	private function gzip_file($path) {
		$gzipped = false;
		if ($this->gzip && function_exists('exec') && exec('command -V gzip >/dev/null && echo "yes" || echo "no"') == 'yes') {
			exec("gzip -9 < '$path' > '$path.gz'", $output, $return);
			if($return == 0) {//gzip worked
				$gzipped = true; 
			}
		}
		return $gzipped;
	}
	
	/* thanks to @lucasbustamante */
	public function refreshed() {
		if ($this->refreshed === true) { 
			do_action('merge_minify_refresh_done'); 
		} 
	} 
}
 
$mergeminifyrefresh = new MergeMinifyRefresh();