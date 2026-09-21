=== QookieQloud™ Consent Management ===
Contributors: qodliab
Tags: cookie consent, GDPR, privacy, consent management, consentmode v2
Requires at least: 5.0
Tested up to: 7.1
Stable tag: 2.0.5
Requires PHP: 7.4
Requires Plugins: wp-consent-api
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connect WordPress to QookieQloud to load your cookie banner and view consent statistics.

== Description ==

Manage banner design, cookie categories and consent settings in QookieQloud. The WordPress plugin loads your banner and shows statistics for the domain you connect.

New installations use the v2 connection flow. Existing v1 installations keep their current banner until an administrator explicitly connects to v2. Availability of v2 depends on your account's rollout access in QookieQloud.

== Installation ==

1. Install and activate WP Consent API and QookieQloud.
2. Open QookieQloud in the WordPress administration menu and click Connect to QookieQloud.
3. Sign in at app.qookieqloud.com in the popup. Select a domain you manage, or add one, and approve.
4. Return to WordPress. The keys are saved automatically: there is nothing to copy. The banner is enabled by default for public visitors; review the banner switches to change this.
5. Clear any page or CDN cache after connecting or changing the banner settings.

PHP OpenSSL is required for v2. HTTPS is required for public callback addresses; HTTP localhost and 127.0.0.1 are supported for local testing.

== External Services ==

QookieQloud is an external service. An account and a domain in QookieQloud are required.

The connection popup opens https://app.qookieqloud.com. It sends the integration type, WordPress return address, a random state and a PKCE challenge. After you approve, WordPress exchanges a one-time code for API credentials over HTTPS. The private credential stays encrypted on your WordPress server and is used to read domain statistics and revoke this installation's access. It cannot sign in to your QookieQloud account.

For enabled banners, visitors load scripts from https://cf-cdn.qookieqloud.com/v2/. Only the public site key appears in the page. The banner contacts the QookieQloud API to retrieve settings and submit detected cookies and consent choices. A public key is not a secret or an account login credential.

Existing v1 installations continue their original registration and statistics requests to https://app.qookieqloud.com/api/v1/ and their original CDN loader until explicitly connected to v2.

Service terms and privacy information: https://qookieqloud.com/policy

== Frequently Asked Questions ==

= Will upgrading interrupt my existing banner? =
Existing v1 installations stay on v1. The plugin switches only after an administrator successfully approves and saves a v2 connection. Clear page caches after switching.

= Do I need to copy API keys? =
No. The popup exchanges keys automatically. The private key remains on the server, while the public key travels with the banner.

= Can a partner connect a customer's domain? =
Yes, provided the account has access to that customer's domain and has v2 access enabled.

= Can I test from localhost? =
Yes. The browser returns to WordPress; QookieQloud does not need to reach your local server. Selecting a live domain shares that domain's settings and statistics. Choose a dedicated test domain to keep test activity separate.

= What happens when I disconnect? =
The plugin revokes this installation's private API access and stops adding the banner. It does not delete the domain, revoke other installations or fall back to v1. Clear cached pages afterwards. Previously published public keys are shared domain identifiers, not revoked private credentials.

= Can I show the banner to logged-in visitors? =
Yes. Enable the corresponding switch in Banner settings and save. Clear page caches afterwards.

= What if I move or clone WordPress? =
The private connection is bound to the site's URL and WordPress authentication salt. Reconnect after either changes. A copied database alone cannot decrypt the connection at another site URL.

== Changelog ==

= 2.0.5 =
* Refined the WordPress dashboard layout with a full-width hero and centered content cards.
* Moved dashboard KPIs into the hero and added a Cookie Scan summary block.
* Added v2 popup connection, private API statistics and public-key banner loading.
* Added confirmed change-connection and disconnect actions, banner switches and bundled connection artwork.
* Preserved v1 for existing installations until explicit migration.

= 2.0.4 =
* Added formal plugin dependency requirement for WP Consent API (Requires Plugins header and admin notice).

= 2.0.3 =
* Moved to a new CDN.

= 2.0.2 =
* Tested and confirmed with Wordpress 7.1.x. Also added Dashboard link to dropdown menu in admin-bar.

= 2.0.0 =
* Added detailed dashboard with stats and insights on cookie consents and user interactions.

= 1.4.5 =
* Compatible with Wordpress 7.0.x

= 1.4.4 =
* Compatible with Wordpress 6.9.x

= 1.4.3 =
* Changed backend and loader URLS

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
