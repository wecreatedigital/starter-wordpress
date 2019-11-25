<?php
do_action( 'learndash-breadcrumbs-before' ); ?>

<div class="ld-breadcrumbs-segments">
    <?php
    $breadcrumbs = learndash_get_breadcrumbs();

    $keys = apply_filters( 'learndash_breadcrumbs_keys', array(
        'course',
        'lesson',
        'topic',
        'current'
    ) );

    foreach( $keys as $key ):
        if( isset($breadcrumbs[$key]) ): ?>
            <span><a href="<?php echo esc_attr($breadcrumbs[$key]['permalink']); ?>"><?php echo esc_html(strip_tags($breadcrumbs[$key]['title'])); ?></a> </span>
        <?php endif;
    endforeach; ?>
</div> <!--/.ld-breadcrumbs-segments-->

<?php
do_action( 'learndash-breadcrumbs-after' ); ?>
