<?php
/**
 * Super PDF plugin for Craft CMS 4.x / 5.x
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperPdf\jobs;

use Craft;
use craft\queue\BaseJob;
use amici\SuperPdf\Plugin;

/**
 * Queue job that generates a PDF asynchronously.
 *
 * @author    Amici Infotech
 * @package   SuperPdf
 * @since     5.1.0
 */
class GeneratePdfJob extends BaseJob
{
    // Public Properties
    // =========================================================================

    /**
     * @var string Generation mode: `html` or `template`.
     */
    public string $mode = 'template';

    /**
     * @var string|null Raw HTML when `$mode` is `html`.
     */
    public ?string $html = null;

    /**
     * @var string|null Twig template path when `$mode` is `template`.
     */
    public ?string $template = null;

    /**
     * @var array PDF settings overrides. Non-persistent types are coerced to `object`.
     */
    public array $settings = [];

    /**
     * @var array Template variables (must be serializable for the queue).
     */
    public array $vars = [];

    // Public Methods
    // =========================================================================

    /**
     * Executes PDF generation on the queue worker.
     *
     * @param mixed $queue The queue instance.
     * @return void
     */
    public function execute($queue): void
    {
        $this->setProgress($queue, 0.1);

        $settings = $this->settings;
        if (!in_array($settings['type'] ?? 'object', ['object', 'url'], true)) {
            $settings['type'] = 'object';
        }

        $pdf = Plugin::$plugin->pdf;

        if ($this->mode === 'html') {
            $pdf->html((string)$this->html, $settings);
        } else {
            $pdf->template((string)$this->template, $settings, $this->vars);
        }

        $this->setProgress($queue, 1);
    }

    // Protected Methods
    // =========================================================================

    /**
     * Returns the default queue job description.
     *
     * @return string|null
     */
    protected function defaultDescription(): ?string
    {
        $filename = $this->settings['filename'] ?? 'pdf';

        return Craft::t('super-pdf', 'Generating PDF “{filename}”', [
            'filename' => $filename,
        ]);
    }
}
