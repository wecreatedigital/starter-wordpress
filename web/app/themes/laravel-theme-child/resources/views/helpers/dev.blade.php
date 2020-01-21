@if( getenv('WP_ENV') == 'local' )
  <div class="responsive-helper">
    <div class="py-2 px-3 bg-primary d-block d-sm-none">
      XS |
      <span class="helper-width"></span> |
      <span class="helper-nodes" title="Number of nodes on this page"></span>
    </div>
    <div class="py-2 px-3 bg-danger d-none d-sm-block d-md-none">
      SM |
      <span class="helper-width"></span> |
      <span class="helper-nodes" title="Number of nodes on this page"></span>
    </div>
    <div class="py-2 px-3 bg-info d-none d-md-block d-lg-none">
      MD |
      <span class="helper-width"></span> |
      <span class="helper-nodes" title="Number of nodes on this page"></span>
    </div>
    <div class="py-2 px-3 bg-warning d-none d-lg-block d-xl-none">
      LG |
      <span class="helper-width"></span> |
      <span class="helper-nodes" title="Number of nodes on this page"></span>
    </div>
    <div class="py-2 px-3 bg-dark text-white d-none d-xl-block">
      XL |
      <span class="helper-width"></span> |
      <span class="helper-nodes" title="Number of nodes on this page"></span>
    </div>
  </div>
  <script>
  jQuery(document).ready(function(){
    jQuery('.helper-nodes').text(jQuery("*").length);
    jQuery('.helper-width').text(jQuery(window).width()+'px');
  });
  jQuery(window).resize(function(){
    jQuery('.helper-width').text(jQuery(window).width()+'px');
  });
  </script>
@endif
