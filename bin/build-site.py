#!/usr/bin/env python3
"""Assemble the GitHub Pages website from site/ into an output folder.

Usage:
    python bin/build-site.py                                 # -> .site-build/
    python bin/build-site.py --out _site --require-release   # used by .github/workflows/pages.yml

Copies site/, adds the icon, banner and screenshots from wp-org-assets/
(so binary files are not duplicated in git) and replaces the __VERSION__
and __BUILD_DATE__ placeholders.

__VERSION__ is the latest *published GitHub release* that has the plugin zip
attached, so the download buttons never point to a release that does not
exist yet (for example right after the version is bumped on main). Use
--version to override it. Without network access the script falls back to
the plugin header version (for local previews only) unless --require-release
is given.
"""

from __future__ import annotations

import argparse
import datetime
import json
import os
import re
import shutil
import sys
import urllib.request
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
REPO = os.environ.get("GITHUB_REPOSITORY", "esmobg/Accessibility-Guardian")
IMAGES = [
    "icon.svg",
    "icon-256x256.png",
    "banner-1544x500.png",
    "screenshot-1.png",
    "screenshot-2.png",
    "screenshot-3.png",
    "screenshot-4.png",
]
TEXT_SUFFIXES = {".html", ".xml", ".txt"}


def plugin_version() -> str:
    header = (ROOT / "accessibility-guardian.php").read_text(encoding="utf-8")
    match = re.search(r"^\s*\*\s*Version:\s*(\S+)", header, re.MULTILINE)
    if not match:
        sys.exit("Could not read Version from the plugin header.")
    return match.group(1)


def latest_release_version() -> str:
    """Version of the latest published release that has its zip attached."""
    request = urllib.request.Request(
        f"https://api.github.com/repos/{REPO}/releases/latest",
        headers={"Accept": "application/vnd.github+json", "User-Agent": "accg-build-site"},
    )
    token = os.environ.get("GITHUB_TOKEN") or os.environ.get("GH_TOKEN")
    if token:
        request.add_header("Authorization", f"Bearer {token}")

    with urllib.request.urlopen(request, timeout=15) as response:
        release = json.load(response)

    version = str(release.get("tag_name", "")).lstrip("v")
    assets = {asset.get("name") for asset in release.get("assets", [])}
    if not version or f"accessibility-guardian-{version}.zip" not in assets:
        raise RuntimeError(f"latest release {release.get('tag_name')!r} has no accessibility-guardian-{version}.zip")
    return version


def resolve_version(args: argparse.Namespace) -> str:
    if args.version:
        return args.version.lstrip("v")
    try:
        return latest_release_version()
    except Exception as error:  # noqa: BLE001 - network or API problems
        if args.require_release:
            sys.exit(f"Could not determine the latest release: {error}")
        fallback = plugin_version()
        print(f"warning: {error}; using the plugin header version {fallback} (preview only)", file=sys.stderr)
        return fallback


def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument("--out", default=".site-build", help="Output folder (default: .site-build).")
    parser.add_argument("--version", help="Release version to link to (default: latest published release).")
    parser.add_argument("--require-release", action="store_true", help="Fail instead of falling back to the plugin header version.")
    args = parser.parse_args()

    version = resolve_version(args)

    out = Path(args.out)
    if not out.is_absolute():
        out = ROOT / out
    shutil.rmtree(out, ignore_errors=True)
    shutil.copytree(ROOT / "site", out)

    img_dir = out / "assets" / "img"
    img_dir.mkdir(parents=True, exist_ok=True)
    for name in IMAGES:
        shutil.copy2(ROOT / "wp-org-assets" / name, img_dir / name)

    replacements = {
        "__VERSION__": version,
        "__BUILD_DATE__": datetime.date.today().isoformat(),
    }
    for path in out.rglob("*"):
        if path.is_file() and path.suffix in TEXT_SUFFIXES:
            text = path.read_text(encoding="utf-8")
            for placeholder, value in replacements.items():
                text = text.replace(placeholder, value)
            path.write_text(text, encoding="utf-8", newline="\n")

    leftovers = [p for p in out.rglob("*") if p.is_file() and p.suffix in TEXT_SUFFIXES and "__VERSION__" in p.read_text(encoding="utf-8")]
    if leftovers:
        sys.exit(f"Unreplaced placeholders in: {', '.join(str(p) for p in leftovers)}")

    print(f"site built in {out} (release {version})")


if __name__ == "__main__":
    main()
