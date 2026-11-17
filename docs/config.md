# Config reference

Create `config/super-pdf.php`:

```php
<?php

use craft\helpers\App;
use Craft;

return [
    // CP / general
    'pluginName' => 'Super PDF',
    'hasCpSection' => false,
    'volume' => 'storage',
    'folder' => '',
    'resaveBehaviour' => 'duplicate',
    'signedUrlExpiry' => 3600,
    'requireSignedUrls' => false,
    'entryPdfTemplate' => '',

    // Output defaults
    'type' => 'render',
    'filename' => 'pdf',
    'forceDownload' => false,
    'compress' => true,

    // Driver (default dompdf — safe for existing installs)
    // Setup for browsershot: see docs/drivers.md (Node, Puppeteer, Chrome)
    'driver' => 'dompdf', // or 'browsershot'
    'tagged' => false,    // set true with browsershot for accessible/tagged PDFs

    // Browsershot — only used when driver = browsershot
    // Install steps: docs/drivers.md (macOS / Ubuntu nginx / DDEV / Docker)
    // 'browsershotNodeBinary' => '/usr/local/bin/node',       // or /opt/homebrew/bin/node
    // 'browsershotNpmBinary' => '/usr/local/bin/npm',
    // 'browsershotChromePath' => '/usr/bin/google-chrome-stable',
    // 'browsershotNoSandbox' => true,                         // DDEV / Docker / some Ubuntu hosts
    // 'browsershotTimeout' => 60,
    // 'browsershotMargins' => ['top' => 10, 'right' => 10, 'bottom' => 10, 'left' => 10, 'unit' => 'mm'],

    // DomPDF
    'defaultPaperSize' => 'A4',
    'defaultPaperOrientation' => 'portrait',
    'defaultFont' => 'DejaVu Sans',
    'dpi' => 96,
    'isRemoteEnabled' => true,
    'isPhpEnabled' => false,

    // DomPDF 3 — restrict remote asset hosts when needed
    'allowedRemoteHosts' => [
        // 'cdn.example.com',
    ],

    // Paths (defaults live under storage/super-pdf/ when null)
    // 'rootDir' => Craft::getAlias('@vendor/dompdf/dompdf'),
    // 'fontDir' => Craft::getAlias('@storage/super-pdf/fonts'),
    // 'fontCache' => Craft::getAlias('@storage/super-pdf/font-cache'),
    // 'tempDir' => Craft::getAlias('@storage/super-pdf/temp'),
    // 'chroot' => [Craft::getAlias('@root'), Craft::getAlias('@storage/super-pdf')],

    'httpContext' => [
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ],

    // Encryption
    'encrypt' => false,
    'password' => '',
    'adminPassword' => '',
    'print' => true,
    'modify' => false,
    'copy' => false,
    'add' => false,

    // Fixed chrome (optional)
    'headerTemplate' => null,
    'footerTemplate' => null,
    'headerHtml' => null,
    'footerHtml' => null,
];
```

Per-call Twig/PHP `$settings` override these values for a single generation.

## Drivers & Browsershot server setup

DomPDF needs no extra server packages. Browsershot needs Node.js, the **puppeteer** npm package, and Chrome on the PHP host.

Step-by-step install guides (macOS direct, Ubuntu + nginx, DDEV, Docker) and which `browsershot*` keys to set:

→ **[Drivers](drivers.md)**

## Common path override

If you keep fonts outside the default storage tree:

```php
'fontDir' => Craft::getAlias(App::env('PDF_SYSTEM_PATH')),
'fontCache' => Craft::getAlias(App::env('PDF_SYSTEM_PATH')),
'tempDir' => Craft::getAlias(App::env('PDF_SYSTEM_PATH')),
'defaultFont' => 'Montserrat',
```

Set `defaultFont` to your primary family so missing bold/italic weights fall back to that family instead of DomPDF’s built-in default.

## Deprecated keys

| Old | Use instead |
|---|---|
| `streamContext` | `httpContext` |
| `type: url` | `type: object` |

Both still work in 5.1.0 with deprecation logs.
