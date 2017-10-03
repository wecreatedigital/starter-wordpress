# A starter WordPress (Version 4.8.2)
This sets the standard for any WordPress build made internally by [We Create Digital](https://wecreatedigital.co.uk)

## This uses/compromised of:
### [1. phpdotenv](https://github.com/vlucas/phpdotenv)
- Why? *"Number three on this list is to store the config in the environment because configuration varies substantially across deploys, code does not."*
- We love working in [Laravel](laravel.com) and have introduced an environment file by [vlucas](https://github.com/vlucas/). [This](https://m.dotdev.co/secure-your-wordpress-config-with-dotenv-d939fcb06e24) article got us started!
- The .env config file follows many common WP constants and a few extras which help speed up development

        ENV=local
        WP_DEBUG=true
        SSL_ENABLED=no
        GOOGLE_ANALYTICS=UA-XXXXXXXXX-X

        DB_NAME=wordpress
        DB_USER=root
        DB_PASSWORD=root
        DB_HOST=localhost
        DB_PREFIX=wp_

        AUTH_KEY=
        SECURE_AUTH_KEY=
        LOGGED_IN_KEY=
        NONCE_KEY=
        AUTH_SALT=
        SECURE_AUTH_SALT=
        LOGGED_IN_SALT=
        NONCE_SALT=

**Generate them from [WordPress.org secret-key service](https://api.wordpress.org/secret-key/1.1/salt/)**

##### ENV
- Common values; `local`, `dev` or `production`
- Will print out `<meta name="robots" content="noindex, nofollow">` if local or dev

##### WP_DEBUG
- Makes both `WP_DEBUG` and `SAVEQUERIES` true in wp-config.php
- Helps with debugging and also enables the [debugbar](https://en-gb.wordpress.org/plugins/debug-bar/)

##### SSL_ENABLED
- Value `YES` will enable
- When refreshing permalinks, htaccess rules will be set to force SSL

##### GOOGLE_ANALYTICS
- Simply provide tracking ID and will insert the new gtag.js immediately after `<head>`

##### DB_NAME, DB_USER, DB_PASSWORD, DB_HOST, DB_PREFIX
- These are all the familiar constants, provide them in the .env file to populate the wp-config.php file

### [2. Sage/Roots](https://roots.io/sage/)
- Extended theme to include some security enhancements and to remove aspects of WordPress we never use

##### lib/ajax.php
- Commented out by default, provides sample AJAX form and code for processing data

##### lib/form.php
- Commented out by default, provides sample POST form and code for processing data

##### lib/cpt.php
- Commented out by default, includes a sample CPT ready for modification

##### lib/security.php
- Sample htaccess rules which prevent access to xmlrpc.php and wp-json
- Enabling SSL if phpdotenv states to do so
- Disables pingback
- Removes RSS feeds
- Set Strict-Transport-Security
- Set Content-Security-Policy
- Set X-Frame-Options
- Cleans up `<head>`

##### lib/general.php
- Looks for ACF and if available adds options page
- Place to set `add_image_size`
- Includes a footer menu

##### Other theme modifications
- Looks for Yoast SEO Breadcrumbs

### 3. Common Plugins
Comes with the following plugins:

- [Advanced Custom Fields PRO](advancedcustomfields.com)
- [Contact Form 7](https://contactform7.com/)
- [Contact Form 7 Honeypot](https://en-gb.wordpress.org/plugins/contact-form-7-honeypot/)
- [Debug Bar](https://en-gb.wordpress.org/plugins/debug-bar/) (development only, `WP_DEBUG` to be set to false in production)
- [iThemes Security](https://ithemes.com/security/) (production only)
- [JS & CSS Script Optimizer](https://en-gb.wordpress.org/plugins/js-css-script-optimizer/) (production only)
- [Mailgun](https://en-gb.wordpress.org/plugins/mailgun/)
- [Minify HTML](https://wordpress.org/plugins/minify-html-markup/) (production only)
- [Regenerate Thumbnails](https://wordpress.org/plugins/regenerate-thumbnails/)
- [Sucuri Security](https://wordpress.org/plugins/sucuri-scanner/) (production only)
- [WP Super Cache](https://wordpress.org/plugins/wp-super-cache/) (production only)
- [WP-PageNavi](https://en-gb.wordpress.org/plugins/wp-pagenavi/)
- [Yoast SEO](https://yoast.com/wordpress/plugins/seo/)


### 4. ACF Migrations
- To sync our field changes we have now included `acf-json` directory within the theme folder. JSON is automatically populated here when fields are created/updated, [read more](https://www.advancedcustomfields.com/resources/local-json/)
- The build includes includes typical fields for the options page such as phone number, email address and social media URLs

## Log of changes
**03/09/2017**
- Included latest version of Bootstrap in bower.json
- Included Slick in bower.json
- Created `acf-json` folder for sync of ACF migrations
- JSON for typical ACF option fields
- Helpful URLs for generating CPT and taxonomies
- Registered nav menu for footer

## To do
- Investigate [Using Composer with WordPress](https://roots.io/using-composer-with-wordpress/) and [Receipe for WordPress/Composer](http://composer.rarst.net/#recipes)
- Add more customisation!
