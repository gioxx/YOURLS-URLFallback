<?php

function url_fallback_get_update_info() {
    static $fetched = false;
    static $info    = null;

    if ( $fetched ) return $info;
    $fetched = true;

    $cached = yourls_get_option( 'url_fallback_update_cache' );

    if (
        is_array( $cached )
        && !empty( $cached['checked_at'] )
        && !empty( $cached['latest_version'] )
        && ( time() - (int) $cached['checked_at'] ) < 86400
    ) {
        $info = [
            'latest_version'   => $cached['latest_version'],
            'update_available' => version_compare( $cached['latest_version'], URL_FALLBACK_VERSION, '>' ),
            'release_url'      => $cached['release_url'] ?? '',
        ];
        return $info;
    }

    $response = url_fallback_remote_get( URL_FALLBACK_GITHUB_API );
    if ( $response && isset( $response['tag_name'] ) ) {
        $latest      = ltrim( $response['tag_name'], 'v' );
        $release_url = $response['html_url'] ?? '';
        yourls_update_option( 'url_fallback_update_cache', [
            'checked_at'     => time(),
            'latest_version' => $latest,
            'release_url'    => $release_url,
        ] );
        $info = [
            'latest_version'   => $latest,
            'update_available' => version_compare( $latest, URL_FALLBACK_VERSION, '>' ),
            'release_url'      => $release_url,
        ];
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
