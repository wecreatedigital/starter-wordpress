=== JS & CSS Script Optimizer ===
Contributors: evgenniy
Donate link: http://4coder.info/en/
Tags: performance, javascript, css, script, js, compress, pack, combine, optimization
Requires at least: 2.8
Tested up to: 4.8
Stable tag: trunk

Make your Website faster by packing and grouping JavaScript and CSS files. Also it provides an opportunity to add CSS & JS via admin panel.

== Description ==
= Features =
- Grouping several scripts into single file (to minimize http requests count)
- Combine several CSS files into single files (with grouping by "media")
- Pack scripts using Dean Edwards's JavaScript Packer or Minify (by Steve Clay)
- Minify CSS files (remove comments, tabs, spaces, newlines)
- Support conditional JS and CSS (html5.js, IE CSS, <!--[if lt IE 9]>)
- Support JavaScript L10n / I18n (wp_localize_script)
- Put JavaScript at bottom
- Ability to include JavaScript and CSS files
- Network / WPMU support

= Requirements =
- Cache directory `/wp-content/cache/` should be writable
- This Plugin processing only those scripts that are included properly (using "wp_enqueue_script" or "wp_enqueue_style" function)
- Read <a href="https://developer.wordpress.org/themes/basics/including-css-javascript/">Including CSS & JavaScript</a>
- If any script fails and web-browser console shows errors you can add this JS to exclude list

For more info visit <a title="This WordPress plugin home page" href="http://4coder.info/en/code/wordpress-plugins/js-css-script-optimizer/">http://4coder.info/en/code/wordpress-plugins/js-css-script-optimizer/</a>.

== Installation ==

1. Upload `plugin-name.php` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

== Screenshots ==

1. Plugin settings page
1. Ability to include JavaScript files
1. Ability to include CSS files
1. YSlow speed test before installation
1. YSlow speed test after installation

== Changelog ==
= 0.3.3 =
* HTTP timeout option added (for wp_remote_get function)
* The issue with image backgrounds was fixed
* Notices "Undefined variable: group" are eliminated 
= 0.3.2 =
* Added new JS paker "Minify" (by Steve Clay)
* More comfortable admin options
= 0.3.1 =
* Translated into Serbian language (by Borisa Djuraskovic)
= 0.3.0 =
* Relative "Cache directory" path
* Ready to translate (PO file added)
= 0.2.9 =
* "wp_remote_get" methods exception was handled
* Notices in WP_DEBUG mode are eliminated
= 0.2.8 =
* Support JavaScript L10n (wp_localize_script)
* Support conditional JS (html5.js, <!--[if lt IE 9]>)
* Disable plugin when define('SCRIPT_DEBUG', true); 
= 0.2.7 =
* Ability to change cache directory from the plugin settings
* Default cache directory changed to "wp-content/cache/scripts"
* Cache directory creation bug  were fixed
= 0.2.6 =
* Better compatibility with others plugins and themes
* Support conditional CSS (IE CSS, <!--[if lt IE 9]>)
* Dean Edwards's packer was updated
* Using "wp_remote_get" instead of "file_get_contents"
* Do not pack JS which already packed
* More correct (save) ordering
= 0.2.5 =
* Better compatibility with other plugins (betta)
* Plugin options updated
* Some minor changes/fixes
= 0.2.4 =
* Added WPMU / Network support
* CSS compression bug has been fixed
* Some minor changes/fixes
= 0.2.3 =
* CSS compression has been improved
* Ability to add CSS files only for logged in users
* Some minor changes/fixes
= 0.2.2 =
* Some cache problems are fixed
= 0.2.1 =
* Some cache issues
* CSS processing problems
= 0.1.7 =
* Bug with options saving
= 0.1.4 =
* Added helpful information
* Some bugs are fixed
= 0.1.3 =
* CSS grouping has been updated
= 0.1.2 =
* Ability to include JavaScript and CSS scripts has been added
= 0.1.0 =
* Release version!
= 0.0.2 =
* Beta version of the JS & CSS Script Optimizer