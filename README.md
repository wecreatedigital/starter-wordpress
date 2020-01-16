
# Starter WordPress theme by We Create digital

Based on [Roots Sage](https://roots.io/sage/). This sets the standard for any WordPress build made internally by [We Create Digital](https://wecreate.digital). In version 2, we use Bedrock and Soil by Roots for a more secure and developer friendly approach.

## Table of Contents

* [Environment variables](#environment-variables)
* [Installation](#installation)
* [Changelog](#changelog)
* [Security](#security)
* [Credits](#credits)

## Environment variables

##### ENV
- Common values; `local` or `development` - other values assume we are on the live environment
- `local` will display the responsive helper for determining the active breakpoint
- If neither set to `local` or `development`, we apply mod_deflate and mod_expires

##### MAINTENANCE
- Enables maintenance mode on the website when set to true
- Logged in admins can continue to use the website under maintenance mode

##### SSL_ENABLED
- When set to true, and having refreshed permalinks, htaccess rules will be set to force SSL

##### DB_NAME, DB_USER, DB_PASSWORD, DB_HOST, DB_PREFIX
- These are all the familiar constants, provide them in the .env file to populate the wp-config.php file

##### *_KEY
- These replace the authentication unique keys and salts you usually get in wp-config.php
- These can be unique per environment

##### STRIPE_PUBLIC, STRIPE_PRIVATE
- For the donate flexible content block, we require these Stripe keys

##### GOOGLE_API
- For Google API Console related services, we simply use this API key
- This is used on both ACF and Google Maps

## Installation

After cloning the repo, copy the sample environment file:

```bash
cp .env.example .env
```

To setup dotenv, plugins and other packages run in the project root:

```bash
composer install
```

Running `composer install` will also clean up the `wp` directory, create a symlink for ServerPilot, install yarn on the child theme and install a handful of plugins we use from day one.

Furthermore, we've included Roots Soil and enabled bcrypt (also by Roots).

Next cd into the child Sage theme:

```bash
cd web/app/themes/laravel-theme-child/
```

Within the child Sage theme, install composer:

```bash
composer install
```

Going forward the following command can be used within the child theme, this gives you local and external URLs for ongoing testing:

```bash
yarn start
```

To compile JS and CSS for development environment:

```bash
yarn build
```

To compile JS and CSS for production environment:

```bash
yarn build:production
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

The current version is location within the child Sage theme (`web/app/themes/laravel-theme-child/resources/style.css`).

### Security

If you discover any security related issues, please email `enquiries@wecreate.digital` instead of using the issue tracker.

### Credits

- [We Create Digital](https://wecreate.digital/)
- [All Contributors](../../contributors)
