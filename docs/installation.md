# Installation

## Composer

```bash
cd /path/to/project
composer require amici/craft-super-pdf
```

Then install the plugin in **Settings → Plugins**.

## Requirements

| Dependency | Version |
|---|---|
| PHP | 8.1+ |
| Craft CMS | 4.x or 5.x |
| DomPDF | 3.x (pulled in by Composer) |

PHP extensions: DOM, MBString, GD (recommended for images).

### Browsershot (tagged / accessible PDFs)

`spatie/browsershot` is installed with the plugin. You still need **Node.js + Puppeteer + Chrome** on the machine that runs PHP.

Follow the step-by-step guides in **[Drivers](drivers.md)** (macOS direct, Ubuntu + nginx, DDEV, Docker), then set `driver` / `tagged` in the CP or [`config/super-pdf.php`](config.md).

## Optional config

Create [`config/super-pdf.php`](config.md) to override CP settings, choose a PDF driver, and DomPDF/Browsershot options (fonts, paper, encryption, remote hosts, and more).

## After upgrading to 5.2.0

1. Run `composer update amici/craft-super-pdf` so `spatie/browsershot` is installed. DomPDF remains the default driver.
2. To enable tagged PDFs: follow [Drivers → Browsershot](drivers.md), then set `driver` → Browsershot and enable **Tagged (accessible) PDF**.
3. Existing Twig/PHP calls keep working unchanged.

## After upgrading to 5.1.0

1. Run `composer update` so DomPDF **3** is installed.
2. Clear caches (**Utilities → Clear Caches**):
   - **Super PDF Cache**
   - **Super PDF Font Cache**
3. Existing Twig (`craft.superpdf.html` / `template`) and project-config settings keep working.
4. Legacy unsigned `/super-pdf/{file}` links keep working (`requireSignedUrls` defaults to **off**). Turn it on when you want stricter downloads — see [Security](security.md).
5. Prefer `amici\SuperPdf\Plugin` and `Plugin::$plugin->pdf` (the `services\Pdf` component). The old `SuperPdf` class name still works as a deprecated subclass.
6. Optionally set **Entry PDF Template** for the CP download action, or try:

```bash
php craft super-pdf/generate --template=_pdf/invoice --filename=smoke-test
```

## Plugin handle / class

| | |
|---|---|
| Handle | `super-pdf` |
| Bootstrap class | `amici\SuperPdf\Plugin` |
| Twig | `craft.superpdf` |
| PDF service | `amici\SuperPdf\services\Pdf` |
