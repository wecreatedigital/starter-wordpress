@extends('layouts.app')

@section('content')
  <div class="jumbotron 404">
    <h1 class="display-4">404</h1>
    @option('supporting_text_1')
    <hr class="my-4">
    @option('supporting_text_2')

    @php( $post_objects = get_field('commonly_used_pages', 'options') )
      @if( $post_objects )
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

    {!! get_search_form(false) !!}

  </div>
@endsection
