<article class="col-12 col-sm-6 @if($counter % 5 == 1 || $counter % 5 == 2 || $counter % 5 == 3) col-lg-4 @else col-lg-6 @endif">
  <div class="card">
    <a href="@permalink">
      @if($counter % 5 == 1 || $counter % 5 == 2 || $counter % 5 == 3)
        <img class="card-img-top" src="@thumbnail('medium', false)" alt="@title">
      @else
        <img class="card-img-top" src="@thumbnail('large', false)" alt="@title">
      @endif
    </a>
    <header class="card-header">

      <h2 class="entry-title">
        <a href="@permalink">
          @title
        </a>
      </h2>

      @include('partials/entry-meta')
    </header>

    <div class="card-body entry-summary">
      @excerpt()
    </div>
  </div>
</article>
