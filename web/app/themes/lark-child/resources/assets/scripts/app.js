/**
 * External Dependencies
 */
import 'jquery';
// import 'slick-carousel/slick/slick'; //- only enable if needed
import 'bootstrap';

// import { library, dom } from '@fortawesome/fontawesome-svg-core';
// import { faFacebookF, faFacebook, faTwitter, faPinterestP, faInstagram, faLinkedin } from '@fortawesome/free-brands-svg-icons';
// import { faEnvelope, faPhone, faHome, faSearch, faChevronLeft, faChevronRight} from '@fortawesome/free-solid-svg-icons';
// import {} from '@fortawesome/pro-solid-svg-icons';
// library.add(faFacebookF, faFacebook, faPinterestP, faTwitter, faInstagram, faLinkedin, faEnvelope, faPhone, faSearch, faHome, faChevronLeft, faChevronRight); //faClock, faAcorn, faMap

// tell FontAwesome to watch the DOM and add the SVGs when it detects icon markup
// dom.watch();

// import local dependencies
import Router from './util/Router';
import hasAccordionBlock from './routes/blocks/accordion';
import hasTestimonialBlock from './routes/blocks/testimonial';
import common from './routes/common';
import home from './routes/home';
import about from './routes/about';
import local from './routes/local';
// import singleProduct from './routes/single-product';

/** Populate Router instance with DOM routes */
const routes = new Router({
  hasAccordionBlock,
  hasTestimonialBlock,
  common,
  home,
  about,
  local,
  // singleProduct,
});

/**
 * Google Font loader - only enable if needed
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
