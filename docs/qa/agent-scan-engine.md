# ScanEngine QA — Accessibility Guardian 1.1.0

**Agent:** ScanEngine QA  
**Date:** 2026-08-13  
**Plugin version:** 1.1.0 (`ACCG_VERSION`)  
**Workspace:** `/home/esmobg/Документи/my   project/WordPress plugin/acessability check`  
**SITE:** `/home/esmobg/Документи/Web APP/WordPress plugin/acessability check/.wp-test/site`

## Charter

- Static inspection of `scanner.js`, `ScanController`, `ResultNormalizer`, `RuleCatalog`, plus read-only WP-CLI eval.
- Did **not** run a full-site 500/1000-URL browser scan.
- Did **not** modify production code or `accg_settings`.
- Did **not** start a UI scan. SITE had `running_scans = 0` at eval time.

Legend: **PASS** | **FAIL** | **SKIP**

## Catalog mapping

| ID | Scenario (this agent’s slice) | Result | Severity |
| --- | --- | --- | --- |
| A4 | Full-site progress, cancel, `fail_stale`. Cancel stores `complete` | **FAIL** | P1 |
| A5 | `batch_size` 1–50 saved **and used by the scanner** | **FAIL** | P2 |
| B5 | X-Frame-Options / CSP `frame-ancestors` → fail log, scan continues | **PASS** | — |
| B6 | Large violation payload: &lt; 512 KiB or HTTP 413 | **PASS** | — |
| C4 | Partial e2e 30–50 URLs + queue-size assertion | **SKIP** (e2e) / **PASS** (queue) | — |

Serious → major is verified (supports A3). Not a catalog row for this agent.

## A4 — Progress, cancel, fail_stale — FAIL (P1)

### Progress — implemented (not live-run)

`processNext()` is strictly sequential: one iframe URL, then `state.index += 1`, then a recursive `processNext()` call. Progress UI updates `ag-progress-bar` / count after each URL.

```184:229:assets/js/scanner.js
	function processNext() {
		if ( state.cancelled || state.index >= state.queue.length ) {
			return finish();
		}
		// ...
			.then( function () {
				state.index += 1;
				setProgress( state.index, state.queue.length );
				processNext();
			} );
	}
```

Iframe load timeout is **30s**, then the URL is logged as failed and the queue continues.

```158:163:assets/js/scanner.js
			var timer = window.setTimeout( function () {
				if ( ! settled ) {
					settled = true;
					reject( new Error( 'timeout' ) );
				}
			}, 30000 );
```

### Cancel — FAIL (P1)

Cancel only sets `state.cancelled = true`. The next `processNext()` still calls `finish()`, which **always** POSTs `accg_finish_scan`. `handle_finish()` then `ScanRepository::complete()` → DB status **`complete`**. Schema statuses are `running` / `complete` / `failed` only. There is no `cancelled` status.

UI copy says “Scan cancelled”; the stored scan is a completed scan with a score, and `latest()` treats it as the latest completed result.

```232:238:assets/js/scanner.js
	function finish() {
		post( 'accg_finish_scan', {
			scan_id: state.scanId,
			passes: state.passes
		} ).then( function ( res ) {
			if ( res && res.success ) {
				var doneLabel = state.cancelled ? t( 'cancelled', 'Scan cancelled' ) : t( 'complete', 'Scan complete' );
```

### `fail_stale` — PASS (code)

`handle_start()` calls `$this->scans->fail_stale()` (default 3600s) before creating a new row. Abandoned “running” scans are marked `failed` on the **next** start, not while the tab is closed.

### Concurrent scan lock — FAIL (P2, related)

`handle_start()` never checks for an existing `running` row. Each start `create()`s another `running` scan. Client-side `if ( state.running ) return` is per tab only. Two admin tabs can overlap.

### `accg_scan_status` — unused

Registered:

```81:82:src/Scan/ScanController.php
		add_action( 'wp_ajax_accg_finish_scan', array( $this, 'handle_finish' ) );
		add_action( 'wp_ajax_accg_scan_status', array( $this, 'handle_status' ) );
```

WP-CLI: `has_action('wp_ajax_accg_scan_status') === true`.

`rg accg_scan_status assets/js` → **no matches**. `scanner.js` never polls status. `handle_status()` also reads `$_GET['scan_id']`, while `post()` always sends POST FormData, so even a naive `post('accg_scan_status', …)` would not supply the id.

Nonce for all scan AJAX is `ScanController::NONCE_ACTION = 'accg_scan'`, matching `wp_create_nonce( ScanController::NONCE_ACTION )` in `AssetManager`. **PASS** (nonce wiring).

`handle_finish` is idempotent when status is already `complete` (returns stored score, does not call `complete()` again). **PASS** (code). Cancel still reaches that path by first completing the scan — that does not fix P1.

## A5 (`batch_size`) — FAIL (P2)

Settings **do** persist `batch_size` clamped to 1–50 (`AdminMenu` save, default 5 in `Installer`). Description: “Number of pages processed before progress is reported.”

The scanner **does not read it**:

- `rg batch_size assets/js/scanner.js` → **no matches**
- `accgScanner` localize in `AssetManager.php` has no `batchSize` / `batch_size`
- `rg batch_size` in plugin PHP hits only `templates/settings.php`, `src/Admin/AdminMenu.php`, `src/Activation/Installer.php`

WP-CLI (read-only): `accg_settings.batch_size = 5`. Throughput is always one URL at a time. Changing the setting cannot affect scan batching.

A5 PRG / `wcag_level` tags were **not** re-tested here.

## B5 — X-Frame-Options / CSP — PASS (code path)

Live header injection was **not** performed (no competing browser scan).

Contract in `loadFrame()`: `contentDocument` access is in try/catch; blocked frames (XFO `DENY`, CSP `frame-ancestors 'none'`, cross-origin) reject. The `processNext()` `.catch()` increments `failures`, logs `{label} — could not be scanned`, then continues to the next URL. A 30s hang also rejects as `timeout` and continues.

This matches “failed log, scan continues.” Residual risk: some browsers fire `onload` on an error document without throwing; that would still be a per-URL failure or a bogus same-origin error page, not a stuck queue.

## B6 — Payload 512 KiB / HTTP 413 — PASS

```148:149:src/Scan/ScanController.php
		if ( strlen( $raw ) > 524288 ) {
			wp_send_json_error( array( 'message' => __( 'Scan payload is too large.', 'accessibility-guardian' ) ), 413 );
```

524288 bytes = 512 KiB. WordPress `wp_send_json_error( …, 413 )` sets HTTP 413.

Client `compactResult()` caps nodes at 25 per rule before POST. PHP still truncates snippets to 2000 characters. A huge page can still exceed 512 KiB; the 413 branch then fails that URL’s save (`saveFailed` log) while `processNext()` continues.

No 512 KiB payload was POSTed (would require a running scan). Guard is static-verified.

## C4 — Partial e2e + queue size — SKIP (e2e) / PASS (queue)

**Not claimed:** 1000 URLs scanned in the browser.

**Queue-size assertion (WP-CLI, read-only, no `accg_start_scan`):**

| Check | Value |
| --- | --- |
| `UrlProvider::DEFAULT_URL_LIMIT` | 1000 |
| `resolve_url_limit()` (no filter) | 1000 |
| `get_full_site_urls( $limit )` count | **1000** |
| First / last labels | Home page / Custom Contact |
| SITE published `post` + `page` | 994 + 6 (plus home → cap binds) |
| `running` scans during eval | 0 |

Workspace and SITE plugin copies both expose `resolve_url_limit()` and default 0 → 1000. Scan page copy documents the cap via `accg_scan_url_limit`.

**30–50 URL browser e2e:** **SKIP** per charter (do not compete with a long UI scan; do not run a full-site browser scan).

## Supporting: serious → major — PASS

`RuleCatalog::severity_from_impact('serious')` → `major`. Unknown-rule fallback in `ResultNormalizer` uses that map. Catalog entries do not store axe impact `serious` as plugin severity.

PHPUnit (`phpunit.xml.dist`, filter `RuleCatalogTest|ResultNormalizerTest`): **8 tests, 138 assertions, OK**.

WP-CLI: unknown rule with impact `serious` normalized to severity `major`.

## WP-CLI snapshot (read-only)

- Plugin 1.1.0; nonce action `accg_scan`.
- Hooks present: `accg_start_scan`, `accg_save_results`, `accg_finish_scan`, `accg_scan_status`.
- `batch_size` setting 5 (unchanged).
- Queue 1000; no running scans.
- `accg_settings` was not written.

## P1 / P2 roll-up

| ID | Issue | Pri |
| --- | --- | --- |
| A4 | Cancel still finalizes via `accg_finish_scan` → status `complete` | P1 |
| A4 | No server-side concurrent-scan lock | P2 |
| A4 | `accg_scan_status` registered, unused (and GET-only) | P2 |
| A5 | `batch_size` saved 1–50, unused by `scanner.js` | P2 |

B5 and B6 are not blockers at code level. C4 does not support a “1000 scanned” claim.
