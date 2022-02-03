const mix = require('laravel-mix');
require('@tinypixelco/laravel-mix-wp-blocks');
require('laravel-mix-purgecss');
require('laravel-mix-copy-watched');
require('laravel-mix-criticalcss');

const tailwindcss = require('tailwindcss');

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

mix.setPublicPath('./dist');

mix.sass('resources/assets/styles/app.scss', 'styles')
   .options({
     processCssUrls: false,
     postCss: [ tailwindcss('./tailwind.config.js') ],
   })
   .criticalCss({
      enabled: mix.inProduction(),
      paths: {
          base: 'https://starter-wordpress.test/',
          templates: './dist/critical/',
          suffix: '_critical.min',
      },
      urls: [
          { url: '/', template: 'home' },
          { url: '/404', template: '404' },
      ],
      options: {
          minify: true,
      },
   });

mix.js('resources/assets/scripts/app.js', 'scripts')
   .extract();

mix.copyWatched('resources/assets/images/**', 'dist/images')
   .copyWatched('resources/assets/fonts/**', 'dist/fonts');

mix.autoload({ jquery: ['$', 'window.jQuery'] })
   .options({ processCssUrls: false })
   .sourceMaps(false, 'source-map')
   .version();
