<?php
/**
 * Super PDF plugin for Craft CMS 4.x / 5.x
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperPdf\drivers;

use amici\SuperPdf\Plugin;
use Spatie\Browsershot\Browsershot;

/**
 * Headless Chrome (Browsershot) rendering backend — supports tagged PDFs.
 *
 * @author    Amici Infotech
 * @package   SuperPdf
 * @since     5.2.0
 */
class BrowsershotDriver implements PdfDriverInterface
{
    /**
     * @inheritdoc
     */
    public function supports(string $capability): bool
    {
        return match ($capability) {
            self::CAPABILITY_TAGGED => true,
            self::CAPABILITY_PAGE_COUNT_PLACEHOLDER => true,
            self::CAPABILITY_ENCRYPTION => false,
            default => false,
        };
    }

    /**
     * @inheritdoc
     */
    public function render(string $html, array $settings): RenderResult
    {
        if (!empty($settings['encrypt'])) {
            Plugin::info(
                'PDF encryption is only supported with the DomPDF driver. Skipping encryption for Browsershot.'
            );
        }

        $html = $this->preparePlaceholders($html);
        $browsershot = Browsershot::html($html);

        $format = (string)($settings['defaultPaperSize'] ?? 'A4');
        if ($format !== '') {
            $browsershot->format($format);
        }

        $orientation = strtolower((string)($settings['defaultPaperOrientation'] ?? 'portrait'));
        if ($orientation === 'landscape') {
            $browsershot->landscape();
        }

        if (!empty($settings['browsershotMargins']) && is_array($settings['browsershotMargins'])) {
            $m = $settings['browsershotMargins'];
            $browsershot->margins(
                (float)($m['top'] ?? 0),
                (float)($m['right'] ?? 0),
                (float)($m['bottom'] ?? 0),
                (float)($m['left'] ?? 0),
                (string)($m['unit'] ?? 'mm')
            );
        }

        $headerHtml = (string)($settings['headerHtml'] ?? '');
        $footerHtml = (string)($settings['footerHtml'] ?? '');
        if ($headerHtml !== '') {
            $browsershot->showBrowserHeaderAndFooter();
            $browsershot->headerHtml($headerHtml);
        }
        if ($footerHtml !== '') {
            $browsershot->showBrowserHeaderAndFooter();
            $browsershot->footerHtml($this->preparePlaceholders($footerHtml));
        }

        // Tagged PDFs: default on for Browsershot unless explicitly disabled
        $tagged = array_key_exists('tagged', $settings)
            ? (bool)$settings['tagged']
            : true;
        if ($tagged && method_exists($browsershot, 'taggedPdf')) {
            $browsershot->taggedPdf();
        }

        if (!empty($settings['browsershotNodeBinary'])) {
            $browsershot->setNodeBinary((string)$settings['browsershotNodeBinary']);
        }
        if (!empty($settings['browsershotNpmBinary'])) {
            $browsershot->setNpmBinary((string)$settings['browsershotNpmBinary']);
        }
        if (!empty($settings['browsershotChromePath'])) {
            $browsershot->setChromePath((string)$settings['browsershotChromePath']);
        }
        if (!empty($settings['browsershotNoSandbox'])) {
            $browsershot->noSandbox();
        }
        if (!empty($settings['browsershotTimeout'])) {
            $browsershot->timeout((int)$settings['browsershotTimeout']);
        }

        $bytes = $browsershot->pdf();

        return new RenderResult($bytes, null, 'browsershot');
    }

    /**
     * Maps Super PDF page-count tokens to Chromium print placeholders.
     *
     * @param string $html HTML or header/footer snippet.
     * @return string
     */
    private function preparePlaceholders(string $html): string
    {
        return str_replace(
            ['SUPER_PDF_TOTAL_PAGES', '{PAGE_COUNT}', '{TP}'],
            '<span class="totalPages"></span>',
            $html
        );
    }
}
