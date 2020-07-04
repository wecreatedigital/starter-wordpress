@extends('layouts.app')

@section('content')
  <div class="jumbotron">
    <div class="container">
      <h1 class="display-4">
        Sorry - this page cannot be found!
      </h1>
      @option('supporting_text_1')
      <hr class="my-4">
      @option('supporting_text_2')
      @php( $post_objects = get_field('commonly_used_pages', 'options') )
        @if( $post_objects )
          <h5>
            Other popular pages:
          </h5>
          <ul>
            @foreach( $post_objects as $post_object)
              <li>
                <a href="{{ get_permalink($post_object->ID) }}">
                  {{ get_the_title($post_object->ID)}}
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
