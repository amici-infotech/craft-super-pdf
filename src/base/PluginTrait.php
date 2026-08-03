<?php
/**
 * Super PDF plugin for Craft CMS 4.x / 5.x
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperPdf\base;

use Craft;
use amici\SuperPdf\services\General;
use amici\SuperPdf\services\Pdf;

/**
 * Plugin Trait
 *
 * Shared component registration and logging helpers for Super PDF.
 *
 * @author    Amici Infotech
 * @package   SuperPdf
 * @since     5.1.0
 *
 * @property-read Pdf $pdf
 * @property-read General $general
 */
trait PluginTrait
{
    // Private Methods
    // =========================================================================

    /**
     * Registers the plugin services as Yii components.
     *
     * @return void
     */
    private function _setPluginComponents(): void
    {
        $this->setComponents([
            'general' => General::class,
            'pdf' => Pdf::class,
        ]);
    }

    // Public Methods
    // =========================================================================

    /**
     * Returns the PDF generation service.
     *
     * @return Pdf
     */
    public function getPdf(): Pdf
    {
        return $this->get('pdf');
    }

    /**
     * Returns the general helpers service.
     *
     * @return General
     */
    public function getGeneral(): General
    {
        return $this->get('general');
    }

    /**
     * Translates a message using the `super-pdf` category.
     *
     * @param string $message The message to translate.
     * @param array $params Optional translation parameters.
     * @return string The translated message.
     */
    public static function t(string $message, array $params = []): string
    {
        return Craft::t('super-pdf', $message, $params);
    }

    /**
     * Writes a log entry using the given Craft log helper.
     *
     * @param string $message The message to log.
     * @param string $type Craft log helper name (`info`, `warning`, `error`, …).
     * @return void
     */
    public static function log(string $message, string $type = 'info'): void
    {
        Craft::$type(self::t($message), __METHOD__);
    }

    /**
     * Writes an info log entry.
     *
     * @param string $message The message to log.
     * @return void
     */
    public static function info(string $message): void
    {
        Craft::info(self::t($message), __METHOD__);
    }

    /**
     * Writes an error log entry.
     *
     * @param string $message The message to log.
     * @return void
     */
    public static function error(string $message): void
    {
        Craft::error(self::t($message), __METHOD__);
    }
}
