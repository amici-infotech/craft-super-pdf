# Changelog

## 1.1.1 - 2024-06-29
- Added a feature where pdf can have page counter and total variables to show a page counter in pdf file.

## 1.1.0 - 2023-11-16
- Upgraded to use DomPdf v2.x.

#### DomPdf 2.0.x highlights
- Modifies callback and page_script/page_text handling
- Switches the HTML5 parser to Masterminds/HTML5
- Improves CSS property parsing and representation
- Switches installed fonts and font metrics cache file format to JSON

#### DomPdf 2.0.x Requirements
- PHP 7.1 or greater
- html5-php v2.0.0 or greater
- php-font-lib v0.5.4 or greater
- php-svg-lib v0.3.3 or greater

Additionally, the following are recommended for optimal use:
- GD (for image processing)

> {warning} After upgrade we request user to QA all PDFs to make sure its working.

## 1.0.8.7 - 2023-08-26
- Removed attribute (also known as annotations) syntax form Pdf.php for $domPdf variable as it doesnt support < PHP 8.

## 1.0.8.6 - 2023-05-19
- Solved a bug where strict syntax of PHP classes want allowing to set $dompdf as null. #17

## 1.0.8.5 - 2023-05-17
- Solved a bug where looping data to create multiple pdf creates only 1 pdf due to variable not reseting. #17

## 1.0.8.4 - 2023-03-24
- Solved a bug where yii2 head, body and footer comments were added in PDF HTML #11

## 1.0.8.3 - 2023-03-09
- Fix the issue where clear cache was clearing main folder instead of sub folder.
- Fix the issue where Preview file was creating main folder path instead of sub folder path. that was throwing file not found error.

## 1.0.8.2 - 2023-01-23
- Deprecated `streamContext` variable.
- Introducing new `httpContext` variable that accepts and set that array as [DomPdf httpContext](https://github.com/dompdf/dompdf/pull/2807). In most cases, it will be just to replace variable from `streamContext` to `httpContext` in config/super-pdf.php file.

## 1.0.8.1 - 2022-09-23
- Solved a bug where override option with Assets volume location was taking up file from storage folder.

## 1.0.8 - 2022-09-23
> {warning} For "url" type, PDF code will return object instead of URL. Code will not break but advisable to use {{ object.getUrl() }} instead {{ object }}.

> {warning} New Settings section added to give user more control over PDF behaviour. Make sure to review and change settings according to your needs.

- Adding Settings to give user more control over the plugin.
- Introducing filesystems so user can store pdfs outside of storage folder.
- Depending on the settings, We can now set wether we want to regenerate file on each page load, override it or ignore new file creation.
- Type "url" is deprecated. Use "object" instead.
- Object can be use with variables or methods to get the URL or other meta data from the file. For example:
```
{% set object = craft.superpdf.template("pdf-template", settings, vars) %}
<!-- File URL -->
{{ object }}
{{ object.url }}
{{ object.getUrl() }}
<!-- File PATH -->
{{ object.path }}
{{ object.getPath() }}
<!-- Filename -->
{{ object.filename }}
{{ object.getFilename() }}
<!-- Meta data -->
{{ object.kind }}
{{ object.size }}
{{ object.dateModified|date("m/d/Y H:i:s") }}
<!-- Returns craft assets element. Only if PDF is stored in craft assets volumes instead of storage folder. -->
{{ object.asset }}
{{ object.getAsset() }}
```

## 1.0.7 - 2022-03-09
- Added new setting `streamContext` where user can pass any HTTP Context. For example:
```
'streamContext' => [
    'ssl' => [
        'allow_self_signed'=> TRUE,
        'verify_peer' => FALSE,
        'verify_peer_name' => FALSE,
    ]
]
```

## 1.0.6 - 2021-11-02
- Added Option to clear pdf cache in craft cms's clear cache utility.

## 1.0.5 - 2021-04-23
> {warning} Super PDF now requires PHP 7.2.5 or newer.

> {warning} Super PDF now requires Craft CMS 3.6.0 or newer.

> {warning} If Craft Commerce is installed, Super PDF now requires Craft Commerce 3.3.0 or newer due to DomPdf version upgrade in commerce.

## 1.0.4 - 2021-01-08
- Solved issue where URL pdf type was only working for logged in members.

## 1.0.3 - 2020-10-28
- Solved Error where Craft v3.5+ throws an error while generating pdf.

## 1.0.2 - 2020-04-15
- Solved Error where PHP 7.4 shows deprecated error (Invalid characters passed for attempted conversion).
- Fix Bug with Super PDF where in devMode off, PDF was not rendered.

## 1.0.1 - 2020-03-11
- Change default Paper Orientation to "portrait".
- Minor fixes.

## 1.0.0 - 2020-03-07
- Initial release.
