<?php $__env->startSection('content'); ?>
    <div class="jumbotron 404" >
        <h1 class="display-4">404</h1>

  <?php if(!have_posts()): ?>

      <p class="lead"><?php (print_r(get_field('supporting_text_1','options'))); ?></p>
      <hr class="my-4">
      <p><?php (print_r(get_field('supporting_text_2','options'))); ?></p>

      <?php ($repeater = get_field('commonly_used_pages','options')); ?>
      <ul>


      <?php $__currentLoopData = $repeater; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $post_object): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
         <li> <a href="<?php (print_r(($post_object['page']->guid))); ?>"><?php (print_r(($post_object['page']->post_title))); ?></a></li>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </ul>
      <?php echo get_search_form(false); ?>


      </div>

  <?php endif; ?>
  </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>