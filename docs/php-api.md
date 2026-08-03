# PHP API

```php
use amici\SuperPdf\Plugin as SuperPdf;
use amici\SuperPdf\events\PdfEvent;
use amici\SuperPdf\services\Pdf;
use yii\base\Event;

$pdf = SuperPdf::$plugin->getPdf();
// or: SuperPdf::$plugin->pdf
```

The PDF service lives at `amici\SuperPdf\services\Pdf`.

## Sync generation

```php
// HTML
$pdf->html($html, ['filename' => 'hello', 'type' => 'object']);
$pdf->fromHtml($html, ['type' => 'download']);

// Template
$pdf->template('_pdf/invoice', [
    'filename' => 'invoice-100',
    'type' => 'object',
], ['entry' => $entry]);

$pdf->fromTemplate('_pdf/invoice', ['type' => 'string'], ['entry' => $entry]);
```

## Queue / async

```php
$jobId = $pdf->queueTemplate('_pdf/invoice', [
    'filename' => 'invoice-100',
    'type' => 'object',
    'volume' => 'documents',
], ['entryId' => $entry->id]);

$jobId = $pdf->queueHtml($html, [
    'filename' => 'note',
    'type' => 'object',
]);
```

> Queue payloads must be serializable. Prefer IDs over full element objects in `$vars`, then load elements inside the template.

Job class: `amici\SuperPdf\jobs\GeneratePdfJob`.

## Email attachments

```php
$attachment = $pdf->asEmailAttachment(
    html: null,
    template: '_pdf/invoice',
    settings: ['filename' => 'invoice-100'],
    vars: ['entry' => $entry],
);

if ($attachment) {
    Craft::$app->getMailer()
        ->compose()
        ->setTo($order->email)
        ->setSubject('Invoice')
        ->setTextBody('Your invoice is attached.')
        ->attach($attachment['path'], [
            'fileName' => $attachment['filename'],
            'contentType' => $attachment['mimeType'],
        ])
        ->send();
}
```

Return shape: `path`, `content`, `filename`, `mimeType`.

You can also pass HTML instead of a template: `asEmailAttachment($html, null, $settings)`.

## Lifecycle events

```php
Event::on(Pdf::class, Pdf::EVENT_BEFORE_RENDER, function(PdfEvent $event) {
    // Mutate HTML / settings, or cancel:
    // $event->isValid = false;
    $event->html = str_replace('CONFIDENTIAL', 'INTERNAL', $event->html);
});

Event::on(Pdf::class, Pdf::EVENT_AFTER_RENDER, function(PdfEvent $event) {
    // $event->dompdf is available
});

Event::on(Pdf::class, Pdf::EVENT_BEFORE_SAVE, function(PdfEvent $event) {
    // Cancel save with $event->isValid = false
});

Event::on(Pdf::class, Pdf::EVENT_AFTER_SAVE, function(PdfEvent $event) {
    // $event->result is the asset wrapper; $event->path is the filesystem path
});
```

| Event | When |
|---|---|
| `Pdf::EVENT_BEFORE_RENDER` | Before DomPDF `loadHtml` / `render` |
| `Pdf::EVENT_AFTER_RENDER` | After render, before stream/save/return |
| `Pdf::EVENT_BEFORE_SAVE` | Before writing `type: object` to storage/volume |
| `Pdf::EVENT_AFTER_SAVE` | After the asset wrapper is created |

Event class: `amici\SuperPdf\events\PdfEvent` (cancelable).

## Console

```bash
php craft super-pdf/generate --template=_pdf/invoice --filename=smoke
php craft super-pdf/generate --html="<h1>Hi</h1>" --filename=hi --type=object
php craft super-pdf/generate --template=_pdf/invoice --elementId=123 --filename=invoice-123
```

| Option | Description |
|---|---|
| `--template` | Twig template path |
| `--html` | Raw HTML (alternative to `--template`) |
| `--filename` | Output name without `.pdf` |
| `--type` | Usually `object` for console |
| `--elementId` | Loads an entry as `entry` / `element` |

## Signed URLs

```php
$url = $pdf->getSignedUrl('invoice-100.pdf');
$ok = $pdf->validateSignature('invoice-100.pdf', $expires, $signature);
```

## Legacy class

`amici\SuperPdf\SuperPdf` still exists as a deprecated subclass of `Plugin`. Prefer `Plugin` and `services\Pdf`.
