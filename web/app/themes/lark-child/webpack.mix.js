const mix = require('laravel-mix');
require('@tinypixelco/laravel-mix-wp-blocks');
require('laravel-mix-purgecss');
require('laravel-mix-copy-watched');
require('laravel-mix-criticalcss');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Sage application. By default, we are compiling the Sass file
 | for your application, as well as bundling up your JS files.
 |
 */

mix
  .setPublicPath('./dist')
  .browserSync('starter-wordpress.test');

mix
  .sass('resources/assets/styles/app.scss', 'styles')
  .sass('resources/assets/styles/editor.scss', 'styles')
  .purgeCss({
    extend: { content: [path.join(__dirname, 'index.php')] },
    whitelist: require('purgecss-with-wordpress').whitelist,
    whitelistPatterns: require('purgecss-with-wordpress').whitelistPatterns,
  })
 .criticalCss({
    enabled: mix.inProduction(),
    paths: {
       base: 'https://starter-wordpress.test',
       templates: './dist/critical/',
       suffix: '_critical.min'
    },
    urls: [
       { url: '/', template: 'home' },
       { url: '/404', template: '404' },
    ],
    options: {
       minify: true,
    }
  });

mix
  .js('resources/assets/scripts/app.js', 'scripts')
  .js('resources/assets/scripts/customizer.js', 'scripts')
  .blocks('resources/assets/scripts/editor.js', 'scripts')
  .extract();

mix
  .copyWatched('resources/assets/images/**', 'dist/images')
  .copyWatched('resources/assets/fonts/**', 'dist/fonts');

mix
  .autoload({ jquery: ['$', 'window.jQuery'] })
  .options({ processCssUrls: false })
  .sourceMaps(false, 'source-map')
  .version();
