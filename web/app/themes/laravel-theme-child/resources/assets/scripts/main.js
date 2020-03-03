import 'jquery';
//import 'slick-carousel/slick/slick.min';
import 'bootstrap';

import { library, dom } from '@fortawesome/fontawesome-svg-core';
import { faFacebookF, faFacebook, faTwitter, faPinterestP, faInstagram, faLinkedin } from '@fortawesome/free-brands-svg-icons';
import { faEnvelope, faPhone, faHome, faSearch, faChevronRight} from '@fortawesome/free-solid-svg-icons';
import {} from '@fortawesome/pro-solid-svg-icons';
library.add(faFacebookF, faFacebook, faPinterestP, faTwitter, faInstagram, faLinkedin, faEnvelope, faPhone, faSearch, faHome, faChevronRight); //faClock, faAcorn, faMap

// tell FontAwesome to watch the DOM and add the SVGs when it detects icon markup
dom.watch();

// import local dependencies
import Router from './util/Router';
import common from './routes/common';
import home from './routes/home';
import aboutUs from './routes/about';
import local from './routes/local';
// import test from './routes/test';

/** Populate Router instance with DOM routes */
const routes = new Router({
  // All pages
  common,
  // Home page
  home,
  // About Us page, note the change from about-us to aboutUs.
  aboutUs,
  local,
  // test,
});
/**
 * Google Font loader
 * @type {Object}
 */
// window.WebFontConfig = {
//   google: { families: [ 'Open+Sans:400,400italic,700:latin' ] },
// };
// (function() {
//   var wf = document.createElement('script');
//   wf.src = 'https://ajax.googleapis.com/ajax/libs/webfont/1/webfont.js';
//   wf.type = 'text/javascript';
//   wf.async = 'true';
//   var s = document.getElementsByTagName('script')[0];
//   s.parentNode.insertBefore(wf, s);
// })();

// Load Events
jQuery(document).ready(() => routes.loadEvents());

/**
 * Adds a class to the accordion to open the first item
 */
jQuery('#collapse1').addClass('show');
/**
 * Accordion open and close script
 */
$('#accordion').on('hide.bs.collapse', function () {
    $('#accordion a svg').removeClass('fa-rotate-270');
});
$('#accordion').on('shown.bs.collapse', function () {
    $(this).find('a[aria-expanded=true] svg').addClass('fa-rotate-270');
});

/**
 * Quote Slick Slider
 */
$('.slick-featured_resources').slick({
   dots: true,
   infinite: true,
   speed: 300,
   slidesToShow: 2,
   variableWidth: true,
 });
