# Changelog

All notable changes to Accessibility Guardian. The WordPress.org changelog lives in [`readme.txt`](readme.txt).

## 1.2.0 — 2026-10-08

Compatibility and WordPress.org release preparation.

### Compatibility
- Tested with WordPress 6.4 (minimum), 7.1 (latest) and 7.2-alpha, and with PHP 8.0, 8.3, 8.4 and 8.5.
- Plugin Check 2.1.0 (all categories): no errors or warnings.
- CSV export passes every `fputcsv()` argument explicitly (omitting `$escape` is deprecated since PHP 8.4).
- Filter callbacks no longer use strict types that another plugin returning `null` could turn into a fatal error.
- Request values are read without `(string)` casts, so array input cannot trigger "Array to string conversion" warnings.
- Scripts load with `strategy => defer`; admin assets are enqueued by screen hook suffix; axe-core has its own cache-busting version.
- Templates receive their data through `load_template()` arguments instead of `extract()`.
- Admin notices use the `success` type and every screen has `wp-header-end`.

### Added
- Best-practice checks (headings, landmarks, page structure), on by default, reported as warnings.
- "Scan accessibility" toolbar link for one-click single-page scans.
- Multisite support: network activation, setup for new sites, table removal when a site is deleted, uninstall on every site.
- Developer hooks: `accg_scan_urls`, `accg_axe_options`, `accg_required_capability`, `accg_scan_completed`.
- Catalog entries for 40 more axe-core rules (lists, target size, captions, keyboard access, landmarks and more).
- Progress chart scaled to the data, with a point per scan and a "from → to" summary.
- Build script (`bin/build-zip.py`), screenshot and asset tooling (`bin/screenshots/`), CI and release workflows.

### Changed
- The WordPress toolbar is excluded from axe-core and the supplemental checks.
- Issue messages and fix suggestions keep element names such as `<ul>` and `<main>`; exports contain plain text.
- HTML snippets are stored as text, so snippets of form fields, SVGs and frames are no longer lost.
- Automatic fixes: no duplicate skip link on block themes, `:focus-visible` focus outline, link underlining skips buttons and menus, a field labelled only by `title` keeps it, and fixes re-apply to content added later.
- Front-end classes use the `accg-` prefix.
- The scan frame is sandboxed (no top-level navigation or pop-ups) and pages the browser refuses to frame are reported as failures.
- Admin colours meet WCAG AA contrast; the plugin's own screens pass axe-core.

### Fixed
- Cancelling a scan saved it as complete and made it the latest report; it is now stored as `cancelled` and kept out of the history.
- The Cancel button was always visible because core button styles overrode the `hidden` attribute.
- Settings checkboxes for post types ran together on one line.
- Exported CSV cells starting with `=`, `+`, `-` or `@` could be run as spreadsheet formulas.

### Removed
- Migration and clean-up of the pre-1.1 `ag_*` tables and options (never published on WordPress.org; the short prefix could match another plugin's data).
- The unused `accg_scan_status` AJAX endpoint and the unused "batch size" setting.

## 1.1.1 — 2026-08-17
- Site score for multi-page scans is the average of per-page scores instead of penalizing the total issue count.
- Scan results show scores as N/100.

## 1.1.0 — 2026-08-13
- WordPress.org readiness: `accg_` prefix, GPLv2 license file, unminified axe-core source, translatable rule catalog, PHP 8.0 requirement.
- axe "serious" impact maps to Major severity.
- Hardened settings saving, payload size limits and uninstall.
- Full-site scans capped at 1,000 URLs by default (`accg_scan_url_limit`).

## 1.0.0 — 2026-06-04
- Initial release: single-page and full-site scans with axe-core, supplemental checks, scoring, dashboard, CSV/JSON export.
