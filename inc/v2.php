<?php
if (!defined('ABSPATH')) exit;

// Existing installations stay on v1 until a successful, explicit connection.
function qookieqloud_api_mode() {
    $mode = get_option('qookieqloud_api_mode', false);
    if ($mode === false) {
        $mode = get_option('qookieqloud_domain_registered', false) !== false ? 'v1' : 'v2';
        add_option('qookieqloud_api_mode', $mode, '', false);
    }
    return $mode;
}
function qookieqloud_v2_connection() {
    $encoded = get_option('qookieqloud_v2_connection', '');
    if (!$encoded || !function_exists('openssl_decrypt')) return null;
    $raw = base64_decode($encoded, true);
    if ($raw === false || strlen($raw) < 29) return null;
    $key = hash('sha256', wp_salt('auth') . home_url('/'), true);
    $json = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', $key, OPENSSL_RAW_DATA, substr($raw, 0, 12), substr($raw, 12, 16));
    $record = $json === false ? null : json_decode($json, true);
    return is_array($record) && qookieqloud_v2_valid_connection($record) ? $record : null;
}
function qookieqloud_v2_valid_connection($data) {
    return is_array($data) && !empty($data['installation_id']) && is_string($data['domain'] ?? null)
        && $data['domain'] !== '' && is_string($data['site_key'] ?? null)
        && is_string($data['server_secret'] ?? null) && preg_match('/\Aqq_pk_[a-f0-9]{64}\z/D', $data['site_key'] ?? '')
        && preg_match('/\Aqq_sec_[a-f0-9]{64}\z/D', $data['server_secret'] ?? '');
}
function qookieqloud_v2_save_connection($data) {
    if (!qookieqloud_v2_valid_connection($data) || !function_exists('openssl_encrypt')) return false;
    $data = array_intersect_key($data, array_flip(['installation_id', 'domain', 'site_key', 'server_secret']));
    $iv = random_bytes(12);
    $tag = '';
    $encrypted = openssl_encrypt(wp_json_encode($data), 'aes-256-gcm', hash('sha256', wp_salt('auth') . home_url('/'), true), OPENSSL_RAW_DATA, $iv, $tag);
    if ($encrypted === false) return false;
    if (!update_option('qookieqloud_v2_connection', base64_encode($iv . $tag . $encrypted), false)) return false;
    update_option('qookieqloud_api_mode', 'v2', false);
    delete_transient('QOOKIEQLOUD_STATS_CACHE');
    return true;
}
function qookieqloud_v2_request($path, $method = 'GET', $data = null, $secret = null) {
    $headers = ['Accept' => 'application/json'];
    if ($secret) $headers['Authorization'] = 'Bearer ' . $secret;
    $args = ['method' => $method, 'headers' => $headers, 'timeout' => 15, 'redirection' => 0, 'sslverify' => true];
    if ($data !== null) {
        $args['headers']['Content-Type'] = 'application/json';
        $args['body'] = wp_json_encode($data);
    }
    return wp_remote_request('https://app.qookieqloud.com/api/v2/' . $path, $args);
}
function qookieqloud_v2_guard($nonce = null) {
    if (!current_user_can('manage_options')) wp_die(esc_html__('You cannot manage this connection.', 'qookieqloud'), '', ['response' => 403]);
    nocache_headers();
    header('Referrer-Policy: no-referrer');
    if ($nonce !== null) {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') wp_die('', '', ['response' => 405]);
        check_admin_referer($nonce);
    }
}
function qookieqloud_v2_result($message) {
    set_transient('qq_v2_notice_' . get_current_user_id(), $message, 60);
    wp_safe_redirect(admin_url('admin.php?page=qookieqloud'));
    exit;
}
function qookieqloud_v2_start() {
    qookieqloud_v2_guard('qookieqloud_connect');
    if (!function_exists('openssl_encrypt')) qookieqloud_v2_result('crypto');
    $state = 'qqwp_' . bin2hex(random_bytes(32));
    $verifier = bin2hex(random_bytes(32));
    // No fixed wp-admin path, action query or rewrite rules required.
    $callback = admin_url('admin-post.php');
    $attempt = ['verifier' => $verifier, 'callback' => $callback, 'user' => get_current_user_id(),
        'session' => hash('sha256', wp_get_session_token()), 'expires' => time() + 1200];
    if (!set_transient('qq_v2_attempt_' . hash('sha256', $state), $attempt, 1200)) qookieqloud_v2_result('failed');
    $url = 'https://app.qookieqloud.com/app/integrations/connect?' . http_build_query([
        'integration' => 'wordpress', 'state' => $state, 'redirect_uri' => $callback,
        'code_challenge_method' => 'S256',
        'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '='),
    ], '', '&', PHP_QUERY_RFC3986);
    wp_redirect($url); // Fixed, trusted production destination.
    exit;
}
add_action('admin_post_qookieqloud_connect', 'qookieqloud_v2_start');

function qookieqloud_v2_callback() {
    $state = isset($_GET['state']) && is_string($_GET['state']) ? wp_unslash($_GET['state']) : '';
    if (!preg_match('/\Aqqwp_[a-f0-9]{64}\z/D', $state)) return;
    qookieqloud_v2_guard();
    $attemptKey = 'qq_v2_attempt_' . hash('sha256', $state);
    $attempt = get_transient($attemptKey);
    if (!$attempt || $attempt['user'] !== get_current_user_id() || $attempt['expires'] < time()
        || !hash_equals($attempt['session'], hash('sha256', wp_get_session_token()))
        || $attempt['callback'] !== admin_url('admin-post.php')) qookieqloud_v2_result('failed');
    $lock = $attemptKey . '_lock';
    if (!add_option($lock, time(), '', false)) qookieqloud_v2_result('failed');
    // Consume before the exchange; an interrupted request requires a new attempt.
    delete_transient($attemptKey);
    $ok = false;
    try {
        $code = isset($_GET['auth_code']) && is_string($_GET['auth_code']) ? wp_unslash($_GET['auth_code']) : '';
        if (!isset($_GET['error']) && preg_match('/\A[a-f0-9]{64}\z/D', $code)) {
            $response = qookieqloud_v2_request('pairing/exchange', 'POST', [
                'auth_code' => $code, 'code_verifier' => $attempt['verifier'], 'redirect_uri' => $attempt['callback'],
            ]);
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                $ok = qookieqloud_v2_save_connection(json_decode(wp_remote_retrieve_body($response), true));
            }
        }
    } catch (Throwable $error) { /* Never log exchange credentials. */ }
    finally { delete_option($lock); }
    set_transient('qq_v2_notice_' . get_current_user_id(), $ok ? 'connected' : 'failed', 60);
    wp_safe_redirect(admin_url('admin-post.php?action=qookieqloud_complete'));
    exit;
}
add_action('admin_post', 'qookieqloud_v2_callback');
add_action('admin_post_nopriv', function () {
    if (isset($_GET['state']) && is_string($_GET['state']) && strpos($_GET['state'], 'qqwp_') === 0) {
        nocache_headers(); header('Referrer-Policy: no-referrer');
        wp_die(esc_html__('Sign in to WordPress and start a new connection from QookieQloud.', 'qookieqloud'));
    }
});
add_action('admin_post_qookieqloud_complete', function () {
    qookieqloud_v2_guard();
    $ok = get_transient('qq_v2_notice_' . get_current_user_id()) === 'connected';
    $parts = wp_parse_url(admin_url());
    $origin = $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');
    ?><!doctype html><html><head><meta charset="utf-8"><meta name="referrer" content="no-referrer"><title>QookieQloud</title></head><body>
    <p><?php echo esc_html($ok ? __('Connected. You can return to WordPress.', 'qookieqloud') : __('The connection could not be completed. Start again from WordPress.', 'qookieqloud')); ?></p>
    <a href="<?php echo esc_url(admin_url('admin.php?page=qookieqloud')); ?>"><?php esc_html_e('Return to QookieQloud', 'qookieqloud'); ?></a>
    <?php if ($ok): ?><script>if(window.opener&&!window.opener.closed){window.opener.postMessage({type:'qookie-connected'},<?php echo wp_json_encode($origin); ?>);window.close();}</script><?php endif; ?>
    </body></html><?php exit;
});
add_action('admin_post_qookieqloud_disconnect', function () {
    qookieqloud_v2_guard('qookieqloud_disconnect');
    $connection = qookieqloud_v2_connection();
    if ($connection) {
        $response = qookieqloud_v2_request('installation', 'DELETE', null, $connection['server_secret']);
        if (is_wp_error($response) || !in_array(wp_remote_retrieve_response_code($response), [204, 401], true)) qookieqloud_v2_result('failed');
    } elseif (get_option('qookieqloud_v2_connection')) {
        // Cannot revoke a credential we cannot decrypt: do not silently forget it.
        qookieqloud_v2_result('failed');
    }
    delete_option('qookieqloud_v2_connection');
    update_option('qookieqloud_api_mode', 'v2', false); // No silent v1 fallback after disconnect.
    delete_transient('QOOKIEQLOUD_STATS_CACHE');
    qookieqloud_v2_result('disconnected');
});
function qookieqloud_v2_stats() {
    $connection = qookieqloud_v2_connection();
    if (!$connection) return [];
    $response = qookieqloud_v2_request('installation/dashboard', 'GET', null, $connection['server_secret']);
    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) return [];
    $data = json_decode(wp_remote_retrieve_body($response), true);
    return is_array($data) && ($data['domain'] ?? null) === $connection['domain'] ? $data : [];
}
function qookieqloud_v2_enqueue() {
    $connection = qookieqloud_v2_connection();
    if (!$connection || !get_option('qookieqloud_banner_enabled', true)) return;
    if (get_option('qookieqloud_load_for_logged_in', 'public') !== 'all' && is_user_logged_in()) return;
    wp_enqueue_script('wp-consent-api');
    wp_enqueue_script('qookieqloud-v2', 'https://cf-cdn.qookieqloud.com/v2/consentLoader.js', ['wp-consent-api'], null, true);
}
add_filter('script_loader_tag', function ($tag, $handle) {
    if ($handle !== 'qookieqloud-v2') return $tag;
    $connection = qookieqloud_v2_connection();
    return $connection ? str_replace('<script ', '<script data-site-key="' . esc_attr($connection['site_key']) . '" ', $tag) : '';
}, 10, 2);
add_action('admin_post_qookieqloud_v2_settings', function () {
    qookieqloud_v2_guard('qookieqloud_v2_settings');
    update_option('qookieqloud_banner_enabled', isset($_POST['enabled']) ? 1 : 0, false);
    update_option('qookieqloud_load_for_logged_in', isset($_POST['logged_in']) ? 'all' : 'public');
    qookieqloud_v2_result('saved');
});
add_action('admin_enqueue_scripts', function ($hook) {
    if (strpos($hook, 'qookieqloud') === false && $hook !== 'index.php') return;
    wp_enqueue_style('qookieqloud-connection', plugins_url('../assets/connection.css', __FILE__), [], '2.1.3');
    wp_enqueue_script('qookieqloud-connection', plugins_url('../assets/connection.js', __FILE__), [], '2.1.0', true);
});
function qookieqloud_render_connection($compact = false) {
    if (!current_user_can('manage_options')) return;
    $connection = qookieqloud_v2_connection();
    $legacy = qookieqloud_api_mode() === 'v1';
    $stats = $connection ? qookieqloud_v2_stats() : [];
    require __DIR__ . '/connection-view.php';
}
function qookieqloud_connection_form($reconnect) {
    ?><form data-qq-connect <?php if ($reconnect): ?>data-confirm="<?php esc_attr_e('Change connection? The current connection remains until the new connection is approved and saved.', 'qookieqloud'); ?>"<?php endif; ?> method="post" target="qookie-connect" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <input type="hidden" name="action" value="qookieqloud_connect"><?php wp_nonce_field('qookieqloud_connect'); ?>
    <button class="qqwp-button" type="submit"><?php echo esc_html($reconnect ? __('Change connection', 'qookieqloud') : __('Connect to QookieQloud', 'qookieqloud')); ?> ↗</button></form><?php
}
