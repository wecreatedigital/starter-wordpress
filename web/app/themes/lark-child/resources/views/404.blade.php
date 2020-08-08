@extends('layouts.app')

@section('content')
  <div class="jumbotron">
    <div class="container">
      <h1 class="display-4">
        Sorry - this page cannot be found!
      </h1>
      @option('supporting_text_1')
      <hr class="fcb-y40">
      @php( $common_pages = get_field('commonly_used_pages', 'options') )
        @if( $common_pages )
          <h5>
            Other popular pages:
          </h5>
          <ul>
            @foreach( $common_pages as $page )
              <li>
                <a href="{{ get_permalink($page) }}">
                  {{ get_the_title($page)}}
                </a>
              </li>
            @endforeach
          </ul>
        @endif

      @if (! have_posts())
        {!! get_search_form(false) !!}
      @endif
    </div>
  </div>
@endsection
