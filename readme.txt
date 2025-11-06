=== QookieQloud™ Consent Management ===
Contributors: qodliab
Tags: cookie consent, GDPR, privacy, consent management, consentmode v2
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 1.4.2
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automated and effortless cookie consent management within WordPress, powered by QookieQloud™. Easily manage cookies, collect consents.

== Description ==

QookieQloud™ Consent Management Platform brings you a powerful, automated solution for cookie consent management within WordPress, making GDPR compliance effortless. Manage cookies and collect consents for one or multiple domains, directly from the QookieQloud™ Platform.
By installing this plugin, you agree to the Terms of Service and Privacy Policy available at:

- **Terms of Service & Privacy Policy **: [https://qookieqloud.com/policy](https://qookieqloud.com/policy)

### Key Features

- **Automated Consent Management**: Automatically load the QookieQloud™ Cookie Consent Manager script in your WordPress site, ensuring cookies are managed in compliance with privacy laws.
- **Domain Registration and Verification**: Quick and easy domain registration with QookieQloud™ directly from your WordPress plugin dashboard.
- **Centralized Management**: Access and manage consents for multiple domains from a single platform, making it ideal for users with multiple sites.
- **Customizable Loading**: Configure settings to load the consent manager script based on visitor type—public visitors only or both logged-in and public visitors.
- **User-Friendly Setup**: Simple, intuitive setup with minimal configuration. Get started with just a few clicks.

### How It Works

1. **Install & Activate**: Install and activate the QookieQloud™ plugin. Upon activation, the plugin will verify your domain with QookieQloud™ to enable automated cookie consent management.
2. **Manage Settings**: Choose whether the consent manager should load for all visitors or only public visitors (excluding logged-in users).
3. **Multi-Domain Support**: Manage cookies and consents for multiple domains from the QookieQloud™ platform dashboard.
4. **Real-Time Consent Management**: Real-time tracking and management of cookies and user consents ensure that your website stays compliant with GDPR and privacy regulations.

== Installation ==

1. Download the QookieQloud™ Consent Management Platform plugin.
2. Upload the plugin files to the `/wp-content/plugins/qookieqloud` directory or install directly through the WordPress plugins screen.
3. Activate the plugin through the 'Plugins' screen in WordPress.
4. Once activated, the plugin will attempt to verify your domain with QookieQloud™. Follow the prompt if your domain is not yet registered.

== External Services ==

This plugin sends the domainname of your wordpress site to verify if the domain is registered in our platform: https://app.qookie.cloud, which ofcourse is free.
It also loads the consent-manager from that same platform to enable the cookie-consent-manager dialog that will appear on your homepage when activated.
The consent-manager itself will check and show your visitors categorized cookies that are used on your homepage, so they can accept or decline the usage of them.

== Frequently Asked Questions ==

= How do I register my domain with QookieQloud™? =
After activation, the plugin will automatically check your domain registration status with QookieQloud™. If the domain is not registered, you’ll see a prompt with a link to register or log in to your QookieQloud™ account.

= Can I configure the plugin to load only for public visitors? =
Yes! In the QookieQloud™ settings, you can specify whether the consent manager should load for public visitors only or all visitors (including logged-in users).

= Where can I manage cookies and consents for multiple domains? =
Once your domain is registered, log in to the QookieQloud™ dashboard at https://app.qookie.cloud. You’ll be able to manage cookies, consents, and more for all your registered domains in one place.

= Do I need to configure cookies individually? =
The QookieQloud™ platform automatically manages cookies and consent collection for you, ensuring privacy compliance without the need for manual configuration.

== Changelog ==

= 1.4.2 =
* Fixed issue with registration in WP Consent API

= 1.4.1 =
* Fixed faulty time/date in dashboard widget

= 1.4.0 =
* Added admin-bar icon and menu with status
* Added Dashboard-widget with stats

= 1.3.2 =
* Corrected versions

= 1.3.1 =
* Tested and confirmed with WP 6.8.3

= 1.3.0 =
* Integrated with WP Consent API

= 1.2.12 =
* Added assets

= 1.2.11 =
* Improved Sanitizing, Escaping and Validation
* Moved remote-image to local plugin/assets

= 1.2.8 =
* Added domain registration verification on activation.
* Enhanced loading options for consent manager script.
* Improved UI for admin notifications and settings page.

== License ==

This plugin is licensed under the GPL v2.0 or later.
