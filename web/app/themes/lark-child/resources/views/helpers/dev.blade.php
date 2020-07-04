@if( getenv('WP_ENV') == 'local' )
  <div class="responsive-helper bg-secondary">

    <div class="py-2 px-3 bg-success d-inline-block d-xs-none">XXS</div>
    <div class="py-2 px-3 bg-primary d-none d-xs-inline-block d-sm-none">XS</div>
    <div class="py-2 px-3 bg-danger d-none d-sm-inline-block d-md-none">SM</div>
    <div class="py-2 px-3 bg-info d-none d-md-inline-block d-lg-none">MD</div>
    <div class="py-2 px-3 bg-warning d-none d-lg-inline-block d-xl-none">LG</div>
    <div class="py-2 px-3 bg-dark text-white d-none d-xl-inline-block">XL</div>

    <div class="btn-group dropup">
      <button type="button" class="btn btn-dark dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <span class="helper-width"></span>
      </button>
      <div class="dropdown-menu">
        <button class="dropdown-item resize-body"><span>2560</span>px</button>
        <button class="dropdown-item resize-body"><span>1920</span>px</button>
        <button class="dropdown-item resize-body"><span>1680</span>px</button>
        <button class="dropdown-item resize-body"><span>1366</span>px</button>
        <button class="dropdown-item resize-body"><span>1280</span>px</button>
        <button class="dropdown-item resize-body"><span>1200</span>px (xl)</button>
        <button class="dropdown-item resize-body"><span>1199</span>px (lg)</button>
        <button class="dropdown-item resize-body"><span>992</span>px (lg)</button>
        <button class="dropdown-item resize-body"><span>991</span>px (md)</button>
        <button class="dropdown-item resize-body"><span>768</span>px (md)</button>
        <button class="dropdown-item resize-body"><span>767</span>px (sm)</button>
        <button class="dropdown-item resize-body"><span>576</span>px (sm)</button>
        <button class="dropdown-item resize-body"><span>575</span>px (xs)</button>
        <button class="dropdown-item resize-body" data-reset>Reset</button>
      </div>
    </div>

    <span class="helper-nodes d-inline-block text-white px-2" title="Number of nodes on this page"></span>

    <input type="checkbox" name="show-cols" class="mr-3">

  </div>

  <style>
    html.development-mode {
      background: #f1f1f1;
    }

    .development-mode body {
      margin: 0 auto;
      box-shadow: 0 30px 60px rgba(0, 0, 0, 0.25);
    }

    .responsive-helper {
      position: fixed;
      bottom: 0;
      left: 0;
      z-index: 99999999;
    }

    .show-cols .row > div {
      border: red 1px solid;
    }

    .show-cols .fcb {
      border-top: red 1px solid;
      border-left: red 1px solid;
      border-right: red 1px solid;
    }

    .btn-group {
      position: relative;
      top: -1px;
    }
  </style>
@endif
