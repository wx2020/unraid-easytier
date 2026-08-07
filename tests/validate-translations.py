#!/usr/bin/env python3

from pathlib import Path
import re


ROOT = Path(__file__).resolve().parents[1]
ENGLISH = ROOT / "translations/en_US/EasyTier/easytier.txt"
CHINESE = ROOT / "translations/zh_CN/EasyTier/easytier.txt"
RESERVED = re.compile(r"^(null|yes|no|true|false|on|off|none)$", re.IGNORECASE)
LOOKUP_PUNCTUATION = re.compile(r"&amp;|[?{}|&~!\[\]()/\\:*^.\"']|<.+?/?>")
TRANSLATE_CALL = re.compile(r"translate\(\s*(['\"])(.*?)\1\s*\)", re.DOTALL)


def read_language_file(path: Path) -> dict[str, str]:
    entries: dict[str, str] = {}
    for number, raw_line in enumerate(path.read_text(encoding="utf-8").splitlines(), 1):
        line = raw_line.strip()
        if not line or line.startswith("#"):
            continue
        if "=" not in line:
            raise RuntimeError(f"{path}:{number}: missing '='")
        key, value = line.split("=", 1)
        key = key.strip()
        if key in entries:
            raise RuntimeError(f"{path}:{number}: duplicate key {key!r}")
        entries[key] = value.strip()
    return entries


def runtime_key(text: str) -> str:
    key = LOOKUP_PUNCTUATION.sub("", text.strip())
    if RESERVED.fullmatch(key):
        key += "."
    return re.sub(r" {2,}", " ", key)


def parsed_file_key(key: str) -> str:
    return f"{key}." if RESERVED.fullmatch(key) else key


english = read_language_file(ENGLISH)
chinese = read_language_file(CHINESE)

if english.keys() != chinese.keys():
    missing = sorted(english.keys() - chinese.keys())
    extra = sorted(chinese.keys() - english.keys())
    raise RuntimeError(f"translation key mismatch: missing={missing}, extra={extra}")

empty_chinese = sorted(key for key, value in chinese.items() if not value)
if empty_chinese:
    raise RuntimeError(f"empty Chinese translations: {empty_chinese}")

available_runtime_keys = {parsed_file_key(key) for key in english}
missing_source_keys: set[str] = set()
for php_file in (ROOT / "src").rglob("*.php"):
    source = php_file.read_text(encoding="utf-8")
    for match in TRANSLATE_CALL.finditer(source):
        key = runtime_key(match.group(2))
        if key not in available_runtime_keys:
            missing_source_keys.add(key)

if missing_source_keys:
    raise RuntimeError(f"source strings without translation keys: {sorted(missing_source_keys)}")

required = {
    "Configuration Server": "配置服务器",
    "A valid Config Server Address overrides local EasyTier settings":
        "有效的配置服务器地址会覆盖本地 EasyTier 设置。",
    "Local EasyTier Configuration": "本地 EasyTier 配置",
    "EasyTier Config File": "EasyTier 配置文件",
    "Configuration file used by EasyTier core":
        "EasyTier 核心程序使用的配置文件（通过 -c 指定）",
}
for key, expected in required.items():
    actual = chinese.get(key)
    if actual != expected:
        raise RuntimeError(f"incorrect Chinese translation for {key!r}: {actual!r}")

print(f"Validated {len(english)} EasyTier translation keys.")
