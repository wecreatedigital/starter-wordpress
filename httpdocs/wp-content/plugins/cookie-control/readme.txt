=== Cookie Control 8===
Contributors: sjtuffin, afroditi
Donate link: 
Tags: cookie, cookies, cookie legislation, eu cookie law
Requires at least: 3.0
Tested up to: 4.9.8
Stable tag: 3.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin enables you to comply with the UK and EU law on cookies.

== Description ==

This Wordpress plugin simplifies the implementation and customisation process of Cookie Control by [Civic UK](http://civicuk.com/ "Civic UK").

With an elegant user-interface that doesn't hurt the look and feel of your site, Cookie Control is a mechanism for controlling user consent for the use of cookies on their computer.

There are several license types available, including:

**Community edition** - Provides all of the core functionality of Cookie Control, and is of course GDPR compliant. You can use it to test Cookie Control, or if you don't require any of its pro features.

**Pro edition** - Includes all of the pro features for use on a single website, priority support and updates during your subscription. 

**Multisite Pro Edition** - Offers all of the pro features for use on up to ten websites, priority support and updates during your subscription.

To find out more about Cookie Control please visit [Civic's Cookie Control home page](https://www.civicuk.com/cookie-control "Civic's Cookie Control home page").


**Please Note**:

You will need to obtain an API KEY from [Civic UK](http://civicuk.com/cookie-law/pricing "Civic UK") in order to use this plugin.

Cookie Control is simply a mechanism to enable you to comply with UK and EU law on cookies. **You need to determine** which elements of your website are using cookies (this can be done via a [Cookie Audit](http://civicuk.com/cookie-law/deployment#audit "Cookie audit"), and ensure they are connected to Cookie Control.

== Installation ==

1. Obtain an API Key from [Civic UK](http://civicuk.com/cookie-law/pricing "Civic UK") for the site that you wish to deploy Cookie Control.*
1. Upload the entire `cookie-control` folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Configure the plugin by selecting 'Cookie Control' on your admin menu.
1. All done. Good job!


* If you already have an API Key and are wanting to update your domain records with CIVIC, please visit [Civic UK](http://civicuk.com/cookie-law/pricing?configure=true "Civic UK")

== Frequently Asked Questions ==

= API Key Error =

If you are using the free version your API key relates to a specific host domain.

So www.mydomain.org might work, but mydomain.org (without the www) might not.

Be sure that you enter the correct host domain when registering for your API key.

The recommended way of avoiding this problem is to create a 301 redirect so all requests to mydomain.org get forwarded to www.mydomain.org

This may have [SEO benefits](http://www.mattcutts.com/blog/seo-advice-url-canonicalization/ "SEO benefits") too as it makes it very clear to search engines which is the canonical (one true) domain.

= Is installing and configuring the plugin enough for compliance? =

Only if the only cookies your site uses are the Google Analytics ones. 
If other plugins set cookies, it is possible that you will need to write additional JavaScript.
To determine what cookies your site uses do a a [Cookie Audit](http://civicuk.com/cookie-law/deployment#audit "Cookie audit"). You will need to do this in any case in order to have a compliant privacy policy.
It is your responsibility as a webmaster to know what cookies your site sets, what they do and when they expire. If you don't you may need to consult whoever put your site together.

= I'm getting an error message / Cookie Control isn't working? =

Support for Cookie Control is available via the forum: [https://groups.google.com/forum/#!forum/cookiecontrol](https://groups.google.com/forum/#!forum/cookiecontrol/ "https://groups.google.com/forum/#!forum/cookiecontrol")

You can also contact the plugin contributors directly:

Sherred: [@sherred](https://twitter.com/sherred/ "@sherred") // [Sherred's website](http://sherred.com/ "Sherred's Website") and send an email to the address you find there.
Sjtuffin: [@sjtuffin](https://twitter.com/sjtuffin/ "@sjtuffin")

== Changelog ==
= 1.10 =
* Fix php7.2 warning

= 1.9 =
* Fix necessary cookies bug

= 1.8 =
* Fix lawfull basis bug

= 1.7 =
* Added alternative styles for closing the module (closeStyle property) and toggling consent to a cookie category (toggleType property).
* Improved accessibility support (accessibility property).
* Renamed initialConsentState to recommendedState so that it is more intuitive.
* Extended the branding options available.
* Simplified the module's Cookie Footprint, and removed the need for localStorage. Everything Cookie Control needs is now stored in a single cookie, named CookieControl.
* Automatically convert invalid cookie names from user settings to valid alternatives.
* Added onLoad callback property to execute custom code when Cookie Control is fully loaded.
* Extended public methods with saveCookie() and geoInfo().
* Fix backward compatibility for logConsent variable


= 1.6 =
* Fix bug when removing catgories and add new ones

= 1.5 =
* Fix logConsent bug
* Remove privacy link icon if url is empty
* Remove some console warnings

= 1.4 =
* Add wp session cookies to necessary cookies by default
* Bar and pop up option for pro version
* More options changing default text strings
* Statement array to add privacy link

= 1.3 =
* Multiple objects support for third party cookies

= 1.2 =
* Consent cookie expiry option added

= 1.1 =
* Third party cookies on acceptance functionality added

= 1.0 =
* Initial Release
