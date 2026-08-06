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

To test the Chinese translation in Unraid, create `zh_CN.zip` with
`EasyTier/easytier.txt` from `translations/zh_CN/` as its only entry, then
upload it from **Tools > webGUI > Language** in developer view. Unraid stores
language-pack entries by basename, so this becomes
`/usr/local/emhttp/languages/zh_CN/easytier.txt`.

The release workflow also publishes a ready-to-upload
`easytier-zh_CN.zip` asset.
