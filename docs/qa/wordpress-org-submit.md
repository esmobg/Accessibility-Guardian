# Submit Accessibility Guardian 1.1.0 to WordPress.org

The plugin zip is review-ready after Plugin Check is clean. This document is the human submission path. It does **not** log into wordpress.org for you.

## Before you submit

1. WordPress.org account: [esmobg](https://profiles.wordpress.org/esmobg/) (must match `Contributors: esmobg` in `readme.txt`).
2. Confirm the slug `accessibility-guardian` is free at https://wordpress.org/plugins/accessibility-guardian/ (404 = available).
3. Use the built zip only: `dist/accessibility-guardian-1.1.0.zip` (built with `.distignore`; no tests, docs, git, or `wp-org-assets`).

## First-time listing (Add Plugin)

1. Open https://wordpress.org/plugins/developers/add/
2. Upload `dist/accessibility-guardian-1.1.0.zip`.
3. Wait for the automated checks, then the human review (often several days to weeks).
4. When approved you receive SVN: `https://plugins.svn.wordpress.org/accessibility-guardian/`

## After approval: SVN layout

```text
assets/          ← from wp-org-assets/ plus screenshots
  banner-772x250.png
  icon-256x256.png
  screenshot-1.png
  screenshot-2.png
  screenshot-3.png
trunk/           ← unzipped plugin folder contents
tags/1.1.0/      ← copy of trunk for the stable tag
```

Example:

```bash
svn co https://plugins.svn.wordpress.org/accessibility-guardian/ ag-svn
# copy plugin files into ag-svn/trunk/
# copy wp-org-assets/* and screenshot-*.png into ag-svn/assets/
svn add ag-svn/trunk/* ag-svn/assets/*
svn copy ag-svn/trunk ag-svn/tags/1.1.0
svn ci -m "Initial 1.1.0 release"
```

Directory banners (`banner-1544x500.png`) are optional. `readme.txt` `Stable tag` must match `tags/1.1.0`.

## What reviewers already have

- GPLv2 `LICENSE.txt`
- Unminified axe-core 4.10.2 as `assets/js/axe.js` (MPL-2.0, declared in readme)
- Unique `accg_` / `ACCG_` prefix
- No `load_plugin_textdomain()` (WordPress.org serves translations)
- Screenshots listed in `readme.txt`
