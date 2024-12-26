=== Attribute Login Access ===
Contributors: imbrick
Donate link: https://imbrick.com/donate
Tags: security, login, authentication, brute force protection, ip tracking
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Enhanced login security with IP tracking, attempt limiting, and temporary lockouts to protect your WordPress site from unauthorized access attempts.

== Description ==

Attribute Login Access provides robust security features to protect your WordPress login system from unauthorized access attempts and potential security threats. The plugin implements intelligent IP tracking, sophisticated attempt limiting, and automated temporary lockouts to maintain your site's security.

= Key Features =

* **Intelligent IP Tracking**
  - Monitor and log all login attempts
  - Track geographic location of login attempts
  - Maintain detailed access logs with timestamps
  - IP address validation and filtering

* **Advanced Attempt Limiting**
  - Configurable maximum login attempts
  - Progressive attempt penalties
  - Custom thresholds for different user roles
  - Smart attempt counter reset timing

* **Temporary Lockouts**
  - Automated temporary account lockouts
  - Progressive lockout duration
  - IP-based and username-based lockouts
  - Admin notification system
  - Manual lockout management

* **Security Dashboard**
  - Real-time activity monitoring
  - Visual analytics and reports
  - Failed login attempt patterns
  - Geographic access visualization

= Pro Features =

* Two-factor authentication support
* Advanced IP filtering rules
* Extended logging capabilities
* Custom security policies
* Priority support

= Use Cases =

1. **Basic Security Enhancement**
   Protect your WordPress site from basic brute force attacks and unauthorized access attempts.

2. **Enterprise Security**
   Implement enterprise-grade security measures for high-traffic WordPress installations.

3. **Compliance Requirements**
   Meet security compliance requirements with detailed access logging and monitoring.

== Installation ==

1. Upload the `attribute-login-access` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to 'Settings > Attribute Login Access' to configure the plugin

= Minimum Requirements =

* WordPress 5.0 or higher
* PHP version 7.4 or higher
* MySQL version 5.6 or higher

== Frequently Asked Questions ==

= How does the login attempt limiting work? =

The plugin tracks login attempts based on both IP address and username. After a configurable number of failed attempts, the system implements a temporary lockout period that increases in duration with subsequent failures.

= Can I customize the lockout duration? =

Yes, administrators can customize both the number of allowed attempts and the lockout duration through the plugin settings panel.

= Does this plugin work with custom login pages? =

Yes, the plugin integrates with both the standard WordPress login page and custom login forms created through themes or other plugins.

= Will this plugin slow down my site? =

No, the plugin is optimized for performance and uses efficient caching mechanisms. The impact on site performance is minimal.

= How does the plugin handle legitimate users behind a shared IP? =

The plugin includes smart detection for shared IPs and provides options to adjust security measures accordingly. Administrators can configure less restrictive settings for known proxy or VPN addresses.

== Screenshots ==

1. Main settings dashboard
2. Login attempt logs
3. Security analytics
4. IP management interface

== Changelog ==

= 1.0.0 =
* Initial release
* Core security features implementation
* Basic admin interface
* Login protection system
* IP tracking functionality

== Upgrade Notice ==

= 1.0.0 =
Initial release with core security features and basic administration interface.

== Privacy Policy ==

This plugin collects IP addresses and login attempt information for security purposes. This data is stored in your WordPress database and can be deleted through the plugin's cleanup tools or upon plugin deletion.

The following data is collected and stored:
* IP addresses of login attempts
* Timestamps of login attempts
* Username used in login attempts
* Success/failure status of login attempts

This data is used solely for security purposes and is not shared with any third parties. Data retention periods are configurable through the plugin settings.

== Support ==

For support please visit:
* [Plugin Support Forum](https://wordpress.org/support/plugin/attribute-login-access/)
* [Documentation](https://imbrick.com/docs/attribute-login-access/)
* [Contact Us](https://imbrick.com/contact/)