# AdminUxSmoke — Accessibility Guardian 1.1.0

Date: 2026-08-13  
Target: http://localhost:8089  
Login: `admin` / `admin123`  
Plugin admin: `/wp-admin/admin.php?page=accessibility-guardian`  
Browser: `user-chrome-devtools` (requested `cursor-ide-browser` was not registered in this session). No plugin PHP was edited. Full-site scan was **not** started.

Legend: **PASS** | **FAIL** | **SKIP**

| ID | Result | Evidence |
| --- | --- | --- |
| A1 | **PASS** | Left menu name is exactly **Accessibility Guardian** (`#toplevel_page_accessibility-guardian` → `.wp-menu-name`). Submenus: Dashboard, Run Scan, Issues, Settings. Plugin dashboard `<h1>` is also “Accessibility Guardian”. |
| A2 | **PASS** | **Empty (before scan):** “No scans yet. Run your first accessibility scan to see results here.” CTA **Run your first scan** → `admin.php?page=accessibility-guardian-scan` (did not click; that URL is full-site). **After single-page scan:** score **95/100 Excellent**, Pages scanned **1**, Errors **1**, Warnings **0**, Passed checks **22**, Issues by severity Critical/Major/Minor/Warning, Recent scans row `2026-08-13 14:29:22 / Single / 1 / 95 / Complete`. |
| A3 | **PASS** | Opened `admin.php?page=accessibility-guardian-scan&post_id=2`. UI: “Single page scan: **Sample Page**” + **Start single page scan**. Clicked start only. Finished in well under 60s: **Scan complete 1/1**, progressbar `aria-valuenow=100`, log “Queued URLs: 1 / Sample Page — 1 issues / Score: 95% (Excellent)”. Issues page: tabs **All 1 / Critical 0 / Major 1 / Minor 0 / Warning 0**; card pill **Major** (rule `list`). No `serious` string in Issues HTML or dashboard. |
| A4 | **SKIP** | Live full-site / cancel / `fail_stale` not run (queue ~500–1000 URLs). Observed idle full-site page `?page=accessibility-guardian-scan` (no `post_id`): copy mentions “at most 1000 URLs (`accg_scan_url_limit`)”, buttons **Start full site scan** and **Cancel**. Did **not** click Start. |
| A5 | **PASS** | Settings `?page=accessibility-guardian-settings`. **WCAG level** `#ag-wcag-level` combobox, options A / **AA (recommended)** (current `aa`). **Batch size** `#ag-batch-size` number, value `5`, min 1 max 50. Did **not** save. Help text is **not** misleading about progress: “Reserved for future server-side batching. The current in-browser scanner always processes one page at a time.” |
| A6 | **PASS** | Dashboard **Export:** buttons **CSV** and **JSON** present after scan. Hrefs: `admin-post.php?action=accg_export&scan_id=1&_wpnonce=…&format=csv` and `…&format=json`. Download click optional; not performed. Export controls are on Dashboard, not Issues. |
| A7 | **SKIP** | Live subscriber login not performed. Users search `subscriber_qa`: **1 item**, login `subscriber_qa`, email `subscriber@example.test`, role **Subscriber**. Capability / AJAX 403 not exercised. |
| A8 | **PASS** | Snapshots: WP skip links “Skip to main content” / “Skip to toolbar”; settings Batch size spinbutton and WCAG combobox named; auto-fix checkboxes wrapped in labels; scan progressbar `aria-label="Scan progress"` + `aria-live=polite` summary; dashboard severity links e.g. `aria-label="View 1 Major issues"`. Tab from Batch size landed on WCAG level. Focus ring on `#ag-batch-size`: `box-shadow: rgb(56, 88, 233) 0px 0px 0px 2px`. Notes: post-type checkbox names include slugs (“Posts post”); CSV/JSON buttons have short names with adjacent “Export:” text; issue message HTML is stripped (“and must only directly contain , or elements”). |
| A9 | **PASS** | Single-page scan is query-arg only: `?page=accessibility-guardian-scan&post_id=2` → “Start single page scan”. Same page **without** `post_id` → “Start full site scan”. Sample Page editor (`post.php?post=2&action=edit`): no editor/metabox scan control; only sidebar menu items “Accessibility Guardian” / “Run Scan”. |

## Method notes

- Did not start a full-site scan.
- Did not submit Settings.
- Did not click CSV/JSON download.
- Did not log out / log in as `subscriber_qa`.
- A later tab visit saw another single-page URL (`post_id=1671` Custom Contact) already complete; A3 verdict is based on the `post_id=2` Sample Page run started in this session.

## Coordinator addendum

Live checks this agent skipped were done outside the browser:

| ID | Catalog result | Evidence |
| --- | --- | --- |
| A6 | **PASS** (download) | Cookie-authenticated GET of the dashboard export URLs: CSV `text/csv` attachment, JSON `application/json` attachment |
| A7 | **PASS** (not SKIP) | `subscriber_qa` session: plugin admin page HTTP 403 (“Sorry, you are not allowed”); `accg_start_scan` AJAX HTTP 403 `You are not allowed to run scans.` |
| A3 extra | **PASS** | Second single-page run `post_id=1671` Custom Contact → `accg_scans.id=2`, status `complete`, score 90 |

A4 remains **SKIP** in the browser. Cancel→`complete` is **FAIL (P1)** in `agent-scan-engine.md` and the go/no-go report.
