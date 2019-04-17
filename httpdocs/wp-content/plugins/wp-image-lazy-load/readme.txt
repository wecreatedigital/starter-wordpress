=== Zedna WP Image Lazy Load ===
Contributors: zedna
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=3ZVGZTC7ZPCH2&lc=CZ&item_name=Zedna%20Brickick%20Website&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted
Tags: bandwith, speed, page load, image, iframe, background, responsive, video, html5, WooCommerce, Visual Composer
Requires at least: 3.0.4
Tested up to: 5.1.0
Stable tag: 1.6.2.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Image lazy load plugin to boost page load time and save bandwidth by removing all the images, background-images, responsive images, iframes and videos.

== Description ==

Decreasing page load time by progressive loading of images and other elements. They will load just when reach visible part of screen.

Plugin affect these elements:

1. `<img>` element

2. CSS property `background-image`

3. responsive images with `srcset` attribute

4. `<iframe>` element

5. `<video>` element

Features:

-reduce up to 90% of page load time, depends on elements amount

-compatible with Visual Composer

-compatible with WooCommerce

-you can choose to skip all iframes or just one in specific element

-you can choose to skip specific elements with some class

-you can show elements earlier or later than are visible on the screen

-optional fade in animation


== Installation ==

1. Upload `wp-image-lazy-load` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

`Do i need to modify my website or plugin to make it work on my custom theme?`

No, this plugin can be used without any modifications or requirements. It´s using general tags for images, background images, iframes and videos.

`How does it work?`

Elements that are most slowing the page load are removed and added separately depending on user´s journey.

`Does it support ads services?`

Unfortunately Ad services like Google Adsense and similiar causing iframe blinking when scrolling. Please use Skip parent element option for ads.

`Can i use embed elements?`

Yes, embed elements are OK, but there could be a random bug when using embed Google maps. All images below Google map might be loaded earlier.

`Why is plugin using !important priority for background-images?`

This is used only when Visual Composer is active on your website, to override VC´s styles.

== Screenshots ==

1. Speed test example

== Upgrade Notice ==
= 1.6.2.2 =
Compatible with WP 5.1.1

= 1.6.2.1 =
Compatible with WP 5.1.0

= 1.6.2 =
Compatible with WP 5.1.0

= 1.6.1 =
Compatible with WP 4.9.2

= 1.6 =
Compatible with WP 4.7.4

= 1.5.1 =
Compatible with WP 4.7.4

= 1.5 =
Compatible with WP 4.7.4

= 1.4 =
Compatible with WP 4.7.3

= 1.3.1 =
Compatible with WP 4.7.3

= 1.3 =
Compatible with WP 4.7.2

= 1.2 =
Compatible with WP 4.7.2

= 1.1 =
Built on WP 4.4.1 but can work on older versions

= 1.0 =
Built on WP 4.4.1 but can work on older versions

== Changelog ==
= 1.6.2.2 =
* IE backwards compatibility

= 1.6.2.1 =
* Fixed error in console log caused by SVG images

= 1.6.2 =
* Option to add multiple classes
* Code refactoring

= 1.6.1 =
* Added translations

= 1.6 =
* Visual Composer compatibility only if needed
* Code refactoring

= 1.5.1 =
* Fixed srcet initial loading
* Skip gradient background
* Compatibility with Visual Composer

= 1.5 =
* Skip specific element option

= 1.4 =
* Video support

= 1.3.1 =
* Code performance
* Fixed opacity on visible images and iframes
* Number validation added to settings

= 1.3 =
* Fixed retrieving images in visible part on page load

= 1.2 =
* Added fade in effect

= 1.1 =
* Fixed retrieving responsive image srcset
* Added options to skip on or all iframes
* Added option to show elements earlier or later than are visible

= 1.0 =
* First version