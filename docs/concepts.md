# Concepts

## Architecture

```text
Twig craft.superpdf  ─┐
PHP Plugin::$plugin->pdf ─┼─► services\Pdf ─► DriverFactory ─┬─ DompdfDriver (default)
Queue GeneratePdfJob ─┘         │                            └─ BrowsershotDriver (optional)
                                ├─ storage/super-pdf  or  asset volume
                                └─ events (before/after render & save)
```

Settings come from (lowest → highest priority):

1. Plugin defaults / CP settings  
2. `config/super-pdf.php`  
3. Per-call `$settings` array in Twig or PHP  

## Drivers

| `driver` | Engine | Tagged / accessible PDFs | Extra packages |
|---|---|---|---|
| `dompdf` (default) | DomPDF 3 | No | None (bundled) |
| `browsershot` | Headless Chrome via Spatie Browsershot | Yes (`tagged: true`) | Node / Puppeteer / Chrome (see [Drivers](drivers.md)) |

Existing installs keep DomPDF until you change `driver` in the CP, config, or a per-call settings array. Server setup for Browsershot (macOS, Ubuntu, DDEV, Docker): **[Drivers](drivers.md)**.

## Generation flow

1. Call `html()`, `template()`, queue helpers, console, or the entry action.
2. Settings are merged; existing files may be reused (`resaveBehaviour`).
3. `EVENT_BEFORE_RENDER` can mutate HTML/settings or cancel.
4. The selected driver renders the HTML to PDF bytes.
5. `EVENT_AFTER_RENDER` runs (`$event->dompdf` is set for DomPDF; `$event->pdf` holds bytes for all drivers).
6. Output depends on `type` (stream, return bytes, or save). Saving fires `beforeSave` / `afterSave`.

## Output types

| `type` | Result |
|---|---|
| `render` (default) | Streams the PDF inline (or attachment if `forceDownload` is true) and ends the request |
| `download` | Always streams as an attachment and ends the request |
| `object` | Saves the file and returns a Super PDF asset object |
| `string` | Returns raw PDF binary |
| `base64` | Returns a base64-encoded string |
| `url` | **Deprecated** — same as `object` |

## Storage targets

- **`volume: storage`** (default): files under `storage/super-pdf[/folder]`, served at `/super-pdf/{filename}` (optionally signed — see [Security](security.md)).
- **Asset volume handle**: saves a Craft Asset; URL comes from the volume/filesystem.

## Resave behaviour

When a file with the same name already exists:

| Value | Behaviour |
|---|---|
| `duplicate` | Append a random suffix |
| `override` | Replace |
| `ignore` | Return the existing object without regenerating (`object` / `url` only) |

## Page totals

Use `SUPER_PDF_TOTAL_PAGES` or `{PAGE_COUNT}` in HTML. After render, Super PDF replaces them with the total page count (padded for alignment).

## Headers and footers

`headerHtml` / `footerHtml` or `headerTemplate` / `footerTemplate` are injected as `position: fixed` blocks so DomPDF repeats them on each page.

## Custom fonts

DomPDF needs `@font-face` rules for each weight you use (headings are bold by default). Prefer filesystem paths via `@webroot`. See [Troubleshooting → Custom fonts](troubleshooting.md#custom-fonts--defaultfont).
