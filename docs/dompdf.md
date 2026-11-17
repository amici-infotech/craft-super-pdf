# DomPDF driver

DomPDF is the **default** Super PDF engine. It runs entirely in PHP — no Node or Chrome. Use it for most invoices, letters, and reports. Switch to [Browsershot](browsershot.md) only when you need tagged / accessible PDFs.

Server install for choosing drivers: **[Drivers](drivers.md)**.

---

## When to use DomPDF

| Use DomPDF when… | Prefer Browsershot when… |
|---|---|
| You want zero extra server packages | You need tagged / PDF/UA / PAC-pass PDFs |
| Templates are CSS 2.1–style print HTML | You rely on modern CSS (flex/grid, web fonts from CDN quirks) |
| You need PDF password encryption | Encryption is not required |
| Pixel-perfect Chrome parity is not required | You already run Node + Puppeteer + Chrome |

---

## Enable / select

Leave unset (default), or:

```php
// config/super-pdf.php
return [
    'driver' => 'dompdf',
];
```

Per call:

```twig
{{ craft.superpdf.html(html, {
    filename: 'invoice',
    driver: 'dompdf',
    type: 'object'
}) }}
```

```php
Plugin::$plugin->pdf->html($html, [
    'filename' => 'invoice',
    'driver' => 'dompdf',
    'type' => 'object',
]);
```

---

## Configuration reference

Keys below apply to DomPDF (and shared output settings). Set them in `config/super-pdf.php` and/or override per call.

### Paper & typography

| Key | Default | Notes |
|---|---|---|
| `defaultPaperSize` | `A4` | DomPDF paper name (`A4`, `letter`, …) |
| `defaultPaperOrientation` | `portrait` | `portrait` or `landscape` |
| `defaultFont` | `arial` | Fallback family when a weight/style is missing |
| `dpi` | `96` | Render DPI |
| `fontHeightRatio` | `1.1` | Line box height ratio |
| `defaultMediaType` | `screen` | CSS media type DomPDF uses |

### Paths

| Key | Default | Notes |
|---|---|---|
| `rootDir` | `vendor/dompdf/dompdf` | DomPDF install root |
| `fontDir` | `storage/super-pdf/fonts` | Where DomPDF can store/read fonts |
| `fontCache` | `storage/super-pdf/font-cache` | Metrics cache — clear after font changes |
| `tempDir` | `storage/super-pdf/temp` | Temp files |
| `chroot` | `[CRAFT_BASE_PATH, storage/super-pdf]` | Allowed local filesystem roots for images/fonts |

### Remote assets & security

| Key | Default | Notes |
|---|---|---|
| `isRemoteEnabled` | `true` | Allow `http(s):` images/CSS |
| `allowedRemoteHosts` | `null` | DomPDF 3+ host allowlist (empty/`null` = any host when remote is on) |
| `httpContext` | `[]` | Stream/curl options. Super PDF merges redirect-following defaults so CDN 302s (e.g. picsum → Fastly) work |
| `isPhpEnabled` | `false` | Keep off unless you trust every HTML source |
| `isJavascriptEnabled` | `false` | DomPDF JS support (rarely needed) |
| `isHtml5ParserEnabled` | `true` | HTML5 parsing |
| `isFontSubsettingEnabled` | `true` | Smaller embedded fonts |

### Encryption (DomPDF only)

| Key | Default | Notes |
|---|---|---|
| `encrypt` | `false` | Enable CPDF encryption |
| `password` | `password` | User password |
| `adminPassword` | `''` | Owner password |
| `print` / `modify` / `copy` / `add` | `false` | Permission flags when encrypted |

### Shared Super PDF keys

`filename`, `type`, `volume`, `folder`, `resaveBehaviour`, `compress`, `forceDownload`, `headerHtml`, `footerHtml`, `headerTemplate`, `footerTemplate` — see [Config](config.md) and [Twig usage](twig-usage.md).

---

## Minimal document

Always use a full HTML document. Do **not** extend CP layouts.

```twig
{% set html %}
<!doctype html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        @page { margin: 80px 40px; }
        body { font-family: DejaVu Sans, Helvetica, sans-serif; font-size: 12px; color: #222; }
    </style>
</head>
<body>
    <h1>{{ entry.title }}</h1>
    <p>{{ entry.summary }}</p>
</body>
</html>
{% endset %}

{% set pdf = craft.superpdf.html(html, {
    filename: entry.slug,
    driver: 'dompdf',
    type: 'object',
    resaveBehaviour: 'override'
}) %}
```

---

## Margins (`@page`)

DomPDF uses CSS `@page` margins. Fixed headers/footers sit **inside** that box (often with negative `top` / `bottom`).

```css
@page {
    margin: 100px 25px; /* top/bottom 100px, left/right 25px */
}

/* Four-value form also works: top right bottom left */
@page { margin: 120px 30px 80px 30px; }
```

Leave room in the top/bottom margin for fixed chrome (see headers below).

---

## Headers & footers (every page)

### Option A — markup in the HTML (classic DomPDF pattern)

```html
<style>
    @page { margin: 100px 25px; }
    header {
        position: fixed;
        top: -60px;
        left: 0;
        right: 0;
        height: 50px;
        background: #d7ebf5;
    }
    footer {
        position: fixed;
        bottom: -60px;
        left: 0;
        right: 0;
        height: 50px;
        text-align: right;
        background: #d7ebf5;
    }
    footer .page-number:after { content: counter(page); }
</style>

<header><h1 style="margin:10px 15px;font-size:18px;">Acme Invoices</h1></header>
<footer>
    <p style="margin:15px;">Page <span class="page-number"></span> of SUPER_PDF_TOTAL_PAGES</p>
</footer>
```

- `counter(page)` → current page  
- `SUPER_PDF_TOTAL_PAGES` (or `{PAGE_COUNT}` / `{TP}`) → total pages after render  

### Option B — settings injection

```twig
{% set pdf = craft.superpdf.template('_pdf/body', {
    driver: 'dompdf',
    type: 'object',
    filename: 'report',
    headerHtml: '<div style="font-size:10px;">Confidential</div>',
    footerHtml: '<div style="font-size:10px;text-align:right;">Total pages: SUPER_PDF_TOTAL_PAGES</div>'
}, { entry: entry }) %}
```

Or `headerTemplate` / `footerTemplate` pointing at Twig partials. Super PDF injects them as `position: fixed` blocks for DomPDF.

Still define `@page { margin: … }` in the body template so chrome does not collide with content or create an extra blank page.

---

## Page breaks

```css
.page-break { page-break-after: always; }
.page-break:last-child { page-break-after: never; }

/* Keep a block together */
.keep-together { page-break-inside: avoid; }

/* Force a break before a section */
h2.chapter { page-break-before: always; }
```

```html
<div class="page-break">…page 1…</div>
<div class="page-break">…page 2…</div>
```

---

## Page number & total

| Token / technique | Meaning |
|---|---|
| `counter(page)` in CSS (`content: counter(page)`) | Current page (DomPDF) |
| `SUPER_PDF_TOTAL_PAGES` | Replaced with total page count |
| `{PAGE_COUNT}` | Alias of the above |
| `{TP}` | Internal form after normalize (also works) |

Example footer:

```html
<p>Page <span class="page-number"></span> of SUPER_PDF_TOTAL_PAGES</p>
<style>
footer .page-number:after { content: counter(page); }
</style>
```

---

## CSS styles & backgrounds

DomPDF supports a large subset of CSS 2.1 plus many CSS3 bits. Practical tips:

```css
body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
h1 { color: #0b3d5c; font-size: 22px; }
a { color: #0b57d0; text-decoration: underline; }

.card {
    background: #fff3cd;
    border: 2px solid #e6a800;
    padding: 12px;
    margin: 16px 0;
}
```

- Prefer solid colours and borders over heavy `box-shadow` / `filter`.  
- Flexbox/grid support is limited — use tables or simple floats for columns.  
- Background colours on blocks work; full-page background images are unreliable.

---

## Images

### Local images (preferred)

Paths must sit inside `chroot` (project root and `storage/super-pdf` by default).

```twig
<img src="{{ alias('@webroot') }}/images/logo.png" width="160" alt="Logo">
```

Absolute filesystem paths are more reliable than site URLs for DomPDF.

### Remote images

Requires `isRemoteEnabled: true` (default). Super PDF enables HTTP redirect following so hosts that 302 to a CDN still load.

```html
<img src="https://example.com/media/photo.jpg" width="120" alt="Photo">
```

Optional host allowlist (DomPDF 3+):

```php
'allowedRemoteHosts' => [
    'example.com',
    'cdn.example.com',
],
```

If a remote host uses redirects, keep Super PDF’s default `httpContext` follow behaviour (or merge your own):

```php
'httpContext' => [
    'http' => [
        'follow_location' => 1,
        'max_redirects' => 10,
        'user_agent' => 'Mozilla/5.0 (compatible; CraftSuperPdf/DomPDF)',
    ],
    'ssl' => [
        'verify_peer' => true,
        'verify_peer_name' => true,
    ],
],
```

Supported formats: JPEG, PNG, GIF, BMP, WebP (with GD/WebP). SVG support is limited — prefer raster for logos when possible.

---

## Custom fonts

Register **every weight** you use. Headings are bold by default — if only `400` is registered, DomPDF falls back to `defaultFont`.

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

After changing fonts, clear the Super PDF font cache (CP utility or delete `storage/super-pdf/font-cache`).

Built-in families that need no `@font-face`: **DejaVu Sans**, **DejaVu Serif**, **DejaVu Sans Mono**, Courier, Helvetica, Times (core PDF fonts vary by install).

More detail: [Troubleshooting → Custom fonts](troubleshooting.md#custom-fonts--defaultfont).

---

## Links

Normal `<a href="…">` anchors become clickable PDF links.

```html
<a href="https://craftcms.com">craftcms.com</a>
<a href="#section-2">Jump in document</a>
```

---

## Encryption example

```twig
{% set pdf = craft.superpdf.html(html, {
    driver: 'dompdf',
    type: 'object',
    filename: 'secure-doc',
    encrypt: true,
    password: 'user-secret',
    adminPassword: 'owner-secret',
    print: true,
    copy: false,
    modify: false,
    add: false
}) %}
```

Encryption is **not** available with Browsershot.

---

## Console smoke test

```bash
php craft super-pdf/generate \
  --html='<!doctype html><html lang="en"><body><h1>DomPDF OK</h1></body></html>' \
  --filename=dompdf-smoke \
  --type=object \
  --driver=dompdf
```

---

## Limitations (know these)

- Not a full browser — complex CSS may differ from Chrome.  
- No tagged / structure tree / catalog `/Lang` (fails PAC “Tagged PDF”).  
- Remote images fail if remote is disabled, host blocked, or SSL/redirects misconfigured.  
- Progressive JPEG and some exotic colour profiles can fail — re-encode if needed.  
- Inline PHP (`isPhpEnabled`) is a security risk; leave off for untrusted HTML.

---

## Related

- [Browsershot driver](browsershot.md) — tagged PDFs, Chrome CSS  
- [Drivers](drivers.md) — comparison + Browsershot server setup  
- [Config reference](config.md)  
- [Twig usage](twig-usage.md)  
- [Troubleshooting](troubleshooting.md)  
