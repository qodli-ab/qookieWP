<?php
if (!defined('ABSPATH')) exit;

/**
 * Render the Dashboard Content
 */
function qookieqloud_render_dashboard_content() {
    // Reuse stats from dashboard-widget.php
    $stats = qookieqloud_get_stats_cached();
    $logo_url = QOOKIEQLOUD_LOGO_URL;
    $domain_status = get_option('qookieqloud_domain_registered', 'not_checked');
    $is_active = ($domain_status === 'registered');
    
    // Dynamic data from API with fallbacks
    $plan = $stats['plan_name'] ?? ($is_active ? 'Pro' : 'Free');
    $verification = $stats['verification_status'] ?? ($is_active ? 'Verified' : 'Unverified');
    
    $accepted = $stats['consents_accepted'] ?? $stats['consents_today'];
    $rejected = $stats['consents_rejected'] ?? 0;
    $custom   = $stats['consents_custom'] ?? 0;
    
    $this_week = $stats['consents_week'] ?? $stats['consents_total'];
    $this_month = $stats['consents_month'] ?? $stats['consents_total'];
    
    $pages_scanned = $stats['pages_scanned'] ?? 'N/A';
    $scan_status = $stats['scan_status'] ?? 'Completed';
    
    $audit_score = $stats['audit_score'] ?? null;
    $audit_risk  = $stats['audit_risk_level'] ?? 'N/A';

    ?>
    <div class="qoq-dashboard-wrap">
        <header class="qoq-dashboard-header">
            <div class="qoq-header-left">
                <h1>QookieQloud™ Dashboard</h1>
            </div>
            <div class="qoq-header-right">
                <a href="https://app.qookieqloud.com" target="_blank" class="qoq-external-link">
                    Open full dashboard <span class="dashicons dashicons-external"></span>
                </a>
            </div>
        </header>

        <div class="qoq-dashboard-grid">
            <!-- Top Row Cards -->
            <div class="qoq-kpi-card">
                <div class="qoq-kpi-icon">
                    <span class="dashicons dashicons-chart-bar"></span>
                </div>
                <div class="qoq-kpi-content">
                    <div class="qoq-kpi-label">CONSENTS TODAY</div>
                    <div class="qoq-kpi-value"><?php echo number_format_i18n($stats['consents_today']); ?></div>
                </div>
            </div>

            <div class="qoq-kpi-card">
                <div class="qoq-kpi-icon">
                    <span class="dashicons dashicons-awards"></span>
                </div>
                <div class="qoq-kpi-content">
                    <div class="qoq-kpi-label">PLAN</div>
                    <div class="qoq-kpi-value-small"><?php echo esc_html($plan); ?></div>
                </div>
            </div>

            <div class="qoq-kpi-card">
                <div class="qoq-kpi-icon">
                    <span class="dashicons dashicons-performance"></span>
                </div>
                <div class="qoq-kpi-content">
                    <div class="qoq-kpi-label">AUDIT SCORE</div>
                    <div class="qoq-kpi-value">
                        <?php echo $audit_score !== null ? $audit_score . '<span class="qoq-unit">/100</span>' : 'N/A'; ?>
                    </div>
                </div>
            </div>

            <div class="qoq-kpi-card">
                <div class="qoq-kpi-icon">
                    <span class="dashicons dashicons-yes-alt"></span>
                </div>
                <div class="qoq-kpi-content">
                    <div class="qoq-kpi-label">VERIFICATION</div>
                    <div class="qoq-kpi-status <?php echo ($verification === 'Verified' || $is_active) ? 'status-verified' : 'status-unverified'; ?>">
                        <?php echo esc_html($verification); ?>
                    </div>
                </div>
            </div>

            <!-- Second Row -->
            <div class="qoq-main-card qoq-consent-overview">
                <div class="qoq-card-header">
                    <h2>Consent Overview</h2>
                    <a href="https://app.qookieqloud.com" target="_blank" class="qoq-card-link">
                        View analytics <span class="dashicons dashicons-external"></span>
                    </a>
                </div>
                <div class="qoq-card-content">
                    <div class="qoq-stat-row">
                        <span>Today</span>
                        <div class="qoq-stat-tags">
                            <span class="tag-accepted">Accepted: <?php echo number_format_i18n($accepted); ?></span>
                            <span class="tag-rejected">Rejected: <?php echo number_format_i18n($rejected); ?></span>
                            <span class="tag-custom">Custom: <?php echo number_format_i18n($custom); ?></span>
                        </div>
                    </div>
                    <div class="qoq-stat-row">
                        <span>This Week</span>
                        <span class="qoq-stat-val"><?php echo number_format_i18n($this_week); ?></span>
                    </div>
                    <div class="qoq-stat-row">
                        <span>This Month</span>
                        <span class="qoq-stat-val"><?php echo number_format_i18n($this_month); ?></span>
                    </div>
                </div>
            </div>

            <div class="qoq-main-card qoq-cookie-scan">
                <div class="qoq-card-header">
                    <h2>Cookie Scan</h2>
                    <div class="qoq-header-actions">
                        <a href="https://app.qookieqloud.com" target="_blank" class="qoq-card-link">View cookies <span class="dashicons dashicons-external"></span></a>
                        <a href="https://app.qookieqloud.com" target="_blank" class="qoq-button secondary">Run New Scan</a>
                    </div>
                </div>
                <div class="qoq-card-content">
                    <div class="qoq-stat-row">
                        <span>Status</span>
                        <span class="status-completed"><?php echo esc_html($scan_status); ?></span>
                    </div>
                    <div class="qoq-stat-row">
                        <span>Last Scan</span>
                        <span class="qoq-stat-val-light"><?php echo esc_html($stats['updated_local']); ?></span>
                    </div>
                    <div class="qoq-stat-row">
                        <span>Cookies Found</span>
                        <span class="qoq-stat-val"><?php echo number_format_i18n($stats['cookies']); ?></span>
                    </div>
                    <div class="qoq-stat-row">
                        <span>Pages Scanned</span>
                        <span class="qoq-stat-val"><?php echo esc_html($pages_scanned); ?></span>
                    </div>
                </div>
            </div>

            <!-- Footer Row -->
            <div class="qoq-full-card qoq-cta-card">
                <div class="qoq-cta-content">
                    <h3>Customise Your Banner</h3>
                    <p>Design your cookie banner, manage cookie categories, and configure consent rules in the QookieQloud dashboard.</p>
                    <a href="https://app.qookieqloud.com" target="_blank" class="qoq-button primary">Open QookieQloud Dashboard <span class="dashicons dashicons-external"></span></a>
                </div>
            </div>
        </div>
    </div>

    <style>
        .qoq-dashboard-wrap {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        }
        .qoq-dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .qoq-dashboard-header h1 {
            font-size: 24px;
            font-weight: 700;
            margin: 0;
            color: #111827;
        }
        .qoq-external-link {
            text-decoration: none;
            color: #374151;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .qoq-external-link:hover {
            color: #111827;
        }
        .qoq-dashboard-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }
        .qoq-unit {
            font-size: 14px;
            color: #6b7280;
            font-weight: 500;
            margin-left: 2px;
        }
        .qoq-kpi-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .qoq-kpi-icon {
            background: #f3f4f6;
            width: 48px;
            height: 48px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .qoq-kpi-icon .dashicons {
            font-size: 24px;
            width: 24px;
            height: 24px;
            color: #374151;
        }
        .qoq-kpi-label {
            font-size: 11px;
            font-weight: 600;
            color: #6b7280;
            letter-spacing: 0.05em;
            margin-bottom: 4px;
        }
        .qoq-kpi-status {
            font-weight: 600;
            font-size: 14px;
            padding: 2px 8px;
            border-radius: 12px;
            display: inline-block;
        }
        .status-active, .status-verified, .status-completed {
            background: #ecfdf5;
            color: #10b981;
        }
        .status-inactive, .status-unverified {
            background: #fef2f2;
            color: #ef4444;
        }
        .qoq-kpi-value {
            font-size: 24px;
            font-weight: 700;
            color: #111827;
        }
        .qoq-kpi-value-small {
            font-size: 18px;
            font-weight: 700;
            color: #1d4ed8;
        }
        
        .qoq-main-card {
            grid-column: span 2;
            background: #fff;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .qoq-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        .qoq-card-header h2 {
            font-size: 18px;
            font-weight: 700;
            margin: 0;
            color: #111827;
        }
        .qoq-card-link {
            text-decoration: none;
            color: #374151;
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .qoq-stat-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .qoq-stat-row:last-child {
            border-bottom: none;
        }
        .qoq-stat-row span:first-child {
            color: #4b5563;
            font-weight: 500;
        }
        .qoq-stat-tags {
            display: flex;
            gap: 8px;
        }
        .qoq-stat-tags span {
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .tag-accepted { background: #ecfdf5; color: #10b981; }
        .tag-rejected { background: #fef2f2; color: #ef4444; }
        .tag-custom { background: #fff7ed; color: #f59e0b; }
        
        .qoq-stat-val {
            font-weight: 700;
            color: #111827;
        }
        .qoq-stat-val-light {
            color: #111827;
            font-weight: 500;
        }
        
        .qoq-header-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .qoq-button {
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        .qoq-button.primary {
            background: #111827;
            color: #fff;
        }
        .qoq-button.primary:hover {
            background: #1f2937;
        }
        .qoq-button.secondary {
            border: 1px solid #d1d5db;
            color: #374151;
        }
        .qoq-button.secondary:hover {
            background: #f9fafb;
        }
        
        .qoq-full-card {
            grid-column: span 4;
            background: #fff;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        .qoq-cta-content h3 {
            font-size: 20px;
            font-weight: 700;
            margin: 0 0 10px;
            color: #111827;
        }
        .qoq-cta-content p {
            color: #6b7280;
            max-width: 500px;
            margin: 0 auto 24px;
            line-height: 1.5;
        }

        /* Responsive */
        @media (max-width: 960px) {
            .qoq-dashboard-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .qoq-main-card, .qoq-full-card {
                grid-column: span 2;
            }
        }
        @media (max-width: 600px) {
            .qoq-dashboard-grid {
                grid-template-columns: 1fr;
            }
            .qoq-kpi-card, .qoq-main-card, .qoq-full-card {
                grid-column: span 1;
            }
        }
    </style>
    <?php
}
