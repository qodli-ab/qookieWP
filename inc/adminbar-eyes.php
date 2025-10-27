<?php
if (!defined('ABSPATH')) exit;

add_action('admin_bar_menu', function($wp_admin_bar){
    if (!is_user_logged_in() || !current_user_can('manage_options')) return;

    // Hämta status
    $domain_status = get_option('qookieqloud_domain_registered', 'not_checked'); // registered | not_registered | not_checked
    $is_registered = ($domain_status === 'registered');
    $status_label  = $is_registered ? __('Registered', 'qookieqloud') : __('Verify', 'qookieqloud');

    // Val av färg på "ögon"-prickar
    $eye_color = $is_registered ? '#5CBF8B' : '#E53935'; // grön / röd

    // Ladda SVG och färga prickarna
    $svg_path = plugin_dir_path(__FILE__) . '../assets/qookieqloud-eyes-white.svg';
    $svg      = '';
    if (file_exists($svg_path)) {
        $svg = file_get_contents($svg_path);

        // a) Om prickarna har class="eye-dot":
        //    Ersätt fill på de noderna. Enkel variant med regex på 'class="eye-dot" ... fill="..."'
        $svg = preg_replace(
            '/(<[^>]*class="[^"]*eye-dot[^"]*"[^>]*\bfill=")[^"]*("[^>]*>)/i',
            '${1}' . $eye_color . '${2}',
            $svg
        );

        // b) Alternativt, om du hellre kör en placeholder i SVG:n:
        //    Lägg fill="__EYE_COLOR__" på prickarna och kör:
        // $svg = str_replace('__EYE_COLOR__', $eye_color, $svg);
    } else {
        $svg = '<span>QoQ</span>'; // fallback
    }

    // Top node (inline SVG)
    $wp_admin_bar->add_node([
        'id'    => 'qookieqloud-eyes',
        'title' => '<span class="qoq-eyes-icon" title="QookieQloud">'.$svg.'</span>',
        'href'  => false,
        'meta'  => ['class' => 'qoq-eyes-node'],
    ]);

    // Första posten: Status (om inte registered → klickbar till verify/recheck)
    $href = $is_registered
        ? false
        : (defined('QOOKIEQLOUD_REGISTER_URL') ? QOOKIEQLOUD_REGISTER_URL
            : wp_nonce_url(add_query_arg('action','qookieqloud_recheck_domain', admin_url('plugins.php')), 'qookieqloud_recheck_nonce'));

    $wp_admin_bar->add_node([
        'id'     => 'qookieqloud-eyes-status',
        'parent' => 'qookieqloud-eyes',
        'title'  => sprintf('Status: <span class="qoq-status %s">%s</span>', $is_registered ? 'ok' : 'warn', esc_html($status_label)),
        'href'   => $href,
    ]);

    $wp_admin_bar->add_node([
        'id'     => 'qookieqloud-eyes-divider-1',
        'parent' => 'qookieqloud-eyes',
        'title'  => '<span class="qoq-eyes-divider"></span>',
        'href'   => false,
        'meta'   => ['class' => 'qoq-eyes-divider-item'],
    ]);

    $wp_admin_bar->add_node([
        'id'     => 'qookieqloud-eyes-cpanel',
        'parent' => 'qookieqloud-eyes',
        'title'  => __('Controlpanel', 'qookieqloud') . ' 
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" 
             width="14" height="14" fill="currentColor" 
             style="margin-left:4px;vertical-align:middle;opacity:.8;">
            <path d="M14 3h7v7h-2V6.41l-9.29 9.3-1.42-1.42L17.59 5H14V3z"/>
            <path d="M5 5h5V3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14
                     c1.1 0 2-.9 2-2v-5h-2v5H5V5z"/>
        </svg>',
        'href'   => 'https://app.qookie.cloud',
        'meta'   => [
            'target' => '_blank',   // öppna i ny flik
            'title'  => __('Open QookieQloud™ Control Panel in a new window', 'qookieqloud'),
            'rel'    => 'noopener noreferrer'
        ],
    ]);

}, 90);

// CSS + JS (alignment + helpers)
add_action('admin_head', 'qookieqloud_adminbar_eyes_assets');
add_action('wp_head',    'qookieqloud_adminbar_eyes_assets');
function qookieqloud_adminbar_eyes_assets() {
    if (!is_user_logged_in()) return;
    ?>
    <style id="qoq-eyes-style">
        /* Justera höjd/align så den ligger i linje med andra ikoner */
        #wpadminbar #wp-admin-bar-qookieqloud-eyes > .ab-item {
            display: flex;
            align-items: center;
            height: 32px;           /* admin bar default height */
            padding: 0 8px;         /* lagom sidopadding */
        }
        #wpadminbar #wp-admin-bar-qookieqloud-eyes .ab-label { display:none; }

        /* Gör så SVG:n centrerar fint och inte "hoppar upp" */
        #wpadminbar .qoq-eyes-icon svg {
            display: block;
            width: 22px;
            height: 22px;
            vertical-align: middle;
            transform: translateY(1px); /* nudge nedåt om den känns hög */
        }

        /* Färger på status i menyn */
        #wpadminbar .qoq-status.ok   { color:#5CBF8B; font-weight:600; }
        #wpadminbar .qoq-status.warn { color:#E53935; font-weight:600; }
        /* Divider inside the QookieQloud Eyes dropdown */
        #wpadminbar .qoq-eyes-divider-item .ab-item {
            pointer-events: none;
            height: 1px;
            background: rgba(255,255,255,0.25);
            margin: 4px 8px;
            padding: 0;
        }

    </style>
    <script>
        (function(){
            if (!window.QookieQloudEyes) window.QookieQloudEyes = {};
            QookieQloudEyes.openPrefs = function(){
                if (window.QookieQloud?.openPreferences) { window.QookieQloud.openPreferences(); return false; }
                if (window.CookieConsent?.openPreferences){ window.CookieConsent.openPreferences(); return false; }
                alert('Could not find CMP on this page.');
                return false;
            };
            QookieQloudEyes.clearConsent = function(){
                try {
                    if (window.CookieConsent?.reset) window.CookieConsent.reset();
                    ['qookieqloud_consent','cookie_consent','cc_prefs'].forEach(k=>{ try{localStorage.removeItem(k);}catch(e){} });
                    alert('Consent cleared.');
                } catch(e){}
                return false;
            };
        })();
    </script>
    <?php
}
