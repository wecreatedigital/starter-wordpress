# A starter WordPress

Version 1.3.1



## Table of Contents

* [Introduction](#introduction)
* [Installation](#installation)
* [Changelog](#changelog)
* [Security](#security)
* [Credits](#credits)



## Introduction

This sets the standard for any WordPress build made internally by [We Create Digital](https://wecreatedigital.co.uk)

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



## Installation

> Note: starter-wordpress is dependant upon [Sage Roots](https://roots.io/sage/).

After cloning the repo, cd into the child Sage Theme:

```bash
cd httpdocs\wp-content\themes\laravel-theme-child
```

Within the child Sage Theme, install `yarn` and `compose`:

```bash
composer install

yarn install
```



### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

The current version is location within the child Sage Theme (`httpdocs/wp-content/themes/laravel-theme-child/resources/style.css`).



### Security

If you discover any security related issues, please email `all@wecreatedigital.co.uk` instead of using the issue tracker.



## Credits

- [WeCreateDigital](https://wecreate.digital/)
- [All Contributors](../../contributors)
