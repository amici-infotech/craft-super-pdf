<?php
/**
 * Super PDF plugin for Craft CMS 4.x / 5.x
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperPdf;

/**
 * Legacy plugin bootstrap class.
 *
 * @deprecated 5.1.0 Use {@see Plugin} instead. Kept so upgrades that still
 *             reference `amici\SuperPdf\SuperPdf` continue to load.
 *
 * @author    Amici Infotech
 * @package   SuperPdf
 * @since     1.0.0
 */
class SuperPdf extends Plugin
{
    /**
     * Ensures the shared Plugin::$plugin static is set when Craft instantiates this class.
     *
     * @return void
     */
    public function init(): void
    {
        parent::init();

        // Craft may still instantiate this legacy class from project config / DB.
        Plugin::$plugin = $this;
    }
}
