<?php
/**
 * Super PDF plugin for Craft CMS 4.x / 5.x
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperPdf\services;

use Craft;
use yii\base\Component;
use craft\base\Model;
use craft\base\Plugin as CraftPlugin;

/**
 * Persists plugin settings from Control Panel forms.
 *
 * @author    Amici Infotech
 * @package   SuperPdf
 * @since     5.1.0
 */
class Settings extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * Applies posted settings, validates, and saves them on the plugin.
     *
     * Lightswitch fields omitted from POST are treated as off.
     *
     * @param CraftPlugin $plugin The Super PDF plugin instance.
     * @param mixed $settings Posted settings array (optionally nested under `settings`).
     * @return Model The settings model (with errors when validation fails).
     */
    public function saveSettings(CraftPlugin $plugin, mixed $settings): Model
    {
        $pluginSettings = $plugin->getSettings();
        $settings = $settings['settings'] ?? $settings;

        foreach ($pluginSettings->getAttributes() as $settingHandle => $value) {
            if (isset($settings[$settingHandle])) {
                $pluginSettings->{$settingHandle} = $settings[$settingHandle] ?? $value;
            }
        }

        // Lightswitches omitted from POST are off
        foreach (['hasCpSection', 'requireSignedUrls'] as $boolSetting) {
            if (is_array($settings) && !array_key_exists($boolSetting, $settings)) {
                $pluginSettings->{$boolSetting} = false;
            }
        }

        if (!$pluginSettings->validate()) {
            return $pluginSettings;
        }

        Craft::$app->getPlugins()->savePluginSettings($plugin, $pluginSettings->getAttributes());

        return $pluginSettings;
    }
}
