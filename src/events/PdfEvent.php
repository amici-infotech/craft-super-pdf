<?php
/**
 * Super PDF plugin for Craft CMS 4.x / 5.x
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperPdf\events;

use craft\events\CancelableEvent;
use Dompdf\Dompdf;

/**
 * Cancelable event for PDF render and save lifecycle hooks.
 *
 * Before events: set `$event->isValid = false` to abort. Mutate `$html` / `$settings` as needed.
 * After events: inspect `$dompdf`, `$result`, or `$path`.
 *
 * @author    Amici Infotech
 * @package   SuperPdf
 * @since     5.1.0
 */
class PdfEvent extends CancelableEvent
{
    /**
     * @var string HTML about to be rendered, or that was rendered.
     */
    public string $html = '';

    /**
     * @var array Runtime PDF settings for this generation.
     */
    public array $settings = [];

    /**
     * @var string|null Twig template path when generating from a template.
     */
    public ?string $template = null;

    /**
     * @var array Template variables when generating from a template.
     */
    public array $vars = [];

    /**
     * @var Dompdf|null Dompdf instance after render (after-render / save events).
     */
    public ?Dompdf $dompdf = null;

    /**
     * @var mixed Generation or save result (asset object, string, etc.).
     */
    public mixed $result = null;

    /**
     * @var string|null Filesystem path when saving to storage/volume.
     */
    public ?string $path = null;
}
