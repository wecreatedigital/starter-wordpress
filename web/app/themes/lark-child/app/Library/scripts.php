<?php
/*
 * w3c valid script and style tags
 */
add_action(
    'after_setup_theme',
    function () {
        add_theme_support('html5', ['script', 'style']);
    }
);

add_filter('wpcf7_load_css', '__return_false');

/**
 * jQuery is outdated in WP and has vulnerabilities, but we don't want break the WP admin_init
 * @author Dean Appleton-Claydon
 * @date   2020-03-02
 *
 * https://stackoverflow.com/questions/1157531/how-can-i-remove-jquery-from-the-frontside-of-my-wordpress
 */
if ( ! is_admin()) {
    add_action('wp_enqueue_scripts', 'my_jquery_enqueue', 11);
}
function my_jquery_enqueue()
{
    wp_deregister_script('jquery');
    wp_register_script('jquery', get_stylesheet_directory_uri().'/resources/assets/scripts/jquery-3.5.1.min.js', false, null, true);
    wp_enqueue_script('jquery');
}

/**
 * Favicons in head
 *
 * @author Dean Appleton-Claydon
 * @date   2020-07-04
 */
add_action('wp_head', 'lark_favicon_head');
function lark_favicon_head()
{
    $path = get_stylesheet_directory_uri(); ?>

<link rel="apple-touch-icon" sizes="57x57" href="<?php echo $path; ?>/resources/assets/favicon/apple-icon-57x57.png">
<link rel="apple-touch-icon" sizes="60x60" href="<?php echo $path; ?>/resources/assets/favicon/apple-icon-60x60.png">
<link rel="apple-touch-icon" sizes="72x72" href="<?php echo $path; ?>/resources/assets/favicon/apple-icon-72x72.png">
<link rel="apple-touch-icon" sizes="76x76" href="<?php echo $path; ?>/resources/assets/favicon/apple-icon-76x76.png">
<link rel="apple-touch-icon" sizes="114x114" href="<?php echo $path; ?>/resources/assets/favicon/apple-icon-114x114.png">
<link rel="apple-touch-icon" sizes="120x120" href="<?php echo $path; ?>/resources/assets/favicon/apple-icon-120x120.png">
<link rel="apple-touch-icon" sizes="144x144" href="<?php echo $path; ?>/resources/assets/favicon/apple-icon-144x144.png">
<link rel="apple-touch-icon" sizes="152x152" href="<?php echo $path; ?>/resources/assets/favicon/apple-icon-152x152.png">
<link rel="apple-touch-icon" sizes="180x180" href="<?php echo $path; ?>/resources/assets/favicon/apple-icon-180x180.png">
<link rel="icon" type="image/png" sizes="192x192"  href="<?php echo $path; ?>/resources/assets/favicon/android-icon-192x192.png">
<link rel="icon" type="image/png" sizes="32x32" href="<?php echo $path; ?>/resources/assets/favicon/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="96x96" href="<?php echo $path; ?>/resources/assets/favicon/favicon-96x96.png">
<link rel="icon" type="image/png" sizes="16x16" href="<?php echo $path; ?>/resources/assets/favicon/favicon-16x16.png">
<link rel="shortcut icon" href="<?php echo $path; ?>/resources/assets/favicon/favicon-16x16.png">
<link rel="manifest" href="<?php echo $path; ?>/resources/assets/favicon/manifest.json">
<meta name="msapplication-TileColor" content="#ffffff">
<meta name="msapplication-TileImage" content="<?php echo $path; ?>/resources/assets/favicon/ms-icon-144x144.png">
<meta name="theme-color" content="#ffffff">
<?php
}

/**
 * Inject critical CSS inline stylesheet created with Laravel Mix https://laravel-mix.com/extensions/criticalcss
 *
 * @author Dean Appleton-Claydon
 * @date   2020-07-04
 */
add_action('wp_head', function (): void {
    $path = get_stylesheet_directory();

    if (is_front_page()) {
        $critical_css = 'home_critical.min.css';
    // } elseif (is_singular()) {
    //     $critical_css = 'single_critical.css';
    } else {
        $critical_css = '404_critical.min.css';
    }

    $file = $path.'/dist/critical/'.$critical_css;
    if (file_exists($file)) {
        echo '<style id="critical-css">'.file_get_contents($file).'</style>';
    }
}, 1);