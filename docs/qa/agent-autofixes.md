# AutoFixes — Accessibility Guardian 1.1.0

Agent: AutoFixes (coordinator)  
Date: 2026-08-13  
Site: `http://localhost:8089/`  
Production PHP: not modified by this agent.

## Catalog

| ID | Result | Evidence |
| --- | --- | --- |
| D1 | **PASS** | Installer seed has **no** `fixes` key. Live `accg_settings` after restore: `has_fixes_key=0`. Settings UI: all nine Automatic fixes checkboxes **unchecked**. `AutoFixer::register()` returns early when no flags are on |
| D2 | **PASS** | Default homepage: `ag-skip-link` count **0**. Opt-in `add_skip_link` + `add_focus_outline` + `add_html_lang`: skip-link `href="#content"` (`accg_skip_link_target` default), `ag-fix-focus-outline` on body, `accg-frontend-fixes` assets. Settings restored (`unset fixes`); homepage skip-link count **0** again. Front end HTML still 200 OK |

## Default vs leftover

An earlier session had all fixes enabled in `accg_settings`. That is **not** the installer default. Coordinator restored the option without a `fixes` key before D1/D2 measurement.

## Code

- Skip target filter: `accg_skip_link_target` in `AutoFixer::render_skip_link()` (default `#content`).
- CSS/JS fixes enqueue only when at least one flag is true.
