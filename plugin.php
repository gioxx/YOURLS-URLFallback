<?php
/*
Plugin Name: YOURLS URL Fallback
Plugin URI: https://github.com/gioxx/YOURLS-URLFallback
Description: Redirect visitors to a configurable fallback URL when they hit a non-existent short URL or the YOURLS root page.
Version: 1.0.0
Author: Gioxx
Author URI: https://gioxx.org
Text Domain: yourls-url-fallback
Domain Path: /languages
*/

if ( !defined( 'YOURLS_ABSPATH' ) ) die();

define( 'URL_FALLBACK_VERSION', '1.0.0' );
define( 'URL_FALLBACK_GITHUB_API', 'https://api.github.com/repos/gioxx/YOURLS-URLFallback/releases/latest' );
define( 'URL_FALLBACK_GITHUB_URL', 'https://github.com/gioxx/YOURLS-URLFallback/releases/latest' );

// Register plugin settings page
yourls_add_action( 'plugins_loaded', 'url_fallback_add_page' );
function url_fallback_add_page() {
    yourls_register_plugin_page( 'url_fallback', 'URL Fallback', 'url_fallback_config_page' );
}

// Update check
yourls_add_action( 'plugins_loaded', 'url_fallback_update_check_setup' );
function url_fallback_update_check_setup() {
    yourls_add_action( 'admin_notices', 'url_fallback_show_update_notice' );
    yourls_add_filter( 'plugin_page_title_url_fallback', 'url_fallback_page_title_with_badge' );
}

function url_fallback_show_update_notice() {
    static $checked = false;
    static $update_available = false;
    static $latest_version = '';
    static $release_url = '';

    if ( $checked ) {
        if ( $update_available ) {
            echo '<div class="notice notice-info">';
            echo '&#x1F195; <strong>YOURLS URL Fallback</strong>: New version available: <strong>' . $latest_version . '</strong>! ';
            echo '<a href="' . $release_url . '" target="_blank">View details on GitHub</a>';
            echo '</div>';
        }
        return;
    }

    $checked = true;
    $response = url_fallback_remote_get( URL_FALLBACK_GITHUB_API );
    if ( !$response || !isset( $response['tag_name'] ) ) return;

    $latest_version = ltrim( $response['tag_name'], 'v' );
    if ( version_compare( $latest_version, URL_FALLBACK_VERSION, '>' ) ) {
        $update_available = true;
        $release_url = $response['html_url'];

        echo '<div class="notice notice-info">';
        echo '&#x1F195; <strong>YOURLS URL Fallback</strong>: New version available: <strong>' . $latest_version . '</strong>! ';
        echo '<a href="' . $release_url . '" target="_blank">View details on GitHub</a>';
        echo '</div>';
    }
}

function url_fallback_page_title_with_badge( $title ) {
    static $update_available = null;
    static $latest_version = '';

    if ( $update_available === null ) {
        $response = url_fallback_remote_get( URL_FALLBACK_GITHUB_API );
        if ( !$response || !isset( $response['tag_name'] ) ) {
            $update_available = false;
        } else {
            $latest_version = ltrim( $response['tag_name'], 'v' );
            $update_available = version_compare( $latest_version, URL_FALLBACK_VERSION, '>' );
        }
    }

    if ( $update_available ) {
        return $title . ' <span class="uf-update-badge">Update Available</span>';
    }

    return $title;
}

function url_fallback_remote_get( $url ) {
    $ch = curl_init();
    curl_setopt_array( $ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT      => 'YOURLS-URLFallback',
        CURLOPT_TIMEOUT        => 5,
    ] );
    $response  = curl_exec( $ch );
    $http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
    curl_close( $ch );

    if ( $http_code !== 200 || $response === false ) return null;
    return json_decode( $response, true );
}

// Intercept early — fires before YOURLS routes/redirects the request.
// Priority 99 ensures this runs after url_fallback_add_page (default priority 10)
// but before YOURLS processes the keyword.
yourls_add_action( 'plugins_loaded', 'url_fallback_intercept', 99 );
function url_fallback_intercept() {
    // Skip admin, API and AJAX contexts.
    if ( defined( 'YOURLS_ADMIN' )      && YOURLS_ADMIN      ) return;
    if ( defined( 'YOURLS_DOING_API' )  && YOURLS_DOING_API  ) return;
    if ( defined( 'YOURLS_DOING_AJAX' ) && YOURLS_DOING_AJAX ) return;

    $fallback_url = yourls_get_option( 'url_fallback_url' );
    if ( !$fallback_url ) return;

    // yourls_get_request() strips the leading slash and query string,
    // returning just the keyword (e.g. "valentina") or empty string for root.
    $keyword = function_exists( 'yourls_get_request' ) ? yourls_get_request() : '';

    // "abc+" is YOURLS native stats page — always let it through.
    if ( $keyword && substr( $keyword, -1 ) === '+' ) {
        return;
    }

    // If there is a keyword, check whether it actually exists in the DB.
    // yourls_get_keyword_longurl() returns the target URL or false/null.
    if ( $keyword && yourls_get_keyword_longurl( $keyword ) ) {
        return; // Valid short URL — let YOURLS handle it normally.
    }

    // Root URL or non-existent keyword: redirect to fallback.
    $redirect_type = (int) yourls_get_option( 'url_fallback_redirect_type' );
    if ( !in_array( $redirect_type, [ 301, 302 ], true ) ) {
        $redirect_type = 302;
    }

    header( 'Location: ' . $fallback_url, true, $redirect_type );
    die();
}

// Settings page
function url_fallback_config_page() {
    $messages = [];

    if ( isset( $_POST['url_fallback_save'] ) ) {
        yourls_verify_nonce( 'url_fallback_config' );
        $result = url_fallback_save_settings();
        $messages[] = [
            'type' => $result['success'] ? 'success' : 'warning',
            'text' => $result['text'],
        ];
    }

    if ( isset( $_POST['url_fallback_reset'] ) ) {
        yourls_verify_nonce( 'url_fallback_reset', $_POST['nonce_reset'] );
        url_fallback_reset_settings();
        $messages[] = [ 'type' => 'warning', 'text' => 'Settings reset to default.' ];
    }

    $fallback_url   = yourls_get_option( 'url_fallback_url' );
    $redirect_type  = yourls_get_option( 'url_fallback_redirect_type' );
    if ( !in_array( (int) $redirect_type, [ 301, 302 ], true ) ) {
        $redirect_type = 302;
    }
    $redirect_type  = (int) $redirect_type;
    $nonce_config   = yourls_create_nonce( 'url_fallback_config' );
    $nonce_reset    = yourls_create_nonce( 'url_fallback_reset' );

    url_fallback_print_admin_style();

    echo '<div class="uf-header">';
    echo '<h2 class="uf-title">&#8618; URL Fallback</h2>';
    echo '<p class="uf-version">Version: ' . URL_FALLBACK_VERSION . '</p>';
    echo '</div>';

    foreach ( $messages as $msg ) {
        echo '<div class="notice notice-' . $msg['type'] . '"><p>' . $msg['text'] . '</p></div>';
    }

    echo '<form method="post" class="uf-form">';
    echo '<input type="hidden" name="nonce" value="' . $nonce_config . '" />';

    echo '<div class="uf-panel">';
    echo '<h3 class="uf-heading">Fallback Settings</h3>';
    echo '<div class="uf-panel-body">';

    echo '<div class="uf-row">';
    echo '<label for="url_fallback_url">Fallback URL</label>';
    echo '<small>Visitors hitting a missing short URL or the YOURLS root page will be redirected here.</small>';
    echo '<input type="text" name="url_fallback_url" id="url_fallback_url" value="' . yourls_esc_attr( $fallback_url ) . '" placeholder="https://example.com" />';
    echo '</div>';

    echo '<div class="uf-row">';
    echo '<label>Redirect Type</label>';
    echo '<small>Use 302 while testing, switch to 301 once the destination is stable (301 is cached by browsers).</small>';
    echo '<div class="uf-radio-group">';
    echo '<label><input type="radio" name="url_fallback_redirect_type" value="302" ' . ( $redirect_type === 302 ? 'checked' : '' ) . ' /> 302 &ndash; Temporary Redirect</label>';
    echo '<label><input type="radio" name="url_fallback_redirect_type" value="301" ' . ( $redirect_type === 301 ? 'checked' : '' ) . ' /> 301 &ndash; Permanent Redirect</label>';
    echo '</div>';
    echo '</div>';

    echo '</div>'; // uf-panel-body
    echo '</div>'; // uf-panel

    echo '<div class="uf-info-box">';
    echo '<h4 class="uf-info-title"><span class="uf-info-icon">i</span>Notes</h4>';
    echo '<ul class="uf-info-list">';
    echo '<li>When <strong>no fallback URL is set</strong>, YOURLS behaves as normal (default 404 page).</li>';
    echo '<li>The redirect fires for <strong>any unknown keyword</strong> and when visiting the <strong>YOURLS root URL</strong> directly.</li>';
    echo '<li>Valid short URLs continue to work without any change.</li>';
    echo '<li>Short URLs ending with <strong>+</strong> (YOURLS stats pages) are always passed through normally.</li>';
    echo '</ul>';
    echo '</div>';

    echo '<div class="uf-actions">';
    echo '<input type="submit" name="url_fallback_save" value="&#128190; Save Settings" class="button button-primary" />';
    echo ' ';
    echo '<input type="submit" name="url_fallback_reset" value="&#8617; Reset to Default" class="button" onclick="return confirm(\'Are you sure you want to reset all settings?\');" formnovalidate />';
    echo '<input type="hidden" name="nonce_reset" value="' . $nonce_reset . '" />';
    echo '</div>';

    echo '<div class="uf-footer">';
    echo '<a href="https://github.com/gioxx/YOURLS-URLFallback" target="_blank" rel="noopener noreferrer">';
    echo '<img src="https://github.githubassets.com/favicons/favicon.png" class="github-icon" alt="GitHub Icon" />';
    echo 'YOURLS URL Fallback</a><br>';
    echo '&#10084;&#65039; Lovingly developed by the usually-on-vacation brain cell of ';
    echo '<a href="https://github.com/gioxx" target="_blank" rel="noopener noreferrer">Gioxx</a> &ndash; ';
    echo '<a href="https://gioxx.org" target="_blank" rel="noopener noreferrer">Gioxx\'s Wall</a>';
    echo '</div>';

    echo '</form>';
}

function url_fallback_save_settings() {
    $url = trim( $_POST['url_fallback_url'] ?? '' );

    if ( $url !== '' ) {
        if ( !preg_match( '#^https?://#i', $url ) ) {
            $url = 'https://' . $url;
        }
        if ( !filter_var( $url, FILTER_VALIDATE_URL ) ) {
            return [ 'success' => false, 'text' => 'Please enter a valid URL (e.g. https://example.com or www.example.com).' ];
        }
    }

    $redirect_type = (int) ( $_POST['url_fallback_redirect_type'] ?? 302 );
    if ( !in_array( $redirect_type, [ 301, 302 ], true ) ) {
        $redirect_type = 302;
    }

    yourls_update_option( 'url_fallback_url', $url );
    yourls_update_option( 'url_fallback_redirect_type', $redirect_type );

    return [ 'success' => true, 'text' => 'Settings saved successfully!' ];
}

function url_fallback_reset_settings() {
    yourls_delete_option( 'url_fallback_url' );
    yourls_delete_option( 'url_fallback_redirect_type' );
}

function url_fallback_print_admin_style() {
    echo '<style>
.uf-header        { display:flex; flex-direction:column; align-items:flex-start; gap:4px; margin-bottom:18px; }
.uf-title         { margin:0; font-size:1.68rem; font-weight:800; background:linear-gradient(135deg,#0b89c4,#15a2df); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
.uf-version       { margin:0; font-size:.76rem; color:#667885; }
.uf-panel         { background:#fff; border:1px solid #d9e6ef; border-radius:10px; margin-bottom:18px; box-shadow:0 1px 0 rgba(2,27,45,.04); padding:16px; }
.uf-heading       { margin:0 0 10px; font-size:1.06rem; font-weight:700; color:#3b4b55; }
.uf-row           { margin-bottom:12px; }
.uf-row:last-child{ margin-bottom:0; }
.uf-row label     { display:block; font-weight:700; margin-bottom:2px; color:#26323a; font-size:.84rem; }
.uf-row small     { display:block; color:#667885; margin-bottom:7px; font-size:.73rem; }
.uf-row input[type="text"] { width:100%; max-width:100%; height:32px; border:1px solid #c7d8e4; border-radius:8px; padding:0 9px; background:#fff; color:#26323a; }
.uf-radio-group   { display:flex; flex-direction:column; gap:6px; }
.uf-radio-group label { font-weight:normal; display:flex; align-items:center; gap:6px; font-size:.84rem; }
.uf-info-box      { background:#f5fbfe; border-left:4px solid #4ea1cf; border-radius:0 8px 8px 0; padding:8px 12px; margin-bottom:18px; }
.uf-info-title    { margin:0 0 6px; font-size:.88rem; font-weight:700; color:#0a5e87; display:flex; align-items:center; gap:6px; }
.uf-info-icon     { display:inline-block; width:14px; height:14px; line-height:14px; margin-right:6px; border-radius:3px; background:#86b8d8; color:#fff; font-size:11px; text-align:center; font-weight:700; vertical-align:1px; flex-shrink:0; }
.uf-info-list     { margin:0; padding-left:16px; }
.uf-info-list li  { margin:2px 0; font-size:.76rem; }
.uf-actions       { margin-top:14px; display:flex; flex-wrap:wrap; gap:8px; align-items:center; }
.uf-footer        { margin-top:28px; padding-top:18px; border-top:1px solid #d6e6f1; font-size:.8rem; color:#667885; text-align:center; line-height:1.7; }
.uf-footer a      { color:#0b89c4; text-decoration:none; }
.uf-footer a:hover{ color:#066d9d; text-decoration:underline; }
.uf-footer .github-icon { vertical-align:text-top; width:16px; height:16px; margin-right:4px; display:inline-block; }
.uf-update-badge  { display:inline-block; background:#0b89c4; color:#fff; font-size:.65rem; font-weight:700; padding:2px 7px; border-radius:10px; vertical-align:middle; margin-left:6px; text-transform:uppercase; letter-spacing:.04em; }
.notice-success   { background:#eafaf0; border-left:4px solid #3da56a; padding:10px 12px; border-radius:6px; margin-bottom:12px; }
.notice-warning   { background:#fff8e9; border-left:4px solid #d79a2b; padding:10px 12px; border-radius:6px; margin-bottom:12px; }
</style>';
}
