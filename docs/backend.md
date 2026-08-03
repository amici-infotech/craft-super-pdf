# Backend / Control Panel

## Settings

Open **Settings → Super PDF** (or the CP section if enabled).

| Field | Purpose |
|---|---|
| **Plugin Name** | CP nav label |
| **Enable CP Section** | Show Super PDF in the sidebar |
| **Default PDF Storage Volume** | `storage` or an asset volume handle |
| **Sub Folder** | Optional subfolder under storage/volume |
| **Reset Behaviour** | `duplicate` / `override` / `ignore` |
| **Entry PDF Template** | Twig path for the entry download action |
| **Signed URL Expiry** | Seconds until signed storage links expire |
| **Require signed storage URLs** | Reject unsigned `/super-pdf/{file}` links |

When `allowAdminChanges` is false, settings remain visible but read-only. Values overridden in `config/super-pdf.php` show a warning on the field.

## Clear caches

**Utilities → Clear Caches** includes:

- **Super PDF Cache** — generated PDFs in storage
- **Super PDF Font Cache** — DomPDF font metrics / installed fonts cache

Clear the font cache after changing custom fonts or `fontDir`.

## Entry action

If **Entry PDF Template** is set, entries gain a **Download PDF** action (single selection). The template receives:

- `entry` — the Entry element  
- `element` — same as `entry`

Use a minimal HTML PDF template (not a CP layout).

## Console

```bash
php craft super-pdf/generate --template=_pdf/invoice --filename=test
php craft super-pdf/generate --html="<h1>Hi</h1>" --filename=hi
php craft super-pdf/generate --template=_pdf/invoice --elementId=123 --filename=invoice-123
```

See [PHP API](php-api.md) for events, email attachments, and the full console option list.

## Dev Mode logging

With Dev Mode on, successful renders log timing and options (type, paper, fontDir) to the Craft log. Exceptions during generation are rethrown in Dev Mode.
