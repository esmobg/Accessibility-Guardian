=== Accessibility Guardian ===
Contributors: accessibilityguardian
Tags: accessibility, wcag, a11y, audit, axe-core
Requires at least: 6.8
Tested up to: 7.0
Requires PHP: 8.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automated WCAG 2.2 AA accessibility auditing for WordPress. Scan your content with axe-core and get prioritized issues with remediation guidance.

== Description ==

Accessibility Guardian audits your WordPress site for accessibility compliance
(WCAG 2.2 Level A & AA) directly from the admin dashboard. It uses the
industry-standard [axe-core](https://github.com/dequelabs/axe-core) engine,
running in your browser against the fully rendered front-end of each page, so
it can evaluate computed color contrast, ARIA, headings, landmarks and form
labelling without any extra server software (no Node.js or headless browser
required).

= Key features =

* Single-page and full-site scans of posts, pages, custom post types,
  WooCommerce products and term archives.
* Real rendered-DOM analysis with axe-core 4.10, plus supplemental checks for
  generic link text, placeholder-only labels, new-window links and linked PDFs.
* Every issue includes a rule id, WCAG reference, severity, category, HTML
  snippet, DOM path, a suggested fix and a documentation link.
* Accessibility score (0-100) with Excellent / Good / Needs Improvement / Poor
  bands.
* Dashboard with summary cards, issues by severity, WCAG category distribution,
  a progress-over-time trend and the most common problems.
* CSV and JSON report export.

= Privacy =

Scanning runs entirely on your own site and in your own browser. No data is
sent to third-party services.

== Installation ==

1. Upload the plugin zip via Plugins > Add New > Upload Plugin, or copy the
   `accessibility-guardian` folder to `wp-content/plugins/`.
2. Activate the plugin through the Plugins screen.
3. Open the new **Accessibility** menu in the admin sidebar.
4. Go to **Run Scan** and start a single-page or full-site scan. Keep the tab
   open until the scan finishes.

== Frequently Asked Questions ==

= Does scanning require Node.js or a headless browser on the server? =

No. Scanning runs in the administrator's browser using a hidden, same-origin
iframe, so standard WordPress hosting is all you need.

= Why does a scan only run while I keep the tab open? =

Because the engine executes in your browser. Scheduled background scanning is on
the roadmap for a future release.

= Which standards are covered? =

WCAG 2.2 Level A and AA via axe-core, which also underpins EN 301 549, the
European Accessibility Act, Section 508 and ADA best-practice expectations.

== Changelog ==

= 1.0.0 =
* Initial release: single-page and full-site scanning, axe-core engine,
  supplemental custom rules, scoring, dashboard and CSV/JSON export.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
