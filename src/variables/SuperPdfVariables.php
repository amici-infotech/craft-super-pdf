<?php
/**
 * Super PDF plugin for Craft CMS 4.x / 5.x
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperPdf\variables;

use amici\SuperPdf\Plugin;

/**
 * Twig variable registered as `craft.superpdf`.
 *
 * @author    Amici Infotech
 * @package   SuperPdf
 * @since     5.1.0
 */
class SuperPdfVariables
{
    // Public Methods
    // =========================================================================

    /**
     * Returns a plugin setting value by handle.
     *
     * @param string $value Setting handle. Empty string returns an empty string.
     * @return mixed The setting value, or an empty string when the handle is blank.
     */
    public function getSettings(string $value = ''): mixed
    {
        if ($value === '') {
            return '';
        }

        return Plugin::$plugin->general->getSettings($value);
    }

    /**
     * Generates a PDF from an HTML string.
     *
     * @param string $html HTML document markup.
     * @param array $settings Per-call settings overrides.
     * @return mixed Streamed response, asset object, string/base64, or false on failure.
     */
    public function html(string $html = '', array $settings = []): mixed
    {
        return Plugin::$plugin->pdf->html($html, $settings);
    }

    /**
     * Generates a PDF from a Twig template.
     *
     * @param string $template Template path.
     * @param array $settings Per-call settings overrides.
     * @param array $vars Variables passed into the template.
     * @return mixed Streamed response, asset object, string/base64, or false on failure.
     */
    public function template(string $template = '', array $settings = [], array $vars = []): mixed
    {
        return Plugin::$plugin->pdf->template($template, $settings, $vars);
    }

    /**
     * Queues asynchronous PDF generation from HTML.
     *
     * @param string $html HTML document markup.
     * @param array $settings Per-call settings overrides (`type` forced to `object` when needed).
     * @param int $priority Queue priority.
     * @return string|int|null Push ID returned by the queue.
     */
    public function queueHtml(string $html, array $settings = [], int $priority = 10): string|int|null
    {
        return Plugin::$plugin->pdf->queueHtml($html, $settings, $priority);
    }

    /**
     * Queues asynchronous PDF generation from a Twig template.
     *
     * @param string $template Template path.
     * @param array $settings Per-call settings overrides.
     * @param array $vars Variables passed into the template (must be serializable).
     * @param int $priority Queue priority.
     * @return string|int|null Push ID returned by the queue.
     */
    public function queueTemplate(string $template, array $settings = [], array $vars = [], int $priority = 10): string|int|null
    {
        return Plugin::$plugin->pdf->queueTemplate($template, $settings, $vars, $priority);
    }

    /**
     * Generates a PDF suitable for attaching to an email.
     *
     * @param string|null $html HTML markup when not using a template.
     * @param string|null $template Twig template path when not using HTML.
     * @param array $settings Per-call settings overrides.
     * @param array $vars Template variables when using `$template`.
     * @return array{path: string, content: string, filename: string, mimeType: string}|false
     */
    public function asEmailAttachment(?string $html = null, ?string $template = null, array $settings = [], array $vars = []): array|false
    {
        return Plugin::$plugin->pdf->asEmailAttachment($html, $template, $settings, $vars);
    }
}
