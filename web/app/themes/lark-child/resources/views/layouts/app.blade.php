@include('layouts.header')

<main class="main" id="main">
  @include('layouts.breadcrumbs')
  @yield('content')
</main>

@hasSection('sidebar')
  <aside class="sidebar">
    @yield('sidebar')
  </aside>
@endif

@include('layouts.footer')
@include('helpers.dev')
