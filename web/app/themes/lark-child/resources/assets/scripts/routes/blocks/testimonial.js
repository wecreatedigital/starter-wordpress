export default {
  init() {
  },
  finalize() {
    $('.testimonial-slick-slider').slick({
      slidesToShow: 1,
      slidesToScroll: 1,
      dots: false,
      arrows: true,
      variableWidth: false,
      centerMode: false,
      infinite: true,
      fade: false,
      // nextArrow: '<button class="slick-prev"><i class="fa fa-chevron-left"></i>test</button>',
      // prevArrow: '<button class="slick-next"><i class="fa fa-chevron-right"></i>test</button>',
     });
  },
};
