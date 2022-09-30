<?php
namespace amici\SuperPdf\services;

use Craft;
use yii\base\Component;

use craft\base\Plugin;
use craft\helpers\Json;

class Settings extends Component
{

    public function saveSettings($plugin, $settings)
    {
        $pluginSettings = $plugin->getSettings();
        $settings = $settings['settings'] ?? $settings;

        foreach ($pluginSettings->getAttributes() as $settingHandle => $value)
        {
            if (isset($settings[$settingHandle]))
            {
                $pluginSettings->{$settingHandle} = $settings[$settingHandle] ?? $value;
            }
        }

        if (! $pluginSettings->validate())
        {
            return $pluginSettings;
        }

        Craft::$app->getPlugins()->savePluginSettings($plugin, $pluginSettings->getAttributes());

        return $pluginSettings;
    }

}
