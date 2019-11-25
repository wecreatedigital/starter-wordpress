<?php
/**
 * LearnDash Settings Loader.
 *
 * @package LearnDash
 * @subpackage Settings
 */

if ( ! defined( 'LEARNDASH_SETTINGS_SECTION_TYPE' ) ) {
	define( 'LEARNDASH_SETTINGS_SECTION_TYPE', 'metabox' );
}

require_once __DIR__ . '/class-ld-settings-fields.php';
require_once __DIR__ . '/class-ld-settings-pages.php';
require_once __DIR__ . '/class-ld-settings-sections.php';
require_once __DIR__ . '/class-ld-settings-metaboxes.php';

require_once __DIR__ . '/settings-fields/settings-fields-loader.php';
require_once __DIR__ . '/settings-pages/settings-pages-loader.php';
require_once __DIR__ . '/settings-sections/settings-sections-loader.php';
