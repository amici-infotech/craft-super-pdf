# Troubleshooting

## Custom fonts / `defaultFont`

DomPDF uses:

1. **`@font-face` + CSS `font-family`** to load and select a font  
2. **`defaultFont`** only as a **fallback** when a requested family/weight/style is missing

You do **not** need `defaultFont` for `@font-face` itself to work. Headings (`h1`/`h2`) are **bold by default**. If you only registered `font-weight: 400` / `normal`, DomPDF falls back to `defaultFont` (plugin default: `arial`) for those headings — which looks like “the font didn’t apply to headings.”

### Recommended pattern

1. Use **filesystem paths** (`@webroot`), not `@web` URLs — DomPDF often fails to install bold/italic faces from relative web URLs.
2. Register every weight you use (at least regular + bold).
3. Prefer numeric weights (`400`, `700`).
4. Optionally set `defaultFont` in config to your family name.
5. Clear **Super PDF Font Cache** after changes.

```twig
{% set fontDir = alias('@webroot') ~ '/fonts/montserrat' %}
<style>
@font-face {
    font-family: 'Montserrat';
    font-style: normal;
    font-weight: 400;
    src: url('{{ fontDir }}/Montserrat-Regular.ttf') format('truetype');
}
@font-face {
    font-family: 'Montserrat';
    font-style: normal;
    font-weight: 700;
    src: url('{{ fontDir }}/Montserrat-Bold.ttf') format('truetype');
}
body, p, h1, h2, h3 {
    font-family: 'Montserrat', DejaVu Sans, sans-serif;
}
</style>
```

```php
// config/super-pdf.php
'defaultFont' => 'Montserrat',
'fontDir' => Craft::getAlias('@storage/super-pdf/fonts'),
'fontCache' => Craft::getAlias('@storage/super-pdf/font-cache'),
```

## Blank HTML page instead of PDF

`type: render` / `download` must stream via Dompdf and exit. If you see an empty `text/html` page, confirm you are on Super PDF **5.1.0+** (older mid-request `Craft::$app->end()` attempts blanked Twig responses).

## Blank or broken PDF content

- Ensure the Twig template does not extend a CP layout.
- Prefer a minimal HTML document without Craft debug chrome.
- Check `storage/logs` (Dev Mode rethrows generation exceptions).

## Signed URL 403

- Link expired — increase `signedUrlExpiry` or regenerate the object.
- `securityKey` changed between generate and download.
- Filename mismatch (must match the stored basename).
- `requireSignedUrls` is on and the link has no valid `e`/`s` params.

## Queue jobs not running

Start a queue worker: `php craft queue/listen`, or enable Craft’s queue runner.

## Console generate fails

```bash
php craft super-pdf/generate --template=your/template --filename=test --type=object
```

Provide either `--template` or `--html`. Use `--elementId` when the template expects `entry`.

## Deprecated settings

- Replace `type: 'url'` with `type: 'object'`.
- Replace `streamContext` with `httpContext`.

## Yii / Craft comments in PDF

Avoid `extends "_layouts/..."` CP layouts in PDF templates. Use a minimal HTML document template.
