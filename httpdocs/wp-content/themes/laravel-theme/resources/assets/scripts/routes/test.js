export default {
  init() {
    // JavaScript to be fired on the Test pages
        $('.slick').slick({
          centerMode: true,
          centerPadding: '60px',
          slidesToShow: 3,
          responsive: [
            {
              breakpoint: 768,
              settings: {
                arrows: false,
                centerMode: true,
                centerPadding: '40px',
                slidesToShow: 3,
            },
            },
            {
              breakpoint: 480,
              settings: {
                arrows: false,
                centerMode: true,
                centerPadding: '40px',
                slidesToShow: 1,
            },
        },
        ],
        });
  },
  finalize() {
    // JavaScript to be fired on the home page, after the init JS
  },
};
