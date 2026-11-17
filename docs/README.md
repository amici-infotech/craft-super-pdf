# Super PDF documentation

Super PDF turns HTML and Twig templates into PDF files. DomPDF is the default driver; optional Browsershot supports tagged/accessible PDFs. It supports Craft CMS **4** and **5**.

## Contents

1. [Installation](installation.md)
2. [Drivers](drivers.md) — DomPDF vs Browsershot; Node/Puppeteer/Chrome setup (macOS, Ubuntu, DDEV, Docker)
3. [Concepts](concepts.md)
4. [Twig usage](twig-usage.md)
5. [PHP API](php-api.md)
6. [Backend / CP](backend.md)
7. [Config reference](config.md)
8. [Security](security.md)
9. [Troubleshooting](troubleshooting.md)

## What you can do

- Generate PDFs from HTML or Twig (`render`, `download`, `object`, `string`, `base64`)
- Choose DomPDF (default) or Browsershot (`driver` + `tagged` for accessible PDFs)
- Save to Craft storage or an asset volume
- Queue generation, attach PDFs to email, run console smoke tests
- Hook into render/save with lifecycle events
- Optional signed storage download URLs

## Requirements

- PHP `^8.1`
- Craft CMS `^4.0` or `^5.0`
- `dompdf/dompdf` `^3.0` (installed with the plugin)
- `spatie/browsershot` (Composer dependency; Node/Chrome needed to run that driver)
- PHP extensions: DOM, MBString, GD (recommended for images)
