<?php $__env->startSection('content'); ?>
    <div class="row d-flex justify-content-center">

  <h1 class="display-1 ">404</h1>
  </div>
  <?php if(!have_posts()): ?>
      <div class="row">
          <div class="col-md-8">
                <div class="h3 alert alert-warning">
                  <?php echo e(__('Sorry, an error has occured, Requested page not found!', 'sage')); ?>

                </div>
            </div>
            <div class="col-md-4 mt-3">
                <?php echo get_search_form(false); ?>

            </div>
    </div>
  <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>