<?php
do_action( 'learndash-certificate-wrapper-before' ); ?>
<div class="ld-course-certificate">
    <?php
    do_action( 'learndash-certificate-before' ); ?>
    <a href="<?php echo esc_attr($course_certficate_link); ?>" class="ld-button"><span class="ld-icon ld-icon-certificate"></span> <?php esc_html_e( 'Download Certificate', 'learndash' ); ?></a>
    <?php
    do_action( 'learndash-certificate-after' ); ?>
</div> <!--/.ld-course-certificate-->
<?php
do_action( 'learndash-certificate-wrapper-after' ); ?>
