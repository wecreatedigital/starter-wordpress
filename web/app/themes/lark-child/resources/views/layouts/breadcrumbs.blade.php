@if ( function_exists('yoast_breadcrumb') && ! have_rows('page_content_block') && ! is_404() )
  <div class="container">
    {!! yoast_breadcrumb( '<p id="breadcrumbs">','</p>' ) !!}
  </div>
@endif
