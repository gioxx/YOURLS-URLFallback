<?php
/*
Plugin Name: YOURLS URL Fallback
Plugin URI: https://github.com/gioxx/YOURLS-URLFallback
Description: Redirect visitors to a configurable fallback URL when they hit a non-existent short URL or the YOURLS root page.
Version: 1.1.2
Author: Gioxx
Author URI: https://gioxx.org
Text Domain: yourls-url-fallback
Domain Path: /languages
*/

if ( !defined( 'YOURLS_ABSPATH' ) ) die();

define( 'URL_FALLBACK_VERSION',    '1.1.2' );
define( 'URL_FALLBACK_GITHUB_API', 'https://api.github.com/repos/gioxx/YOURLS-URLFallback/releases/latest' );
define( 'URL_FALLBACK_GITHUB_URL', 'https://github.com/gioxx/YOURLS-URLFallback' );
define( 'URL_FALLBACK_PLUGIN_DIR', dirname( __FILE__ ) );

$uf_inc = URL_FALLBACK_PLUGIN_DIR . '/inc/';
require_once $uf_inc . 'helpers.php';
require_once $uf_inc . 'update-check.php';
require_once $uf_inc . 'intercept.php';
require_once $uf_inc . 'admin-page.php';

yourls_add_action( 'plugins_loaded', 'url_fallback_add_page' );
function url_fallback_add_page() {
    yourls_register_plugin_page( 'url_fallback', 'URL Fallback', 'url_fallback_config_page' );
}

yourls_add_filter( 'plugin_page_title_url_fallback', 'url_fallback_page_title_with_badge' );
