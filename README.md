# Super PDF Plugin for Craft CMS 4 / 5

Create PDF files from HTML or Twig templates with DomPDF. Twig-first API, storage/volume persistence, queue support, lifecycle events, and optional signed download URLs.

### Requirements
- PHP 8.1 or higher
- Craft CMS 4.0 or 5.0
- DOM and MBString extensions (GD recommended)

### Features
- Generate PDFs from HTML or Twig templates
- Output modes: `render`, `download`, `object`, `string`, `base64`
- Save to Craft storage or an asset volume
- Optional signed, expiring storage download links
- Queue / async generation
- Email attachment helper
- Console `super-pdf/generate` command
- Header & footer HTML or Twig partials
- Page total placeholder (`SUPER_PDF_TOTAL_PAGES` / `{PAGE_COUNT}`)
- Lifecycle events (`beforeRender`, `afterRender`, `beforeSave`, `afterSave`)
- Encryption and permission flags
- Entry “Download PDF” CP action
- DomPDF 3 with configurable font/temp directories

### Installation

```bash
composer require amici/craft-super-pdf
```

In the Control Panel, go to Settings → Plugins and click **Install** for Super PDF.

### Quick usage

```twig
{% set html %}
    <h1>Hello</h1>
{% endset %}

{{ craft.superpdf.html(html, { filename: "My_PDF" }) }}
```

```twig
{% set object = craft.superpdf.template("_pdf/invoice", {
    filename: "invoice",
    type: "object"
}, { entry: entry }) %}

{{ object.url }}
```

```php
use amici\SuperPdf\Plugin as SuperPdf;

SuperPdf::$plugin->getPdf()->fromTemplate('_pdf/invoice', [
    'filename' => 'invoice',
    'type' => 'object',
], ['entry' => $entry]);
```

```bash
php craft super-pdf/generate --template=_pdf/invoice --filename=smoke --type=object
```

### Documentation
- In-repo docs: [`docs/README.md`](docs/README.md)
- Online: [docs.amiciinfotech.com/craft-cms/super-pdf](https://docs.amiciinfotech.com/craft-cms/super-pdf)

### Support
[Amici Infotech Support](https://amiciinfotech.com/contact) or [GitHub issues](https://github.com/amici-infotech/craft-super-pdf/issues)
