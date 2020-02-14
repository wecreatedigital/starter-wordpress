@if( getenv('WP_ENV') == 'local' )
  <div class="responsive-helper bg-secondary">
    <div class="py-2 px-3 bg-primary d-inline-block d-sm-none">
      XS |
      <span class="helper-width"></span>
    </div>
    <div class="py-2 px-3 bg-danger d-none d-sm-inline-block d-md-none">
      SM |
      <span class="helper-width"></span>
    </div>
    <div class="py-2 px-3 bg-info d-none d-md-inline-block d-lg-none">
      MD |
      <span class="helper-width"></span>
    </div>
    <div class="py-2 px-3 bg-warning d-none d-lg-inline-block d-xl-none">
      LG |
      <span class="helper-width"></span>
    </div>
    <div class="py-2 px-3 bg-dark text-white d-none d-xl-inline-block">
      XL |
      <span class="helper-width"></span>
    </div>
    <span class="helper-nodes d-inline-block text-white px-2" title="Number of nodes on this page"></span>
    <input type="checkbox" name="show-col" class="mr-3">
  </div>
  <script>
  jQuery(document).ready(function(){
    jQuery('.helper-nodes').text(jQuery("*").length);
    jQuery('.helper-width').text(jQuery(window).width()+'px');
    jQuery('input[name=show-col]').click(function(){
      jQuery('body').toggleClass('show-col');
    });
  });
  jQuery(window).resize(function(){
    jQuery('.helper-width').text(jQuery(window).width()+'px');
  });
  </script>
  <style>
    .responsive-helper {
      position: fixed;
      bottom: 0;
      left: 0;
      z-index: 99999999;
    }
    .show-col .row > div,
    .fcb {
      border: red 1px solid;
    }
  </style>
@endif
