# Unraid webGUI translation reference

The behavior below is based on Unraid webGUI's `Translations.php`, especially
`parse_plugin()`, `parse_lang_file()`, and `_()`:

<https://github.com/unraid/webgui/blob/84c8ea739e913df9eb3914ad3d15a5158921abe3/emhttp/plugins/dynamix/include/Translations.php>

## Runtime file and loader

For a plugin named `easytier` and locale `zh_CN`, Unraid looks for:

```text
/usr/local/emhttp/languages/zh_CN/easytier.txt
```

`parse_plugin('easytier')` lowercases the plugin name and merges the parsed
file into the global language table. The filename is therefore significant:
`easytier.txt` is reliable; `EasyTier.txt` or a page-specific filename is not
equivalent.

Unraid also writes a serialized cache beside the file:

```text
/usr/local/emhttp/languages/zh_CN/easytier.dot
```

The parser reads the `.txt` file only when the `.dot` file does not exist. It
does not compare timestamps or content. Replacing only `.txt` leaves old keys
active indefinitely. Every installer or upgrade path that replaces a locale
file must remove its matching `.dot` cache.

## Key normalization

The regular `_()` lookup removes the following categories before looking up a
key: `&amp;`, lookup punctuation (`? { } | & ~ ! [ ] ( ) / \ : * ^ . " '`),
and HTML tags. It then collapses repeated spaces. The exact expression can
change with the Unraid release, so inspect the target `Translations.php` when
supporting a new version.

The parser has an additional PHP/INI compatibility rule for keys exactly equal
to `null`, `yes`, `no`, `true`, `false`, `on`, `off`, or `none` (case
insensitive): it appends a dot to the runtime key. Keep the normal source key
in the `.txt` file and let the parser apply that rule.

Example:

```php
translate('A valid Config Server Address overrides local EasyTier settings.')
```

The file key is the normalized form without the period:

```text
A valid Config Server Address overrides local EasyTier settings=有效的配置服务器地址会覆盖本地 EasyTier 设置。
```

Keep punctuation required in the UI on the translated value, not as an
unverified variation of the left-hand lookup key.

## `parse_ini_string()` hazards

`parse_lang_file()` adapts the text file for PHP's INI parser. Avoid casually
adding syntax that has INI meaning to keys or values. Validate the actual
parser behavior for quotes, reserved words, and unusual delimiters. Split
translation lines at the first `=` when writing repository-side validators.

## Packaging and upgrade behavior

The repository file is not the runtime file. A release must install it under
`/usr/local/emhttp/languages/<locale>/` either by:

1. copying it from the utils package, or
2. downloading it from a plugin-manifest `<FILE>` entry with a checksum.

For automatic localization, embedding the file in the package is usually less
fragile than publishing a second language ZIP that users must upload. The
package install script should perform this sequence:

```sh
copy current locale .txt into the webGUI language directory
remove matching locale .dot cache
run the normal package setup
```

The next webGUI request rebuilds the cache. On uninstall, remove both `.txt`
and `.dot` so an old parsed table cannot survive a later reinstall.

## Failure signatures

| Symptom | Likely cause |
| --- | --- |
| Every plugin label is English | Locale is not active, file is missing, or plugin file name/path is wrong. |
| Old labels are Chinese but newly added labels are English | Stale `<plugin>.dot` cache. |
| Only global words such as Yes/No are Chinese | Global `translations.txt` is loaded, but the plugin file was not loaded. |
| Source file has a key but page falls back | Left key is not normalized like `_()`, or the PHP source string differs. |
| Fresh install works but upgrade fails | Upgrade copied `.txt` without invalidating `.dot`. |
| CI passes source checks but Unraid fails | The release package/manifest did not install the runtime file. |

## Verification commands on an Unraid host

Use read-only checks first:

```sh
grep 'locale' /var/local/emhttp/var.ini
ls -l /usr/local/emhttp/languages/zh_CN/easytier.txt \
      /usr/local/emhttp/languages/zh_CN/easytier.dot
grep -n 'Configuration Server\|Local EasyTier Configuration' \
      /usr/local/emhttp/languages/zh_CN/easytier.txt
```

If an upgrade script is being tested and the installed version is known to be
correct, removing only the generated plugin `.dot` cache is a safe diagnostic;
the next page request should recreate it. Prefer fixing the installer so this
is automatic for all users.
