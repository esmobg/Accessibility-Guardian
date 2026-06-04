# Accessibility Guardian for WordPress

Automated WCAG 2.2 Level AA accessibility auditor that runs entirely inside the
WordPress dashboard. It scans posts, pages, custom post types, WooCommerce
products and term archives using [axe-core](https://github.com/dequelabs/axe-core)
and reports issues with severity ratings, WCAG references and remediation
guidance.

This is the Phase 1 MVP.

## How it works

Scanning runs in the administrator's browser. Each queued URL is loaded into a
hidden, same-origin iframe in `wp-admin`; axe-core (plus a handful of
supplemental custom rules) is injected and executed against the fully rendered
DOM. Results are streamed back to the server, normalized into a canonical issue
schema, stored in custom tables and scored.

Because the engine uses the real rendered DOM, it can evaluate computed color
contrast, ARIA, headings, landmarks and form labelling without any server-side
runtime (no Node.js or headless Chromium required).

```
Admin clicks Scan -> UrlProvider builds the queue -> scanner.js iterates URLs
-> axe.run() in iframe -> AJAX save -> ResultNormalizer -> IssueRepository
-> ScoreCalculator -> Dashboard
```

## Requirements

- PHP 8.2+
- WordPress 6.8+

## Installation

1. Copy this directory into `wp-content/plugins/accessibility-guardian`.
2. Activate **Accessibility Guardian** from the Plugins screen.
3. Open the **Accessibility** menu in the admin sidebar.

The plugin ships with a fallback PSR-4 autoloader, so `composer install` is only
needed for development (PHPUnit).

## Usage

- **Run Scan**: start a full-site scan or a single-page scan. Keep the tab open
  until the scan completes.
- **Dashboard**: accessibility score, summary cards, issues by severity, WCAG
  category distribution, progress over time, most common problems and recent
  scans. Export the latest scan as CSV or JSON.
- **Settings**: choose which post types to include, whether to scan term
  archives, the batch size and the target WCAG level.

## Scoring

Starts at 100 and deducts per issue: critical `-10`, major `-5`, minor `-2`,
warning `-1`, clamped to 0-100.

| Band | Score |
| --- | --- |
| Excellent | 95-100 |
| Good | 80-94 |
| Needs Improvement | 60-79 |
| Poor | < 60 |

## Database tables

- `{prefix}ag_scans` - one row per scan (type, status, totals, score)
- `{prefix}ag_issues` - one row per detected issue
- `{prefix}ag_history` - score history for the trend chart

Settings are stored in the `ag_settings` option.

## Development

```bash
composer install
composer test
```

The PHPUnit suite (`tests/phpunit/`) runs the pure-logic classes
(`ScoreCalculator`, `ResultNormalizer`, `UrlProvider`) with lightweight
WordPress function stubs, so no WordPress install is required to run it.

## Roadmap (not in this MVP)

REST API, AI-assisted fixes, scheduled background scans, one-click automatic
fixes, the front-end highlighter overlay, HTML/PDF reports and page-builder
specific handling are planned for later phases.

## License

GPL-2.0-or-later. Bundles axe-core (Mozilla Public License 2.0).
