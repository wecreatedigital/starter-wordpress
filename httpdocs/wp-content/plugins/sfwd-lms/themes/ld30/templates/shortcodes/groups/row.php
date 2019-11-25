<?php
$has_content = ( empty( $group->post_content ) ? false : true ); ?>

<div class="ld-item-list-item ld-expandable ld-item-group-item" id="<?php echo esc_attr('ld-expand-' . $group->ID ); ?>">
    <div class="ld-item-list-item-preview ld-group-row">
        <span class="ld-item-name"><?php echo esc_html($group->post_title); ?></span>
        <?php if( $has_content ): ?>
            <div class="ld-item-details">
                <div class="ld-expand-button ld-button-alternate" data-ld-expands="<?php echo esc_attr('ld-expand-' . $group->ID ); ?>">
                    <span class="ld-icon-arrow-down ld-icon ld-primary-background"></span>
                    <span class="ld-text ld-primary-color"><?php esc_html_e( 'Expand', 'learndash' ); ?></span>
                </div> <!--/.ld-expand-button-->
            </div> <!--/.ld-item-details-->
        <?php endif; ?>
    </div> <!--/.ld-item-list-item-preview-->
    <?php
    if( $has_content ): ?>
        <div class="ld-item-list-item-expanded">
            <div class="ld-item-list-content">
                <?php
                SFWD_LMS::content_filter_control( false );

                $group_content = apply_filters('the_content', $group->post_content);
                $group_content = str_replace(']]>', ']]&gt;', $group_content );
                echo $group_content;

                SFWD_LMS::content_filter_control( true ); ?>
            </div>
        </div>
    <?php endif; ?>
</div> <!--/.ld-table-list-item-->
