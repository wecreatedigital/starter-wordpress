<?php
/**
 * LearnDash Settings Pages Loader.
 *
 * @package LearnDash
 * @subpackage Settings
 */

require_once __DIR__ . '/class-ld-settings-page-overview.php';
require_once __DIR__ . '/class-ld-settings-page-custom-labels.php';
require_once __DIR__ . '/class-ld-settings-page-courses-options.php';
require_once __DIR__ . '/class-ld-settings-page-courses-shortcodes.php';
require_once __DIR__ . '/class-ld-settings-page-lessons-options.php';
require_once __DIR__ . '/class-ld-settings-page-topics-options.php';
require_once __DIR__ . '/class-ld-settings-page-quizzes-options.php';
require_once __DIR__ . '/class-ld-settings-page-questions-options.php';
require_once __DIR__ . '/class-ld-settings-page-certificate-shortcodes.php';
require_once __DIR__ . '/class-ld-settings-page-assignments-options.php';

require_once __DIR__ . '/class-ld-settings-page-general.php';
require_once __DIR__ . '/class-ld-settings-page-paypal.php';
require_once __DIR__ . '/class-ld-settings-page-data-upgrades.php';
require_once __DIR__ . '/class-ld-settings-page-support.php';

// Add-ons Page.
if ( ( defined( 'LEARNDASH_ADDONS_UPDATER' ) ) && ( LEARNDASH_ADDONS_UPDATER === true ) ) {
	require_once __DIR__ . '/class-ld-settings-page-addons.php';
}

if ( ( defined( 'LEARNDASH_TRANSLATIONS' ) ) && ( LEARNDASH_TRANSLATIONS === true ) ) {
	require_once __DIR__ . '/class-ld-settings-page-translations.php';
}

