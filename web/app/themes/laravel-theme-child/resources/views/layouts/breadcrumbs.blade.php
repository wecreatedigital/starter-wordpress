@if ( function_exists('yoast_breadcrumb') && ! have_rows('page_content_block') )
  {!! yoast_breadcrumb( '<p id="breadcrumbs">','</p>' ) !!}
@endif
