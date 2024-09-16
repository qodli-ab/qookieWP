<?php

/*
Plugin Name: QookieQloud Wordpress Plugin
Plugin URI: https://qookie.qodli.cloud
Description: Connects to and integrates Cookie-Consent-Manager from Qodli.
Version: 1.0
Author: Qod:li AB
Author URI: https://qodli.se
License: A "Slug" license name e.g. GPL2
*/

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enqueue the consentManager.js script and styles
 */
function ccm_enqueue_scripts() {
    // Enqueue the consent manager script
    wp_enqueue_script('consent-manager', 'https://qookie.qodli.cloud/js/consentLoader.js', array(), '1.0', true);

}
add_action('wp_enqueue_scripts', 'ccm_enqueue_scripts');
