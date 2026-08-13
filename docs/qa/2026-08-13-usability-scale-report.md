# Go / no-go — Accessibility Guardian 1.1.0 usability and ~1000 URL scale

**Date:** 2026-08-13  
**Product:** Accessibility Guardian **1.1.0**  
**Question:** Is the plugin fit for real users, and does a full-site scan hold up on a WordPress site with ~1000 public URLs?  
**Not in scope:** 1000 separate WordPress.org installs; scoring/AI/REST/scheduled scans; Plugin Directory upload.

**Verdict: GO-WITH-LIMITATIONS**

Typical single-site admins can activate, scan one page, read Critical/Major/Minor/Warning issues, export CSV/JSON, and change WCAG A/AA. A ~1000-URL site now queues **1000** URLs (P0 cap raised from 500) and the cap is documented in the scan UI, FAQ, and changelog. A full 1000-URL iframe run was **not** executed (worst case ~8 hours with a 30s timeout per URL and the admin tab left open). Cancel still stores the scan as **complete** (P1). This test database still has leftover `ag_*` tables beside `accg_*` (B7).

---

## Environment

| Item | Value |
| --- | --- |
| Workspace | plugin 1.1.0 (three-space `my   project` copy) |
| Local WP | `.wp-test/site` on `http://localhost:8089/` |
| PHP | 8.3.14 |
| WordPress | 7.0 (update nag to 7.0.4) |
| DB | SQLite |
| Theme | Twenty Twenty-Five (Astra used briefly for B1, then restored) |
| Content | 994 published posts + 6 pages = **1000**; public CPT `sureforms_form` |
| Dist zip | `dist/accessibility-guardian-1.1.0.zip` rebuilt after the URL-cap fix |

## Agent artifacts

| Agent | File |
| --- | --- |
| UnitAndStatic | [agent-unit-static.md](agent-unit-static.md) |
| AdminUxSmoke | [agent-admin-ux.md](agent-admin-ux.md) |
| ScanEngine | [agent-scan-engine.md](agent-scan-engine.md) |
| Scale1000Urls | [agent-scale.md](agent-scale.md) |
| CompatMatrix | [agent-compat.md](agent-compat.md) |
| AutoFixes | [agent-autofixes.md](agent-autofixes.md) |
| Scenario grid | [2026-08-13-scenario-catalog.md](2026-08-13-scenario-catalog.md) |

---

## Results summary

| Area | Outcome |
| --- | --- |
| A user journeys | 8 PASS, A4 FAIL (P1 cancel) with live full-site SKIP |
| B compatibility | B1/B2/B4 PASS; B3/B5/B6 PASS code + SKIP live; **B7 FAIL** |
| C scale | C1/C2/C3 PASS after P0; C4 queue PASS, 30–50 URL e2e SKIP |
| D auto-fixes | D1/D2 PASS |

PHPUnit (fresh, coordinator): **22 tests, 157 assertions, OK** (includes `test_resolve_url_limit_defaults_to_one_thousand` and slice test).  
Plugin Check (UnitAndStatic, pre-rebuild zip copy on SITE): **0 ERROR**, 51 WARNING.

---

## Scale metrics (C)

| Metric | Value |
| --- | --- |
| Published post+page | 1000 |
| `UrlProvider::resolve_url_limit()` | 1000 (filter `accg_scan_url_limit`, hard max 10000) |
| `get_full_site_urls()` default | **1000** |
| `get_full_site_urls(2000)` | **1000** (corpus collapsed: home + 994 posts + 5 unique pages) |
| Queue JSON size | 79968 bytes (~78 KiB) |
| Queue build time | 0.249 s |
| Peak memory delta | +4.00 MiB |
| Iframe timeout | 30 s / URL |
| Worst-case wall time | 1000 × 30 s = **30000 s ≈ 8.33 h** (formula, not measured) |
| 1000-URL browser scan | **not run** |

Pre-fix (original 1.1.0 zip): `get_full_site_urls( int $limit = 500 )` and `ScanController` called the default → **C2 FAIL / P0**.  
Post-fix: default 1000, `ScanController` passes `UrlProvider::resolve_url_limit()`, FAQ + scan intro document the cap.

## P0 applied (because C2 failed on the 500 cap)

| Change | Where |
| --- | --- |
| `DEFAULT_URL_LIMIT = 1000` + `resolve_url_limit()` | `src/Scan/UrlProvider.php` |
| Full-site start uses the resolver | `src/Scan/ScanController.php` |
| UI copy | `templates/scan.php` |
| Honest `batch_size` help | `templates/settings.php` |
| FAQ + changelog | `readme.txt` |
| PHPUnit | `tests/phpunit/UrlProviderTest.php` |

No new scan engine. `batch_size` remains unused by `scanner.js` and is labeled reserved.

---

## P0 / P1 / P2

| Sev | ID | Issue | User impact |
| --- | --- | --- | --- |
| ~~P0~~ | C2 | 500 URL silent truncate | **Fixed** this cycle (default 1000 + filter + docs) |
| P1 | A4 | Cancel finishes the scan as `complete` | Abandoned/cancelled runs look like successful completed scans and become “latest” |
| P1 | B7 | `ag_*` not renamed when `accg_*` already exist | Sites that created 1.1 tables without a prior rename keep old 1.0 rows unread |
| P2 | C3 | Sequential iframe, tab must stay open | Large sites are hours-long; closing the tab leaves a running row until `fail_stale` |
| P2 | — | Plugin Check 51 WARNING | Mostly custom-table / nonce false positives; 0 ERROR |
| P2 | B3 | Woo not in this fixture | Shop/cart URLs are never queued even if Woo is installed |

No activation fatal, no scan data-loss on the happy path, no `serious` leak into the Issues UI.

---

## Fit for users

**Yes, with the limits above**, for:

- Small and mid-size sites (hundreds of URLs, or up to the 1000 cap).
- Admins who keep the Run Scan tab open.
- WCAG A/AA axe tags, CSV/JSON export, optional Guideline 10 fixes (off until checked).

**Not claimed:**

- “1000 URLs were scanned end-to-end.”
- Background / scheduled / multi-tab scanning.
- Automatic migration of leftover `ag_*` rows when `accg_*` tables already exist.
- WooCommerce shop/cart coverage.

**GO for “1000 pages” (catalog rule):** queue ≥ 1000 **and** documented cap — **met** after the P0 fix. Overall product verdict remains **GO-WITH-LIMITATIONS** because of cancel-as-complete, leftover dual tables on this fixture, and the in-browser time bound.

## Out of this cycle (unchanged)

Fixing all Plugin Check warnings, GitHub push, wordpress.org upload.
