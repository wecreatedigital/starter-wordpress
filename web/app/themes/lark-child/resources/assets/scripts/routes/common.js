
export default {
  init() { // JavaScript to be fired on all pages
  },
  finalize() { // JavaScript to be fired on all pages, after page specific JS is fired
    function scrollToHash(target){
      if( target.length ) {
        $('html, body').stop().animate({
          scrollTop: target.offset().top,
        }, 750);
      }
    }

    if(window.location.hash) {
      scrollToHash($(window.location.hash));
    }

    // For accessibility reasons use button if target is on same page, see below function
    $('a[href^="#"]').on('click', function(event) {
      if( ! $(this).attr('data-toggle') ) {
        event.preventDefault;
        scrollToHash($(this.getAttribute('href')));
      }
    });

    $('button[data-target]').on('click', function(event) {
      if( ! $(this).attr('data-toggle') ) {
        event.preventDefault;
        scrollToHash($(this.getAttribute('data-target')));
      }
    });

    // Prevent image download
    $('html:not(.development-mode)').on('contextmenu', 'img', function() {
      return false;
    });
  },
};
