# Scale1000Urls — Accessibility Guardian 1.1.0

Agent: Scale1000Urls  
Date: 2026-08-13  
Site: `http://localhost:8089/`  
WP path: `/home/esmobg/Документи/Web APP/WordPress plugin/acessability check/.wp-test/site`  
Loaded plugin: `.../wp-content/plugins/accessibility-guardian` version **1.1.0** (active)  
Constraint: production plugin PHP was not modified by this agent; WP settings were not changed.  
Method: `$PHP $WPCLI --path=SITE eval` using `AccessibilityGuardian\Plugin::instance()->service(UrlProvider::class)`.

## Catalog

| ID | Scenario | Result | Evidence |
| --- | --- | --- | --- |
| C1 | Count published post+page | **PASS** | `994` posts + `6` pages = **1000** published |
| C2 | `get_full_site_urls()` default vs `2000`; JSON size of default queue | **PASS** | default **1000** (not &lt; 1000); `get_full_site_urls(2000)` = **1000**; `strlen(wp_json_encode(default))` = **79968** |
| C3 | Peak memory around `get_full_site_urls(2000)`; worst-case iframe time | **PASS** | peak **78970880** B (75.31 MiB); peak delta **4194304** B (4.00 MiB); build **0.2491** s; worst-case **1000 × 30 s = 30000 s** (8.33 h) |
| C4 | 500-URL browser scan | **SKIP** | Not run. E2E subset is owned by AdminUxSmoke. |

**Scale verdict (this agent):** default full-site queue is **1000** (`UrlProvider::DEFAULT_URL_LIMIT` / `resolve_url_limit()`). On this ~1000-URL site, default and `2000` both return **1000** URLs (home + 994 posts + 5 pages; one published page collapsed as the home permalink). No claim is made that 1000 URLs were scanned in a browser.

## C1 — published post+page count

`wp_count_posts()` and WP-CLI `post list --post_type=post,page --post_status=publish --format=count`:

| Type | Published |
| --- | ---: |
| post | 994 |
| page | 6 |
| **total** | **1000** |

Result: **PASS**.

## C2 — UrlProvider queue

Eval used the live Plugin container:

```php
$provider = AccessibilityGuardian\Plugin::instance()
    ->service( AccessibilityGuardian\Scan\UrlProvider::class );
```

Live signatures (loaded file `src/Scan/UrlProvider.php`):

- `DEFAULT_URL_LIMIT = 1000`
- `get_full_site_urls( int $limit = 0 )` — when `$limit < 1`, uses `resolve_url_limit()` (filter `accg_scan_url_limit`, hard cap 10000)
- `ScanController` starts a full-site scan with `get_full_site_urls( UrlProvider::resolve_url_limit() )`

Observed (settings were **not** changed by this agent; `accg_settings.include_post_types` = `post,page`; `include_terms` = false):

| Call | Count |
| --- | ---: |
| `resolve_url_limit()` | 1000 |
| `resolve_url_limit(2000)` | 2000 |
| `get_full_site_urls()` (default) | **1000** |
| `get_full_site_urls(2000)` | **1000** |
| `strlen( wp_json_encode( default queue ) )` | **79968** (~78.1 KiB) |

`2000`-limit composition:

- `1` home (`http://localhost:8089/`, `post_id` 0)
- `994` posts
- `5` pages

That is **1000** unique URLs. The 6th published page is the front page and is removed by `deduplicate()` against `home_url('/')`. Expectation “~1001” matches this corpus after that collapse.

C2 fail rule: **FAIL if default &lt; 1000**. Measured default is **1000**, so **PASS**.

## C3 — memory and worst-case iframe time

Isolated eval: `memory_get_peak_usage(true)` immediately around `get_full_site_urls(2000)` (`memory_limit=-1`):

| Metric | Value |
| --- | --- |
| Peak before call | 74776576 B (71.31 MiB) |
| Peak after call | **78970880 B (75.31 MiB)** |
| Peak delta | **4194304 B (4.00 MiB)** |
| `memory_get_usage(true)` delta | 4194304 B |
| Wall time to build 2000-limit queue | **0.2491 s** |
| `strlen(wp_json_encode(2000-limit queue))` | 79968 (same as default; both lists are 1000 items) |
| `wp_accg_issues` rows | 1 (pre-existing; this agent did not scan) |
| `wp_accg_scans` rows | 1 (pre-existing; this agent did not scan) |

Iframe per-URL timeout in `assets/js/scanner.js` `loadFrame()` is **30000 ms**.

Worst-case iframe time (formula, not a measured scan):

`count(get_full_site_urls(2000)) * 30 s` = **1000 × 30 = 30000 s ≈ 8.33 hours**

Queue build is cheap (~0.25 s, +4 MiB peak). Serial iframe + 30 s timeout is the scale risk if many URLs hang. UI freeze / time-per-URL were **not** measured here.

Result: **PASS** (metrics captured).

## C4 — browser scan

**SKIP.** A 500-URL (or 1000-URL) browser/iframe scan was **not** executed, per Scale1000Urls scope.

End-to-end subset (progress UI, cancel, fail_stale, 30–50 URL smoke) is owned by **AdminUxSmoke**. This report must not be read as “1000 URLs were scanned.”

## Commands (reproducible)

PHP: `/home/esmobg/Документи/Web APP/WordPress plugin/acessability check/.wp-test/bin/php`  
WP-CLI: `/home/esmobg/Документи/Web APP/WordPress plugin/acessability check/.wp-test/bin/wp-cli.phar`  
`--path=`: `/home/esmobg/Документи/Web APP/WordPress plugin/acessability check/.wp-test/site`

## GO rule (informational)

Catalog GO for 1000 pages requires queue ≥ 1000 **or** a documented cap in UI/readme. Measured default queue is **1000**. This agent does not set the final usability verdict (see scenario catalog).
