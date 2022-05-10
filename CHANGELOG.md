# Changelog

## 2.0.0 - 2022-05-10
- Upgraded to support craft cms 4.

> {warning} Super PDF now requires PHP 8.0.2 or newer.

> {warning} Super PDF now requires Craft CMS 4.0.0 or newer.

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
