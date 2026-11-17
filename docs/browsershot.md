# Browsershot driver

Browsershot renders PDFs with **headless Chrome** (Spatie Browsershot + Puppeteer). Use it for **tagged / accessible PDFs**, modern CSS, and closer-to-browser layout. DomPDF remains the default for simple PHP-only installs — see [DomPDF](dompdf.md).

**Server setup** (Node, Puppeteer, Chrome) by OS: **[Drivers](drivers.md)** (macOS, Ubuntu, DDEV, Docker).

---

## When to use Browsershot

| Use Browsershot when… | Prefer DomPDF when… |
|---|---|
| You need tagged / PDF/UA / PAC-friendly output | No Node/Chrome on the host |
| Templates use modern CSS that DomPDF struggles with | You need PDF password encryption |
| Remote webfonts / complex images must match the browser | Simplest deploy path |

Composer already requires `spatie/browsershot`. You must still install **Node**, the **puppeteer** npm package, and **Chrome** on the PHP host.

---

## Enable / select

```php
// config/super-pdf.php
return [
    'driver' => 'browsershot',
    'tagged' => true,

    'browsershotNodeBinary' => '/usr/bin/node',
    'browsershotNpmBinary' => '/usr/bin/npm',
    'browsershotChromePath' => '/usr/bin/google-chrome-stable',
    // 'browsershotNoSandbox' => true, // DDEV / Docker / locked-down Linux
];
```

Per call:

```twig
{{ craft.superpdf.html(html, {
    filename: 'accessible-doc',
    driver: 'browsershot',
    tagged: true,
    type: 'object'
}) }}
```

```php
Plugin::$plugin->pdf->html($html, [
    'filename' => 'accessible-doc',
    'driver' => 'browsershot',
    'tagged' => true,
    'type' => 'object',
]);
```

Discover paths on the **same machine** that runs PHP:

```bash
which node && which npm
node -e '(async()=>{const p=require("puppeteer"); console.log(await p.executablePath());})()'
```

---

## Configuration reference

### Driver flags

| Key | Default | Notes |
|---|---|---|
| `driver` | `dompdf` | Set `browsershot` |
| `tagged` | `false` | `true` requests a tagged (accessible) PDF |

### Binaries & process

| Key | Default | Notes |
|---|---|---|
| `browsershotNodeBinary` | system `node` | Absolute path if php-fpm PATH differs |
| `browsershotNpmBinary` | system `npm` | Absolute path to npm |
| `browsershotChromePath` | Puppeteer’s Chrome | Absolute Chrome / Chromium / Chrome for Testing binary |
| `browsershotNoSandbox` | `false` | Required on many Docker/DDEV hosts |
| `browsershotTimeout` | library default | Seconds for the Node process |

### Layout & assets

| Key | Default | Notes |
|---|---|---|
| `browsershotMargins` | auto from `@page` when possible | `['top'=>…,'right'=>…,'bottom'=>…,'left'=>…,'unit'=>'px'|'mm'|'cm'|'in']` |
| `browsershotShowBackground` | `true` | Print CSS backgrounds (Chrome off by default without this) |
| `browsershotAllowLocalFileAccess` | `true` | Local `@font-face` / CSS / images |
| `browsershotWaitUntilNetworkIdle` | `true` | Wait for remote fonts/CSS/images |
| `browsershotContentUrl` | site URL | Base URL for relative `src` / `href` / `@font-face` paths |
| `defaultPaperSize` | `A4` | Passed to Chrome `format` |
| `defaultPaperOrientation` | `portrait` | `landscape` supported |
| `headerHtml` / `footerHtml` | `null` | Native Chromium chrome (see below) |
| `headerTemplate` / `footerTemplate` | `null` | Twig partials resolved into chrome HTML |

Shared keys (`filename`, `type`, `volume`, …): [Config](config.md).

**Not supported:** `encrypt` / password permissions — DomPDF only (skipped + logged on Browsershot).

---

## DomPDF template compatibility

You can usually keep the **same Twig/HTML** used with DomPDF. Super PDF adapts it:

| DomPDF pattern | What Browsershot does |
|---|---|
| `<header>` / `<footer>` with `position: fixed` + negative offsets | Extracted → Chromium native header/footer templates |
| `@page { margin: … }` | Mapped to `browsershotMargins` (and stripped from CSS to avoid double inset) |
| Outer left/right gutter around chrome bars | Applied as horizontal padding on chrome templates |
| `.page-number` + `counter(page)` | Mapped to Chromium `.pageNumber` |
| `SUPER_PDF_TOTAL_PAGES` / `{PAGE_COUNT}` / `{TP}` | Mapped to `.totalPages` in chrome (or two-pass replace in body) |
| Background colours | `showBackground` + `print-color-adjust: exact` |
| Local fonts/images | `allowLocalFileAccess` + optional `browsershotContentUrl` |

Not pixel-identical (Chrome ≠ DomPDF), but the same markup should work without a Browsershot-only rewrite.

---

## Minimal tagged document

Set `lang` on `<html>` for accessibility.

```twig
{% set html %}
<!doctype html>
<html lang="en-GB">
<head>
    <meta charset="utf-8"/>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 12px; }
    </style>
</head>
<body>
    <h1>Accessible heading</h1>
    <p>Body copy with real structure tags in the PDF.</p>
</body>
</html>
{% endset %}

{% set pdf = craft.superpdf.html(html, {
    filename: 'tagged-sample',
    driver: 'browsershot',
    tagged: true,
    type: 'object',
    resaveBehaviour: 'override'
}) %}
```

---

## Margins

### From `@page` (recommended with DomPDF-compatible templates)

```css
@page { margin: 100px 25px; }
```

Super PDF reads this and calls Browsershot `margins()` with the same values. Fixed header/footer space uses the top/bottom; left/right become the outer gutter (including around chrome bars).

### Explicit override

```twig
{% set pdf = craft.superpdf.html(html, {
    driver: 'browsershot',
    tagged: true,
    type: 'object',
    filename: 'margins',
    browsershotMargins: {
        top: 25,
        right: 15,
        bottom: 25,
        left: 15,
        unit: 'mm'
    }
}) %}
```

If you set `browsershotMargins` yourself, you own top/bottom space for native header/footer — leave enough room for their height.

---

## Headers & footers (every page)

### Option A — DomPDF-style fixed chrome in HTML

Same markup as DomPDF; Super PDF maps it to Chromium chrome:

```html
<style>
    @page { margin: 100px 25px; }
    header {
        position: fixed; top: -60px; left: 0; right: 0;
        height: 50px; background-color: lightblue;
    }
    footer {
        position: fixed; bottom: -60px; left: 0; right: 0;
        height: 50px; background-color: lightblue; text-align: right;
    }
    footer .page-number:after { content: counter(page); }
    h1 { color: #0b3d5c; font-size: 22px; }
</style>

<header><h1 style="margin-top:10px;margin-left:15px;font-size:24px;">Some Header Branding</h1></header>
<footer>
    <p>Page <span class="page-number"></span> of SUPER_PDF_TOTAL_PAGES</p>
</footer>
```

### Option B — native Chromium templates via settings

```twig
{% set pdf = craft.superpdf.html(html, {
    driver: 'browsershot',
    tagged: true,
    type: 'object',
    filename: 'native-chrome',
    browsershotMargins: { top: 20, right: 15, bottom: 20, left: 15, unit: 'mm' },
    headerHtml: '<div style="font-size:10px;width:100%;">Confidential</div>',
    footerHtml: '<div style="font-size:10px;width:100%;text-align:right;">Page <span class="pageNumber"></span> of <span class="totalPages"></span></div>'
}) %}
```

Chromium special classes in header/footer HTML:

| Class | Meaning |
|---|---|
| `pageNumber` | Current page |
| `totalPages` | Total pages |
| `date` / `title` / `url` | Chromium builtins (usually leave unused) |

`SUPER_PDF_TOTAL_PAGES` inside `headerHtml` / `footerHtml` is rewritten to `<span class="totalPages"></span>` automatically.

---

## Page number & total

| Technique | Where |
|---|---|
| DomPDF-style `.page-number` + `SUPER_PDF_TOTAL_PAGES` in fixed `<footer>` | Preferred for shared templates — auto-mapped |
| `<span class="pageNumber"></span>` / `<span class="totalPages"></span>` | Native chrome templates |
| `SUPER_PDF_TOTAL_PAGES` in the **body** | Two-pass render (count pages, then substitute) |

---

## Page breaks

Chrome honours standard print CSS:

```css
.page-break { page-break-after: always; break-after: page; }
.page-break:last-child { page-break-after: avoid; break-after: avoid; }
.keep-together { page-break-inside: avoid; break-inside: avoid; }
```

```html
<section class="page-break">…</section>
<section class="page-break">…</section>
```

---

## CSS styles & backgrounds

Browsershot is a real browser — flexbox, grid, modern selectors, etc. work far better than DomPDF.

Backgrounds are **on by default** via `browsershotShowBackground`. Super PDF also injects:

```css
-webkit-print-color-adjust: exact;
print-color-adjust: exact;
```

```css
.card {
    background: #fff3cd;
    border: 2px solid #e6a800;
    padding: 12px;
}
```

Disable backgrounds only if you need ink-saving output:

```twig
{ browsershotShowBackground: false }
```

---

## Images

### Remote

Chrome loads remote images like a browser (redirects, modern formats). With `browsershotWaitUntilNetworkIdle: true` (default), generation waits for network idle so images/fonts finish.

```html
<img class="sample-img" src="https://picsum.photos/120/80" alt="Sample photo">
```

### Local / relative

```html
<img src="/images/logo.png" alt="Logo" width="160">
```

- Relative URLs resolve against `browsershotContentUrl` (defaults to the Craft site URL).  
- Filesystem / `file://` assets need `browsershotAllowLocalFileAccess: true` (default).

```twig
{% set pdf = craft.superpdf.html(html, {
    driver: 'browsershot',
    browsershotContentUrl: alias('@web') ~ '/',
    browsershotAllowLocalFileAccess: true
}) %}
```

---

## Custom fonts

### Local `@font-face`

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
body, h1, h2, h3 { font-family: 'Montserrat', Helvetica, sans-serif; }
</style>
```

Keep `browsershotAllowLocalFileAccess: true`. If you use **web** URLs instead of filesystem paths, ensure `browsershotContentUrl` matches the site and network idle wait is on.

### Remote / Google Fonts

```html
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap" rel="stylesheet">
<style>body { font-family: Inter, sans-serif; }</style>
```

Network idle wait helps these finish before print.

---

## Links

```html
<a href="https://craftcms.com">craftcms.com</a>
```

Links remain clickable in the PDF. Same-document anchors work when the IDs exist in the HTML.

---

## Full shared-template example

Works with **both** drivers (set `driver` as needed):

```twig
{% set html %}
<!doctype html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        @page { margin: 100px 25px; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 12px; color: #222; }
        .page-break { page-break-after: always; }
        .page-break:last-child { page-break-after: never; }
        header {
            position: fixed; top: -60px; left: 0; right: 0;
            background-color: lightblue; height: 50px;
        }
        footer {
            position: fixed; bottom: -60px; left: 0; right: 0;
            background-color: lightblue; height: 50px; text-align: right;
        }
        footer .page-number:after { content: counter(page); }
        h1 { color: #0b3d5c; font-size: 22px; }
        a { color: #0b57d0; }
        .card {
            background: #fff3cd; border: 2px solid #e6a800;
            padding: 12px; margin: 16px 0;
        }
        .sample-img { width: 120px; height: auto; border: 1px solid #999; }
    </style>
</head>
<body>
    <header><h1 style="margin-top:10px;margin-left:15px;font-size:24px;">Some Header Branding</h1></header>
    <footer>
        <p>Page <span class="page-number"></span> of SUPER_PDF_TOTAL_PAGES</p>
    </footer>

    <section>
        <div class="card">
            Styled card. Link: <a href="https://craftcms.com">craftcms.com</a>
        </div>
        <p>Remote image: <img class="sample-img" src="https://picsum.photos/120/80" alt="Sample photo"></p>

        {% for i in 1..2 %}
            <div class="page-break">
                <h1>Page {{ i }}</h1>
                <p>Body copy for page {{ i }}.</p>
            </div>
        {% endfor %}
    </section>
</body>
</html>
{% endset %}

{% set pdf = craft.superpdf.html(html, {
    filename: 'My_PDF',
    driver: craft.app.request.getQueryParam('driver') ?? 'browsershot',
    tagged: true,
    type: 'object',
    resaveBehaviour: 'override'
}) %}
```

---

## Console smoke test

```bash
php craft super-pdf/generate \
  --html='<!doctype html><html lang="en"><body><h1>Browsershot OK</h1></body></html>' \
  --filename=browsershot-smoke \
  --type=object \
  --driver=browsershot \
  --tagged=1
```

---

## Troubleshooting (short)

| Symptom | Fix |
|---|---|
| `Cannot find module 'puppeteer'` | `npm install puppeteer` on the PHP host; see [Drivers](drivers.md) |
| Wrong Node / empty PATH under php-fpm | Set `browsershotNodeBinary` / `browsershotNpmBinary` |
| Chrome crash in Docker/DDEV | `browsershotNoSandbox: true` |
| Missing backgrounds | Keep `browsershotShowBackground: true` |
| Missing local fonts/images | `browsershotAllowLocalFileAccess: true` + correct `browsershotContentUrl` |
| Remote assets incomplete | `browsershotWaitUntilNetworkIdle: true` / raise `browsershotTimeout` |
| Encryption ignored | Use DomPDF for password PDFs |

Full install matrices: [Drivers](drivers.md). Broader help: [Troubleshooting](troubleshooting.md).

---

## Limitations

- Requires Node + Puppeteer + Chrome on every web/queue host that generates PDFs.  
- Heavier and slower than DomPDF.  
- Not pixel-identical to DomPDF for the same HTML.  
- No built-in PDF password encryption.  
- Header/footer templates are HTML **snippets** (Chromium rules) — prefer DomPDF-style fixed chrome in the document and let Super PDF map them.

---

## Related

- [DomPDF driver](dompdf.md)  
- [Drivers](drivers.md) — install Node/Puppeteer/Chrome  
- [Config reference](config.md)  
- [Twig usage](twig-usage.md)  
- [Security](security.md)  
