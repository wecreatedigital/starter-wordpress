export default {
  init() {
    // JavaScript to be fired on all product pages
  },
  finalize() { // JavaScript to be fired on all product pages, after page specific JS is fired
    /**
     * Related products
     */
    $('.related.products .related-products').slick({
      dots: false,
      infinite: false,
      speed: 300,
      slidesToShow: 4,
      slidesToScroll: 1,
      // nextArrow: '<button class="slick-prev"><i class="fa fa-chevron-left"></i>test</button>',
      // prevArrow: '<button class="slick-next"><i class="fa fa-chevron-right"></i>test</button>',
      //rows: 0,
      responsive: [
        {
          breakpoint: 992,
          settings: {
            slidesToShow: 2,
            slidesToScroll: 2,
          },
        },
        {
          breakpoint: 576,
          settings: {
            slidesToShow: 1,
            slidesToScroll: 1,
            centerMode: true,
          },
        },
        // You can unslick at a given breakpoint now by adding:
        // settings: "unslick"
        // instead of a settings object
      ],
   });
  },
};
