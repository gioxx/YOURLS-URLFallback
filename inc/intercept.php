<?php

function url_fallback_is_alternative_index_active() {
    // defined('YAI_VERSION') reflects actual runtime state: true only when
    // AlternativeIndex is loaded, avoiding false positives from stale DB records.
    return defined( 'YAI_VERSION' );
}

yourls_add_action( 'plugins_loaded', 'url_fallback_intercept', 99 );
function url_fallback_intercept() {
    if ( defined( 'YOURLS_ADMIN' )      && YOURLS_ADMIN      ) return;
    if ( defined( 'YOURLS_DOING_API' )  && YOURLS_DOING_API  ) return;
    if ( defined( 'YOURLS_DOING_AJAX' ) && YOURLS_DOING_AJAX ) return;

    $fallback_url = yourls_get_option( 'url_fallback_url' );
    if ( !$fallback_url ) return;

    // yourls_get_request() returns the keyword (e.g. "foo") or empty string for root.
    $keyword = function_exists( 'yourls_get_request' ) ? yourls_get_request() : '';

    // Root URL: yield to AlternativeIndex if it is active.
    if ( $keyword === '' && url_fallback_is_alternative_index_active() ) {
        return;
    }

    // "abc+" is YOURLS stats page — let it through only if the keyword exists.
    if ( $keyword && substr( $keyword, -1 ) === '+' ) {
        if ( yourls_get_keyword_longurl( substr( $keyword, 0, -1 ) ) ) {
            return;
        }
        // Non-existent keyword + "+": fall through to fallback redirect.
    }

    // If there is a keyword (no "+"), check whether it actually exists in the DB.
    if ( $keyword && substr( $keyword, -1 ) !== '+' && yourls_get_keyword_longurl( $keyword ) ) {
        return;
    }

    $redirect_type = (int) yourls_get_option( 'url_fallback_redirect_type' );
    if ( !in_array( $redirect_type, [ 301, 302 ], true ) ) {
        $redirect_type = 302;
    }

    header( 'Location: ' . yourls_esc_url( $fallback_url ), true, $redirect_type );
    die();
}
