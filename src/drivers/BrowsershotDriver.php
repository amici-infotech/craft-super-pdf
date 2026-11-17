<?php
/**
 * Super PDF plugin for Craft CMS 4.x / 5.x
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperPdf\drivers;

use amici\SuperPdf\Plugin;
use craft\helpers\UrlHelper;
use Spatie\Browsershot\Browsershot;

/**
 * Headless Chrome (Browsershot) rendering backend — supports tagged PDFs.
 *
 * DomPDF templates use fixed header/footer inside @page margins. Chromium clips
 * that pattern, so this driver maps the same markup onto Chrome's native
 * header/footer + margin APIs while leaving body HTML/CSS alone.
 *
 * @author    Amici Infotech
 * @package   SuperPdf
 * @since     5.2.0
 */
class BrowsershotDriver implements PdfDriverInterface
{
    private const TOTAL_PAGES_TOKEN = '___SUPER_PDF_TOTAL_PAGES___';

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

        [$html, $settings, $bodyHasTotalToken] = $this->adaptDompdfTemplate($html, $settings);

        $bytes = $this->renderOnce($html, $settings);

        // Only re-render when a total-pages token remains in the body (Chrome
        // header/footer templates already expose totalPages natively).
        if ($bodyHasTotalToken) {
            $total = $this->countPdfPages($bytes);
            $html = str_replace(self::TOTAL_PAGES_TOKEN, (string)$total, $html);
            $bytes = $this->renderOnce($html, $settings);
        }

        return new RenderResult($bytes, null, 'browsershot');
    }

    /**
     * Maps DomPDF fixed chrome + @page margins onto Chromium print APIs.
     *
     * @param string $html Source HTML.
     * @param array $settings Merged settings.
     * @return array{0: string, 1: array, 2: bool} HTML, settings, body-has-total-token.
     */
    private function adaptDompdfTemplate(string $html, array $settings): array
    {
        $css = $this->extractStyleText($html);
        $headerRules = $this->parseCssRules($css, ['header', '.super-pdf-header']);
        $footerRules = $this->parseCssRules($css, ['footer', '.super-pdf-footer']);

        if (empty($settings['browsershotMargins'])) {
            $settings['browsershotMargins'] = $this->extractPageMargins($css)
                ?? ['top' => 100.0, 'right' => 25.0, 'bottom' => 100.0, 'left' => 25.0, 'unit' => 'px'];
        }

        $hasExplicitChrome = !empty($settings['headerHtml']) || !empty($settings['footerHtml']);
        if (!$hasExplicitChrome) {
            [$html, $headerInner, $footerInner] = $this->extractFixedChrome($html);
            $sharedChromeCss = $this->extractSharedChromeCss($css);

            if ($headerInner !== '') {
                $settings['headerHtml'] = $this->buildChromeTemplate(
                    $headerInner,
                    $headerRules,
                    'header',
                    $settings['browsershotMargins'],
                    $sharedChromeCss
                );
            }
            if ($footerInner !== '') {
                $settings['footerHtml'] = $this->buildChromeTemplate(
                    $footerInner,
                    $footerRules,
                    'footer',
                    $settings['browsershotMargins'],
                    $sharedChromeCss
                );
            }
        } else {
            // Settings-based chrome: map DomPDF tokens to Chromium placeholders
            foreach (['headerHtml', 'footerHtml'] as $key) {
                if (!empty($settings[$key])) {
                    $settings[$key] = $this->prepareNativeChromePlaceholders((string)$settings[$key]);
                }
            }
        }

        // Body tokens (rare) still use two-pass replacement
        $bodyHasTotalToken = (bool)preg_match(
            '/SUPER_PDF_TOTAL_PAGES|\{PAGE_COUNT\}|\{TP\}|' . preg_quote(self::TOTAL_PAGES_TOKEN, '/') . '/',
            $html
        );
        if ($bodyHasTotalToken) {
            $html = str_replace(
                ['SUPER_PDF_TOTAL_PAGES', '{PAGE_COUNT}', '{TP}'],
                self::TOTAL_PAGES_TOKEN,
                $html
            );
        }

        $html = $this->injectBodyCompatCss($html);

        return [$html, $settings, $bodyHasTotalToken];
    }

    /**
     * Builds a Chromium header/footer template from DomPDF chrome markup + CSS.
     *
     * DomPDF: `@page { margin: 100px }` + `header { top: -60px; height: 50px }`
     * places the bar 40px from the page edge. Chromium templates are otherwise
     * vertically centered in the margin, so we pad to match DomPDF.
     *
     * @param string $inner Extracted inner HTML.
     * @param array<string, string> $rules Parsed CSS declarations for the chrome selector.
     * @param string $which `header` or `footer`.
     * @param array $margins Browsershot margins derived from @page.
     * @param string $sharedCss Extra CSS copied from the document for chrome descendants.
     * @return string
     */
    private function buildChromeTemplate(
        string $inner,
        array $rules,
        string $which,
        array $margins,
        string $sharedCss = ''
    ): string {
        $inner = $this->prepareNativeChromePlaceholders($inner);

        // Chromium fills .pageNumber; DomPDF templates use .page-number + :after
        $inner = preg_replace(
            '/class=(["\'])([^"\']*\s)?page-number(\s[^"\']*)?\1/i',
            'class=$1$2pageNumber$3$1',
            $inner
        ) ?? $inner;
        $inner = preg_replace(
            '/<span([^>]*)class=(["\'])([^"\']*\b)page-number(\b[^"\']*)\2([^>]*)>\s*<\/span>/i',
            '<span$1class=$2$3pageNumber$4$2$5></span>',
            $inner
        ) ?? $inner;

        $background = $rules['background-color'] ?? $rules['background'] ?? 'lightblue';
        $height = $rules['height'] ?? '50px';
        $heightPx = $this->cssLengthToPx($height) ?? 50.0;
        $textAlign = $rules['text-align'] ?? ($which === 'footer' ? 'right' : 'left');
        $color = $rules['color'] ?? '#000';
        $fontFamily = $rules['font-family'] ?? 'Helvetica, Arial, sans-serif';
        $fontSize = $rules['font-size'] ?? '12px';

        $offsetProp = $which === 'header' ? 'top' : 'bottom';
        $offsetPx = $this->cssLengthToPx($rules[$offsetProp] ?? null) ?? -60.0;
        $pageMarginPx = $this->marginValueToPx($margins, $which === 'header' ? 'top' : 'bottom', 100.0);
        // DomPDF distance from page edge to chrome = pageMargin + offset (offset is negative)
        $edgePad = max(0.0, $pageMarginPx + $offsetPx);
        // DomPDF fixed chrome sits inside @page left/right; Chromium templates are full-bleed
        $sideLeftPx = $this->marginValueToPx($margins, 'left', 25.0);
        $sideRightPx = $this->marginValueToPx($margins, 'right', 25.0);

        $barStyle = implode(';', [
            'width:100%',
            'box-sizing:border-box',
            'margin:0',
            'padding:0',
            'background:' . $background,
            'background-color:' . $background,
            'height:' . $heightPx . 'px',
            'min-height:' . $heightPx . 'px',
            'overflow:hidden',
            'color:' . $color,
            'font-family:' . $fontFamily,
            'font-size:' . $fontSize,
            'text-align:' . $textAlign,
            '-webkit-print-color-adjust:exact',
            'print-color-adjust:exact',
        ]);

        $outerPadParts = [
            'padding-left:' . $sideLeftPx . 'px',
            'padding-right:' . $sideRightPx . 'px',
        ];
        if ($which === 'header') {
            $outerPadParts[] = 'padding-top:' . $edgePad . 'px';
            $outerPadParts[] = 'padding-bottom:0';
        } else {
            $outerPadParts[] = 'padding-top:0';
            $outerPadParts[] = 'padding-bottom:' . $edgePad . 'px';
        }

        $outerStyle = 'width:100%;margin:0;box-sizing:border-box;'
            . implode(';', $outerPadParts) . ';'
            . '-webkit-print-color-adjust:exact;print-color-adjust:exact;';

        $styleTag = $sharedCss !== ''
            ? '<style>html,body{margin:0;padding:0;}' . $sharedCss . '</style>'
            : '<style>html,body{margin:0;padding:0;}</style>';

        return $styleTag
            . '<div style="' . $outerStyle . '"><div style="' . $barStyle . '">' . $inner . '</div></div>';
    }

    /**
     * Converts a browsershotMargins side value to CSS pixels.
     *
     * @param array $margins Margin map with optional unit key.
     * @param string $side top|right|bottom|left.
     * @param float $default Default when missing.
     * @return float
     */
    private function marginValueToPx(array $margins, string $side, float $default): float
    {
        if (!isset($margins[$side]) || $margins[$side] === '' || $margins[$side] === null) {
            return $default;
        }

        $unit = strtolower((string)($margins['unit'] ?? 'px'));
        $value = (float)$margins[$side];

        return match ($unit) {
            'mm' => $value * 96 / 25.4,
            'cm' => $value * 96 / 2.54,
            'in' => $value * 96,
            default => $value,
        };
    }

    /**
     * Copies heading/paragraph/link rules into chrome templates (no document CSS inherit).
     *
     * @param string $css Full document CSS text.
     * @return string
     */
    private function extractSharedChromeCss(string $css): string
    {
        $chunks = [];
        foreach (['h1', 'h2', 'h3', 'h4', 'p', 'a', 'span'] as $selector) {
            $rules = $this->parseCssRules($css, [$selector]);
            if ($rules === []) {
                continue;
            }
            $decls = [];
            foreach ($rules as $prop => $value) {
                $decls[] = $prop . ':' . $value;
            }
            $chunks[] = $selector . '{' . implode(';', $decls) . ';}';
        }

        return implode('', $chunks);
    }

    /**
     * Parses a CSS length to pixels (px/mm/cm/in; unitless treated as px).
     *
     * @param string|null $value CSS length.
     * @return float|null
     */
    private function cssLengthToPx(?string $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!preg_match('/^([+-]?[0-9.]+)\s*(px|mm|cm|in)?$/i', trim($value), $m)) {
            return null;
        }

        $n = (float)$m[1];
        $unit = strtolower($m[2] ?? 'px');

        return match ($unit) {
            'mm' => $n * 96 / 25.4,
            'cm' => $n * 96 / 2.54,
            'in' => $n * 96,
            default => $n,
        };
    }

    /**
     * Collects concatenated CSS from <style> blocks.
     *
     * @param string $html HTML document.
     * @return string
     */
    private function extractStyleText(string $html): string
    {
        if (!preg_match_all('/<style\b[^>]*>(.*?)<\/style>/is', $html, $matches)) {
            return '';
        }

        return implode("\n", $matches[1]);
    }

    /**
     * Parses simple CSS declarations for the first matching selector.
     *
     * @param string $css CSS text.
     * @param list<string> $selectors Selectors to try.
     * @return array<string, string>
     */
    private function parseCssRules(string $css, array $selectors): array
    {
        foreach ($selectors as $selector) {
            $pattern = '/' . preg_quote($selector, '/') . '\s*\{([^}]*)\}/i';
            if (!preg_match($pattern, $css, $m)) {
                continue;
            }

            $rules = [];
            foreach (explode(';', $m[1]) as $decl) {
                $decl = trim($decl);
                if ($decl === '' || !str_contains($decl, ':')) {
                    continue;
                }
                [$prop, $value] = array_map('trim', explode(':', $decl, 2));
                $rules[strtolower($prop)] = $value;
            }

            return $rules;
        }

        return [];
    }

    /**
     * Parses `@page { margin: ... }`.
     *
     * @param string $css CSS text.
     * @return array{top: float, right: float, bottom: float, left: float, unit: string}|null
     */
    private function extractPageMargins(string $css): ?array
    {
        if (!preg_match('/@page\s*\{[^}]*margin\s*:\s*([^;}]+)[;}]/i', $css, $m)) {
            return null;
        }

        $parts = preg_split('/\s+/', trim($m[1])) ?: [];
        $parsed = [];
        foreach ($parts as $part) {
            if (preg_match('/^([0-9.]+)(px|mm|cm|in)?$/i', $part, $pm)) {
                $parsed[] = [
                    'value' => (float)$pm[1],
                    'unit' => strtolower($pm[2] ?? 'px'),
                ];
            }
        }

        if ($parsed === []) {
            return null;
        }

        $unit = $parsed[0]['unit'];
        $values = array_map(static fn(array $p) => $p['value'], $parsed);

        return match (count($values)) {
            1 => [
                'top' => $values[0], 'right' => $values[0],
                'bottom' => $values[0], 'left' => $values[0], 'unit' => $unit,
            ],
            2 => [
                'top' => $values[0], 'right' => $values[1],
                'bottom' => $values[0], 'left' => $values[1], 'unit' => $unit,
            ],
            3 => [
                'top' => $values[0], 'right' => $values[1],
                'bottom' => $values[2], 'left' => $values[1], 'unit' => $unit,
            ],
            default => [
                'top' => $values[0], 'right' => $values[1],
                'bottom' => $values[2], 'left' => $values[3], 'unit' => $unit,
            ],
        };
    }

    /**
     * Extracts DomPDF-style fixed header/footer nodes from HTML.
     *
     * @param string $html Source HTML.
     * @return array{0: string, 1: string, 2: string}
     */
    private function extractFixedChrome(string $html): array
    {
        $header = '';
        $footer = '';

        if (preg_match('/<header\b[^>]*>(.*?)<\/header>/is', $html, $m)) {
            $header = trim($m[1]);
            $html = preg_replace('/<header\b[^>]*>.*?<\/header>/is', '', $html, 1) ?? $html;
        }

        if (preg_match('/<footer\b[^>]*>(.*?)<\/footer>/is', $html, $m)) {
            $footer = trim($m[1]);
            $html = preg_replace('/<footer\b[^>]*>.*?<\/footer>/is', '', $html, 1) ?? $html;
        }

        if ($header === '' && preg_match(
            '/<div\b[^>]*class=(["\'])[^"\']*super-pdf-header[^"\']*\1[^>]*>(.*?)<\/div>/is',
            $html,
            $m
        )) {
            $header = trim($m[2]);
            $html = preg_replace(
                '/<div\b[^>]*class=(["\'])[^"\']*super-pdf-header[^"\']*\1[^>]*>.*?<\/div>/is',
                '',
                $html,
                1
            ) ?? $html;
        }

        if ($footer === '' && preg_match(
            '/<div\b[^>]*class=(["\'])[^"\']*super-pdf-footer[^"\']*\1[^>]*>(.*?)<\/div>/is',
            $html,
            $m
        )) {
            $footer = trim($m[2]);
            $html = preg_replace(
                '/<div\b[^>]*class=(["\'])[^"\']*super-pdf-footer[^"\']*\1[^>]*>.*?<\/div>/is',
                '',
                $html,
                1
            ) ?? $html;
        }

        // Strip DomPDF chrome rules that would no longer apply (optional cleanup)
        $html = preg_replace(
            '/(header|footer|\.super-pdf-header|\.super-pdf-footer)\s*\{[^}]*\}/i',
            '',
            $html
        ) ?? $html;

        // @page margins are applied via Chromium's margin API; leaving them in CSS
        // can double-inset body content relative to DomPDF.
        $html = preg_replace('/@page\s*\{[^}]*\}/i', '', $html) ?? $html;

        return [$html, $header, $footer];
    }

    /**
     * Body-only compat CSS (does not rewrite DomPDF header positioning).
     *
     * @param string $html HTML document.
     * @return string
     */
    private function injectBodyCompatCss(string $html): string
    {
        $compatCss = <<<'CSS'
<style id="super-pdf-browsershot-compat" type="text/css">
html, body, * {
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
}
img { max-width: 100%; }
a { word-break: break-word; }
</style>
CSS;

        if (stripos($html, '</head>') !== false) {
            return preg_replace('/<\/head>/i', $compatCss . '</head>', $html, 1) ?? ($html . $compatCss);
        }

        return $compatCss . $html;
    }

    /**
     * Maps DomPDF page tokens to Chromium header/footer placeholders.
     *
     * @param string $html Header/footer snippet.
     * @return string
     */
    private function prepareNativeChromePlaceholders(string $html): string
    {
        $html = str_replace(
            ['SUPER_PDF_TOTAL_PAGES', '{PAGE_COUNT}', '{TP}', self::TOTAL_PAGES_TOKEN],
            '<span class="totalPages"></span>',
            $html
        );

        return $html;
    }

    /**
     * Single Browsershot PDF pass.
     *
     * @param string $html HTML document.
     * @param array $settings Merged settings.
     * @return string PDF bytes.
     */
    private function renderOnce(string $html, array $settings): string
    {
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
                (string)($m['unit'] ?? 'px')
            );
        }

        $headerHtml = (string)($settings['headerHtml'] ?? '');
        $footerHtml = (string)($settings['footerHtml'] ?? '');
        if ($headerHtml !== '' || $footerHtml !== '') {
            $browsershot->showBrowserHeaderAndFooter();
            // Avoid Chromium's default date/URL chrome
            if ($headerHtml === '') {
                $headerHtml = '<span></span>';
            }
            if ($footerHtml === '') {
                $footerHtml = '<span></span>';
            }
            $browsershot->headerHtml($headerHtml);
            $browsershot->footerHtml($footerHtml);
        }

        if ($this->settingEnabled($settings, 'browsershotShowBackground', true)) {
            $browsershot->showBackground();
        }

        if ($this->settingEnabled($settings, 'browsershotAllowLocalFileAccess', true)
            && method_exists($browsershot, 'allowLocalFileAccess')
        ) {
            $browsershot->allowLocalFileAccess();
        }

        $contentUrl = $settings['browsershotContentUrl'] ?? null;
        if ($contentUrl === null || $contentUrl === '') {
            $contentUrl = rtrim(UrlHelper::siteUrl(), '/') . '/';
        }
        if ($contentUrl !== '' && method_exists($browsershot, 'setContentUrl')) {
            $browsershot->setContentUrl((string)$contentUrl);
        }

        if ($this->settingEnabled($settings, 'browsershotWaitUntilNetworkIdle', true)
            && method_exists($browsershot, 'waitUntilNetworkIdle')
        ) {
            $browsershot->waitUntilNetworkIdle();
        }

        if (method_exists($browsershot, 'emulateMedia')) {
            $browsershot->emulateMedia('print');
        }
        if (method_exists($browsershot, 'ignoreHttpsErrors')) {
            $browsershot->ignoreHttpsErrors();
        }

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

        return $browsershot->pdf();
    }

    /**
     * Counts page objects in a PDF binary.
     *
     * @param string $pdf PDF bytes.
     * @return int
     */
    private function countPdfPages(string $pdf): int
    {
        if (preg_match_all('/\/Type\s*\/Page\b/', $pdf, $matches)) {
            return max(1, count($matches[0]));
        }

        return 1;
    }

    /**
     * Reads a boolean setting with a default when the key is missing.
     *
     * @param array $settings Merged settings.
     * @param string $key Setting key.
     * @param bool $default Default when unset.
     * @return bool
     */
    private function settingEnabled(array $settings, string $key, bool $default): bool
    {
        if (!array_key_exists($key, $settings) || $settings[$key] === null) {
            return $default;
        }

        return filter_var($settings[$key], FILTER_VALIDATE_BOOLEAN);
    }
}
