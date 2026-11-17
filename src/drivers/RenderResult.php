<?php
/**
 * Super PDF plugin for Craft CMS 4.x / 5.x
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperPdf\drivers;

use Dompdf\Dompdf;

/**
 * Outcome of a driver render pass.
 *
 * @author    Amici Infotech
 * @package   SuperPdf
 * @since     5.2.0
 */
class RenderResult
{
    /**
     * @param string $bytes Raw PDF file contents.
     * @param Dompdf|null $dompdf Dompdf instance when the DomPDF driver was used.
     * @param string $driver Driver id (`dompdf`, `browsershot`, …).
     */
    public function __construct(
        public readonly string $bytes,
        public readonly ?Dompdf $dompdf = null,
        public readonly string $driver = 'dompdf',
    ) {
    }
}
