# CompatMatrix QA — Accessibility Guardian 1.1.0

Agent: CompatMatrix QA  
Date: 2026-08-13  
Scope: scenarios **B1–B7**  
Production PHP: **not modified**  
Browser scans: **not run** (no long iframe runs; no site-wide `X-Frame-Options` injection)

## Environment

| Item | Value |
| --- | --- |
| Plugin under test | Accessibility Guardian **1.1.0** (active) |
| WP-test site | `/home/esmobg/Документи/Web APP/WordPress plugin/acessability check/.wp-test/site` |
| PHP | 8.3.14 (`/.wp-test/bin/php`) |
| Database | SQLite (`sqlite-database-integration` 2.2.23) |
| Active theme (start/end) | **Twenty Twenty-Five** 1.5 (`is_block_theme=yes`) |
| Content | **994** published posts, **6** pages, **1** public CPT (`sureforms_form` #1629) |

Temporary `accg_settings` and theme changes were applied for B1/B4 and **restored** at the end (`stylesheet=twentytwentyfive`, `include_post_types=["post","page"]`, `include_terms=false`).

Legend: **PASS** | **FAIL** | **SKIP**

## Summary

| ID | Scenario | Result |
| --- | --- | --- |
| B1 | Classic theme + posts/pages | **PASS** |
| B2 | Block theme / Blocksy / TT5 | **PASS** |
| B3 | WooCommerce `product`; shop/cart not in UrlProvider | **SKIP** (Woo live) / **PASS** (code) |
| B4 | Public CPT + term archives on/off | **PASS** |
| B5 | X-Frame-Options DENY → failed log, scan continues | **PASS** (code+FAQ) / **SKIP** (live headers) |
| B6 | Payload cap 512KB → HTTP 413 | **PASS** (code) / **SKIP** (live 413) |
| B7 | Legacy `ag_*` tables/options migrate to `accg_*` | **FAIL** |

---

## B1 — Classic theme + posts/pages

**Result: PASS**

Installed themes:

| Theme | Status | `theme.json` | `wp_is_block_theme()` | Notes |
| --- | --- | --- | --- | --- |
| twentytwentyfive | **active** (restored) | yes | yes | Block theme |
| twentytwentyfour | inactive | yes | (block) | Block theme |
| twentytwentythree | inactive | yes | (block) | **Block** theme, not classic (only `patterns/*.php`) |
| astra 4.13.4 | inactive | yes (hybrid) | **no** | Classic PHP templates (`index.php`, `page.php`, `single.php`) |
| blocksy 2.1.44 | inactive | yes | — | Hybrid; see B2 |

There is **no** Twenty Twenty / Twenty Twenty-One style classic default theme. **Astra** is the classic (non-FSE) theme on this site.

Live check: switched to Astra (`is_block_theme=no`), then restored Twenty Twenty-Five. Published posts/pages exist (994 + 6). UrlProvider with default settings returns home + post/page permalinks (queue capped; see B4 caveat).

---

## B2 — Block theme / Blocksy / Twenty Twenty-Five

**Result: PASS**

- **Twenty Twenty-Five 1.5** is installed and was the active theme (`is_block_theme=yes`).
- **Blocksy 2.1.44** is installed (inactive). Companion plugin `blocksy-companion` 2.1.44 is active.
- **Twenty Twenty-Four 1.5** is also present (inactive).

No Blocksy activation was required: presence is enough. Active block theme (TT5) was confirmed live.

---

## B3 — WooCommerce `product`; shop/cart not in UrlProvider

**Result: SKIP (Woo live) / PASS (code)**

**SKIP reason:** WooCommerce is **not installed**. `plugin list` has no `woocommerce`; `class_exists('WooCommerce')=no`; `post_type_exists('product')=no`; no `wp-content/plugins/*woo*` directory. Leftover `woocommerce_*_page_title` options exist (likely Astra Starter Templates) but do not register a `product` post type.

**Code (PASS):**

- Grep of `src/Scan/UrlProvider.php` (workspace and WP-test plugin copy): **no** `shop`, `cart`, or `woocommerce` strings. UrlProvider only emits home, public post-type permalinks, and (optional) public taxonomy term links.
- Default `include_post_types` is **`post`, `page` only**:
  - `Installer::seed_default_settings()` (`src/Activation/Installer.php`):
    `'include_post_types' => array( 'post', 'page' )`
  - Live `accg_settings` (restored): `include_post_types: ["post","page"]`
- Live: adding `product` to settings while Woo is absent yields `scannable_post_types() = post,page` (`array_intersect` with public types). Empty `include_post_types` would scan all public types (`post,page,sureforms_form` here), which is the documented “all public types” path — not the seeded default.

Unit coverage: `tests/phpunit/UrlProviderTest.php` includes `product` only as a stubbed public type, not as hardcoded shop/cart URLs.

---

## B4 — Public CPT + term archives on/off

**Result: PASS**

A public CPT already exists — **no registration required**.

| CPT | ID | Permalink | Flags |
| --- | --- | --- | --- |
| `sureforms_form` | 1629 Simple Contact Form | `http://localhost:8089/form/simple-contact-form/` | `public=true`, `publicly_queryable=true`, `has_archive=true` |

**Code:** `UrlProvider::scannable_post_types()` intersects settings with `get_post_types( array( 'public' => true ) )` (attachment removed). `include_terms` gates `get_taxonomies( array( 'public' => true ) )` + `get_terms()`.

**Live (settings restored afterward):**

| Settings | Queue | CPT / terms |
| --- | --- | --- |
| `include_post_types=sureforms_form`, terms off | home + **Simple Contact Form** | CPT **included** |
| `include_post_types=page`, `include_terms=true` | 7 URLs | term **`category: Uncategorized`** present |
| same, `include_terms=false` | 6 URLs | term **absent** |

**Caveat (scale, not a B4 miss):** with default `post`+`page` and 994 posts, a 500-URL slice never reached the CPT or the Uncategorized archive (posts fill the cap first). Default cap in code is now `UrlProvider::DEFAULT_URL_LIMIT = 1000` (`accg_scan_url_limit` filter, hard max 10000). CPT/terms still work when those types are actually in the sliced queue.

---

## B5 — X-Frame-Options DENY / CSP `frame-ancestors none`

**Result: PASS (code + FAQ) / SKIP (live header injection)**

**SKIP live:** site-wide DENY / `frame-ancestors 'none'` was **not** injected (would break other agents).

**Code (PASS) — per-URL continue:**

`assets/js/scanner.js` `loadFrame()` rejects when `contentDocument` throws (typical DENY / frame-ancestors failure):

```172:177:assets/js/scanner.js
				try {
					var doc = frame.contentDocument || frame.contentWindow.document;
					resolve( { win: frame.contentWindow, doc: doc } );
				} catch ( err ) {
					reject( err );
				}
```

The scan loop catches that, logs a per-URL failure, then **always** advances:

```221:228:assets/js/scanner.js
			.catch( function () {
				state.failures += 1;
				logLine( entry.label + ' — ' + t( 'failed', 'could not be scanned' ), 'error' );
			} )
			.then( function () {
				state.index += 1;
				setProgress( state.index, state.queue.length );
				processNext();
			} );
```

After finish, remaining failures are summarized (`pagesFailed` / “pages could not be scanned”). The queue is not aborted.

**FAQ / README:** documents the iframe requirement:

> The in-browser scanner needs pages to be embeddable in a same-origin iframe, so it will not work if the site sends `X-Frame-Options: DENY` (most sites don't).

Wording is **site-level** (“will not work”) rather than “this URL fails and the rest continue.” Behavior in `scanner.js` is the per-URL continue path. CSP `frame-ancestors` is not named in the README but hits the same `catch`.

---

## B6 — Large violation payload / 512KB cap

**Result: PASS (code) / SKIP (live 413)**

**SKIP live:** did not POST a >512KB `accg_save_results` payload (risk of filling `accg_issues` / stressing the shared WP-test site).

**Code (PASS):** `ScanController::handle_save()` rejects payloads over **524288** bytes with HTTP **413**:

```148:149:src/Scan/ScanController.php
		if ( strlen( $raw ) > 524288 ) {
			wp_send_json_error( array( 'message' => __( 'Scan payload is too large.', 'accessibility-guardian' ) ), 413 );
```

524288 = 512 × 1024. Check is `strlen` on the raw JSON string **before** `json_decode`. Pages whose compacted axe payload stays under this cap save normally; oversize URLs get 413 rather than a fatal.

---

## B7 — Legacy `ag_*` → `accg_*`

**Result: FAIL** (code path exists; this site did **not** consolidate)

**Code:** `Installer::maybe_migrate_legacy()` exists (**private**), called from `activate()` **before** `install_tables()`, and from `maybe_upgrade()` (`admin_init` in `Plugin::init()`). It:

1. `SHOW TABLES LIKE` for each pair `ag_scans|issues|history` → `accg_*`
2. `RENAME TABLE` only if **old exists and new does not**
3. Copies `ag_settings` → `accg_settings` and `ag_db_version` → `accg_db_version` only if the new option is absent

SQLite: `$wpdb->get_results("SELECT name FROM sqlite_master WHERE type='table'")` and `SHOW TABLES LIKE %s` **both work** on this site (sqlite drop-in translates `SHOW TABLES`).

**Live tables:**

| Table | Present | Rows |
| --- | --- | --- |
| `wp_ag_scans` | yes | 8 |
| `wp_ag_issues` | yes | 4945 |
| `wp_ag_history` | yes | 8 |
| `wp_accg_scans` | yes | 1 |
| `wp_accg_issues` | yes | 1 |
| `wp_accg_history` | yes | 1 |

**Live options:** `accg_db_version=1.1.0` and `accg_settings` exist; **`ag_db_version=1.0.1` and `ag_settings` still exist**.

Because `accg_*` tables already exist, `maybe_migrate_legacy()` **skips RENAME**. Legacy 1.0 scan data (4945 issues) is **not** moved. Uninstall would drop both prefixes (`uninstall.php`), but runtime ScanRepository only reads `accg_scans`.

This is an upgrade-gap: dual-prefix leftover is possible when 1.1 tables are created without a prior rename (or when 1.0 tables are reintroduced). Happy-path (only `ag_*` present) was not simulated.

---

## Restore verification

| Item | After tests |
| --- | --- |
| Theme | `twentytwentyfive` / Twenty Twenty-Five |
| `accg_settings.include_post_types` | `["post","page"]` |
| `accg_settings.include_terms` | `false` |
| `accg_settings.batch_size` / `wcag_level` | `5` / `aa` |

No production plugin PHP was changed. No DENY headers were added. No long browser scans were run.
