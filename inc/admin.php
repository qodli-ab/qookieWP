<?php

// Check for direct access
if (!defined('ABSPATH')) exit;

define('QQ_LOGO_URL', 'https://cdn.qookie.cloud/media/QookieQloud-Logo.png');

/**
 * Add admin notice if the domain is not registered
 */
function qqm_display_admin_notice() {
    $domain_status = get_option('qqm_domain_registered', 'not_checked');

    // Show notice if domain is not registered
    if ($domain_status === 'not_registered') {
        echo '<div class="notice notice-warning" style="display: flex; align-items: center;">
                <img src="' . esc_url(QQ_LOGO_URL) . '" alt="QookieQloud™ Logo" style="width: 30px; height: auto; margin-right: 10px;">
                <p><strong>QookieQloud™ Notice:</strong> This domain is not registered with QookieQloud™. Please <a href="' . esc_url(QOOKIE_REGISTER_URL) . '" target="_blank">register or log in</a> to add your domain and activate the Cookie-Consent-Manager.</p>
              </div>';
    } else {
        //error_log("Admin notice suppressed. Domain status: " . $domain_status);
    }
}
add_action('admin_notices', 'qqm_display_admin_notice');

/**
 * Add plugin settings page in the WordPress admin menu
 */
function qqm_add_settings_page() {
    add_options_page(
        'QookieQloud Settings',
        'QookieQloud',
        'manage_options',
        'qookieqloud',
        'qqm_render_settings_page'
    );
}
add_action('admin_menu', 'qqm_add_settings_page');

/**
 * Render the settings page content
 */
function qqm_render_settings_page() {
    ?>
    <div class="wrap">
        <img src="<?php echo esc_url(QQ_LOGO_URL);?>" alt="QookieQloud Logo" style="max-width: 150px; margin-bottom: 20px; position: absolute; top: 20px; right: 20px;">

        <h1>QookieQloud™ Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('qqm_settings');
            do_settings_sections('qookieqloud');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

/**
 * Register the settings and fields for the settings page
 */
function qqm_register_settings() {
    register_setting('qqm_settings', 'qqm_load_for_logged_in', ['default' => 'public']);

    add_settings_section(
        'qqm_general_settings',
        'General Settings',
        null,
        'qookieqloud'
    );

    add_settings_field(
        'qqm_load_for_logged_in',
        'Show Cookie Consent Manager',
        'qqm_load_for_logged_in_render',
        'qookieqloud',
        'qqm_general_settings'
    );
}
add_action('admin_init', 'qqm_register_settings');

/**
 * Render the field for loading the consent manager script
 */
function qqm_load_for_logged_in_render() {
    $value = get_option('qqm_load_for_logged_in', 'public');
    ?>
    <select name="qqm_load_for_logged_in">
        <option value="public" <?php selected($value, 'public'); ?>>Public Visitors Only</option>
        <option value="all" <?php selected($value, 'all'); ?>>All Visitors (Including Logged In)</option>
    </select>
    <p class="description">Choose whether the QookieQloud™ Consentmanager script should load for public visitors only or for everyone.</p>
    <?php
}


/**
 * Handle the re-check action for domain registration
 */
function qqm_recheck_domain() {
    // Verify nonce for security
    $nonce = isset($_GET['nonce']) ? wp_unslash($_GET['nonce']) : '';
    if (!wp_verify_nonce(sanitize_text_field($nonce), 'qqm_recheck_nonce')) {
        wp_die('Security check failed');
    }

    // Re-run domain registration check
    qqm_check_domain_registration();

    // Redirect back to the plugins page after re-check
    wp_safe_redirect(admin_url('plugins.php'));
    exit;
}

// Conditionally add action only when needed
function qqm_maybe_add_recheck_action() {
    $action = isset($_GET['action']) ? wp_unslash($_GET['action']) : '';
    if (sanitize_text_field($action) === 'qqm_recheck_domain') {
        add_action('admin_init', 'qqm_recheck_domain');
    }
}
add_action('admin_init', 'qqm_maybe_add_recheck_action');
