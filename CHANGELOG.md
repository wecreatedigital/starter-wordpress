# Changelog

All notable changes to `starter-wordpress` will be documented in this file

## 1.0.0 - 08-03-2019

- initial release

## 1.0.1 - 05-04-2019

- Removed old theme folder
- Included Timeline Express plugins
- Created header, footer, home, single, archive, global and common scss files
- Added frontpage.blade.php
- Added ACF Pro
- Created folders for partials, pages, layouts and svgs in Views
- Added Dist folder to .gitignore
- Included Slick Slider auto loaded to build
- Added Cookie Control plugin
- added simple 404 page with Bootstrap 4

## 1.0.2 - 08-04-2019

- Added placeholder favicons
- updated security.php and general.php in lib folder
- Added redirection.me plugins
- Added lib/media.pgp file

## 1.0.3 - 09-04-2019

- Included Merge + Minify + Refresh plugins
- Included Page Builder plugins
- Edited lib folder to suite new Roots Build
- Included Yoast Breadcrumbs

## 1.0.4 - 12-04-2019

- Changes to build to allow Yarn start

## 1.0.5 - 16-04-2019

- Included Google fonts script

## 1.0.6 - 17-04-2019

- Added Lazy load plugin

## 1.0.7 - 26-04-2019

- Added new customised helper function to simplify is template function
- Added Fontawesome to the Build

## 1.0.8 - 29-04-2019

- Added ACF awesome snippet for backend loading
- Added Autoptimization plugins
- Removed JS & CSS Script Optimizer
- Removed Merge + Minify + Refresh
- Removed Minify HTML
- Updated Redirection
- Updated Yoast
- Updated WordPress 5.1 -> 5.1.1

## 1.0.9 - 30-04-2019

- Added robot.txt function in admin.php

## 1.1.0 - 08-05-2019

- Added favicon code to header

## 1.1.1 - 09-05-2019

- Added Rocket-Lazy-Load plugin
- Removed WP-Image-Lazy-Load plugin

## 1.1.2 - 14-05-2019

- Updated ACF PRO
- Updated Regenerate Thumbnails
- Updated Sucuri Security
- Updated WP Super Cache
- Updated Yoast SEO
- Added iThemes Security Pro Plugins

## 1.1.3 - 15-05-2019

- Build now includes FontAwesome Pro

## 1.1.4 - 28-05-2019

- Added Child theme
- Added Bootstrap Nav Walker
- Added excerpt function

## 1.1.5 - 10-06-2019

- Updated .gitignore

## 1.1.6 - 18-06-2019

- Added Child theme

## 1.1.7 - 02-07-2019

- Added acf-json folder to child theme
- Updated WordPress version to 5.2.2
- Updated ACF PRO -> 5.8.1
- Updated Autoptimize -> 2.5.1
- Updated Contact Form 7 -> 5.1.3
- Updated Page Builder by SiteOrigin -> 2.10.6
- Updated Redirection -> 4.3.1
- Updated WP Super Cache -> 1.6.8
- Updated Yoast SEO -> 11.5

## 1.1.8 - 08-07-2019

- Added code to remove auto p tags from CF7
- Deleted Page Builder by SiteOrigin Plugin
- Deleted Sucuri Security - Auditing, Malware Scanner and    Hardening Plugin
- Deleted Timeline Express Plugin
- Updated WordPress translations

## 1.1.9 - 03-09-2019

- Updated scss
- Updated `resources\view` directories

## 1.2.0 - 09-09-2019

- Updated scss

## 1.2.5 - 15-10-2019

- Added Custom Post Type examples
- Updated Contact template name
- Hide parent theme template
- Added frontend example
- Added sample ACF groups
- Hide Woo breadcrumbs for Yoast when available
- Default options set automatically for new users
- Force postname permalinks on inital theme

## 1.3.0 - 19-11-2019

- WordPress update
- Updated WordPress plugins
- Removed TwentyTwenty theme

## 1.3.1 - 14-01-2020

- Updated Custom Post Types setup
- Updated child Roots Sage theme requirements from parent theme

## 1.3.1.1 - 14-01-2020

- Tweak to donate flexible blocks
- Comment to help identify which lib files to exclude

## 1.3.1.2 - 14-01-2020

- Update README
- Check all instances of `getenv`

## 1.3.1.3 - 15-01-2020

- Update composer.json

## 1.3.1.4 - 15-01-2020

- Update loading .env

## 1.3.1.5 - 15-01-2020

- Stop committing vendor folder

## 1.3.1.6 - 15-01-2020

- 404 page fixes

## 2.0 - 16-01-2020

- Implemented Roots Bedrock approach
- Updated env inline with Bedrock
- Fix: incorrect styling on hero FCB

## 2.0.1 - 17-01-2020

- w3c valid structure out of the box
- Remove Roots Soil - conflict with Bootstrap WP_Bootstrap_Navwalker
- Typo for donate FCB

## 2.0.2 - 21-01-2020

- Responsive helper now includes number of nodes on the page and current window width

## 2.0.3 - 23-01-2020

- Dev toolbar added a toggle to show Bootstrap columns

## 2.0.4 - 26-01-2020

- Contact template not displaying in WordPress unless in root of views directory
- Taxonomy helper separate from CPT helper - fixes issue when you have shared taxonomy over more than one CPT

## 2.0.5 - 27-01-2020

- Enhancement: generate content and apply settings when activating theme

## 2.0.6 - 27-01-2020

- Remove tags taxonomy - we never use it
- Updated composer.json
- Sample add taxonomy terms when activating theme

## 2.0.7 - 28-01-2020

- Tweaked CPT script
- Added helper text for setting up Google API Console with ACF

## 2.0.8 - 29-01-2020

- Tweak image quality (optional)

## 2.0.9 - 04-02-2020

- Force excerpt

## 2.0.10 - 14-02-2020

- Responsive table with fade for added UX
- Move red borders into dev helper
- Vertical align class

## 3.0.0 - 04-03-2020
- New block: image
- New block: carousel
- New block: accordion
- New block: card
- Revised block: text (all combined into one)
- Revised block: image/text (now can switch between left vs right)
- All blocks: choose background colour
- All blocks: choose padding
- All blocks: choose container fluid or not
- All blocks: used accordion to group by ‘Content’, ‘Style’ and ‘Advanced’
- For all new ACF selects, we dynamically update the values in code only - using web/app/themes/laravel-theme-child/app/lib/fcb.php
- Created mixins to have our own responsive padding (only!); define yours in variables.scss
- Improved dev helper; can now dynamically change the body width to help with precise testing such as with common large screen sizes + BS precise start and end breakpoints
- Dozen or so extra .fcb-* classes in web/app/themes/laravel-theme-child/resources/assets/styles/flexible/common/_main.scss
- Added min-width 320px and overflow hidden on FCB and navbar (since we have to do it for every build!)
- Removed CF7 CSS, added SCSS/BS4 styles
- Enhancement: FCBs have a start and end blade - this allows us to control globally all the relevant improvements above
- Enhancement: dynamic heading tags i.e. we have one H1, then three H2s and then the rest on the page (unless hardcoded) are H3
- Enhancement: added WP logging on local
- Enhancement: added two image sizes needed for 2x FCB blocks
- Enhancement: added more htaccess rules for speed
- Enhancement: added array value for ‘supports’ so we can determine what we need per post type i.e. title, editor, revisions, featured image etc
- Fix: removed excerpt function in place for one that automatically assumes we don’t want the continued link
- Fix: commented out jQuery since it’s included with WordPress and we use Autoptimize to compile
- Fix: red line shows with dev helper only on local
- Fix: removed wp API disable script - not needed anymore
- Fix: removed htaccess code we no longer need
- Fix: resolved other warnings

## 3.0.1 - 08-03-2020
- Include Smush.it and SendGrid plugins
- Include production config file for catching WP Super Cache and iThemes Security constants
- Update ACF FCB group including A-Z order and using FCB content partial on new FCBs
- Update accordion and testimonials blocks
- Rename: relevant FCBs files so file naming is consistent
- Fix: allow for multiple accordions on the page
- Fix: CTA button on xs and sm should be an anchor and not a button

## 3.0.11 - 21-07-2020

- Added Mega Menu Blade templating when using Custom Post Type selector

## 3.0.12 - 22-07-2020

- Laid out Mega Menu setup instructions

## 3.0.13 - 22-01-2021

- Created installation.md
- Updated cpt.php file to use Laravel Str class helper
- Removed httpdocs symlink

## 3.0.20 - 25-01-2021

- Updated Flexible Blade to use custom flexible.php config variables and Sage Roots View Composer class
- Added custom WordPress filter to hide ACF 'Custom Fields' menu from WordPress when on live by checking .env value
- Moved entire installation setup to a terminal command `php bin/console lark:install {sitename}`, this requires some more testing though

## 3.0.21 - 01-02-2021

- Added `dd()` when block blade view does not exist instead of a exception

## 3.0.22 - 08-02-2021

- Added new variable to flexible configuration file whereby we can show and hide the ACF menu in WordPress programmatically.

## 3.0.22 - 24-03-2021

- viewExists method wasn't added in recent update

## 3.0.24 - 30-03-2021

- Added new install param whereby stack stubs are available. For example, `php bin/console lark:install {sitename} --stack=tailwindcss`

## 3.0.25 - 13-04-2021

- Added `menu_for()` helper method
- Added `fullAddress()` helper method
- Added `headingSize()` helper method

## 3.0.26 - 20-04-2021

- Upon switching themes, create 30 default posts to test the blog index styling with
- Upon switching themes, auto activate plugins defined in the theme configuration file
- Added ability to set dynamic heading values for both mobile and desktop where necessary using the `headingSize()` helper method

## 3.0.27 - 26-04-2021

- Added some default styling to the rendered wysiwyg text editor content

## 3.1 - 29-07-2021

- Added more custom, dynamic heading size options support, including it's own component
- Add excerpt support to page post type
- Added descriptions to text helpers within `helpers/text.php`
- Updated ACF dynamic select file since those variables are always used in Lark sites
- Updated shared global variables
- Added `link.blade.php` file which dynamically changes based on link type passed

## 3.3 - 05-11-2021

- Override padding is now included by default
- TinyMCE Advanced plugin is now included by default
- GoogleMaps API script - optional
- Stripe webhook - optional

## 3.3.1 - 08-11-2021

- Included some default styling for both list and ordered list HTMl elements

## 3.4 - 19-01-2022

- Default added new ACF field to toggle displaying a primary navigation menu item as a button
- Replaced default ACF Flexible Content file with an updated version that reflects new standards
- Added the 'Go Live' plugin by default
- Added some default blade views based on recent Lark sites

## 3.5 - 01-02-2022

- Removed instances of Bootstrap 4, Popperjs and FontAwesome
- Updated Roots Sage parent theme

## 3.6 - 03-02-2022

- FIX: latest Roots Sage theme was not working, waiting until stable release

## 3.6.1 - 07-03-2022

- FIX: resolved include responsive-background-image.js path

## 3.6.2 - 16-03-2022

- Added some commented out rewrite logic for CPT

## 3.6.3 - 16-06-2022

- Removed some of the default breadcrumbs

## 3.6.4 - 20-06-2022

- Resolved Google Maps API console issue when an config key isn't presented
- Resolved Flexible Content issue whereby the same Post Type is specified 3 times over
- Resolved heading partial issue whereby the incrementation isn't quite working as expected
- Resolved Hero Flexible Content Block example whereby there are a few broken tags

## To do
- Create sample WooCommerce
