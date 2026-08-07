# EasyTier Unraid translations

These files use Unraid's language-pack format:

```text
English source text=Translated text
```

The left-hand side is Unraid's normalized lookup key. Apply the same cleanup
as Unraid's `_()` function: remove its lookup punctuation (including `/`,
`.`, `:`, `'`, `!`, `?`, and parentheses), then collapse repeated spaces.
Keep the original English text in the PHP `translate()` calls. Punctuation
needed in the displayed translation belongs on the right-hand side.

The plugin-specific file must be named `easytier.txt` (lowercase). It is
loaded by Unraid's `parse_plugin('easytier')` call, so all EasyTier strings
belong in this single file. `translations/en_US/EasyTier/` is the English
master set, and the Chinese file is under `translations/zh_CN/EasyTier/`.

The release build copies this file into the utils package at
`/usr/local/emhttp/languages/zh_CN/easytier.txt`. Installing or upgrading the
plugin therefore installs the Chinese translation automatically; no separate
language-pack upload or release asset is required.

Unraid stores the parsed result in `easytier.dot` and does not rebuild that
cache when `easytier.txt` is replaced. The package installation script removes
the old `.dot` file on every install or upgrade so Unraid parses the current
translation file on the next page request.
