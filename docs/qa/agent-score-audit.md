# ScoreAuditor — accessibility score 0–100

Agent: ScoreAuditor  
Date: 2026-08-17  
Scope: reproduce broken aggregate scoring vs page-average fix

## Root cause (pre-1.1.1)

Formula per page (unchanged):

`score = max(0, min(100, 100 − critical×10 − major×5 − minor×2 − warning×1))`

At scan finish, `ScanController::handle_finish()` called `IssueRepository::severity_counts( $scan_id )`, which **sums all issue rows** for the scan, then applied the formula once.

## Fixture comparison

| Scenario | Old (aggregate) | New (page average) |
| --- | ---: | ---: |
| 1 page, 1 Major | 95 | 95 |
| 10 pages, each 1 Major | 100 − 50 = **0** | 10 × 95 / 10 = **95** |
| 1 page 5 Critical + 9 clean pages | 100 − 50 = **50** (if counted as 5 on one URL only) | (50 + 900) / 10 = **95** |
| 5 scanned: 2 pages with issues, 3 clean | Penalty on total issues only | Average of page scores + 3×100 |

## Clean pages

URLs successfully saved with `inserted = 0` have no rows in `accg_issues` but increment `scanned_urls`. The fix treats `clean_pages = scanned_urls − count(urls with issues)` as score **100** each.

## Fix (1.1.1)

- `ScoreCalculator::calculate_site_score( $counts_by_url, $scanned_urls )`
- `IssueRepository::severity_counts_by_url( $scan_id )`
- `handle_finish()` uses site average; aggregate `severity_counts()` unchanged for Issues UI totals

## Out of scope

- Historical scan rows are not recalculated
- Passes still do not affect the score
