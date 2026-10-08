# Submitting Accessibility Guardian 1.2.0 to WordPress.org

This is the step-by-step path from this repository to a live listing at `https://wordpress.org/plugins/accessibility-guardian/`. The steps that need the **esmobg** WordPress.org account must be done by the account owner.

## 0. Pre-flight (already done for 1.2.0)

| Check | Result |
| --- | --- |
| Plugin Check 2.1.0, all categories, errors + warnings, on the built zip (WP 7.1.3, PHP 8.5) | No errors, no warnings |
| Activation, admin pages, full scan, cancel, export, settings on WP 6.4.13 + PHP 8.0 | No PHP errors or notices |
| Same on WP 7.1.3 + PHP 8.5 | No PHP errors or notices |
| Multisite (network activation, new site, site deletion, uninstall) on WP 7.2-alpha + PHP 8.4 | Works; uninstall removes all tables and options |
| Block theme (Twenty Twenty-Five) with all automatic fixes enabled | One skip link (core's), prefixed classes, fixes re-applied to late content |
| axe-core WCAG 2.2 AA + best-practice on the plugin's own admin screens | 0 violations |
| Slug `accessibility-guardian` | Free (`plugins_api` returns "Plugin not found") |
| Name | No existing plugin with the same or a confusingly similar name |
| `readme.txt` | Short description 140/150 characters, 5 tags, `Stable tag` = `Version` = 1.2.0, `Tested up to: 7.1` |

Re-run these after any code change. CI runs PHP lint (8.0–8.5), PHPUnit and Plugin Check on every push.

## 1. Get the zip

Use the release zip, never GitHub's "Download ZIP":

- From the GitHub release: `https://github.com/esmobg/Accessibility-Guardian/releases/tag/v1.2.0` → `accessibility-guardian-1.2.0.zip`, or
- build it locally: `python bin/build-zip.py` → `dist/accessibility-guardian-1.2.0.zip`.

The zip contains only the plugin (46 files, about 450 KB) inside an `accessibility-guardian/` folder. Tests, docs, tooling, screenshots and banners are excluded by `.distignore`.

## 2. Submit for review (account owner)

1. Sign in at https://login.wordpress.org/ as **esmobg**. The account needs two-factor authentication enabled and a verified email address.
2. Open https://wordpress.org/plugins/developers/add/.
3. Upload `accessibility-guardian-1.2.0.zip`, confirm the checkboxes (you own the code, it is GPL-compatible, you follow the guidelines) and submit.
4. The automated checks run immediately. Then the plugin waits in the review queue. You will receive emails at the account address; reply to the reviewer from that email thread.

### If the reviewer asks questions

Typical topics and where the answers are:

| Topic | Answer |
| --- | --- |
| Bundled library | axe-core 4.10.2 by Deque, MPL-2.0 (GPL-compatible). Minified `assets/js/axe.min.js` plus unminified source `assets/js/axe.js`. Declared in readme under "Third-party library". |
| External services / remote calls | None. No `wp_remote_*`, no CDN, no tracking. Documentation links are plain links opened only on click. |
| Data stored | Three prefixed tables (`{prefix}accg_*`) and two options (`accg_settings`, `accg_db_version`); all removed in `uninstall.php`, on every site of a network. |
| Prefixing | PHP namespace `AccessibilityGuardian\`, constants `ACCG_*`, options/tables/hooks/AJAX `accg_*`, handles `accg-*`, JS globals `accg*`, CSS `ag-` (admin screens) / `accg-` (front end). |
| Security | Every AJAX/admin-post handler checks a nonce and the capability (`manage_options` by default, filterable). Input is unslashed and sanitized; output is escaped; SQL uses `$wpdb->prepare()`. |
| Front-end impact | Nothing loads on the front end unless the site owner enables an automatic fix. |
| Claims | The readme says clearly that automated testing does not certify compliance. |

If the reviewer requests changes: fix them in this repo, bump to 1.2.1 if code changed, rebuild the zip and reply with it in the same email thread.

## 3. After approval: publish with SVN (account owner)

You receive the SVN URL `https://plugins.svn.wordpress.org/accessibility-guardian/`. Use your WordPress.org username and an **SVN password** (set it in your WordPress.org profile under "Account & Security").

```bash
svn checkout https://plugins.svn.wordpress.org/accessibility-guardian ag-svn
cd ag-svn

# Plugin code: the contents of the zip's accessibility-guardian/ folder go into trunk/.
unzip ../accessibility-guardian-1.2.0.zip -d /tmp/ag
cp -R /tmp/ag/accessibility-guardian/. trunk/

# Listing assets: icons, banners, screenshots and the Live Preview blueprint.
cp ../Accessibility-Guardian/wp-org-assets/icon.svg \
   ../Accessibility-Guardian/wp-org-assets/icon-128x128.png \
   ../Accessibility-Guardian/wp-org-assets/icon-256x256.png \
   ../Accessibility-Guardian/wp-org-assets/banner-772x250.png \
   ../Accessibility-Guardian/wp-org-assets/banner-1544x500.png \
   ../Accessibility-Guardian/wp-org-assets/screenshot-*.png assets/
mkdir -p assets/blueprints
cp ../Accessibility-Guardian/wp-org-assets/blueprints/blueprint.json assets/blueprints/

# Tag the release (Stable tag in readme.txt points to it).
svn add --force trunk assets
svn copy trunk tags/1.2.0

svn status            # review
svn commit -m "Release 1.2.0"
```

Notes:

- SVN `assets/` is for the listing only and never ships in the plugin zip.
- The listing shows `screenshot-N.png` with the captions from the `== Screenshots ==` section of `readme.txt` (1 = Dashboard, 2 = completed scan, 3 = Issues, 4 = Settings).
- `assets/blueprints/blueprint.json` adds a **Live Preview** button. It installs the plugin from WordPress.org, creates a small demo site with typical problems and opens the Run Scan page. It only works once the plugin is published.
- The listing updates within a few minutes; the download zip within about 15 minutes.

## 4. Every later release

1. Update the version (plugin header, `ACCG_VERSION`, `Stable tag`), `readme.txt` changelog and `CHANGELOG.md`.
2. Push to `main`, wait for CI, then push a tag `vX.Y.Z` (the release workflow builds the zip and creates the GitHub release).
3. In SVN: copy the new files into `trunk/`, `svn copy trunk tags/X.Y.Z`, commit.
4. When a new WordPress version is released, test it and bump `Tested up to` (a readme-only change can be committed to `trunk/` and the current tag without a new version).
