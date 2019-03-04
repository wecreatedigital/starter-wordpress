=== Minify HTML ===
Contributors: teckel
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=99J6Y4WCHCUN4&lc=US&item_name=Minify%20HTML&item_number=Minify%20HTML%20Plugin&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted
Tags: minify, minifer, minification, HTML, fast, faster, speed, performance, optimize, optimization, downloading, beautify, beautifier, sloppy, clean, cleaner, markup, compress, css, javascript
Requires at least: 1.5
Tested up to: 4.9.6
Stable tag: 1.99
Requires PHP: 5.2.4
License: GPLv3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Minify HTML output for clean looking markup and faster downloading.


== Description ==

Ever look at the HTML markup of your website and notice how sloppy and amateurish it looks? The Minify HTML plugin cleans up sloppy looking markup and minifies, which also speeds up download time.

Make your website's markup look professional by using Minify HTML. Easy to use, simply install and activate (with additional options for advanced settings).

Minify HTML also has optional specialized minification for JavaScript and internal CSS. It doesn't mess with your textareas or preformatted text.

Settings options to removes HTML, CSS and JavaScript comments (leaving MSIE conditional comments), remove unneeded XHTML closing tags from HTML5 void elements and remove unneeded relative schemes and domains from links.

> Do you know it's also important for SEO to have your images optimized? We recommend you use the **[ShortPixel Image Optimization](https://shortpixel.com/wp/af/1SFO1HD28044 "ShortPixel Image Optimization")** plugin for that. Use this **[special link](https://shortpixel.com/wp/af/1SFO1HD28044 "ShortPixel Image Optimization")** to get 50% more credits!

== Installation ==

= For an automatic installation through WordPress: =

1. Select **Add New** from the WordPress **Plugins** menu in the admin area.
2. Search for **Minify HTML**.
3. Click **Install Now**, then **Activate Plugin**.

= For manual installation via FTP: =

1. Upload the **minify-html-markup** folder to the **/wp-content/plugins/** directory.
2. Activate the plugin from the **Plugins** screen in your WordPress admin area.

= To upload the plugin through WordPress, instead of FTP: =

1. From the **Add New** plugins page in your WordPress admin area, select the **Upload Plugin** button.
2. Select the **minify-html-markup.zip** file, click **Install Now** and **Activate Plugin**.


== Frequently Asked Questions ==

= Are there any settings for Minify HTML? =

Yes.  Under the **Settings** menu in the admin console there's a **Minify HTML** menu item which allows you to modify Minify HTML settings.

= How does it work? =

Magic, obviously! Actually, it creates an output buffer and then preforms its 'magic' on the output buffer. It kinda is like magic.

= Will it break my stuff? =

Normally, you won't notice any changes to the way the site looks in the browser. However, if you rely on white space between HTML tags (which you shouldn't be doing) you may notice very isolated elements with reduced horizontal spacing. This is because inline objects will add a few pixels of spacing if there's whitespace between the objects. I consider Minify HTML a good way of finding these style 'errors' and correcting them with proper padding or margin style.

= Is it fast? =

Plenty. On my server that's running dozens of websites, it takes 0.007 seconds to minify a page. Also, because the HTML file is smaller, it will speed up transfer and rendering times. Minify HTML doesn't use bloated external libraries that add additional overhead and therefore take longer to process.

= How much smaller will my HTML be? =

With real-world test sites, the HTML file is around 20-25% smaller. Even when using deflate/gzip compression the resulting file is around 20% smaller, speeding up download times. 

= I'm using deflate/gzip to compress HTML, do I need Minify HTML? =

With Minify HTML, the source HTML file will be smaller, therefore, it will compress faster and be even smaller once compressed. Also, even with deflate/gzip, your HTML markup will still look sloppy and amateurish. Minify HTML corrects this even for deflated/gzip transfers (and also saves another 20% in bandwidth).


== Screenshots ==

1. Minify HTML settings.
2. Minify HTML turns this...
3. To this!


== Changelog ==

= v1.99 - 05/21/2018 =
* Compatiblity with WordPress thru 4.9.6
* Text changes

= v1.98 - 03/15/2017 =
* Forces the multi-byte UTF-8 encoding option to default to OFF
* Added support for WP-CLI (command line interface for WordPress) http://wp-cli.org/.

= v1.97 - 03/06/2017 =
* Added option to support multi-byte UTF-8 encoding if your foreign language website introduces odd characters.

= v1.96 - 03/02/2017 =
* Removed multi-byte non-English encoded content support because it caused problems with many English sites.  Will make this an option instead in a future release.

= v1.95 - 03/02/2017 =
* Correctly deals with multi-byte UTF-8 encoded content (typically non-English language websites).

= v1.94 - 01/03/2017 =
* Added option to not minify JavaScript.

= v1.93 - 12/28/2016 =
* Removed extra blank lines after v1.92 modification.

= v1.92 - 12/27/2016 =
* Corrected rare problem with inline JavaScript line comments.

= v1.91 - 6/29/2016 =
* Fixed a problem with errors in the log and the "Remove schemes" switch not quite working correctly.

= v1.9 - 5/10/2016 =
* Broke up option to remove relative schemes and domains into two options.
* Cleaned up settings page, gives option suggestions.

= v1.8 - 5/10/2016 =
* New Minify HTML Settings menu item in the admin console.
* Option to deactivate Minify HTML without
* Option to remove HTML, JavaScript and CSS comments.
* Option to remove XHTML closing tags from HTML5 void elements.
* Option to remove relative schemes and domains from links.

= v1.7 - 4/21/2016 =
* Now removes CSS and JavaScript comments.
* Fixed issue with minification of internal CSS media queries.
* Fixed issue with Google AMP reporting problems with the style boilerplate.
* No longer minifies XML files (which Minify HTML was never designed to do).

= v1.6 - 3/1/2016 =
* Removes HTML comments to further reduce file size (doesn't remove MSIE conditional comments).

= v1.5 - 2/23/2016 =
* No longer minifies the admin dashboard.
* Some additional JavaScript minification.

= v1.4 - 2/23/2016 =
* Fixed bug that could cause 'M1N1FY-ST4RT' to output to website.

= v1.3 - 2/22/2016 =
* Added compatibility for PHP 7.0.0

= v1.2 - 2/22/2016 =
* Compatible with older versions of PHP.
* More effective at cleaning up tabs, JavaScript, and internal CSS.
* Cleaned up source code and made a few performance tweaks.

= v1.1 - 2/19/2016 =
* Also minifies internal CSS.

= v1.0 - 2/18/2016 =
* Initial release.