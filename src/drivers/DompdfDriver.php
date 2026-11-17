<?php
/**
 * Super PDF plugin for Craft CMS 4.x / 5.x
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperPdf\drivers;

use Craft;
use amici\SuperPdf\services\Pdf;
use craft\helpers\FileHelper;
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * DomPDF rendering backend (default).
 *
 * @author    Amici Infotech
 * @package   SuperPdf
 * @since     5.2.0
 */
class DompdfDriver implements PdfDriverInterface
{
    /**
     * @inheritdoc
     */
    public function supports(string $capability): bool
    {
        return match ($capability) {
            self::CAPABILITY_ENCRYPTION,
            self::CAPABILITY_PAGE_COUNT_PLACEHOLDER => true,
            self::CAPABILITY_TAGGED => false,
            default => false,
        };
    }

    /**
     * @inheritdoc
     */
    public function render(string $html, array $settings): RenderResult
    {
        $storage = FileHelper::normalizePath(Craft::$app->getPath()->getStoragePath() . '/super-pdf');
        $pathDefaults = [
            'rootDir' => FileHelper::normalizePath(CRAFT_VENDOR_PATH . '/dompdf/dompdf'),
            'fontDir' => $storage . '/fonts',
            'fontCache' => $storage . '/font-cache',
            'tempDir' => $storage . '/temp',
        ];
        foreach ($pathDefaults as $key => $default) {
            if (empty($settings[$key])) {
                $settings[$key] = $default;
            }
        }
        if (empty($settings['chroot'])) {
            $settings['chroot'] = [
                FileHelper::normalizePath(CRAFT_BASE_PATH),
                $storage,
            ];
        }

        $this->ensureRuntimeDirectories($settings);
        $options = $this->buildOptions($settings);
        $dompdf = new Dompdf($options);
        $dompdf->setPaper(
            $settings['defaultPaperSize'] ?? 'A4',
            $settings['defaultPaperOrientation'] ?? 'portrait'
        );

        if (!empty($settings['httpContext'])) {
            $dompdf->setHttpContext($settings['httpContext']);
        } elseif (!empty($settings['streamContext'])) {
            Craft::$app->getDeprecator()->log(
                'streamContext',
                'PDF Settings `streamContext` is deprecated. Use `httpContext` instead.'
            );
            $dompdf->setHttpContext(stream_context_create($settings['streamContext']));
        }

        $dompdf->loadHtml($html);
        $dompdf->render();

        if (str_contains($html, '{TP}')) {
            $this->injectPageCount($dompdf);
        }

        if (!empty($settings['encrypt'])) {
            $this->injectEncryption($dompdf, $settings);
        }

        $compress = !empty($settings['compress']);
        $bytes = $dompdf->output(['compress' => (int)$compress]);

        return new RenderResult($bytes, $dompdf, 'dompdf');
    }

    /**
     * Builds Dompdf Options from runtime settings, excluding Super PDF-only keys.
     *
     * @param array $settings Merged settings.
     * @return Options
     */
    private function buildOptions(array $settings): Options
    {
        $options = new Options();
        $attributes = $settings;

        foreach (Pdf::PLUGIN_SETTING_KEYS as $key) {
            unset($attributes[$key]);
        }

        foreach ($attributes as $key => $value) {
            if ($value === null) {
                continue;
            }

            if ($key === 'rootDir') {
                $options->setRootDir((string)$value);
                continue;
            }

            if ($key === 'chroot') {
                $options->setChroot($value);
                continue;
            }

            if ($key === 'tempDir') {
                $options->setTempDir((string)$value);
                continue;
            }

            if ($key === 'fontDir') {
                $options->setFontDir((string)$value);
                continue;
            }

            if ($key === 'fontCache') {
                $options->setFontCache((string)$value);
                continue;
            }

            $options->set($key, $value);
        }

        if (!empty($settings['allowedRemoteHosts']) && method_exists($options, 'setAllowedRemoteHosts')) {
            $options->setAllowedRemoteHosts($settings['allowedRemoteHosts']);
        } elseif (!empty($settings['allowedRemoteHosts'])) {
            $options->set('allowedRemoteHosts', $settings['allowedRemoteHosts']);
        }

        $rootDir = $settings['rootDir'] ?? (CRAFT_VENDOR_PATH . '/dompdf/dompdf');
        $options->setRootDir(FileHelper::normalizePath((string)$rootDir));

        return $options;
    }

    /**
     * Ensures font/temp directories from settings exist and are writable.
     *
     * @param array $settings Merged settings.
     * @return void
     */
    private function ensureRuntimeDirectories(array $settings): void
    {
        foreach (['fontDir', 'fontCache', 'tempDir'] as $key) {
            $path = $settings[$key] ?? null;
            if (is_string($path) && $path !== '') {
                FileHelper::createDirectory(FileHelper::normalizePath($path));
            }
        }
    }

    /**
     * Replaces `{TP}` tokens in the rendered PDF with the total page count.
     *
     * @param Dompdf $dompdf Active Dompdf instance.
     * @return void
     */
    private function injectPageCount(Dompdf $dompdf): void
    {
        $canvas = $dompdf->getCanvas();
        $pdf = $canvas->get_cpdf();
        $totalPages = $canvas->get_page_count();
        $paddedTotal = str_pad((string)$totalPages, 3, ' ', STR_PAD_LEFT);

        foreach ($pdf->objects as &$o) {
            if (($o['t'] ?? null) === 'contents') {
                $o['c'] = str_replace('{TP}', $paddedTotal, $o['c']);
            }
        }
        unset($o);
    }

    /**
     * Applies CPDF encryption and permission flags from settings.
     *
     * @param Dompdf $dompdf Active Dompdf instance.
     * @param array $settings Merged settings.
     * @return void
     */
    private function injectEncryption(Dompdf $dompdf, array $settings): void
    {
        $allow = [];
        if (!empty($settings['print'])) {
            $allow[] = 'print';
        }
        if (!empty($settings['modify'])) {
            $allow[] = 'modify';
        }
        if (!empty($settings['copy'])) {
            $allow[] = 'copy';
        }
        if (!empty($settings['add'])) {
            $allow[] = 'add';
        }

        $dompdf->getCanvas()->get_cpdf()->setEncryption(
            (string)($settings['password'] ?? ''),
            (string)($settings['adminPassword'] ?? ''),
            $allow
        );
    }
}
