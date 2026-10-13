#!/usr/bin/env python3
"""Assemble the GitHub Pages website from site/ into an output folder.

Usage:
    python bin/build-site.py                # -> .site-build/
    python bin/build-site.py --out _site    # used by .github/workflows/pages.yml

Copies site/, adds the icon, banner and screenshots from wp-org-assets/
(so binary files are not duplicated in git) and replaces the __VERSION__
and __BUILD_DATE__ placeholders with the plugin version and today's date.
"""

from __future__ import annotations

import argparse
import datetime
import re
import shutil
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
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


def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument("--out", default=".site-build", help="Output folder (default: .site-build).")
    args = parser.parse_args()

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
        "__VERSION__": plugin_version(),
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

    print(f"site built in {out} (version {replacements['__VERSION__']})")


if __name__ == "__main__":
    main()
