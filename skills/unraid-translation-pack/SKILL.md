---
name: unraid-translation-pack
description: Implement, debug, validate, package, and release Unraid webGUI plugin translations using Unraid's parse_plugin() loader, normalized _() keys, locale files, .dot cache invalidation, package installation, and CI checks. Use when adding Chinese or other Unraid language support, diagnosing untranslated plugin labels, upgrading existing installations, or publishing a plugin with automatic localization.
---

# Unraid Translation Pack

## Objective

Make an Unraid plugin translation work on a fresh install and on an upgrade.
Treat the installed locale file and Unraid's parsed cache as runtime artifacts;
do not stop after editing a source translation file.

Read [the Unraid translation reference](references/unraid-webgui-translation.md)
when checking parser behavior, key normalization, or cache semantics. Use the
bundled validator before committing translation changes.

## Workflow

### 1. Inspect the runtime contract

- Inspect Unraid's `Translations.php` for the target version when behavior is
  uncertain; do not infer gettext behavior from another platform.
- Confirm the active locale, plugin name, webGUI document root, and whether the
  request is rendered by PHP, a `.page` file, JavaScript, or an API fragment.
- Keep PHP source strings in English and route them through `_()` or a helper
  that calls Unraid's `_()`.

### 2. Create the locale files

- Use one lowercase plugin file per locale:
  `/usr/local/emhttp/languages/<locale>/<plugin>.txt`.
- Keep the source layout consistent, normally
  `translations/en_US/<Plugin>/<plugin>.txt` and the matching translated
  locale directory.
- Put `English source key=Translated value` on each line. Keep values on the
  right-hand side; do not put translated text into PHP source.
- Keep all strings used by `parse_plugin('<plugin>')` in the one plugin file;
  page-name files are selected by request URI and are not a replacement for
  the plugin file.

### 3. Normalize and validate keys

- Normalize the PHP lookup text with Unraid's exact punctuation removal and
  repeated-space rules. Preserve punctuation in the displayed value.
- Account for Unraid's special handling of `null`, `yes`, `no`, `true`,
  `false`, `on`, `off`, and `none` keys; the parser gives these runtime keys a
  trailing dot.
- Do not manually "simplify" keys based on assumptions from gettext. In
  particular, check the target Unraid source before deciding whether a symbol
  is removed.
- Run:

  ```powershell
  python <skill-dir>/scripts/validate_unraid_translations.py `
    --english-file translations/en_US/EasyTier/easytier.txt `
    --translated-file translations/zh_CN/EasyTier/easytier.txt `
    --source-root src
  ```

- Require parity between master and translated keys, non-empty translated
  values, and coverage for every literal `translate('...')` or `_('...')`
  call found under the source root.

### 4. Install and upgrade safely

- Install the translation into the webGUI language directory from the plugin
  package or plugin installer. A source file inside the repository is not
  automatically visible to Unraid.
- Prefer embedding the locale file in the plugin's utils/package payload when
  automatic installation is required. If using a separate release asset,
  make the plugin manifest download and checksum it; never publish an asset
  that the manifest does not reference or reference an asset that is not
  published.
- Delete the matching parsed cache after replacing the text file:
  `/usr/local/emhttp/languages/<locale>/<plugin>.dot`.
- Put cache invalidation in the install/upgrade script, after the package has
  copied the new text file. Also remove the cache on uninstall.
- Do not rely on users uploading a language ZIP manually for a plugin that is
  supposed to localize itself.

### 5. Load at the right time

- Call `parse_plugin('<plugin>')` only after Unraid's `parse_plugin()` and `_()`
  are available. If a helper can run outside a normal webGUI request, retry
  instead of marking translations permanently loaded while `_()` is absent.
- Avoid defining a competing global `_()` function. Use Unraid's function and
  preserve the English fallback for tests or non-Unraid rendering.

### 6. Test and release

- Test a fresh package and an upgrade path separately.
- Inspect the built archive for the installed locale path and the cache
  invalidation command.
- Run PHP and shell syntax checks, the translation validator, page rendering
  tests, and `git diff --check`.
- Verify the generated plugin manifest has the correct package version,
  checksums, and URLs. If the locale is embedded in the package, verify the
  release has no obsolete standalone language asset.
- After release, verify the actual asset list and latest tag. The first page
  request after upgrade should recreate `.dot` from the current `.txt`.

## Troubleshooting checklist

When a label remains English, inspect in this order:

1. Confirm the plugin version and active locale are the intended ones.
2. Confirm `/usr/local/emhttp/languages/<locale>/<plugin>.txt` exists and
   contains the exact normalized key.
3. Check for `<plugin>.dot`. If it predates the text file or contains no new
   key, the install script failed to invalidate it.
4. Confirm the PHP page calls `translate()`/`_()` with the expected English
   source string and that the helper calls `parse_plugin()` after Unraid
   translation initialization.
5. Re-run the validator and inspect the generated package, not only the
   repository source.

Never "fix" an untranslated label by hardcoding Chinese directly into the PHP
page; that hides loader, key, locale, and upgrade defects.

## Bundled resources

- `scripts/validate_unraid_translations.py`: deterministic key, source-call,
  and value validation.
- `references/unraid-webgui-translation.md`: parser, cache, packaging, and
  release details that are easy to miss.
