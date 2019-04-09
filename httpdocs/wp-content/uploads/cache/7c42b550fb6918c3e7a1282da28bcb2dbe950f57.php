<?php $__env->startSection('content'); ?>
    <div class="jumbotron">
        <h1 class="display-4">404</h1>

  <?php if(!have_posts()): ?>

      <p class="lead">This is a simple hero unit, a simple jumbotron-style component for calling extra attention to featured content or information.</p>
      <hr class="my-4">
      <p>It uses utility classes for typography and spacing to space content out within the larger container.</p>


      <?php echo get_search_form(false); ?>


      </div>

  <?php endif; ?>
  </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>