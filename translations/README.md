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

`translations/en_US/EasyTier/` is the English master set. The Chinese files
are under `translations/zh_CN/EasyTier/`.

To test the Chinese translation in Unraid, create a ZIP whose root contains
the `EasyTier/` directory from `translations/zh_CN/`, name it `zh_CN.zip`, and
upload it from **Tools > webGUI > Language** in developer view.
