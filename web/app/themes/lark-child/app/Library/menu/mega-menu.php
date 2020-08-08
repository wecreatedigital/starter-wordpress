<?php

/*
|--------------------------------------------------------------------------
| MEGA MENU - Setup instructions
|--------------------------------------------------------------------------
|
| TODO: Can we automate a lot of this with post_type_exists()?
|
| Here you may customize and register the Mega Menu for the given application.
| Instructions:
|   Backend Setup:
|     1. Uncomment the mega-menu CPT (Custom Post Type) in
|        'lark-child/app/Library/cpt.php'.
|     2. Uncomment 'lark-child/app/Library/menu/mega-menu.php'
|        in 'lark-child/functions.php'.
|     3. Make sure the correct Field Group Location is for 'nav_menu_item'
|        i.e. 'value' => 'location/primary_navigation' - these can be configured
|        in lark-child/app/Library/menu/register-nav-menus.php.

|   Frontend Setup:
|     1. Uncomment the has_mega_menu code within
|        'lark-child/app/Library/menu/navwalker.php' - this relays the backend
|        logic to blade to better separate HTML from PHP.
|     2. Customize the frontend styling here and you should be good to go:
|        'lark-child/resources/views/mega-menu/index.blade.php'
|
*/

if (function_exists('acf_add_local_field_group')):
  acf_add_local_field_group([
      'key' => 'group_5eb01857382ec',
      'title' => 'Mega menu',
      'fields' => [
          [
              'key' => 'field_5eb0185cd9641',
              'label' => 'Columns',
              'name' => 'columns',
              'type' => 'repeater',
              'instructions' => '',
              'required' => 0,
              'conditional_logic' => 0,
              'wrapper' => [
                  'width' => '',
                  'class' => '',
                  'id' => '',
              ],
              'collapsed' => '',
              'min' => 1,
              'max' => 4,
              'layout' => 'table',
              'button_label' => 'Add Column',
              'sub_fields' => [
                  [
                      'key' => 'field_5eb01877d9642',
                      'label' => 'Column type',
                      'name' => 'column_type',
                      'type' => 'select',
                      'instructions' => '',
                      'required' => 1,
                      'conditional_logic' => 0,
                      'wrapper' => [
                          'width' => '',
                          'class' => '',
                          'id' => '',
                      ],
                      'choices' => [
                          'menu' => 'Menu',
                          'text' => 'Text',
                          'image' => 'Image',
                      ],
                      'default_value' => [],
                      'allow_null' => 0,
                      'multiple' => 0,
                      'ui' => 0,
                      'return_format' => 'value',
                      'ajax' => 0,
                      'placeholder' => '',
                  ],
                  [
                      'key' => 'field_5eb018cbd9643',
                      'label' => 'Menu items',
                      'name' => 'menu_items',
                      'type' => 'repeater',
                      'instructions' => '',
                      'required' => 0,
                      'conditional_logic' => [
                          [
                              [
                                  'field' => 'field_5eb01877d9642',
                                  'operator' => '==',
                                  'value' => 'menu',
                              ],
                          ],
                      ],
                      'wrapper' => [
                          'width' => '',
                          'class' => '',
                          'id' => '',
                      ],
                      'collapsed' => '',
                      'min' => 0,
                      'max' => 0,
                      'layout' => 'table',
                      'button_label' => '',
                      'sub_fields' => [
                          [
                              'key' => 'field_5eb018dad9644',
                              'label' => 'Item link',
                              'name' => 'item_link',
                              'type' => 'link',
                              'instructions' => '',
                              'required' => 0,
                              'conditional_logic' => 0,
                              'wrapper' => [
                                  'width' => '',
                                  'class' => '',
                                  'id' => '',
                              ],
                              'return_format' => 'array',
                          ],
                      ],
                  ],
                  [
                      'key' => 'field_5eb0190dd9645',
                      'label' => 'Text',
                      'name' => 'text',
                      'type' => 'textarea',
                      'instructions' => '',
                      'required' => 0,
                      'conditional_logic' => [
                          [
                              [
                                  'field' => 'field_5eb01877d9642',
                                  'operator' => '==',
                                  'value' => 'text',
                              ],
                          ],
                      ],
                      'wrapper' => [
                          'width' => '',
                          'class' => '',
                          'id' => '',
                      ],
                      'default_value' => '',
                      'placeholder' => '',
                      'maxlength' => '',
                      'rows' => '',
                      'new_lines' => '',
                  ],
                  [
                      'key' => 'field_5eb01944d9646',
                      'label' => 'Image',
                      'name' => 'image',
                      'type' => 'image',
                      'instructions' => '',
                      'required' => 0,
                      'conditional_logic' => [
                          [
                              [
                                  'field' => 'field_5eb01877d9642',
                                  'operator' => '==',
                                  'value' => 'image',
                              ],
                          ],
                      ],
                      'wrapper' => [
                          'width' => '',
                          'class' => '',
                          'id' => '',
                      ],
                      'return_format' => 'array',
                      'preview_size' => 'medium',
                      'library' => 'all',
                      'min_width' => '',
                      'min_height' => '',
                      'min_size' => '',
                      'max_width' => '',
                      'max_height' => '',
                      'max_size' => '',
                      'mime_types' => '',
                  ],
              ],
          ],
      ],
      'location' => [
          [
              [
                  'param' => 'post_type',
                  'operator' => '==',
                  'value' => 'mega-menu',
              ],
          ],
      ],
      'menu_order' => 0,
      'position' => 'normal',
      'style' => 'default',
      'label_placement' => 'top',
      'instruction_placement' => 'label',
      'hide_on_screen' => '',
      'active' => 1,
      'description' => '',
  ]);
endif;

if (function_exists('acf_add_local_field_group')):
  acf_add_local_field_group(array(
      'key' => 'group_nav_menu_item_assign_mega_menu',
      'title' => 'Assign mega menu',
      'fields' => [
          [
              'key' => 'field_nav_menu_item_assign_mega_menu',
              'label' => 'Has mega menu?',
              'name' => 'has_mega_menu',
              'type' => 'post_object',
              'instructions' => '',
              'required' => 0,
              'conditional_logic' => 0,
              'wrapper' => [
                  'width' => '',
                  'class' => '',
                  'id' => '',
              ],
              'post_type' => [
                  'mega-menu',
              ],
              'taxonomy' => '',
              'allow_null' => 1,
              'multiple' => 0,
              'return_format' => 'id',
              'ui' => 1,
          ],
      ],
      'location' => array(
          array(
              array(
                  'param' => 'nav_menu_item',
                  'operator' => '==',
                  // Apply to all menu types
                  // 'value' => 'all',

                  // The ID of menu type in wp_terms:term_id
                  // 'value' => '2',

                  // Custom menu type name, registered in
                  // Library/menu/register-nav-menus.php
                  'value' => 'location/primary_navigation',
              ),
          ),
      ),
      'menu_order' => 0,
      'position' => 'normal',
      'style' => 'default',
      'label_placement' => 'top',
      'instruction_placement' => 'label',
      'hide_on_screen' => '',
  ));
endif;
