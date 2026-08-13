# Unit and static QA — Accessibility Guardian 1.1.0

Agent: UnitAndStatic  
Date: 2026-08-13  
Workspace: plugin copy with three spaces in `my   project`  
Production plugin code: **not modified**  
Browser scans: **not started**  
Settings: **not changed**

Verdict for this slice: **PASS** (no P0/P1). PHPUnit green. Plugin Check: 0 ERROR, 51 WARNING (expected custom-table / false-positive nonce noise).

---

## 1. PHPUnit

Command:

```text
PHP=/home/esmobg/Документи/Web APP/WordPress plugin/acessability check/.wp-test/bin/php
PHPUNIT=/home/esmobg/Документи/Web APP/WordPress plugin/acessability check/.wp-test/bin/phpunit.phar
cd "<plugin workspace>"
$PHP $PHPUNIT --configuration phpunit.xml.dist
```

| Item | Result |
| --- | --- |
| PHPUnit | 10.5.45 |
| PHP | 8.3.14 |
| Config | `phpunit.xml.dist` |
| Result | **OK** |
| Tests | **20** |
| Assertions | **154** |
| Failures / errors / skipped | 0 / 0 / 0 |
| Runtime | ~0.005 s |
| Memory | 22.91 MB |

Per suite (`--testdox`):

| Class | Tests | Notes |
| --- | --- | --- |
| `ResultNormalizerTest` | 5 | Violation row shape, incomplete → warning, unknown-rule fallback, UTF-8 snippet truncate, severity counts |
| `RuleCatalogTest` | 3 | axe `serious` → plugin `major`; catalog never stores `serious` as severity |
| `ScoreCalculatorTest` | 9 | Perfect 100, weighted penalties, clamp to 0, error/warning counts, band thresholds |
| `UrlProviderTest` | 3 | Empty config → all public types (includes `product`); configured types respected; non-public types dropped |

---

## 2. Plugin Check

Dist zip present: `dist/accessibility-guardian-1.1.0.zip`.  
Target copy (already installed, not re-copied by this agent):

`/home/esmobg/Документи/Web APP/WordPress plugin/acessability check/.wp-test/site/wp-content/plugins/accessibility-guardian`

Command:

```text
$PHP $WPCLI --path=$SITE plugin check accessibility-guardian --format=table
```

| Item | Count |
| --- | --- |
| **ERROR** | **0** |
| **WARNING** | **51** |
| Exit code | 0 |

Warnings by file:

| File | WARNING |
| --- | --- |
| `src/Storage/ScanRepository.php` | 19 |
| `src/Scan/ScanController.php` | 14 |
| `src/Storage/IssueRepository.php` | 13 |
| `src/Activation/Installer.php` | 3 |
| `src/Plugin.php` | 1 |
| `uninstall.php` | 1 |

Warnings by sniff:

| Code | Count | Assessment |
| --- | --- | --- |
| `WordPress.DB.DirectDatabaseQuery.DirectQuery` | 17 | Expected: custom `accg_*` tables, not WP APIs |
| `WordPress.DB.DirectDatabaseQuery.NoCaching` | 14 | Same; scan/issue reads are request-scoped |
| `WordPress.Security.NonceVerification.Missing` | 12 | False positive: `ScanController::guard()` calls `check_ajax_referer( 'accg_scan', 'nonce' )` before `$_POST` reads |
| `WordPress.DB.PreparedSQL.InterpolatedNotPrepared` | 3 | Table identifiers (`RENAME TABLE` / `DROP TABLE`) built from `$wpdb->prefix` + hardcoded suffixes |
| `WordPress.Security.NonceVerification.Recommended` | 2 | `handle_status` reads `$_GET['scan_id']` after the same AJAX nonce guard |
| `PluginCheck.Security.DirectDB.UnescapedDBParameter` | 2 | Legacy rename / history query; identifiers not user input |
| `PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound` | 1 | WP.org hosting note only; not a security defect |

No Plugin Check ERROR. Interpolated DDL is the usual WordPress table-name pattern (cannot bind identifiers). Not raised as P0/P1.

---

## 3. Escape / sanitize spot-check

### 3.1 `ScanController::handle_save`

Path: `src/Scan/ScanController.php`

| Control | Present? |
| --- | --- |
| Capability | Yes — `guard()` → `current_user_can( 'manage_options' )` else JSON 403 |
| Nonce | Yes — `check_ajax_referer( self::NONCE_ACTION, 'nonce' )` (`accg_scan`) **before** POST reads |
| `scan_id` | `absint( wp_unslash( … ) )` |
| `payload` | `wp_unslash` then JSON decode; phpcs ignore documents why it is not `sanitize_*` as a string |
| Size cap | `strlen( $raw ) > 524288` → JSON error **HTTP 413** |
| Decode | `json_decode( $raw, true )` must be `array` else 400 |
| Persist | `ResultNormalizer::normalize()` then `IssueRepository::insert_batch()` |

Assessment: **PASS**. Plugin Check nonce warnings on lines 141–142 do not reflect a missing nonce.

### 3.2 `IssueRepository::insert_batch`

Path: `src/Storage/IssueRepository.php`

`$wpdb->insert()` with explicit formats. Per-field sanitization:

| Column | Sanitizer |
| --- | --- |
| `scan_id` | `int` argument |
| `url` | `esc_url_raw` |
| `post_id` | `(int)` |
| `rule_id` | `sanitize_key` |
| `wcag_ref` | `sanitize_text_field` |
| `severity` | `sanitize_key` |
| `category` | `sanitize_key` |
| `impact` | `sanitize_text_field` |
| `message` | `sanitize_textarea_field` |
| `html_snippet` | `wp_kses_post` |
| `dom_path` | `sanitize_text_field` |
| `fix_suggestion` | `sanitize_textarea_field` |
| `doc_link` | `esc_url_raw` |
| `status` | `(string)` only — **not** `sanitize_key` |
| `created_at` | `current_time( 'mysql' )` |

Production path: `handle_save` → `ResultNormalizer::build_rows()` hardcodes `'status' => 'open'` and sanitizes URL/rule/help/snippet before insert. Client cannot set `status` on that path.

Defense-in-depth gap (not P1): if `insert_batch()` is ever called with a raw client array, `status` would be unsanitized. Current callers are safe.

Assessment: **PASS** for the live save path.

### 3.3 `AdminMenu::maybe_save_settings`

Path: `src/Admin/AdminMenu.php`

| Control | Present? |
| --- | --- |
| Early exit | No `accg_settings_submit` → return |
| Capability | `manage_options` else `wp_die( esc_html__( … ) )` |
| Nonce | `check_admin_referer( 'accg_save_settings' )` |
| `include_post_types` | `array_map( 'sanitize_key', wp_unslash( … ) )`; empty array allowed (UrlProvider then uses all public types) |
| `fixes` | Keys from catalog; submitted keys `sanitize_key`; stored as booleans |
| `wcag_level` | `sanitize_key`; allowlist `a` / `aa`; else `aa` |
| `include_terms` | Checkbox → boolean (`! empty`) |
| `batch_size` | `absint` then `min( 50, max( 1, … ) )` |
| Persist | `update_option( 'accg_settings', $settings )` |
| PRG | Transient notice + `wp_safe_redirect` to settings page + `exit` |

Assessment: **PASS**. Post types are not intersected with public types at save time; `UrlProvider::scannable_post_types()` intersects at scan time.

---

## 4. P0 / P1

| ID | Severity | Finding |
| --- | --- | --- |
| — | — | **None** |

Not P0/P1:

- Plugin Check nonce warnings (guard-before-read).
- Direct `$wpdb` on custom tables.
- `status` string-cast in `insert_batch` (normalizer hardcodes `open`).
- `load_plugin_textdomain` discouragement.

---

## 5. Catalog items this agent can judge

Legend from `2026-08-13-scenario-catalog.md`. Items not listed stay **PEND** for other agents.

| ID | Result | Evidence |
| --- | --- | --- |
| A3 | **PASS** (unit) | `RuleCatalogTest`: `serious` → `major`; catalog severities only `critical` / `major` / `minor` / `warning`. `ResultNormalizerTest` incomplete → `warning`. |
| A5 | **PASS** (unit + static; not e2e) | `UrlProviderTest`: empty post types → all public types. `maybe_save_settings`: `wcag_level` a/aa, `batch_size` 1–50, PRG redirect. Axe tag change vs WCAG level is JS — not executed here. |
| A7 | **PEND** (static only) | `guard()` / `maybe_save_settings` require `manage_options` and nonces. No PHPUnit coverage of 403. Needs e2e subscriber check. |
| B3 | **PASS** (unit + static; not live Woo) | `UrlProviderTest` includes `product` in public types and honors `include_post_types`. `get_full_site_urls()` enumerates home + public post permalinks + optional terms only — no shop/cart endpoints. |
| B6 | **PASS** (static) | `handle_save`: payload `> 524288` bytes → HTTP 413. Not load-tested with a huge page. |
| B7 | **PEND** | `Installer::maybe_migrate_legacy()` renames `ag_*` → `accg_*` and copies options. Not executed. Plugin Check WARNs on interpolated `RENAME TABLE`. |
| D1 | **PASS** (static) | `seed_default_settings()` does not store a `fixes` key. `AutoFixer` treats missing/empty as all off. Save path only enables catalog keys that were posted. |
| C2 | **PEND** (this agent) | Static note only at write time. Coordinator later: PHPUnit 22/157 includes cap tests; live default queue = 1000. |

---

## Coordinator addendum (2026-08-13)

Fresh PHPUnit on the workspace after the URL-cap tests landed:

`OK (22 tests, 157 assertions)` — PHPUnit 10.5.45, PHP 8.3.14.

A7 subscriber 403 was confirmed live (see `agent-admin-ux.md`). B7 live FAIL is in `agent-compat.md`.

---

## 6. Scope limits

- No browser scans.
- No settings writes.
- No production plugin edits.
- Plugin Check ran against the existing `.wp-test` plugin copy, not a fresh unzip in this agent.
