# Security

## Signed storage URLs

PDFs saved with `volume: storage` and `type: object` return URLs like:

```
https://example.com/super-pdf/invoice.pdf?e=1735689600&s=...
```

| Param | Meaning |
|---|---|
| `e` | Unix expiry timestamp |
| `s` | HMAC-SHA256 of `filename\|expiry` using Craft’s `securityKey` |

**Backwards compatibility:** `requireSignedUrls` defaults to **off**. Legacy unsigned links (`/super-pdf/invoice.pdf`) keep working after upgrade. Enable the setting when you want to reject unsigned or guessable filenames.

When signature query params are present, they are always validated (expired or tampered links return HTTP 403).

Path traversal in the filename is rejected (`basename` + storage root check).

### Configure expiry

CP: **Signed URL Expiry (seconds)**, or config / per-call:

```twig
{% set object = craft.superpdf.template('_pdf/doc', {
    type: 'object',
    filename: 'doc',
    signedUrlExpiry: 86400
}, { entry: entry }) %}
```

```php
$url = $pdf->getSignedUrl('doc.pdf', time() + 86400);
```

## Permanent public links

Use an **asset volume** with a public filesystem instead of Craft storage if you need stable, unsigned URLs.

## Remote assets

DomPDF can fetch remote CSS/images when `isRemoteEnabled` is true. Restrict hosts with `allowedRemoteHosts` in `config/super-pdf.php`.

Prefer local/`@webroot` font and image paths when possible.

## Encryption

Set `encrypt: true` plus `password` / `adminPassword` and permission flags (`print`, `modify`, `copy`, `add`).
