// import external dependencies
import 'jquery';

// slickSlider
import 'slick-carousel/slick/slick.min';

// Import everything from autoload
import './autoload/**/*'

// import local dependencies
import Router from './util/Router';
import common from './routes/common';
import home from './routes/home';
import aboutUs from './routes/about';
import test from './routes/test';

/** Populate Router instance with DOM routes */
const routes = new Router({
  // All pages
  common,
  // Home page
  home,
  // About Us page, note the change from about-us to aboutUs.
  aboutUs,
  // Test Template
  test,
});
/**
 * Google Font loader
 * @type {Object}
 */
window.WebFontConfig = {
      google: { families: [ 'Open+Sans:400,400italic,700:latin' ] },
};
(function() {
      var wf = document.createElement('script');
      wf.src = 'https://ajax.googleapis.com/ajax/libs/webfont/1/webfont.js';
      wf.type = 'text/javascript';
      wf.async = 'true';
      var s = document.getElementsByTagName('script')[0];
      s.parentNode.insertBefore(wf, s);
})();

// Load Events
jQuery(document).ready(() => routes.loadEvents());
