// import 'jquery'; // web/app/themes/lark-child/app/Library/scripts.php we include jQuery here instead

require('alpinejs');

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
    // 'Cabin:100,300,300i,400i,400,600,700,800',
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
