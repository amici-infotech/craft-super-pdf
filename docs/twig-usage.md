# Twig usage

All helpers hang off `craft.superpdf`.

## From HTML (stream)

```twig
{% set html %}
    <h1>{{ entry.title }}</h1>
    <p>Page SUPER_PDF_TOTAL_PAGES</p>
{% endset %}

{{ craft.superpdf.html(html, {
    filename: entry.slug,
    defaultPaperSize: 'A4'
}) }}
```

With default `type: render`, this streams a PDF and stops the request (the browser shows/downloads the file).

## From a template (save + URL)

```twig
{% set object = craft.superpdf.template('_pdf/invoice', {
    filename: 'invoice-' ~ entry.id,
    type: 'object'
}, {
    entry: entry
}) %}

<a href="{{ object.url }}" target="_blank">Download PDF</a>
```

Object helpers:

| Access | Meaning |
|---|---|
| `object` / `object.url` | Public or signed URL |
| `object.path` | Filesystem path |
| `object.filename` | Filename including `.pdf` |
| `object.asset` | Craft Asset element (volume storage only) |
| `object.size` / `object.dateModified` | File meta |

## Queue from Twig

```twig
{% do craft.superpdf.queueTemplate('_pdf/invoice', {
    filename: 'invoice-' ~ entry.id,
    type: 'object'
}, { entry: entry }) %}
```

Also available: `craft.superpdf.queueHtml(html, settings)`.

Queued jobs always persist (`type` forced to `object` when needed). Run a queue worker (`php craft queue/listen`).

## Headers and footers

```twig
{% set object = craft.superpdf.template('_pdf/body', {
    type: 'object',
    filename: 'report',
    headerHtml: '<div style="font-size:10px;">Confidential</div>',
    footerHtml: '<div style="font-size:10px;">Page total: SUPER_PDF_TOTAL_PAGES</div>'
}, { entry: entry }) %}
```

Or set `headerTemplate` / `footerTemplate` to Twig paths. Partials receive the same vars as the main template.

## Output modes

```twig
{# Force download #}
{{ craft.superpdf.template('_pdf/doc', { type: 'download', filename: 'doc' }, { entry: entry }) }}

{# Binary / API — do not echo into an HTML page #}
{% set pdf = craft.superpdf.template('_pdf/doc', { type: 'string' }, { entry: entry }) %}
{% set b64 = craft.superpdf.template('_pdf/doc', { type: 'base64' }, { entry: entry }) %}
```

## Email attachment (Twig)

```twig
{% set attachment = craft.superpdf.asEmailAttachment(
    null,
    '_pdf/invoice',
    { filename: 'invoice-' ~ entry.id },
    { entry: entry }
) %}
{# attachment.path, attachment.filename, attachment.mimeType, attachment.content #}
```

Prefer the [PHP helper](php-api.md#email-attachments) inside modules/controllers that send mail.

## Read a setting

```twig
{{ craft.superpdf.getSettings('volume') }}
```

## Template tips

- Use a minimal HTML document (doctype, head, body). Do **not** extend CP layouts.
- For custom fonts, use `@webroot` paths and register bold faces for `h1`/`h2` — see [Troubleshooting](troubleshooting.md#custom-fonts--defaultfont).
- Page totals: `SUPER_PDF_TOTAL_PAGES` or `{PAGE_COUNT}`.
