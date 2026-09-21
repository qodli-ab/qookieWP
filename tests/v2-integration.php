<?php
// Offline contract tests: no WordPress database or external requests.
define('ABSPATH', __DIR__);
$options = $transients = $hooks = $filters = $requests = $scripts = [];
$can = true; $user = 1; $session = 'session-one'; $salt = 'test-salt'; $loggedIn = false;
$response = ['code' => 200, 'body' => '{}'];
class Redirected extends Exception {}
class Denied extends Exception {}
class WP_Error {}
function add_action($name, $fn, ...$rest) { $GLOBALS['hooks'][$name] = $fn; }
function add_filter($name, $fn, ...$rest) { $GLOBALS['filters'][$name] = $fn; }
function get_option($name, $default = false) { return $GLOBALS['options'][$name] ?? $default; }
function add_option($name, $value, ...$rest) { if (isset($GLOBALS['options'][$name])) return false; $GLOBALS['options'][$name] = $value; return true; }
function update_option($name, $value, ...$rest) { $GLOBALS['options'][$name] = $value; return true; }
function delete_option($name) { unset($GLOBALS['options'][$name]); }
function get_transient($name) { return $GLOBALS['transients'][$name] ?? false; }
function set_transient($name, $value, $ttl) { $GLOBALS['transients'][$name] = $value; return true; }
function delete_transient($name) { unset($GLOBALS['transients'][$name]); }
function wp_salt($type) { return $GLOBALS['salt']; }
function home_url($path = '') { return 'http://127.0.0.1:8099/site' . $path; }
function admin_url($path = '') { return 'http://127.0.0.1:8099/site/wp-admin/' . $path; }
function wp_json_encode($data) { return json_encode($data); }
function current_user_can($cap) { return $GLOBALS['can']; }
function get_current_user_id() { return $GLOBALS['user']; }
function wp_get_session_token() { return $GLOBALS['session']; }
function nocache_headers() {}
function esc_html__($s, $domain) { return $s; }
function esc_attr($s) { return htmlspecialchars($s, ENT_QUOTES); }
function wp_die(...$args) { throw new Denied(); }
function check_admin_referer($name) { if (($_POST['_wpnonce'] ?? '') !== $name) throw new Denied(); }
function wp_unslash($s) { return $s; }
function wp_redirect($url) { throw new Redirected($url); }
function wp_safe_redirect($url) { throw new Redirected($url); }
function wp_remote_request($url, $args) { $GLOBALS['requests'][] = [$url, $args]; return $GLOBALS['response']; }
function is_wp_error($r) { return $r instanceof WP_Error; }
function wp_remote_retrieve_response_code($r) { return $r['code']; }
function wp_remote_retrieve_body($r) { return $r['body']; }
function is_user_logged_in() { return $GLOBALS['loggedIn']; }
function wp_enqueue_script(...$args) { $GLOBALS['scripts'][] = $args; }
require dirname(__DIR__) . '/inc/v2.php';
function verify($condition, $message) { if (!$condition) throw new Exception($message); $GLOBALS['checks'] = ($GLOBALS['checks'] ?? 0) + 1; }
function redirect_from($fn) { try { $fn(); } catch (Redirected $e) { return $e->getMessage(); } throw new Exception('Expected redirect'); }
function denied($fn) { try { $fn(); } catch (Denied $e) { verify(true, 'denied'); return; } throw new Exception('Expected denial'); }
function start_attempt() {
    $_SERVER['REQUEST_METHOD'] = 'POST'; $_POST['_wpnonce'] = 'qookieqloud_connect';
    $url = redirect_from('qookieqloud_v2_start');
    parse_str(parse_url($url, PHP_URL_QUERY), $query);
    return $query;
}
verify(qookieqloud_api_mode() === 'v2', 'New installation uses v2');
$options = ['qookieqloud_domain_registered' => 'registered'];
verify(qookieqloud_api_mode() === 'v1', 'Existing installation stays on v1');
$can = false; denied('qookieqloud_v2_start'); $can = true;
$_SERVER['REQUEST_METHOD'] = 'GET'; denied('qookieqloud_v2_start');
$_SERVER['REQUEST_METHOD'] = 'POST'; denied('qookieqloud_v2_start');
$query = start_attempt();
$attemptKey = 'qq_v2_attempt_' . hash('sha256', $query['state']);
$attempt = $transients[$attemptKey];
verify($query['redirect_uri'] === admin_url('admin-post.php'), 'Callback preserves path and port');
verify($query['integration'] === 'wordpress', 'WordPress integration identity');
verify($query['code_challenge'] === rtrim(strtr(base64_encode(hash('sha256', $attempt['verifier'], true)), '+/', '-_'), '='), 'PKCE S256');
$_GET = ['state' => $query['state'], 'auth_code' => str_repeat('a', 64)];
$session = 'other'; redirect_from('qookieqloud_v2_callback');
verify(!$requests, 'Other browser session cannot exchange'); $session = 'session-one';
$user = 2; redirect_from('qookieqloud_v2_callback'); verify(!$requests, 'Other admin cannot exchange'); $user = 1;
$transients[$attemptKey]['expires'] = time() - 1; redirect_from('qookieqloud_v2_callback'); verify(!$requests, 'Expired attempt denied');
$transients[$attemptKey] = $attempt;
$options[$attemptKey . '_lock'] = time(); redirect_from('qookieqloud_v2_callback');
verify(!$requests, 'Concurrent exchange cannot acquire lock'); unset($options[$attemptKey . '_lock']);

$record = ['installation_id' => 42, 'domain' => 'customer.example', 'site_key' => 'qq_pk_' . str_repeat('b',64), 'server_secret' => 'qq_sec_' . str_repeat('c',64)];
$response = ['code' => 200, 'body' => json_encode($record)];
redirect_from('qookieqloud_v2_callback');
verify(qookieqloud_v2_connection() === $record, 'Successful pairing stores connection');
verify(qookieqloud_api_mode() === 'v2', 'Successful pairing switches mode');
verify(strpos(base64_decode($options['qookieqloud_v2_connection']), $record['server_secret']) === false, 'Private key encrypted at rest');
verify(json_decode($requests[0][1]['body'], true)['code_verifier'] === $attempt['verifier'], 'Exchange sends verifier');
redirect_from('qookieqloud_v2_callback'); verify(count($requests) === 1, 'Callback cannot replay');
$salt = 'changed'; verify(qookieqloud_v2_connection() === null, 'Changed salt cannot decrypt'); $salt = 'test-salt';
qookieqloud_v2_enqueue();
verify($scripts[1][1] === 'https://cf-cdn.qookieqloud.com/v2/consentLoader.js', 'v2 loader selected');
$tag = $filters['script_loader_tag']('<script src="loader"></script>', 'qookieqloud-v2');
verify(strpos($tag, $record['site_key']) !== false && strpos($tag, $record['server_secret']) === false, 'Only public key in HTML');
$scripts = []; $loggedIn = true; qookieqloud_v2_enqueue(); verify(!$scripts, 'Logged-in visitors excluded by default');
$options['qookieqloud_load_for_logged_in'] = 'all'; qookieqloud_v2_enqueue(); verify(count($scripts) === 2, 'All visitors setting respected');
$scripts = []; $options['qookieqloud_banner_enabled'] = 0; qookieqloud_v2_enqueue(); verify(!$scripts, 'Paused banner omitted');
$response = ['code' => 200, 'body' => json_encode(['domain' => 'wrong.example', 'consents_today' => 99])];
verify(qookieqloud_v2_stats() === [], 'Wrong domain statistics rejected');
$response['body'] = json_encode(['domain' => 'customer.example', 'consents_today' => 4]);
verify(qookieqloud_v2_stats()['consents_today'] === 4, 'Private API statistics returned');
$last = end($requests); verify($last[1]['headers']['Authorization'] === 'Bearer ' . $record['server_secret'] && $last[1]['redirection'] === 0, 'Private bearer cannot follow redirects');
$query = start_attempt(); $_GET = ['state' => $query['state'], 'auth_code' => str_repeat('a',64)];
$response = new WP_Error(); redirect_from('qookieqloud_v2_callback');
verify(qookieqloud_v2_connection() === $record, 'Failed reconnect preserves connection');
$_POST['_wpnonce'] = 'qookieqloud_disconnect';
redirect_from($hooks['admin_post_qookieqloud_disconnect']);
verify(qookieqloud_v2_connection() === $record, 'Failed revoke preserves connection');
$response = ['code' => 204, 'body' => '']; redirect_from($hooks['admin_post_qookieqloud_disconnect']);
verify(qookieqloud_v2_connection() === null && qookieqloud_api_mode() === 'v2', 'Disconnect does not restore v1');
$scripts = []; qookieqloud_v2_enqueue(); verify(!$scripts, 'Disconnected installation loads no banner');
qookieqloud_v2_save_connection($record);
$response = ['code' => 401, 'body' => '']; redirect_from($hooks['admin_post_qookieqloud_disconnect']);
verify(qookieqloud_v2_connection() === null, 'Already revoked credential can disconnect locally');
verify(!qookieqloud_v2_valid_connection(['installation_id'=>1,'domain'=>'x','site_key'=>[],'server_secret'=>[]]), 'Malformed credentials rejected');
echo $checks . " checks passed.\n";
