<?php

function url_fallback_print_admin_assets() {
    $css_file = URL_FALLBACK_PLUGIN_DIR . '/assets/admin.css';
    $js_file  = URL_FALLBACK_PLUGIN_DIR . '/assets/admin.js';
    $css_url  = url_fallback_asset_url( 'assets/admin.css' );
    $js_url   = url_fallback_asset_url( 'assets/admin.js' );
    $css_ver  = file_exists( $css_file ) ? filemtime( $css_file ) : URL_FALLBACK_VERSION;
    $js_ver   = file_exists( $js_file )  ? filemtime( $js_file )  : URL_FALLBACK_VERSION;

    if ( $css_url !== '' ) {
        echo '<link rel="stylesheet" href="' . yourls_esc_url( $css_url ) . '?v=' . $css_ver . '" />';
    }
    if ( $js_url !== '' ) {
        echo '<script src="' . yourls_esc_url( $js_url ) . '?v=' . $js_ver . '"></script>';
    }
}

function url_fallback_config_page() {
    $messages = [];

    if ( isset( $_POST['url_fallback_save'] ) ) {
        yourls_verify_nonce( 'url_fallback_config' );
        $result     = url_fallback_save_settings();
        $messages[] = [
            'type' => $result['success'] ? 'success' : 'warning',
            'text' => $result['text'],
        ];
    }

    if ( isset( $_POST['url_fallback_reset'] ) ) {
        yourls_verify_nonce( 'url_fallback_reset', isset( $_POST['nonce_reset'] ) ? $_POST['nonce_reset'] : '' );
        url_fallback_reset_settings();
        $messages[] = [ 'type' => 'warning', 'text' => 'Settings reset to default.' ];
    }

    $fallback_url  = yourls_get_option( 'url_fallback_url' );
    $redirect_type = yourls_get_option( 'url_fallback_redirect_type' );
    if ( !in_array( (int) $redirect_type, [ 301, 302 ], true ) ) {
        $redirect_type = 302;
    }
    $redirect_type = (int) $redirect_type;
    $nonce_config  = yourls_create_nonce( 'url_fallback_config' );
    $nonce_reset   = yourls_create_nonce( 'url_fallback_reset' );

    $ai_active = url_fallback_is_alternative_index_active();

    url_fallback_print_admin_assets();
    url_fallback_show_update_notice();

    echo '<div class="uf-header">';
    echo '<h2 class="uf-title">&#8618; <span class="uf-title-text">URL Fallback</span></h2>';
    echo '<p class="uf-version">Version ' . URL_FALLBACK_VERSION . '</p>';
    echo '</div>';

    foreach ( $messages as $msg ) {
        echo '<div class="notice notice-' . $msg['type'] . '"><p>' . $msg['text'] . '</p></div>';
    }

    if ( $ai_active ) {
        echo '<div class="uf-compat-notice uf-compat-notice--ok">';
        echo '<strong>&#9989; AlternativeIndex detected &amp; active</strong><br>';
        echo 'The root page (<code>/</code>) is managed by <strong>AlternativeIndex</strong>. ';
        echo 'URL Fallback will only redirect visitors hitting <strong>unknown short URLs</strong> &mdash; ';
        echo 'root page interception is automatically disabled to avoid conflicts.';
        echo '</div>';
    }

    echo '<form method="post" class="uf-form">';
    echo '<input type="hidden" name="nonce" value="' . $nonce_config . '" />';

    echo '<div class="uf-panel">';
    echo '<h3>Fallback Settings</h3>';

    echo '<div class="uf-row">';
    echo '<label for="url_fallback_url">Fallback URL</label>';
    echo '<small>Visitors hitting a missing short URL';
    if ( !$ai_active ) echo ' or the YOURLS root page';
    echo ' will be redirected here.</small>';
    echo '<input type="text" name="url_fallback_url" id="url_fallback_url" ';
    echo 'value="' . yourls_esc_attr( $fallback_url ) . '" placeholder="https://example.com" />';
    echo '</div>';

    echo '<div class="uf-row">';
    echo '<label>Redirect Type</label>';
    echo '<small>Use 302 while testing, switch to 301 once the destination is stable (301 is cached by browsers).</small>';
    echo '<div class="uf-radio-group">';
    echo '<label><input type="radio" name="url_fallback_redirect_type" value="302" ';
    echo ( $redirect_type === 302 ? 'checked' : '' ) . ' /> 302 &ndash; Temporary Redirect</label>';
    echo '<label><input type="radio" name="url_fallback_redirect_type" value="301" ';
    echo ( $redirect_type === 301 ? 'checked' : '' ) . ' /> 301 &ndash; Permanent Redirect</label>';
    echo '</div>';
    echo '</div>';

    echo '</div>'; // .uf-panel

    echo '<div class="uf-info-box">';
    echo '<h4 class="uf-info-title"><span class="uf-info-icon">i</span>Notes</h4>';
    echo '<ul class="uf-info-list">';
    echo '<li>When <strong>no fallback URL is set</strong>, YOURLS behaves as normal (default 404 page).</li>';
    echo '<li>The redirect fires for <strong>any unknown keyword</strong>';
    if ( !$ai_active ) echo ' and when visiting the <strong>YOURLS root URL</strong> directly';
    echo '.</li>';
    echo '<li>Valid short URLs continue to work without any change.</li>';
    echo '<li>Short URLs ending with <strong>+</strong> (YOURLS stats pages) are always passed through normally.</li>';
    if ( $ai_active ) {
        echo '<li><strong>AlternativeIndex</strong> is active: the root page is handled by that plugin and URL Fallback will not intercept it.</li>';
    }
    echo '</ul>';
    echo '</div>';

    echo '<div class="uf-actions">';
    echo '<button type="submit" name="url_fallback_save" class="button">&#128190; Save Settings</button>';
    echo '<button type="submit" name="url_fallback_reset" class="button" ';
    echo 'onclick="return confirm(\'Are you sure you want to reset all settings?\');" formnovalidate>';
    echo '&#128260; Reset to Default</button>';
    echo '<input type="hidden" name="nonce_reset" value="' . $nonce_reset . '" />';
    echo '</div>';

    echo '</form>';

    echo '<div class="plugin-footer">';
    echo '<div class="plugin-footer-top">';
    echo '<span>';
    echo '<a href="https://yourls.gioxx.org/plugins/url-fallback" target="_blank" rel="noopener noreferrer">&#8618; URL Fallback</a>';
    echo '&nbsp;&middot;&nbsp;';
    echo '<img src="https://github.githubassets.com/favicons/favicon.png" class="github-icon" alt="" />';
    echo '<a href="' . URL_FALLBACK_GITHUB_URL . '" target="_blank" rel="noopener noreferrer">GitHub</a>';
    echo '</span>';
    echo '<a href="#" onclick="window.scrollTo({top:0,behavior:\'smooth\'});return false;">&#8593; Back to top</a>';
    echo '</div>';
    echo '<span>&#10084;&#65039; Lovingly developed by the usually-on-vacation brain cell of ';
    echo '<a href="https://github.com/gioxx" target="_blank" rel="noopener noreferrer">Gioxx</a> &ndash; ';
    echo '<a href="https://gioxx.org" target="_blank" rel="noopener noreferrer">Gioxx\'s Wall</a></span>';
    echo '</div>';
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
