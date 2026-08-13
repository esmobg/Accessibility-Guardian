# QA scenario catalog — Accessibility Guardian 1.1.0

Status: **filled**. Final verdict: [2026-08-13-usability-scale-report.md](2026-08-13-usability-scale-report.md).

Legend: PASS | FAIL | SKIP | PEND

## A. User journeys

| ID | Scenario | Result | Evidence |
| --- | --- | --- | --- |
| A1 | Activate without fatal; admin menu title “Accessibility Guardian” | **PASS** | Plugin active 1.1.0; menu + H1 on dashboard (`localhost:8089`) |
| A2 | Empty dashboard CTA to Run Scan | **PASS** | Template empty state “Run your first scan”; live CTA “Run new scan” (site already had a scan) |
| A3 | Single-page scan completes; Issues use critical/major/minor/warning (never `serious`) | **PASS** | Sample Page `post_id=2` scan complete 95%; Custom Contact `post_id=1671` scan id 2 score 90; UI Critical/Major/Minor/Warning; `serious` rows = 0 |
| A4 | Full-site scan progress, cancel, fail_stale. Cancel currently stores `complete` | **FAIL** (P1 cancel) / **SKIP** (live full-site) | Code: cancel → `accg_finish_scan` → `complete`. `fail_stale` on next start. 1000-URL e2e not run |
| A5 | Settings PRG; wcag_level a/aa changes axe tags; batch_size 1–50 saved; empty post types = all public types | **PASS** | PRG redirect; AssetManager tags; batch_size help “Reserved for future…”. Empty types → all public (PHPUnit) |
| A6 | Export CSV/JSON of latest scan (cap 10 000 issues) | **PASS** | `admin-post.php?action=accg_export` CSV + JSON 200 for scan 1 |
| A7 | Subscriber cannot see menu / AJAX 403 | **PASS** | Subscriber HTTP 403 on plugin page; AJAX `accg_start_scan` 403 |
| A8 | Admin UI keyboard, focus, labels | **PASS** | Labeled settings, skip links, severity `aria-label`s, progressbar label. No full AT pass |
| A9 | Single-page scan only via `?post_id=N` (no editor button) | **PASS** | `?post_id=1671` → “Single page scan: Custom Contact”; no editor hook in plugin |

## B. Compatibility

| ID | Scenario | Result | Evidence |
| --- | --- | --- | --- |
| B1 | Classic theme + posts/pages | **PASS** | Astra classic switch + restore; posts/pages in queue ([agent-compat.md](agent-compat.md)) |
| B2 | Block theme / Blocksy / TT5 | **PASS** | Twenty Twenty-Five active (`is_block_theme=yes`); Blocksy installed |
| B3 | WooCommerce `product` in include_post_types; shop/cart not in UrlProvider | **SKIP** (Woo live) / **PASS** (code) | Woo not installed; UrlProvider has no shop/cart |
| B4 | Public CPT + term archives on/off | **PASS** | `sureforms_form` + Uncategorized term toggled; settings restored |
| B5 | X-Frame-Options DENY / CSP frame-ancestors none → failed log, scan continues | **PASS** (code) / **SKIP** (live headers) | scanner.js catch + continue; FAQ |
| B6 | Page with many violations: payload < 512KB or HTTP 413 | **PASS** (code) / **SKIP** (live 413) | `strlen > 524288` → 413 |
| B7 | Legacy `ag_*` tables/options migrate to `accg_*` | **FAIL** | Dual tables: `wp_ag_issues` 4945 rows **and** `wp_accg_*`; rename only if `accg_*` missing |

## C. Scale (~1000 URLs)

| ID | Scenario | Result | Evidence |
| --- | --- | --- | --- |
| C1 | Seed ~1000 published posts/pages | **PASS** | 994 posts + 6 pages = 1000 |
| C2 | `accg_start_scan` queue length (expect 500 today without filter) | **PASS** (after P0) | Pre-fix default 500. Post-fix `resolve_url_limit()=1000`, default queue **1000** |
| C3 | Metrics: time/URL, memory, queue JSON size, `accg_issues` rows, UI freeze | **PASS** | Queue JSON 79968 B; peak +4 MiB; build 0.25 s; worst-case 1000×30s ≈ 8.33 h (formula) |
| C4 | Partial e2e 30–50 URLs + queue-size assertion; do not claim 1000 scanned unless run | **SKIP** (30–50 e2e) / **PASS** (queue size) | Queue 1000 asserted. Single-page e2e only. **Not** 1000 URLs scanned |

## D. Auto-fixes (Guideline 10)

| ID | Scenario | Result | Evidence |
| --- | --- | --- | --- |
| D1 | All automatic fixes off by default | **PASS** | Installer has no `fixes` key; UI checkboxes unchecked; homepage no skip-link |
| D2 | Skip-link `accg_skip_link_target`, focus outline, lang only when opt-in; front end does not break | **PASS** | Opt-in then restore; skip-link `#content` only while enabled |

## Verdict rule

- **GO** for 1000 pages: queue ≥ 1000 **or** documented cap in UI/readme.
- **GO-WITH-LIMITATIONS**: usable for typical sites; scale/compat caveats listed.
- **NO-GO**: activation, scan, or data-loss blockers.
