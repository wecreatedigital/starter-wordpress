<?php
/*
Plugin Name: Minify HTML
Plugin URI: https://wordpress.org/plugins/minify-html-markup/
Description: Minify your HTML for faster downloading and cleaning up sloppy looking markup.
Version: 1.98
Author: Tim Eckel
Author URI: https://www.dogblocker.com
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Text Domain:minify-html
*/

/*
	Copyright 2017  Tim Eckel  (email : tim@leethost.com)

	Minify HTML is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 3 of the License, or
	any later version.

	Minify HTML is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with Minify HTML; if not, see https://www.gnu.org/licenses/gpl-3.0.html
*/

if ( !defined( 'ABSPATH' ) ) exit;

function teckel_init_minify_html() {
	$minify_html_active = get_option( 'minify_html_active' );
	if ( $minify_html_active != 'no' )
		ob_start('teckel_minify_html_output');
}
if ( !is_admin() )
	if ( !( defined( 'WP_CLI' ) && WP_CLI ) )
		add_action( 'init', 'teckel_init_minify_html', 1 );

function teckel_minify_html_output($buffer) {
	if ( substr( ltrim( $buffer ), 0, 5) == '<?xml' )
		return ( $buffer );
	$minify_javascript = get_option( 'minify_javascript' );
	$minify_html_comments = get_option( 'minify_html_comments' );
	$minify_html_utf8 = get_option( 'minify_html_utf8' );
	if ( $minify_html_utf8 == 'yes' && mb_detect_encoding($buffer, 'UTF-8', true) )
		$mod = '/u';
	else
		$mod = '/s';
	$buffer = str_replace(array (chr(13) . chr(10), chr(9)), array (chr(10), ''), $buffer);
	$buffer = str_ireplace(array ('<script', '/script>', '<pre', '/pre>', '<textarea', '/textarea>', '<style', '/style>'), array ('M1N1FY-ST4RT<script', '/script>M1N1FY-3ND', 'M1N1FY-ST4RT<pre', '/pre>M1N1FY-3ND', 'M1N1FY-ST4RT<textarea', '/textarea>M1N1FY-3ND', 'M1N1FY-ST4RT<style', '/style>M1N1FY-3ND'), $buffer);
	$split = explode('M1N1FY-3ND', $buffer);
	$buffer = ''; 
	for ($i=0; $i<count($split); $i++) {
		$ii = strpos($split[$i], 'M1N1FY-ST4RT');
		if ($ii !== false) {
			$process = substr($split[$i], 0, $ii);
			$asis = substr($split[$i], $ii + 12);
			if (substr($asis, 0, 7) == '<script') {
				$split2 = explode(chr(10), $asis);
				$asis = '';
				for ($iii = 0; $iii < count($split2); $iii ++) {
					if ($split2[$iii])
						$asis .= trim($split2[$iii]) . chr(10);
					if ( $minify_javascript != 'no' )
						if (strpos($split2[$iii], '//') !== false && substr(trim($split2[$iii]), -1) == ';' )
							$asis .= chr(10);
				}
				if ($asis)
					$asis = substr($asis, 0, -1);
				if ( $minify_html_comments != 'no' )
					$asis = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $asis);
				if ( $minify_javascript != 'no' )
					$asis = str_replace(array (';' . chr(10), '>' . chr(10), '{' . chr(10), '}' . chr(10), ',' . chr(10)), array(';', '>', '{', '}', ','), $asis);
			} else if (substr($asis, 0, 6) == '<style') {
				$asis = preg_replace(array ('/\>[^\S ]+' . $mod, '/[^\S ]+\<' . $mod, '/(\s)+' . $mod), array('>', '<', '\\1'), $asis);
				if ( $minify_html_comments != 'no' )
					$asis = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $asis);
				$asis = str_replace(array (chr(10), ' {', '{ ', ' }', '} ', '( ', ' )', ' :', ': ', ' ;', '; ', ' ,', ', ', ';}'), array('', '{', '{', '}', '}', '(', ')', ':', ':', ';', ';', ',', ',', '}'), $asis);
			}
		} else {
			$process = $split[$i];
			$asis = '';
		}
		$process = preg_replace(array ('/\>[^\S ]+' . $mod, '/[^\S ]+\<' . $mod, '/(\s)+' . $mod), array('>', '<', '\\1'), $process);
		if ( $minify_html_comments != 'no' )
			$process = preg_replace('/<!--(?!\s*(?:\[if [^\]]+]|<!|>))(?:(?!-->).)*-->' . $mod, '', $process);
		$buffer .= $process.$asis;
	}
	$buffer = str_replace(array (chr(10) . '<script', chr(10) . '<style', '*/' . chr(10), 'M1N1FY-ST4RT'), array('<script', '<style', '*/', ''), $buffer);
	$minify_html_xhtml = get_option( 'minify_html_xhtml' );
	$minify_html_relative = get_option( 'minify_html_relative' );
	$minify_html_scheme = get_option( 'minify_html_scheme' );
	if ( $minify_html_xhtml == 'yes' && strtolower( substr( ltrim( $buffer ), 0, 15 ) ) == '<!doctype html>' )
		$buffer = str_replace( ' />', '>', $buffer );
	if ( $minify_html_relative == 'yes' )
		$buffer = str_replace( array ( 'https://' . $_SERVER['HTTP_HOST'] . '/', 'http://' . $_SERVER['HTTP_HOST'] . '/', '//' . $_SERVER['HTTP_HOST'] . '/' ), array( '/', '/', '/' ), $buffer );
	if ( $minify_html_scheme == 'yes' )
		$buffer = str_replace( array( 'http://', 'https://' ), '//', $buffer );
	return ($buffer);
}

function teckel_minify_html_menu() {
	add_options_page( 'Minify HTML Options', 'Minify HTML', 'manage_options', 'minify_html_options', 'minify_html_menu_options' );
}
add_action( 'admin_menu', 'teckel_minify_html_menu' );

function minify_html_menu_options() {
	if ( !current_user_can( 'manage_options' ) )
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	$minify_html_active = get_option( 'minify_html_active' );
	$minify_javascript = get_option( 'minify_javascript' );
	$minify_html_comments = get_option( 'minify_html_comments' );
	$minify_html_xhtml = get_option( 'minify_html_xhtml' );
	$minify_html_relative = get_option( 'minify_html_relative' );
	$minify_html_scheme = get_option( 'minify_html_scheme' );
	$minify_html_utf8 = get_option( 'minify_html_utf8' );
	if ( !$minify_html_active ) $minify_html_active = 'yes';
	if ( !$minify_javascript ) $minify_javascript = 'yes';
	if ( !$minify_html_comments ) $minify_html_comments = 'yes';
	if ( !$minify_html_xhtml ) $minify_html_xhtml = 'no';
	if ( !$minify_html_relative ) $minify_html_relative = 'no';
	if ( !$minify_html_scheme ) $minify_html_scheme = 'no';
	if ( !$minify_html_utf8 ) $minify_html_utf8 = 'no';
	if ( isset($_POST[ 'minify_html_submit_hidden' ]) && $_POST[ 'minify_html_submit_hidden' ] == 'Y' ) {
		if ( isset( $_POST[ 'minify_html_active' ] ) ) $minify_html_active = filter_var ( $_POST[ 'minify_html_active' ], FILTER_SANITIZE_STRING ); else $minify_html_active = 'yes';
		if ( isset( $_POST[ 'minify_javascript' ] ) ) $minify_javascript = filter_var ( $_POST[ 'minify_javascript' ], FILTER_SANITIZE_STRING ); else $minify_javascript = 'yes';
		if ( isset( $_POST[ 'minify_html_comments' ] ) ) $minify_html_comments = filter_var ( $_POST[ 'minify_html_comments' ], FILTER_SANITIZE_STRING ); else $minify_html_comments = 'yes';
		if ( isset( $_POST[ 'minify_html_xhtml' ] ) ) $minify_html_xhtml = filter_var ( $_POST[ 'minify_html_xhtml' ], FILTER_SANITIZE_STRING ); else $minify_html_xhtml = 'no';
		if ( isset( $_POST[ 'minify_html_relative' ] ) ) $minify_html_relative = filter_var ( $_POST[ 'minify_html_relative' ], FILTER_SANITIZE_STRING ); else $minify_html_relative = 'no';
		if ( isset( $_POST[ 'minify_html_scheme' ] ) ) $minify_html_scheme = filter_var ( $_POST[ 'minify_html_scheme' ], FILTER_SANITIZE_STRING ); else $minify_html_scheme = 'no';
		if ( isset( $_POST[ 'minify_html_utf8' ] ) ) $minify_html_utf8 = filter_var ( $_POST[ 'minify_html_utf8' ], FILTER_SANITIZE_STRING ); else $minify_html_utf8 = 'no';
		update_option( 'minify_html_active', $minify_html_active );
		update_option( 'minify_javascript', $minify_javascript );
		update_option( 'minify_html_comments', $minify_html_comments );
		update_option( 'minify_html_xhtml', $minify_html_xhtml );
		update_option( 'minify_html_relative', $minify_html_relative );
		update_option( 'minify_html_scheme', $minify_html_scheme );
		update_option( 'minify_html_utf8', $minify_html_utf8 );
		echo '<div class="updated"><p><strong>' . __( 'Settings saved.', 'minify-html' ) . '</strong></p></div>';
	}
?>
<style>
#minify_html label {white-space:nowrap}
#minify_html input[type="radio"] {margin-left:15px}
#minify_html input[type="radio"]:first-child {margin-left:0}
#minify_html .value {display:inline-block;min-width:50px}
@media screen and (max-width: 500px) {#minify_html label {white-space:normal}}
</style>
<div class="wrap">
	<h2>Minify HTML Settings</h2>
	<form name="form1" id="minify_html" method="post" action="">
		<input type="hidden" name="minify_html_submit_hidden" value="Y">
		<table class="form-table">
			<tbody>
				<tr class="minify_html_active">
					<th><label><?php echo __( 'Minify HTML', 'minify-html' ); ?></label></th>
					<td>
						<input type="radio" name="minify_html_active" value="yes"<?php echo ($minify_html_active=='yes' ? ' checked' : ''); ?>><span class="value"><strong><?php _e( 'Enable', 'minify-html' ); ?></strong></span>
						<input type="radio" name="minify_html_active" value="no"<?php echo ($minify_html_active!='yes' ? ' checked' : ''); ?>><span class="value"><?php _e( 'Disable', 'minify-html' ); ?></span>
						<p class="description"><?php echo __( 'Enable or disable Minify HTML', 'minify-html' ); ?></p>
					</td>
				</tr>
				<tr class="minify_javascript minify_html_options">
					<th><label><?php echo __( 'Minify inline JavaScript', 'minify-html' ); ?></label></th>
					<td>
						<input type="radio" name="minify_javascript" value="yes"<?php echo ($minify_javascript=='yes' ? ' checked' : ''); ?>><span class="value"><strong><?php _e( 'Yes', 'minify-html' ); ?></strong></span>
						<input type="radio" name="minify_javascript" value="no"<?php echo ($minify_javascript!='yes' ? ' checked' : ''); ?>><span class="value"><?php _e( 'No', 'minify-html' ); ?></span>
						<p class="description"><?php echo __( 'This option is typically safe to set to "Yes"', 'minify-html' ); ?></p>
					</td>
				</tr>
				<tr class="minify_html_comments minify_html_options">
					<th><label><?php echo __( 'Remove HTML, JavaScript and CSS comments', 'minify-html' ); ?></label></th>
					<td>
						<input type="radio" name="minify_html_comments" value="yes"<?php echo ($minify_html_comments=='yes' ? ' checked' : ''); ?>><span class="value"><strong><?php _e( 'Yes', 'minify-html' ); ?></strong></span>
						<input type="radio" name="minify_html_comments" value="no"<?php echo ($minify_html_comments!='yes' ? ' checked' : ''); ?>><span class="value"><?php _e( 'No', 'minify-html' ); ?></span>
						<p class="description"><?php echo __( 'This option is typically safe to set to "Yes"', 'minify-html' ); ?></p>
					</td>
				</tr>
				<tr class="minify_html_xhtml minify_html_options">
					<th><label><?php echo __( 'Remove XHTML closing tags from HTML5 void elements', 'minify-html' ); ?></label></th>
					<td>
						<input type="radio" name="minify_html_xhtml" value="yes"<?php echo ($minify_html_xhtml=='yes' ? ' checked' : ''); ?>><span class="value"><?php _e( 'Yes', 'minify-html' ); ?></span>
						<input type="radio" name="minify_html_xhtml" value="no"<?php echo ($minify_html_xhtml!='yes' ? ' checked' : ''); ?>><span class="value"><strong><?php _e( 'No', 'minify-html' ); ?></strong></span>
						<p class="description"><?php echo __( 'This option is typically safe to set to "Yes"', 'minify-html' ); ?></p>
					</td>
				</tr>
				<tr class="minify_html_relative minify_html_options">
					<th><label><?php echo __( 'Remove relative domain from internal URLs', 'minify-html' ); ?></label></th>
					<td>
						<input type="radio" name="minify_html_relative" value="yes"<?php echo ($minify_html_relative=='yes' ? ' checked' : ''); ?>><span class="value"><?php _e( 'Yes', 'minify-html' ); ?></span>
						<input type="radio" name="minify_html_relative" value="no"<?php echo ($minify_html_relative!='yes' ? ' checked' : ''); ?>><span class="value"><strong><?php _e( 'No', 'minify-html' ); ?></strong></span>
						<p class="description"><?php echo __( 'This option is typically safe to set to "Yes"', 'minify-html' ); ?></p>
					</td>
				</tr>
				<tr class="minify_html_scheme minify_html_options">
					<th><label><?php echo __( 'Remove schemes (HTTP: and HTTPS:) from all URLs', 'minify-html' ); ?></label></th>
					<td>
						<input type="radio" name="minify_html_scheme" value="yes"<?php echo ($minify_html_scheme=='yes' ? ' checked' : ''); ?>><span class="value"><?php _e( 'Yes', 'minify-html' ); ?></span>
						<input type="radio" name="minify_html_scheme" value="no"<?php echo ($minify_html_scheme!='yes' ? ' checked' : ''); ?>><span class="value"><strong><?php _e( 'No', 'minify-html' ); ?></strong></span>
						<p class="description"><?php echo __( 'This option is typically best to leave as "No"', 'minify-html' ); ?></p>
					</td>
				</tr>
				<tr class="minify_html_utf8 minify_html_options">
					<th><label><?php echo __( 'Support multi-byte UTF-8 encoding (if you see odd characters)', 'minify-html' ); ?></label></th>
					<td>
						<input type="radio" name="minify_html_utf8" value="yes"<?php echo ($minify_html_utf8=='yes' ? ' checked' : ''); ?>><span class="value"><?php _e( 'Yes', 'minify-html' ); ?></span>
						<input type="radio" name="minify_html_utf8" value="no"<?php echo ($minify_html_utf8!='yes' ? ' checked' : ''); ?>><span class="value"><strong><?php _e( 'No', 'minify-html' ); ?></strong></span>
						<p class="description"><?php echo __( 'This option is typically best to leave as "No"', 'minify-html' ); ?></p>
					</td>
				</tr>
				</tr>
					<td>&nbsp;</td>
					<td>(<strong><?php echo __( 'Bold', 'minify-html' ); ?></strong> = <?php echo __( 'default value', 'minify-html' ); ?>)</td>
				</tr>
			</tbody>
		</table>
		<p class="submit">
			<input type="submit" name="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Changes' ) ?>" />
		</p>
	</form>
</div>
<script>
(function($) {
	$('#minify_html .minify_html_active input').on('change', function() {
		if ($('input[name=minify_html_active]:checked', '#minify_html').val()=='no') {
			$('#minify_html .minify_html_options').css('opacity','0.4');
			$('#minify_html .minify_html_options input').prop( "disabled", true );
		} else {
			$('#minify_html .minify_html_options').css('opacity','1');
			$('#minify_html .minify_html_options input').prop( "disabled", false );
		}
	});
	$('#minify_html .minify_html_active input').trigger('change');
})( jQuery );
</script>

<?php

}

?>