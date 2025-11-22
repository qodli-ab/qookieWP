<?php
if (!defined('ABSPATH')) exit;

/**
 * QookieQloud Dashboard Widget
 * - Renders four KPIs
 * - Server-side render uses cached data (transient)
 * - AJAX endpoint for manual refresh
 */

// ------- Settings -------
define('QOOKIEQLOUD_STATS_TRANSIENT', 'QOOKIEQLOUD_STATS_CACHE');
define('QOOKIEQLOUD_STATS_TTL', 60 * 60); // cache 5 min
define('QOOKIEQLOUD_STATS_URL', 'https://app.qookieqloud.com/api/v1/domainstats');
// -----------------------

/**
 * Mock/backend bridge: hämta stats från din Laravel-backend.
 * Byt ut innehållet mot riktig API-kallare (wp_remote_get / Guzzle etc).
 */
function qookieqloud_fetch_stats_from_backend() : array {
    $domain = wp_parse_url(home_url(), PHP_URL_HOST);
    $data = wp_json_encode(['domain' => $domain]);

    // Signera exakt som i qookieqloud_check_domain_registration()
    $auth = qookieqloud_generate_signature($domain);

    // Välj stats-endpoint. Om du vill återanvända samma bas-URL, sätt QOOKIEQLOUD_STATS_URL i ditt plugin.
    // Fallback: prova {QOOKIEQLOUD_API_URL}/stats/summary
    if (defined('QOOKIEQLOUD_STATS_URL')) {
        $endpoint = QOOKIEQLOUD_STATS_URL;
    } else {
        $endpoint = rtrim(QOOKIEQLOUD_API_URL, '/').'../stats';
    }

// Send POST request to your API
    $response = wp_remote_post($endpoint, [
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
        // Hård fallback – behåll ev. cache om den finns
        $cached = get_transient(QOOKIEQLOUD_STATS_TRANSIENT);
        if ($cached && is_array($cached)) {
            return $cached + ['source' => 'cache', 'error' => $response->get_error_message()];
        }
        return [
            'consents_total' => 0,
            'consents_today' => 0,
            'cookies'        => 0,
            'trackers'       => 0,
            'updated_at'     => current_time('mysql'),
            'source'         => 'error',
        ];
    }

    $status = wp_remote_retrieve_response_code($response);
    $body   = json_decode(wp_remote_retrieve_body($response), true);

    if ($status === 200 && is_array($body)) {

        $updated_raw = $body['updated_at'] ?? '';
        $updated_ts  = is_numeric($updated_raw) ? (int) $updated_raw : strtotime($updated_raw);
        $updated_local = $updated_ts ? wp_date( get_option('date_format') . ' dashboard-widget.php' .get_option('time_format'), $updated_ts ) : '';

        // Mappa/normalisera säkert
        $consents_total = isset($body['consents_total']) ? (int)$body['consents_total'] : 0;
        $consents_today = isset($body['consents_today']) ? (int)$body['consents_today'] : 0;
        $cookies        = isset($body['cookies'])        ? (int)$body['cookies']        : 0;
        $trackers       = isset($body['trackers'])       ? (int)$body['trackers']       : 0;
        $updated_at     = !empty($body['updated_at'])    ? $body['updated_at']          : current_time('mysql');

        return [
            'consents_total' => $consents_total,
            'consents_today' => $consents_today,
            'cookies'        => $cookies,
            'trackers'       => $trackers,
            'updated_local'  => $updated_local,
            'source'         => 'api',
        ];
    }

    // Oväntad respons: fall back till cache om möjligt
    $cached = get_transient(QOOKIEQLOUD_STATS_TRANSIENT);
    if ($cached && is_array($cached)) {
        return $cached + ['source' => 'cache', 'error' => 'unexpected_response_'.$status];
    }

    return [
        'consents_total' => 0,
        'consents_today' => 0,
        'cookies'        => 0,
        'trackers'       => 0,
        'updated_at'     => current_time('mysql'),
        'source'         => 'unexpected',
    ];
}

/** Hämta (ev. från cache) */
function qookieqloud_get_stats_cached() : array {
    $data = get_transient(QOOKIEQLOUD_STATS_TRANSIENT);
    if ($data && is_array($data)) {
        return $data + ['cached' => true];
    }
    $fresh = qookieqloud_fetch_stats_from_backend();
    set_transient(QOOKIEQLOUD_STATS_TRANSIENT, $fresh, QOOKIEQLOUD_STATS_TTL);
    $fresh['cached'] = false;
    return $fresh;
}

/** Dashboard widget registrering */
add_action('wp_dashboard_setup', function () {
    wp_add_dashboard_widget(
        'qookieqloud_stats_widget',
        __('QookieQloud Stats', 'qookieqloud'),
        'qookieqloud_render_stats_widget'
    );
});

/** Render widget UI (server-side) */
function qookieqloud_render_stats_widget() {
    $stats = qookieqloud_get_stats_cached();
    $accent = '#5CBF8B';
    ?>
    <div id="qoq-stats" class="qoq-stats-wrap" data-refresh-nonce="<?php echo esc_attr( wp_create_nonce('qookieqloud_stats_refresh') ); ?>">
        <div class="qoq-stats-grid">
            <div class="qoq-kpi">
                <div class="qoq-kpi-label"><?php _e('Consents', 'qookieqloud'); ?></div>
                <div class="qoq-kpi-value" id="qoq-consents-total"><?php echo number_format_i18n($stats['consents_total']); ?></div>
            </div>
            <div class="qoq-kpi">
                <div class="qoq-kpi-label"><?php _e('New today', 'qookieqloud'); ?></div>
                <div class="qoq-kpi-value" id="qoq-consents-today"><?php echo number_format_i18n($stats['consents_today']); ?></div>
            </div>
            <div class="qoq-kpi">
                <div class="qoq-kpi-label"><?php _e('Cookies', 'qookieqloud'); ?></div>
                <div class="qoq-kpi-value" id="qoq-cookies"><?php echo number_format_i18n($stats['cookies']); ?></div>
            </div>
            <div class="qoq-kpi">
                <img src="<?php echo esc_url(QOOKIEQLOUD_LOGO_URL); ?>" alt="QookieQloud™ Logo" style="width: 80px; height: auto;">
            </div>
        </div>

        <div class="qoq-stats-footer">
                <?php
                $updated_raw = $stats['updated_at'] ?? '';
                $updated_ts  = is_numeric($updated_raw) ? (int) $updated_raw : strtotime($updated_raw); // funkar för ISO8601/Z och "Y-m-d H:i:s"
                $updated_local = $updated_ts ? wp_date( get_option('date_format') . ' dashboard-widget.php' .get_option('time_format'), $updated_ts ) : '';
                ?>
            <span class="qoq-updated">
              <?php _e('Updated', 'qookieqloud'); ?>:
              <strong id="qoq-updated-at"><?php echo esc_html( $updated_local ); ?></strong>
              <?php if (!empty($stats['cached'])): ?>
                  <em class="qoq-cached"><?php _e('(cached)', 'qookieqloud'); ?></em>
              <?php endif; ?>
            </span>
        </div>
    </div>

    <style>
        #qoq-stats .qoq-stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin: 6px 0 10px;
        }
        #qoq-stats .qoq-kpi {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 12px;
        }
        #qoq-stats .qoq-kpi-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 4px;
        }
        #qoq-stats .qoq-kpi-value {
            font-size: 22px;
            font-weight: 700;
            line-height: 1.1;
            color: #1f2937;
        }
        #qoq-stats .qoq-stats-footer {
            display:flex; align-items:center; justify-content:space-between;
        }
        #qoq-stats .qoq-updated strong { color:#111; }
        #qoq-stats .qoq-cached { color:#6b7280; margin-left:6px; }
        #qoq-stats .button.qoq-refresh {
            border-color: <?php echo esc_html($accent); ?>;
            color: <?php echo esc_html($accent); ?>;
            box-shadow: none;
        }
        #qoq-stats .button.qoq-refresh:hover {
            background: <?php echo esc_html($accent); ?>;
            color: #fff;
        }

        /* Dark mode-ish harmony under modern WP schemes */
        body.admin-color-midnight #qoq-stats .qoq-kpi { background:#1d2327; border-color:#2c3338; }
        body.admin-color-midnight #qoq-stats .qoq-kpi-label { color:#c3c4c7; }
        body.admin-color-midnight #qoq-stats .qoq-kpi-value { color:#f0f0f1; }
    </style>

    <script>
        (function(){
            const wrap = document.getElementById('qoq-stats');
            if (!wrap) return;

            wrap.querySelector('.qoq-refresh').addEventListener('click', function(){
                const nonce = wrap.getAttribute('data-refresh-nonce');
                wrap.classList.add('is-loading');
                fetch(ajaxurl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},
                    body: new URLSearchParams({
                        action: 'qookieqloud_stats_refresh',
                        _wpnonce: nonce
                    })
                })
                    .then(r => r.json())
                    .then(data => {
                        wrap.classList.remove('is-loading');
                        if (!data || !data.success) { alert(data?.message || 'Refresh failed'); return; }
                        const s = data.data || {};
                        const nf = new Intl.NumberFormat(<?php echo wp_json_encode( get_locale() ); ?>);

                        document.getElementById('qoq-consents-total').textContent = nf.format(s.consents_total || 0);
                        document.getElementById('qoq-consents-today').textContent = nf.format(s.consents_today || 0);
                        document.getElementById('qoq-cookies').textContent        = nf.format(s.cookies || 0);
                        document.getElementById('qoq-trackers').textContent       = nf.format(s.trackers || 0);
                        document.getElementById('qoq-updated-at').textContent     = s.updated_local || '';
                    })
                    .catch(()=>{ wrap.classList.remove('is-loading'); alert('Network error'); });
            });
        })();
    </script>
    <?php
}

/** AJAX: uppdatera stats on demand */
add_action('wp_ajax_qookieqloud_stats_refresh', function(){
    check_ajax_referer('qookieqloud_stats_refresh');

    // Hämta färskt (bypass cache) eller respektera TTL? Här bypassar vi:
    $fresh = qookieqloud_fetch_stats_from_backend();
    set_transient(QOOKIEQLOUD_STATS_TRANSIENT, $fresh, QOOKIEQLOUD_STATS_TTL);

    // Format för klienten
    $updated_local = get_date_from_gmt( gmdate('Y-m-d H:i:s', strtotime($fresh['updated_at'])), get_option('date_format') . ' dashboard-widget.php' .get_option('time_format') );
    wp_send_json_success([
        'consents_total' => (int) $fresh['consents_total'],
        'consents_today' => (int) $fresh['consents_today'],
        'cookies'        => (int) $fresh['cookies'],
        'trackers'       => (int) $fresh['trackers'],
        'updated_local'  => $updated_local,
        'source'         => $fresh['source'] ?? 'api',
    ]);
});
