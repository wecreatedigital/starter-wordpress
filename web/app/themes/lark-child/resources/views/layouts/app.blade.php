@include('layouts.header')

<main class="main font-bitter text-base relative z-3" id="main">
  @yield('content')
</main>

@hasSection('sidebar')
  <aside class="sidebar">
    @yield('sidebar')
  </aside>
@endif

@stack('scripts')
@include('layouts.footer')
