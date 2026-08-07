#!/usr/bin/env python3
"""Validate Unraid plugin translation files and literal PHP lookups."""

from __future__ import annotations

import argparse
import re
from pathlib import Path


RESERVED = re.compile(r"^(null|yes|no|true|false|on|off|none)$", re.IGNORECASE)
LOOKUP_PUNCTUATION = re.compile(
    r'''&amp;|[?{}|&~!\[\]()/\\:*^."']|<.+?/?>'''
)
TRANSLATE_CALL = re.compile(
    r'''(?<![\w])(?:translate|_)\(\s*(['"])(.*?)\1\s*\)''',
    re.DOTALL,
)


def read_entries(path: Path) -> dict[str, str]:
    entries: dict[str, str] = {}
    for number, raw_line in enumerate(path.read_text(encoding="utf-8").splitlines(), 1):
        line = raw_line.strip()
        if not line or line.startswith("#"):
            continue
        if "=" not in line:
            raise ValueError(f"{path}:{number}: missing '='")
        key, value = line.split("=", 1)
        key = key.strip()
        if key in entries:
            raise ValueError(f"{path}:{number}: duplicate key {key!r}")
        entries[key] = value.strip()
    return entries


def runtime_key(text: str) -> str:
    key = LOOKUP_PUNCTUATION.sub("", text.strip())
    if RESERVED.fullmatch(key):
        key += "."
    return re.sub(r" {2,}", " ", key)


def parsed_file_key(key: str) -> str:
    return f"{key}." if RESERVED.fullmatch(key) else key


def source_keys(source_root: Path) -> set[str]:
    keys: set[str] = set()
    for path in source_root.rglob("*.php"):
        text = path.read_text(encoding="utf-8")
        keys.update(runtime_key(match.group(2)) for match in TRANSLATE_CALL.finditer(text))
    return keys


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--english-file", type=Path, required=True)
    parser.add_argument("--translated-file", type=Path, required=True)
    parser.add_argument("--source-root", type=Path, required=True)
    args = parser.parse_args()

    english = read_entries(args.english_file)
    translated = read_entries(args.translated_file)

    missing = sorted(english.keys() - translated.keys())
    extra = sorted(translated.keys() - english.keys())
    if missing or extra:
        raise ValueError(f"translation key mismatch: missing={missing}, extra={extra}")

    empty = sorted(key for key, value in translated.items() if not value)
    if empty:
        raise ValueError(f"empty translated values: {empty}")

    available = {parsed_file_key(key) for key in english}
    uncovered = sorted(source_keys(args.source_root) - available)
    if uncovered:
        raise ValueError(f"source lookups without translation keys: {uncovered}")

    print(f"Validated {len(english)} Unraid translation keys.")
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except (OSError, ValueError) as error:
        raise SystemExit(f"translation validation failed: {error}")
