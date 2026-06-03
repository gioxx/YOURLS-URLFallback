<?php

function url_fallback_remote_get( $url ) {
    $ch = curl_init();
    curl_setopt_array( $ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT      => 'YOURLS-URLFallback/' . URL_FALLBACK_VERSION,
        CURLOPT_TIMEOUT        => 5,
    ] );
    $response  = curl_exec( $ch );
    $http_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
    curl_close( $ch );
    if ( $http_code !== 200 || $response === false ) return null;
    return json_decode( $response, true );
}

function url_fallback_asset_url( $relative_path ) {
    $relative_path = ltrim( (string) $relative_path, '/' );
    $plugin_dir    = URL_FALLBACK_PLUGIN_DIR;

    if ( function_exists( 'yourls_plugin_url' ) ) {
        return rtrim( (string) yourls_plugin_url( $plugin_dir ), '/' ) . '/' . $relative_path;
    }

    if ( defined( 'YOURLS_PLUGINDIRURL' ) ) {
        $slug = basename( $plugin_dir );
        return rtrim( (string) YOURLS_PLUGINDIRURL, '/' ) . '/' . $slug . '/' . $relative_path;
    }

    if ( defined( 'YOURLS_SITE' ) && defined( 'YOURLS_ABSPATH' ) ) {
        $rel = str_replace( '\\', '/', str_replace( (string) YOURLS_ABSPATH, '', $plugin_dir ) );
        $rel = trim( $rel, '/' );
        return rtrim( (string) YOURLS_SITE, '/' ) . '/' . $rel . '/' . $relative_path;
    }

    return '';
}
