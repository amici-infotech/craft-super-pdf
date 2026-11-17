<?php
/**
 * Super PDF plugin for Craft CMS 4.x / 5.x
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperPdf\drivers;

/**
 * Contract for PDF rendering backends.
 *
 * @author    Amici Infotech
 * @package   SuperPdf
 * @since     5.2.0
 */
interface PdfDriverInterface
{
    public const CAPABILITY_ENCRYPTION = 'encryption';
    public const CAPABILITY_TAGGED = 'tagged';
    public const CAPABILITY_PAGE_COUNT_PLACEHOLDER = 'pageCountPlaceholder';

    /**
     * Renders HTML to PDF bytes.
     *
     * @param string $html Full HTML document.
     * @param array $settings Merged Super PDF settings for this generation.
     * @return RenderResult PDF bytes and optional Dompdf instance.
     */
    public function render(string $html, array $settings): RenderResult;

    /**
     * Whether this driver supports a named capability.
     *
     * @param string $capability One of the CAPABILITY_* constants.
     * @return bool
     */
    public function supports(string $capability): bool;
}
