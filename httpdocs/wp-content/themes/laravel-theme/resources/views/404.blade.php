@extends('layouts.app')

@section('content')
    <div class="jumbotron 404" >
        <h1 class="display-4">404</h1>

  @if (!have_posts())

      <p class="lead">@php(print_r(get_field('supporting_text_1','options')))</p>
      <hr class="my-4">
      <p>@php(print_r(get_field('supporting_text_2','options')))</p>

      @php($repeater = get_field('commonly_used_pages','options'))
      <ul>


      @foreach($repeater as $post_object)
         <li> <a href="@php(print_r(($post_object['page']->guid)))">@php(print_r(($post_object['page']->post_title)))</a></li>
      @endforeach
      </ul>
      {!! get_search_form(false) !!}

      </div>

  @endif
  </div>
@endsection
