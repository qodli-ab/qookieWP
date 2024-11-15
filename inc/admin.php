<?php

// Check for direct access
if (!defined('ABSPATH')) exit;

define('QOOKIEQLOUD_LOGO_URL', plugins_url('../assets/QookieQloud-Logo.png', __FILE__));

/**
 * Add admin notice if the domain is not registered
 */
function qookieqloud_display_admin_notice() {
    $domain_status = get_option('qookieqloud_domain_registered', 'not_checked');

    // Show notice if domain is not registered
    if ($domain_status === 'not_registered') {
        echo '<div class="notice notice-warning" style="display: flex; align-items: center;">
                <img src="' . esc_url(QOOKIEQLOUD_LOGO_URL) . '" alt="QookieQloud™ Logo" style="width: 30px; height: auto; margin-right: 10px;">
                <p><strong>QookieQloud™ Notice:</strong> This domain is not registered with QookieQloud™. Please <a href="' . esc_url(QOOKIEQLOUD_REGISTER_URL) . '" target="_blank">register or log in</a> to add your domain and activate the Cookie-Consent-Manager.</p>
              </div>';
    } else {
        //error_log("Admin notice suppressed. Domain status: " . $domain_status);
    }
}
add_action('admin_notices', 'qookieqloud_display_admin_notice');

/**
 * Add plugin settings page in the WordPress admin menu
 */
function qookieqloud_add_settings_page() {
    add_options_page(
        'QookieQloud Settings',
        'QookieQloud',
        'manage_options',
        'qookieqloud',
        'qookieqloud_render_settings_page'
    );
}
add_action('admin_menu', 'qookieqloud_add_settings_page');

/**
 * Render the settings page content
 */
function qookieqloud_render_settings_page() {
    ?>
    <div class="wrap">
        <img src="<?php echo esc_url(QOOKIEQLOUD_LOGO_URL);?>" alt="QookieQloud Logo" style="max-width: 150px; margin-bottom: 20px; position: absolute; top: 20px; right: 20px;">

        <h1>QookieQloud™ Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('qookieqloud_settings');
            do_settings_sections('qookieqloud');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}


function qookieqloud_sanitize_load_setting($input) {
    // Validate and sanitize the input
    $allowed_values = ['public', 'private', 'logged_in']; // Specify allowed values
    if (in_array($input, $allowed_values, true)) {
        return $input; // Return the valid value
    }
    return 'public'; // Return the default value if invalid
}

/**
 * Register the settings and fields for the settings page
 */
function qookieqloud_register_settings() {
    register_setting(
        'qookieqloud_settings',
        'qookieqloud_load_for_logged_in',
        [
            'default' => 'public',
            'sanitize_callback' => 'qookieqloud_sanitize_load_setting'
        ]
    );

    add_settings_section(
        'qookieqloud_general_settings',
        'General Settings',
        null,
        'qookieqloud'
    );

    add_settings_field(
        'qookieqloud_load_for_logged_in',
        'Show Cookie Consent Manager',
        'qookieqloud_load_for_logged_in_render',
        'qookieqloud',
        'qookieqloud_general_settings'
    );
}
add_action('admin_init', 'qookieqloud_register_settings');

/**
 * Render the field for loading the consent manager script
 */
function qookieqloud_load_for_logged_in_render() {
    // Retrieve and sanitize the option value
    $value = sanitize_text_field(get_option('qookieqloud_load_for_logged_in', 'public'));

    ?>
    <select name="qookieqloud_load_for_logged_in">
        <option value="public" <?php echo esc_attr(selected($value, 'public', false)); ?>>Public Visitors Only</option>
        <option value="all" <?php echo esc_attr(selected($value, 'all', false)); ?>>All Visitors (Including Logged In)</option>
    </select>
    <p class="description">
        <?php esc_html_e('Choose whether the QookieQloud™ Consentmanager script should load for public visitors only or for everyone.', 'qookieqloud'); ?>
    </p>
    <?php
}


/**
 * Handle the re-check action for domain registration
 */
function qookieqloud_recheck_domain() {
    // Verify nonce for security
    $nonce = isset($_GET['nonce']) ? sanitize_text_field(wp_unslash($_GET['nonce'])) : '';
    if (!wp_verify_nonce(sanitize_text_field($nonce), 'qookieqloud_recheck_nonce')) {
        wp_die('Security check failed');
    }

    // Re-run domain registration check
    qookieqloud_check_domain_registration();

    // Redirect back to the plugins page after re-check
    wp_safe_redirect(admin_url('plugins.php'));
    exit;
}

// Conditionally add action only when needed
function qookieqloud_maybe_add_recheck_action() {
    $action = isset($_GET['action']) ? sanitize_text_field(wp_unslash($_GET['action'])) : '';

    if ($action === 'qookieqloud_recheck_domain') {
        // Verify the nonce before proceeding
        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';
        if (!wp_verify_nonce($nonce, 'qookieqloud_recheck_nonce')) {
            wp_die(esc_html__('Security check failed', 'qookieqloud'));
        }

        // Add admin_init action if the sanitized and validated action matches the expected value
        add_action('admin_init', 'qookieqloud_recheck_domain');
    }
}
add_action('admin_init', 'qookieqloud_maybe_add_recheck_action');
