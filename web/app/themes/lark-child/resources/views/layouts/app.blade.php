@include('layouts.header')

<main class="main font-nunito text-base relative z-3" id="main">
  @include('layouts.breadcrumbs')
  @yield('content')
</main>
@hasSection('sidebar')
  <aside class="sidebar">
    @yield('sidebar')
  </aside>
@endif

@stack('scripts')
@include('layouts.footer')
