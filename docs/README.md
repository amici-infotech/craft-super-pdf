# Super PDF documentation

Super PDF turns HTML and Twig templates into PDF files using DomPDF. It supports Craft CMS **4** and **5**.

## Contents

1. [Installation](installation.md)
2. [Concepts](concepts.md)
3. [Twig usage](twig-usage.md)
4. [PHP API](php-api.md)
5. [Backend / CP](backend.md)
6. [Config reference](config.md)
7. [Security](security.md)
8. [Troubleshooting](troubleshooting.md)

## What you can do

- Generate PDFs from HTML or Twig (`render`, `download`, `object`, `string`, `base64`)
- Save to Craft storage or an asset volume
- Queue generation, attach PDFs to email, run console smoke tests
- Hook into render/save with lifecycle events
- Optional signed storage download URLs

## Requirements

- PHP `^8.1`
- Craft CMS `^4.0` or `^5.0`
- `dompdf/dompdf` `^3.0` (installed with the plugin)
- PHP extensions: DOM, MBString, GD (recommended for images)
