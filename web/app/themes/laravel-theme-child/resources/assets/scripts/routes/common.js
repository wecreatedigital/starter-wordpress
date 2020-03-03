
export default {
  init() {
    // JavaScript to be fired on all pages
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
  finalize() {
    /**
     * Adds a class to the accordion to open the first item
     */

      $('#collapse-1').addClass('show');
      /**
       * Accordion open and close script
       */
      $('#accordion').on('hide.bs.collapse', function () {
          $('#accordion a svg').removeClass('fa-rotate-270');
      });
      $('#accordion').on('shown.bs.collapse', function () {
          $(this).find('a[aria-expanded=true] svg').addClass('fa-rotate-270');
      });
    // JavaScript to be fired on all pages, after page specific JS is fired
    /**
     * Quote Slick Slider
     */
     /**
      * Slick Slider Script for Quotes
      */


      $('.testimonial-slick-slider').slick({
        slidesToShow: 1,
        slidesToScroll: 1,
        dots: false,
        arrows: true,
        variableWidth: false,
        centerMode: false,
        infinite: true,
        fade: false,
        nextArrow: '<button class="slick-prev"><i class="fa fa-chevron-left"></i>test</button>',
        prevArrow: '<button class="slick-next"><i class="fa fa-chevron-right"></i>test</button>',
     });

  },
};
