<?php

/*
Plugin Name: QookieQloud™ Consent Management
Plugin URI: https://qookieqloud.com/wordpress
Description: Connects to and integrates Cookie-Consent-Manager from QookieQloud™ by Qodli AB.
Version: 1.4.1
Author: Qod:li AB
Author URI: https://qodli.se
License: GPLv2
*/

if (!defined('ABSPATH')) exit;

define('QOOKIEQLOUD_API_URL', 'https://app.qookie.cloud/api/v1/check-domain');
define('QOOKIEQLOUD_REGISTER_URL', 'https://app.qookie.cloud/login');
define('QOOKIEQLOUD_SECRET', 'dapfe1?Wutfix/cerhig');

// Include other files
require_once plugin_dir_path(__FILE__) . 'inc/helpers.php';
require_once plugin_dir_path(__FILE__) . 'inc/admin.php';
require_once plugin_dir_path(__FILE__) . 'inc/adminbar-eyes.php';
require_once plugin_dir_path(__FILE__) . 'inc/dashboard-widget.php';


/**
 * Check domain registration on activation
 */
function qookieqloud_check_domain_registration() {
    $domain = wp_parse_url(home_url(), PHP_URL_HOST);
    $data = wp_json_encode(['domain' => $domain]);

    // Generate authorization headers
    $auth = qookieqloud_generate_signature($domain);

    // Send POST request to your API
    $response = wp_remote_post(QOOKIEQLOUD_API_URL, [
        'body' => $data,
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-Timestamp' => $auth['timestamp'],
            'X-Signature' => $auth['signature'],
            'Authorization' => 'Bearer ' . QOOKIEQLOUD_SECRET,
        ],
    ]);

    if (is_wp_error($response)) {
        update_option('qookieqloud_domain_registered', 'not_checked');
        //error_log('API request failed: ' . $response->get_error_message());
        return;
    }

    $status_code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);

    // Check if the API responded with the expected data
    if ($status_code === 200 && !empty($body['registered'])) {
        $is_registered = $body['registered'] === true;
        update_option('qookieqloud_domain_registered', $is_registered ? 'registered' : 'not_registered');
        //error_log('Domain registration check successful: ' . ($is_registered ? 'registered' : 'not_registered'));
    } else {
        update_option('qookieqloud_domain_registered', 'not_registered');
        //error_log('Unexpected API response: ' . wp_remote_retrieve_body($response));
    }
}

register_activation_hook(__FILE__, 'qookieqloud_check_domain_registration');

// Conditionally add "Re-check" or "Registered" in the plugin actions
function qookieqloud_add_recheck_or_registered_link($links) {
    $domain_status = get_option('qookieqloud_domain_registered', 'not_checked');

    if ($domain_status === 'registered') {
        // Show "Registered" text if the domain is registered
        $registered_text = '<span style="color: #28a745; font-weight: bold;">Registered</span>';
        array_unshift($links, $registered_text);
    } else {
        // Add the "Re-check" link if the domain is not registered
        $recheck_url = add_query_arg([
            'action' => 'qookieqloud_recheck_domain',
            'nonce' => wp_create_nonce('qookieqloud_recheck_nonce')
        ], admin_url('admin.php'));

        $recheck_link = '<a href="' . esc_url($recheck_url) . '">Re-check</a>';
        array_unshift($links, $recheck_link);
    }

    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'qookieqloud_add_recheck_or_registered_link');


// Add a "Settings" link in the plugins list
function qookieqloud_add_settings_link($links) {
    // URL to the plugin's settings page
    $settings_url = add_query_arg('page', 'qookieqloud', admin_url('options-general.php'));

    // Add the link to the existing links array
    $settings_link = '<a href="' . esc_url($settings_url) . '">Settings</a>';
    array_unshift($links, $settings_link); // Add the link to the beginning of the array

    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'qookieqloud_add_settings_link');


/**
 * Enqueue the consent manager script
 */
function qookieqloud_enqueue_scripts() {
    $domain_registered = get_option('qookieqloud_domain_registered', 'not_checked');
    $load_setting = get_option('qookieqloud_load_for_logged_in', 'public');

    if ($domain_registered === 'registered') {
        // Ladda WP Consent API
        wp_enqueue_script('wp-consent-api');

        // Check if script should load for all users or only public users
        if ($load_setting === 'all' || ($load_setting === 'public' && !is_user_logged_in())) {
            wp_enqueue_script('consent-manager', 'https://app.qookie.cloud/js/consentLoader.js', ['wp-consent-api'], '1.2.x', true);
            wp_script_add_data('consent-manager', 'async', true);
        }
    }
}
add_action('wp_enqueue_scripts', 'qookieqloud_enqueue_scripts');
