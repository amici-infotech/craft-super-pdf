# Changelog

## 5.1.0 - 2026-08-03

### Added
- DomPDF 3 support with explicit `rootDir`, `fontDir`, `fontCache`, `tempDir`, and `chroot` defaults under Craft storage.
- Optional signed, expiring download URLs for PDFs stored in Craft storage (`requireSignedUrls` setting; **default off** for backwards compatibility).
- PHP service aliases `fromHtml()` / `fromTemplate()` plus Twig/PHP `queueHtml()` / `queueTemplate()` helpers.
- Queue job `GeneratePdfJob` for asynchronous PDF generation.
- Output modes: `download`, `string`, and `base64` (in addition to `render` and `object`).
- Header/footer support via `headerHtml` / `footerHtml` or `headerTemplate` / `footerTemplate`.
- `{PAGE_COUNT}` placeholder alias for `SUPER_PDF_TOTAL_PAGES`.
- Clear Caches option for Super PDF font cache.
- Entry element action **Download PDF** when `entryPdfTemplate` is configured.
- Settings for signed URL expiry and entry PDF template.
- `allowedRemoteHosts` DomPDF option passthrough.
- English translation file.
- In-repo documentation under `docs/`.
- Plugin bootstrap class `amici\SuperPdf\Plugin` (legacy `SuperPdf` class retained as a deprecated subclass).
- PDF and General helpers moved from `libraries/` to first-class `services\` components.
- Lifecycle events: `Pdf::EVENT_BEFORE_RENDER`, `EVENT_AFTER_RENDER`, `EVENT_BEFORE_SAVE`, `EVENT_AFTER_SAVE`.
- Email attachment helper `asEmailAttachment()` (PHP + Twig).
- Console command `php craft super-pdf/generate`.
- Dev Mode render timing / options logging.

### Changed
- Storage PDF object URLs include optional signed query parameters (`e`, `s`). Legacy unsigned `/super-pdf/{file}` links still work unless `requireSignedUrls` is enabled.
- DomPDF options are built through a dedicated `Options` object; plugin settings are no longer passed wholesale into Dompdf.
- PDF streaming for `render` / `download` uses Dompdf `stream()` + `exit` again (Craft `$app->end()` blank-pages Twig responses).
- Settings remain viewable when `allowAdminChanges` is false (read-only).
- Composer support/docs/changelog URLs now point at `amici-infotech/craft-super-pdf`.
- Plugin version requirement remains Craft CMS 4 and 5 (`^4.0 || ^5.0`).
- `amici\SuperPdf\libraries\Pdf` / `General` removed; use `amici\SuperPdf\services\Pdf` / `General` (accessed via `Plugin::$plugin->pdf`).

### Deprecated
- `amici\SuperPdf\SuperPdf` class — use `amici\SuperPdf\Plugin`.
- `type: url` — use `type: object` (still works with a deprecation log).
- `streamContext` — use `httpContext` (still works with a deprecation log).

### Security
- Optional HMAC-signed storage URLs (`requireSignedUrls`).
- Filename handling rejects path traversal attempts.

### Fixed
- DomPDF `rootDir` not applied correctly (cause of the 5.0.4 DomPDF 3 revert).
- Removed `error_reporting` masks from Twig variable methods.
- Blank page when using `{{ craft.superpdf.html(...) }}` / `template()` with `type: render` (Craft `Application::end()` during template responses).

## 5.0.4 - 2025-12-24
- Reverted DomPDF v3 force back to v2 as there is an issue with `rootDir` that sets blank for custom fonts unless you manually override it in super-pdf.php

## 5.0.3 - 2025-12-06
- Fixed an issue where in DomPDF v3, Root directory is not auto assigned from dompdf library.

## 5.0.2 - 2025-12-03
- Fixed an issue with SUPER_PDF_TOTAL_PAGES variable where on text alignment to right, it was added spaces of those characters after parsing. #30

## 5.0.1 - 2024-06-29
- Added a feature where pdf can have page counter and total variables to show a page counter in pdf file.

## 5.0.0 - 2024-03-27
- Upgraded plugin to support Craft 5.

## 2.0.2.5 - 2023-05-19
- Solved a bug where strict syntax of PHP classes want allowing to set $dompdf as null. #17

## 2.0.2.4 - 2023-05-17
- Solved a bug where looping data to create multiple pdf creates only 1 pdf due to variable not reseting. #17

## 2.0.2.3 - 2023-03-24
- Solved a bug where yii2 head, body and footer comments were added in PDF HTML #11

## 2.0.2.2 - 2023-03-09
- Fix the issue where clear cache was clearing main folder instead of sub folder.
- Fix the issue where Preview file was creating main folder path instead of sub folder path. that was throwing file not found error.

## 2.0.2.1 - 2023-01-23
- Deprecated `streamContext` variable.
- Introducing new `httpContext` variable that accepts and set that array as [DomPdf httpContext](https://github.com/dompdf/dompdf/pull/2807). In most cases, it will be just to replace variable from `streamContext` to `httpContext` in config/super-pdf.php file.
- Solved a bug where all this variables `streamContext`, `encrypt`, `print`, `modify`, `copy`, `add`, `password`, `adminPassword` were using object pointer instead of array and due to that none of it was working. [#12](https://github.com/amici-infotech/craft-super-pdf/issues/12)

## 2.0.2 - 2022-09-30

> {warning} For "url" type, PDF code will return object instead of URL. Code will not break but advisable to use {{ object.getUrl() }} instead {{ object }}.

> {warning} New Settings section added to give user more control over PDF behaviour. Make sure to review and change settings according to your needs.

- Adding Settings to give user more control over the plugin.
- Introducing filesystems so user can store pdfs outside of storage folder.
- Depending on the settings, We can now set wether we want to regenerate file on each page load, override it or ignore new file creation.
- Type "url" is deprecated. Use "object" instead.
- Object can be use with variables or methods to get the URL or other meta data from the file.

## 2.0.1 - 2022-08-03
> {warning} Super PDF now requires DomPdf v2.0.0 or newer.

## 2.0.0 - 2022-05-10
- Upgraded to support craft cms 4.

> {warning} Super PDF now requires PHP 8.0.2 or newer.

> {warning} Super PDF now requires Craft CMS 4.0.0 or newer.

## 1.0.7 - 2022-03-09
- Added new setting `streamContext` where user can pass any HTTP Context.

## 1.0.6 - 2021-xx-xx
- Added Option to clear pdf cache in craft cms's clear cache utility.
