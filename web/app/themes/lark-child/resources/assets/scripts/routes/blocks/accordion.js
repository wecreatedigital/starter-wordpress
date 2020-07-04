export default {
  init() {
  },
  finalize() {
    $('.accordion').each(function(){
      $(this).find('.panel-collapse').first().addClass('show');
      $(this).on('hide.bs.collapse', function () {
        $(this).find('.minus').removeClass('minus');
      }).on('shown.bs.collapse', function () {
        $(this).find('button[aria-expanded=true] svg').addClass('minus');
      });
    });
  },
};
