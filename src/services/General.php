<?php
/**
 * Super PDF plugin for Craft CMS 4.x / 5.x
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperPdf\services;

use Craft;
use craft\base\Component;
use craft\helpers\FileHelper;
use craft\elements\Asset;

use amici\SuperPdf\Plugin;

/**
 * General helpers for settings access, storage paths, and volume options.
 *
 * @author    Amici Infotech
 * @package   SuperPdf
 * @since     5.1.0
 */
class General extends Component
{
    // Public Properties
    // =========================================================================

    /**
     * @var mixed|Plugin Plugin instance.
     */
    public mixed $plugin;

    // Public Methods
    // =========================================================================

    /**
     * Creates the helper and stores the plugin instance.
     */
    /**
     * Initializes the service.
     *
     * @return void
     */
    public function init(): void
    {
        parent::init();
        $this->plugin = Plugin::$plugin;
    }

    /**
     * Returns all settings as an array, or a single setting by handle.
     *
     * @param string $key Setting handle. Empty string returns all settings.
     * @return mixed Settings array or a single value.
     */
    public function getSettings(string $key = ''): mixed
    {
        $settings = $this->plugin->getSettings();

        if ($key === '') {
            return (array)$settings;
        }

        return $settings->$key ?? '';
    }

    /**
     * Returns the base Super PDF storage directory, creating it when missing.
     *
     * @return string Absolute filesystem path.
     */
    public function getStoragePath(): string
    {
        $path = Craft::$app->getPath()->getStoragePath();
        $path = rtrim($path, '/') . '/super-pdf';
        $path = FileHelper::normalizePath($path);
        FileHelper::createDirectory($path);

        return $path;
    }

    /**
     * Returns select options for PDF storage targets (Craft storage + volumes).
     *
     * @return array<int, array{label: string, value: string}>
     */
    public function getSourceOptions(): array
    {
        $sourceOptions = [];
        $sourceOptions[] = [
            'label' => Craft::t('super-pdf', 'Craft Storage (Default)'),
            'value' => 'storage',
        ];

        foreach (Asset::sources('settings') as $volume) {
            if (!isset($volume['heading'])) {
                $sourceOptions[] = [
                    'label' => $volume['label'],
                    'value' => $volume['data']['volume-handle'],
                ];
            }
        }

        return $sourceOptions;
    }
}
