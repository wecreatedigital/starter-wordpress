@if( ! have_rows('page_content_block') )
<aside>
  <div class="page-header">
    <h1>{!! App::title() !!}</h1>
  </div>
</aside>
@endif
