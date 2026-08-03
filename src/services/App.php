<?php
/**
 * Super PDF plugin for Craft CMS 4.x / 5.x
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperPdf\services;

use craft\base\Component;

/**
 * Thin application service container for Super PDF.
 *
 * @author    Amici Infotech
 * @package   SuperPdf
 * @since     5.1.0
 *
 * @property Settings $settings
 */
class App extends Component
{
    // Public Properties
    // =========================================================================

    /**
     * @var Settings Settings persistence service.
     */
    public Settings $settings;

    // Public Methods
    // =========================================================================

    /**
     * Initializes nested services.
     *
     * @return void
     */
    public function init(): void
    {
        parent::init();
        $this->settings = new Settings();
    }
}
