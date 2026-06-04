<?php

function url_fallback_get_update_info() {
    static $fetched  = false;
    static $info     = null;

    if ( !$fetched ) {
        $fetched  = true;
        $response = url_fallback_remote_get( URL_FALLBACK_GITHUB_API );
        if ( $response && isset( $response['tag_name'] ) ) {
            $latest = ltrim( $response['tag_name'], 'v' );
            $info   = [
                'latest_version'   => $latest,
                'update_available' => version_compare( $latest, URL_FALLBACK_VERSION, '>' ),
                'release_url'      => $response['html_url'] ?? '',
            ];
        }
    }

    return $info;
}

function url_fallback_show_update_notice() {
    $info = url_fallback_get_update_info();
    if ( !$info || !$info['update_available'] ) return;

    echo '<div class="notice notice-info uf-update-notice">';
    echo '&#x1F195; <strong>YOURLS URL Fallback</strong>: ';
    echo sprintf( yourls__( 'New version available: <strong>%s</strong>!', 'yourls-url-fallback' ), yourls_esc_attr( $info['latest_version'] ) );
    echo ' <a href="' . yourls_esc_url( $info['release_url'] ) . '" target="_blank" rel="noopener noreferrer">' . yourls__( 'View details on GitHub', 'yourls-url-fallback' ) . '</a>';
    echo '</div>';
}

function url_fallback_page_title_with_badge( $title ) {
    $info = url_fallback_get_update_info();
    if ( $info && $info['update_available'] ) {
        return $title . ' <span class="uf-update-badge">' . yourls__( 'Update Available', 'yourls-url-fallback' ) . '</span>';
    }
    return $title;
}
