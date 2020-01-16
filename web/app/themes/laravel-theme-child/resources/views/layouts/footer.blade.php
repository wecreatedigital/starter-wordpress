<footer class="content-info">
  <div class="container">
    @php dynamic_sidebar('sidebar-footer') @endphp
  </div>
</footer>
@if( getenv('WP_ENV') == 'local' )
  <div class="responsive-helper">
    <div class="p-4 bg-primary d-block d-sm-none">
    XS
    </div>
    <div class="p-4 bg-danger d-none d-sm-block d-md-none">
    SM
    </div>
    <div class="p-4 bg-info d-none d-md-block d-lg-none">
    MD
    </div>
    <div class="p-4 bg-warning d-none d-lg-block d-xl-none">
    LG
    </div>
    <div class="p-4 bg-dark text-white d-none d-xl-block">
    XL
    </div>
  </div>
@endif
