=== Plugin Name ===
Contributors: miacodeweb
Donate link: https://miacodeweb.com
Tags: gdpr, cookies, privacy, lgpd, cookie notice
Requires at least: 5.0
Tested up to: 6.5
Stable tag: 1.1.0
Requires PHP: 7.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A lightweight and customizable WordPress plugin to comply with GDPR regulations, blocking tracking scripts until user consent is obtained.

== Description ==

MiaCodeWEB GDPR Cookie Blocker is the ultimate lightweight solution to ensure your WordPress site complies with GDPR and LGPD regulations. Unlike other free plugins that only display a warning banner, our plugin actually **blocks** third-party scripts (like Google Analytics, Meta Pixel, etc.) from executing until the user explicitly clicks the "Accept" button.

Developed by [MiaCodeWEB](https://miacodeweb.com), this plugin has zero negative impact on your site's speed as it requires no external libraries like jQuery.

### Features:
* **Real Script Blocking:** Scripts are paused and not injected into the HTML until consent is granted.
* **Customizable Banner:** Easily change the text and background color to match your brand.
* **Zero Dependencies:** Built with pure PHP and Vanilla JS for maximum performance.
* **Clean Settings Panel:** A native WordPress settings page separated into intuitive tabs.

== Installation ==

1. Upload the `miacodeweb-gdpr-cookies` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to the new 'GDPR Cookies' menu item in your WordPress dashboard.
4. Configure your banner colors and text in the "Appearance" tab.
5. Paste your tracking scripts (e.g., `<script>...</script>`) in the "Scripts" tab.

== Frequently Asked Questions ==

= Does this really block scripts? =
Yes. The scripts pasted in the settings panel are completely omitted from the site's source code until the consent cookie is detected.

= Will this slow down my site? =
Absolutely not. The plugin is written with Vanilla JavaScript and pure PHP, without calling any external frameworks.

= How do I reset my cookie to test it again? =
Clear your browser cookies or open your website in an Incognito/Private window.

== Screenshots ==

1. The admin settings panel with tabs.
2. The appearance customization tab.
3. The cookie consent banner displayed on the frontend.

== Changelog ==

= 1.1.0 =
* Added tabbed navigation to the admin panel.
* Included security enhancements (escaping and sanitization).
* Prepared codebase for official WordPress.org repository.

= 1.0.0 =
* Initial release on GitHub.