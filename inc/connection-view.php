<?php if (!defined('ABSPATH')) exit;
$asset = plugins_url('../assets/', __FILE__);
$notice = get_transient('qq_v2_notice_' . get_current_user_id());
$messages = [
    'connected' => __('Connected to QookieQloud. Clear your site cache.', 'qookieqloud'),
    'failed' => __('The action could not be completed. Your existing connection has been kept. Please try again.', 'qookieqloud'),
    'disconnected' => __('Disconnected. Clear your site cache to remove the banner from cached pages.', 'qookieqloud'),
    'saved' => __('Settings saved. Clear your site cache.', 'qookieqloud'),
    'crypto' => __('PHP OpenSSL is required to store the connection securely.', 'qookieqloud'),
];
if ($notice) delete_transient('qq_v2_notice_' . get_current_user_id());
$number = function ($key) use ($stats) { return isset($stats[$key]) && is_numeric($stats[$key]) ? number_format_i18n($stats[$key]) : '—'; };
?>
<div class="qqwp <?php echo $compact ? 'qqwp-compact' : 'wrap'; ?>">
<?php if (isset($messages[$notice])): ?><p class="qqwp-notice" role="status"><?php echo esc_html($messages[$notice]); ?></p><?php endif; ?>
<?php if ($compact && !$legacy): ?>
    <p><?php echo esc_html($connection ? sprintf(__('Connected to %s', 'qookieqloud'), $connection['domain']) : __('Connect your website to start using QookieQloud.', 'qookieqloud')); ?></p>
    <?php if ($connection): ?><dl class="qqwp-widget-metrics">
    <?php foreach (['consents_today' => __('Consents today', 'qookieqloud'), 'cookies' => __('Cookies found', 'qookieqloud'), 'consents_total' => __('Total consents', 'qookieqloud')] as $key => $label): ?>
    <div><dt><?php echo esc_html($label); ?></dt><dd><?php echo esc_html($number($key)); ?></dd></div>
    <?php endforeach; ?></dl><?php endif; ?>
    <a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=qookieqloud')); ?>"><?php esc_html_e('Open QookieQloud', 'qookieqloud'); ?></a>
<?php else: ?>
<section class="qqwp-hero">
    <div class="qqwp-hero-copy">
        <img class="qqwp-logo" src="<?php echo esc_url($asset . 'qookieqloud-logo-white.svg'); ?>" width="190" alt="QookieQloud">
        <h1><?php echo esc_html($connection ? $connection['domain'] : __('Your cookie banner starts here', 'qookieqloud')); ?></h1>
        <p><?php echo esc_html($legacy ? __('Your existing banner keeps using v1. Connect when you are ready to switch this installation to v2.', 'qookieqloud') : ($connection ? __('Your consent overview, banner settings and connection in one place.', 'qookieqloud') : __('Sign in, choose your domain and approve the connection. No API keys to copy or scripts to paste.', 'qookieqloud'))); ?></p>
        <?php if ($connection): ?>
            <a class="qqwp-button" href="https://app.qookieqloud.com/app" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Open QookieQloud', 'qookieqloud'); ?> ↗</a>
        <?php else: qookieqloud_connection_form(false); endif; ?>
    </div>
    <?php if (!$compact): ?><aside class="qqwp-guide">
        <img src="<?php echo esc_url($asset . 'wordpress-connect-flow.svg'); ?>" width="280" height="88" alt="" aria-hidden="true">
        <?php if ($connection): ?>
            <h2><?php esc_html_e('This installation', 'qookieqloud'); ?></h2>
            <p><?php echo esc_html($stats ? __('API connection available', 'qookieqloud') : __('API data currently unavailable', 'qookieqloud')); ?></p>
            <p><?php echo esc_html(get_option('qookieqloud_banner_enabled', true) ? __('Banner enabled', 'qookieqloud') : __('Banner paused', 'qookieqloud')); ?></p>
        <?php else: ?>
            <h2><?php esc_html_e('Three steps. One connection.', 'qookieqloud'); ?></h2>
            <ol><li><?php esc_html_e('Sign in to QookieQloud in the window that opens.', 'qookieqloud'); ?></li><li><?php esc_html_e('Choose a domain you manage, or add a new one.', 'qookieqloud'); ?></li><li><?php esc_html_e('Approve, return here and review your banner settings.', 'qookieqloud'); ?></li></ol>
        <?php endif; ?>
    </aside><?php endif; ?>
    <?php if ($connection): ?><div class="qqwp-hero-kpis">
        <?php foreach (['consents_today' => __('Consents today', 'qookieqloud'), 'cookies' => __('Cookies found', 'qookieqloud'), 'audit_score' => __('Privacy audit', 'qookieqloud'), 'consents_total' => __('Total consents', 'qookieqloud')] as $key => $label): ?>
        <div class="qqwp-hero-kpi">
            <span class="dashicons dashicons-<?php echo $key === 'audit_score' ? 'shield' : ($key === 'cookies' ? 'search' : 'chart-bar'); ?>" aria-hidden="true"></span>
            <span><small><?php echo esc_html($label); ?></small><strong><?php echo esc_html($number($key)); ?></strong></span>
        </div>
        <?php endforeach; ?>
    </div><?php endif; ?>
</section>
<?php if (!$compact): ?><div class="qqwp-content">
<?php if ($connection): ?>
<?php if (!$stats): ?><p class="qqwp-notice"><?php esc_html_e('Statistics are currently unavailable. You can still manage the banner and connection below.', 'qookieqloud'); ?></p><?php endif; ?>
<div class="qqwp-grid">
<section class="qqwp-card"><h2><?php esc_html_e('Consent overview', 'qookieqloud'); ?></h2><dl>
<?php foreach (['consents_accepted'=>__('Accepted today', 'qookieqloud'),'consents_rejected'=>__('Rejected today', 'qookieqloud'),'consents_custom'=>__('Custom choices today', 'qookieqloud'),'consents_week'=>__('Last 7 days', 'qookieqloud'),'consents_month'=>__('Last 30 days', 'qookieqloud'),'pages_scanned'=>__('Pages scanned', 'qookieqloud')] as $key=>$label): ?>
<div><dt><?php echo esc_html($label); ?></dt><dd><?php echo esc_html($number($key)); ?></dd></div>
<?php endforeach; ?></dl></section>
<section class="qqwp-card"><h2><?php esc_html_e('Banner settings', 'qookieqloud'); ?></h2>
<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
<input type="hidden" name="action" value="qookieqloud_v2_settings"><?php wp_nonce_field('qookieqloud_v2_settings'); ?>
<label class="qqwp-toggle"><span><?php esc_html_e('Enable the banner', 'qookieqloud'); ?></span><input role="switch" type="checkbox" name="enabled" value="1" <?php checked(get_option('qookieqloud_banner_enabled', true)); ?>></label>
<label class="qqwp-toggle"><span><?php esc_html_e('Show the banner for logged-in users', 'qookieqloud'); ?></span><input role="switch" type="checkbox" name="logged_in" value="1" <?php checked(get_option('qookieqloud_load_for_logged_in', 'public'), 'all'); ?>></label>
<p><?php esc_html_e('Clear the site cache after changing these settings.', 'qookieqloud'); ?></p><button class="qqwp-button" type="submit"><?php esc_html_e('Save settings', 'qookieqloud'); ?></button>
</form></section>
<section class="qqwp-card"><div class="qqwp-card-heading"><h2><?php esc_html_e('Cookie Scan', 'qookieqloud'); ?></h2><a class="qqwp-card-link" href="https://app.qookieqloud.com/app" target="_blank" rel="noopener noreferrer"><?php esc_html_e('View cookies', 'qookieqloud'); ?> ↗</a></div><dl>
<div><dt><?php esc_html_e('Status', 'qookieqloud'); ?></dt><dd><span class="qqwp-status-pill"><?php echo esc_html($stats['scan_status'] ?? __('Completed', 'qookieqloud')); ?></span></dd></div>
<div><dt><?php esc_html_e('Last scan', 'qookieqloud'); ?></dt><dd><?php echo esc_html($stats['updated_local'] ?? '—'); ?></dd></div>
<div><dt><?php esc_html_e('Cookies found', 'qookieqloud'); ?></dt><dd><?php echo esc_html($number('cookies')); ?></dd></div>
<div><dt><?php esc_html_e('Pages scanned', 'qookieqloud'); ?></dt><dd><?php echo esc_html($number('pages_scanned')); ?></dd></div>
</dl></section>
<section class="qqwp-card"><h2><?php esc_html_e('Connection', 'qookieqloud'); ?></h2><p><?php echo esc_html(sprintf(__('Connected to %s', 'qookieqloud'), $connection['domain'])); ?></p><div class="qqwp-actions">
<?php qookieqloud_connection_form(true); ?>
<form data-qq-disconnect data-confirm="<?php esc_attr_e('Disconnect this installation? Its private API access will be revoked and the plugin will stop loading the banner. Your domain remains in QookieQloud. Clear the site cache afterwards.', 'qookieqloud'); ?>" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
<input type="hidden" name="action" value="qookieqloud_disconnect"><?php wp_nonce_field('qookieqloud_disconnect'); ?><button class="qqwp-button qqwp-danger" type="submit"><?php esc_html_e('Disconnect', 'qookieqloud'); ?></button></form>
</div></section></div>
<?php else: ?>
<p><?php esc_html_e('Only API access is granted. The plugin cannot sign in to your QookieQloud account. Banner design and consent settings are managed in QookieQloud.', 'qookieqloud'); ?></p>
<?php endif; ?></div><?php endif; ?>
<?php endif; ?>
</div>
