<?php
/**
 * Super PDF plugin for Craft CMS 4.x / 5.x
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperPdf\drivers;

use amici\SuperPdf\Plugin;
use yii\base\Exception;

/**
 * Resolves a PDF driver instance from a driver id.
 *
 * @author    Amici Infotech
 * @package   SuperPdf
 * @since     5.2.0
 */
class DriverFactory
{
    public const DRIVER_DOMPDF = 'dompdf';
    public const DRIVER_BROWSERSHOT = 'browsershot';

    /**
     * @var list<string>
     */
    public const DRIVERS = [
        self::DRIVER_DOMPDF,
        self::DRIVER_BROWSERSHOT,
    ];

    /**
     * Creates a driver for the given id.
     *
     * @param string $driver Driver id (`dompdf`, `browsershot`).
     * @return PdfDriverInterface
     * @throws Exception When the driver is unknown or its package is missing.
     */
    public static function create(string $driver): PdfDriverInterface
    {
        $driver = strtolower(trim($driver));
        if ($driver === '') {
            $driver = self::DRIVER_DOMPDF;
        }

        return match ($driver) {
            self::DRIVER_DOMPDF => new DompdfDriver(),
            self::DRIVER_BROWSERSHOT => self::createBrowsershot(),
            default => throw new Exception(Plugin::t(
                'Unknown PDF driver “{driver}”. Supported drivers: {list}.',
                [
                    'driver' => $driver,
                    'list' => implode(', ', self::DRIVERS),
                ]
            )),
        };
    }

    /**
     * @return BrowsershotDriver
     * @throws Exception
     */
    private static function createBrowsershot(): BrowsershotDriver
    {
        if (!class_exists(\Spatie\Browsershot\Browsershot::class)) {
            throw new Exception(Plugin::t(
                'The Browsershot PDF driver could not load Spatie Browsershot. Run `composer update` and ensure Node.js, Puppeteer, and Chrome/Chromium are available.'
            ));
        }

        return new BrowsershotDriver();
    }
}
