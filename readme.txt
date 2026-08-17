=== Accessibility Guardian ===
Contributors: esmobg
Tags: accessibility, wcag, a11y, audit, axe-core
Requires at least: 6.4
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 1.1.1
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
* Optional, opt-in front-end remediations (skip link, focus outline, language
  attribute, and similar).
* CSV and JSON report export.

= Third-party libraries =

This plugin bundles [axe-core 4.10.2](https://github.com/dequelabs/axe-core/tree/v4.10.2)
by Deque Systems, licensed under the Mozilla Public License 2.0 (GPL-compatible).
The minified runtime is `assets/js/axe.min.js`. The unminified source is shipped
as `assets/js/axe.js`. Upstream source: https://github.com/dequelabs/axe-core

= Privacy =

Scanning runs entirely on your own site and in your own browser. No data is
sent to third-party services. Optional automatic fixes only change front-end
markup on your site when you enable them in Settings.

== Installation ==

1. Upload the plugin zip via Plugins > Add New > Upload Plugin, or copy the
   `accessibility-guardian` folder to `wp-content/plugins/`.
2. Activate the plugin through the Plugins screen.
3. Open the **Accessibility Guardian** menu in the admin sidebar.
4. Go to **Run Scan** and start a single-page or full-site scan. Keep the tab
   open until the scan finishes.

== Frequently Asked Questions ==

= Does scanning require Node.js or a headless browser on the server? =

No. Scanning runs in the administrator's browser using a hidden, same-origin
iframe, so standard WordPress hosting is all you need.

= Why does a scan only run while I keep the tab open? =

Because the engine executes in your browser. Scheduled background scanning is on
the roadmap for a future release.

= A page could not be scanned. Why? =

The scan iframe must be allowed to load your front-end. Security headers such as
`X-Frame-Options: DENY` or `Content-Security-Policy: frame-ancestors 'none'`
block embedding and prevent that URL from being analysed. Allow same-origin
framing for the scan to work.

= Which standards are covered? =

WCAG 2.2 Level A and AA via axe-core, which also underpins EN 301 549, the
European Accessibility Act, Section 508 and ADA best-practice expectations.

= How many pages can a full-site scan cover? =

By default the queue is capped at 1000 URLs (home, then published content in ID
order). Raise or lower the cap with the `accg_scan_url_limit` filter. The scan
still runs in your browser one page at a time, so very large sites take a long
time and require the admin tab to stay open.

= How is the accessibility score calculated? =

Each scanned page starts at 100 and loses points for issues (Critical −10,
Major −5, Minor −2, Warning −1). For multi-page scans, the site score is the
average of those per-page scores. Pages scanned with no violations count as 100.

= Where is the axe-core source code? =

Unminified axe-core 4.10.2 is included as `assets/js/axe.js`. The project is
maintained at https://github.com/dequelabs/axe-core (tag v4.10.2).

== Screenshots ==

1. Dashboard with accessibility score, severity bars and recent scans.
2. Run Scan page with progress, completion summary and issue log.
3. Issues page filtered by severity, with recommended fixes.

== Changelog ==

= 1.1.1 =
* Fix site score for multi-page scans: average per-page scores instead of
  penalizing the total issue count (which could incorrectly show 0).
* Scan UI shows scores as N/100 to match the dashboard.

= 1.1.0 =
* WordPress.org readiness: unique `accg_` prefix, GPLv2 LICENSE.txt, axe-core
  source, i18n for rule catalog, PHP 8.0 requirement.
* Map axe "serious" impact to Major severity so scores and filters stay accurate.
* Harden settings save, payload size, skip-link target filter and uninstall.
* Full-site scans default to 1000 URLs (`accg_scan_url_limit`).

= 1.0.0 =
* Initial release: single-page and full-site scanning, axe-core engine,
  supplemental custom rules, scoring, dashboard and CSV/JSON export.

== Upgrade Notice ==

= 1.1.1 =
Multi-page scan scores are now averaged per page. Re-run a scan to refresh the
dashboard score; older completed scans keep their previous values.

= 1.1.0 =
Database tables and settings keys are renamed to the accg_ prefix. Existing
scan data is migrated automatically on upgrade.
