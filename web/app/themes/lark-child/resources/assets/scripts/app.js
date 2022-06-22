// import 'jquery'; // web/app/themes/lark-child/app/Library/scripts.php we include jQuery here instead

require('alpinejs');

// import '@splidejs/splide';

(function($) {

  var wf = document.createElement('script');
  wf.src = 'https://ajax.googleapis.com/ajax/libs/webfont/1/webfont.js';
  wf.type = 'text/javascript';
  wf.async = 'true';
  var s = document.getElementsByTagName('script')[0];
  s.parentNode.insertBefore(wf, s);

})(jQuery);

/**
 * Google Font loader
 * @type {Object}
 */
window.WebFontConfig = {
  google: { families: [
    'Bitter:100,200,300,400,500,600,700,800,900i,100i,200i,300i,400i,500i,600i,700i,800i,900',
  ] },
};
(function() {
  var wf = document.createElement('script');
  wf.src = 'https://ajax.googleapis.com/ajax/libs/webfont/1/webfont.js';
  wf.type = 'text/javascript';
  wf.async = 'true';
  var s = document.getElementsByTagName('script')[0];
  s.parentNode.insertBefore(wf, s);
})();
