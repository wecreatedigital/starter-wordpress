<?php

/**
 * Action to add custom content before the content tabs
 *
 * @since 3.0
 */
do_action( 'learndash-content-tabs-before', get_the_ID(), $course_id, $user_id );
do_action( 'learndash-' . $context . '-content-tabs-before', get_the_ID(), $course_id, $user_id );

$tab_count = 0;

/**
 * Include as a filter so other plugins can add additional tabs
 *
 * @var [array]
 */

$tabs = apply_filters( 'learndash_content_tabs', array(
    array(
        'id'        => 'content',
        'icon'      => 'ld-icon-content',
        'label'     => LearnDash_Custom_Label::get_label($context),
        'content'   => $content
    ),
    array(
        'id'        =>  'materials',
        'icon'      =>  'ld-icon-materials',
        'label'     =>  __( 'Materials', 'learndash' ),
        'content'   => $materials,
        'condition' => ( isset($materials) && !empty($materials ) )
    )
), $context, $course_id, $user_id );

foreach( $tabs as $tab ) {

    if( !isset($tab['condition']) ) {
        $tab_count++;
    }

    if( isset($tab['condition']) && $tab['condition'] ) {
        $tab_count++;
    }

} ?>

<div class="ld-tabs <?php echo esc_attr( 'ld-tab-count-' . $tab_count ); ?>">
    <?php
    /**
     * If we have more than one tab, show them
     * @var [type]
     */
    if( $tab_count > 1 ): $i = 0; ?>
         <div class="ld-tabs-navigation">
             <?php
             foreach( $tabs as $tab ):

                 // Skip if conditionally indicated
                 if( isset( $tab['condition']) && !$tab['condition'] ) {
                     continue;
                 }

                 $tab_class = 'ld-tab ' . ( $i == 0 ? 'ld-active' : '' ); ?>

                 <div class="<?php echo esc_attr($tab_class); ?>" data-ld-tab="<?php echo esc_attr( 'ld-tab-' . $tab['id'] . '-' . get_the_ID() ); ?>">
                     <span class="<?php echo esc_attr( 'ld-icon ' . $tab['icon'] ); ?>"></span>
                     <span class="ld-text"><?php echo esc_attr( $tab['label'] ); ?></span>
                 </div>
            <?php
            $i++; endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="ld-tabs-content">
        <?php
        /**
         * Action to add custom content before the content tabs
         *
         * @since 3.0
         */
        do_action( 'learndash-content-tab-listing-before', get_the_ID(), $context, $course_id, $user_id );
        do_action( 'learndash-' . $context . '-content-tab-listing-before', get_the_ID(), $course_id, $user_id );

        $i = 0; foreach( $tabs as $tab ):

            // Skip if conditionally indicated
            if( isset($tab['condition']) && !$tab['condition'] ) {
                continue;
            }

            $tab_class = 'ld-tab-content ' . ( $i == 0 ? 'ld-visible' : '' );

            do_action( 'learndash-content-tabs-' . $tab['id'] . '-before', get_the_ID(), $context, $course_id, $user_id ); ?>

            <div class="<?php echo esc_attr($tab_class); ?>" id="<?php echo esc_attr( 'ld-tab-' . $tab['id'] . '-' . get_the_ID() ); ?>">
                <?php echo $tab['content']; ?>
            </div>

            <?php
            do_action( 'learndash-content-tabs-' . $tab['id'] . '-after', get_the_ID(), $context, $course_id, $user_id );

        $i++; endforeach;

        /**
         * Action to add custom content after the content tabs
         *
         * @since 3.0
         */
        do_action( 'learndash-content-tab-listing-after', get_the_ID(), $course_id, $user_id );
        do_action( 'learndash-' . $context . '-content-tab-listing-after', get_the_ID(), $course_id, $user_id ); ?>

    </div> <!--/.ld-tabs-content-->

</div> <!--/.ld-tabs-->
<?php
/**
 * Action to add custom content after the content tabs
 *
 * @since 3.0
 */
do_action( 'learndash-content-tabs-after', get_the_ID(), $course_id, $user_id );
do_action( 'learndash-' . $context . '-content-tabs-after', get_the_ID(), $course_id, $user_id );
