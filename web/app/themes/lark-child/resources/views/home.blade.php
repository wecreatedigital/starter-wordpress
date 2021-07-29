@extends('layouts.app')

@section('content')
  @include('layouts.flexible', [
    'post_id' => get_option('page_for_posts')
  ])
@endsection
