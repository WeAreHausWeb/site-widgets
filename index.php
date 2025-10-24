<?php
/**
 * Plugin Name: Site widgets
 * Plugin URI: https://plugins.webien.io
 * Author: Webien
 * Description: Site specific widgets.
 * Author URI: https://plugins.webien.io
 */

define('WEBIEN_SITE_PLUGIN_PATH', __DIR__);
define('WEBIEN_SITE_PLUGIN_URI', plugin_dir_url(__FILE__));


if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}


if (file_exists(WEBIEN_SITE_PLUGIN_PATH . '/vendor/autoload.php')) {
    require WEBIEN_SITE_PLUGIN_PATH . '/vendor/autoload.php';
}

// Includes
// require_once WEBIEN_SITE_PLUGIN_PATH . '/src/XXX.php';


// Register Elementor Widgets
add_action('init', function () {
    if (!defined('WP_INSTALLING') && did_action('elementor/loaded') && class_exists('acf')) {

        /*
        \Elementor\Plugin::instance()
            ->widgets_manager
            ->register(new \Webien\Site\XXX\Widget());
        */

    }

});


add_action('wp_enqueue_scripts', function () {

    $ver = '1';
    //$ver = $_ENV['WP_ENV'] === 'production' ? '' : '?time=' . time();

    wp_enqueue_style('webien-site-widgets',  plugin_dir_url( __FILE__ ) . 'dist/style.css', [], $ver);
    wp_enqueue_script('webien-site-widgets',  plugin_dir_url( __FILE__ ) . 'dist/script.js', [], $ver, true);

    // Register to only call when used.
    //wp_register_script('webien-site-app',  plugin_dir_url( __FILE__ ) . 'dist/xxx.js', [], $ver, true);


    // Ajax connection
    wp_localize_script('webien-site-widgets', 'webienSite', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wp_rest'),
    ]);
});


// Admin enqueues
function webien_site_widgets_enqueue_admin() {
    wp_enqueue_style('webien-site-widgets-admin',  plugin_dir_url( __FILE__ ) . 'dist/admin.css', [], false);
}
add_action( 'admin_enqueue_scripts', 'webien_site_widgets_enqueue_admin' );
