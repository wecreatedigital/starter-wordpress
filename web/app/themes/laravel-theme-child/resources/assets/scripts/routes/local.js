export default {
  init() {
    // Show cols if in dev mode
    if( jQuery('html').hasClass('development-mode') ) {
      jQuery('body').addClass('show-cols');
    }

    // Count the number of nodes, over 1.5k and we have a problem!
    jQuery('.helper-nodes').text(jQuery('*').length);
    jQuery('.helper-width').text(jQuery(window).width()+'px');

    // Toggle cols
    jQuery('input[name=show-cols]').click(function(){
      jQuery('body').toggleClass('show-cols');
    });

    jQuery('.resize-body').click(function(){
      var $width = parseInt(jQuery(this).find('span').text());
      if( $width ){
        jQuery('body').width($width+'px');
        jQuery('.helper-width').text($width+'px');
      } else {
        jQuery('body').width(jQuery(window).width());
        jQuery('.helper-width').text(jQuery(window).width()+'px');
      }
    });

    // Current browser width
    jQuery(window).resize(function(){
      jQuery('body').width(jQuery(window).width()+'px');
      jQuery('.helper-width').text(jQuery(window).width()+'px');
    });
  },
  finalize() {
    // JavaScript to be fired on the home page, after the init JS
  },
};
