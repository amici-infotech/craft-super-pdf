<?php
/**
 * Super PDF plugin for Craft CMS 4.x / 5.x
 *
 * Generate PDF files from HTML and Twig templates using DomPDF.
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperPdf;

use Craft;
use yii\base\Event;

use craft\base\Plugin as CraftPlugin;
use craft\console\Application as ConsoleApplication;
use craft\elements\Entry;
use craft\events\RegisterElementActionsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterCacheOptionsEvent;
use craft\helpers\FileHelper;
use craft\helpers\UrlHelper;
use craft\services\Plugins;
use craft\utilities\ClearCaches;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;

use amici\SuperPdf\base\PluginTrait;
use amici\SuperPdf\elements\actions\DownloadPdf;
use amici\SuperPdf\models\Settings;
use amici\SuperPdf\services\App;
use amici\SuperPdf\variables\SuperPdfVariables;

/**
 * Super PDF Plugin
 *
 * @author    Amici Infotech
 * @package   SuperPdf
 * @since     5.1.0
 *
 * @property-read Settings $settings
 * @property-read \amici\SuperPdf\services\Pdf $pdf
 * @property-read \amici\SuperPdf\services\General $general
 * @method Settings getSettings()
 */
class Plugin extends CraftPlugin
{
    use PluginTrait;

    // Static Properties
    // =========================================================================

    /**
     * @var App Application service container.
     */
    public static App $app;

    /**
     * @var CraftPlugin Singleton plugin instance.
     */
    public static CraftPlugin $plugin;

    /**
     * @var string Plugin handle.
     */
    public static string $pluginHandle = 'super-pdf';

    // Public Properties
    // =========================================================================

    /**
     * @var string Database schema version.
     */
    public string $schemaVersion = '5.1.0';

    /**
     * @var bool Whether the plugin exposes a CP section.
     */
    public bool $hasCpSection = false;

    /**
     * @var bool Whether the plugin exposes CP settings.
     */
    public bool $hasCpSettings = true;

    // Public Methods
    // =========================================================================

    /**
     * Initializes the plugin and registers components, routes, variables, and cache options.
     *
     * @return void
     */
    public function init(): void
    {
        parent::init();

        self::$plugin = $this;
        self::$app = new App();

        if (Craft::$app instanceof ConsoleApplication) {
            $this->controllerNamespace = 'amici\SuperPdf\console\controllers';
        }

        $this->_setPluginComponents();
        $this->_registerCpRoutes();
        $this->_registerWebRoutes();
        $this->_registerVariables();
        $this->_registerElementActions();

        $this->hasCpSection = (bool)$this->getSettings()->hasCpSection;

        Event::on(Plugins::class, Plugins::EVENT_AFTER_LOAD_PLUGINS, function() {
            $this->_registerCacheOptions();
        });

        Craft::info(
            Craft::t('super-pdf', '{name} plugin loaded', ['name' => $this->name]),
            __METHOD__
        );
    }

    /**
     * Creates the plugin settings model.
     *
     * @return Settings The settings model instance.
     */
    protected function createSettingsModel(): Settings
    {
        return new Settings();
    }

    /**
     * Returns the CP navigation item for the plugin section.
     *
     * @return array|null The nav item config, or null when unavailable.
     */
    public function getCpNavItem(): ?array
    {
        $parent = parent::getCpNavItem();
        if ($parent === null) {
            return null;
        }

        $label = $this->getSettings()->pluginName;
        $parent['label'] = $label !== '' ? $label : 'Super PDF';
        $parent['url'] = 'super-pdf';

        return $parent;
    }

    /**
     * Returns the path to the CP nav mask icon, when present.
     *
     * @return string|null Absolute filesystem path, or null when the icon is missing.
     */
    protected function cpNavIconPath(): ?string
    {
        $path = $this->getBasePath() . DIRECTORY_SEPARATOR . 'icon-mask.svg';
        return is_file($path) ? $path : null;
    }

    /**
     * Redirects the plugin settings button to the Super PDF settings screen.
     *
     * @return mixed Redirect response.
     */
    public function getSettingsResponse(): mixed
    {
        return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('super-pdf/settings'));
    }

    // Private Methods
    // =========================================================================

    /**
     * Registers the front-end route used to serve storage PDFs.
     *
     * @return void
     */
    private function _registerWebRoutes(): void
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules['super-pdf/<filename:[^\/]+>'] = 'super-pdf/pdf';
        });
    }

    /**
     * Registers Control Panel URL rules for settings.
     *
     * @return void
     */
    private function _registerCpRoutes(): void
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules = array_merge($event->rules, [
                'super-pdf' => 'super-pdf/settings/general',
                'super-pdf/settings' => 'super-pdf/settings/general',
            ]);
        });
    }

    /**
     * Registers Clear Caches options for generated PDFs and DomPDF font metrics.
     *
     * @return void
     */
    private function _registerCacheOptions(): void
    {
        Event::on(ClearCaches::class, ClearCaches::EVENT_REGISTER_CACHE_OPTIONS,
            static function(RegisterCacheOptionsEvent $event) {
                $pdf = self::$plugin->pdf;
                $event->options[] = [
                    'key' => 'super-pdf-cache',
                    'label' => Craft::t('super-pdf', 'Super PDF Cache'),
                    'info' => Craft::t('super-pdf', 'Local copies of Super PDF generated PDFs in storage folder. <br> <code>{path}</code>', [
                        'path' => $pdf->getStoragePath(),
                    ]),
                    'action' => FileHelper::normalizePath($pdf->getStoragePath()),
                ];

                $event->options[] = [
                    'key' => 'super-pdf-font-cache',
                    'label' => Craft::t('super-pdf', 'Super PDF Font Cache'),
                    'info' => Craft::t('super-pdf', 'DomPDF font metrics cache. <br> <code>{path}</code>', [
                        'path' => $pdf->getFontCachePath(),
                    ]),
                    'action' => FileHelper::normalizePath($pdf->getFontCachePath()),
                ];
            }
        );
    }

    /**
     * Registers the `craft.superpdf` Twig variable.
     *
     * @return void
     */
    private function _registerVariables(): void
    {
        Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $event) {
            $event->sender->set('superpdf', SuperPdfVariables::class);
        });
    }

    /**
     * Registers the entry “Download PDF” action when a template is configured.
     *
     * @return void
     */
    private function _registerElementActions(): void
    {
        Event::on(Entry::class, Entry::EVENT_REGISTER_ACTIONS, function(RegisterElementActionsEvent $event) {
            $template = trim((string)$this->getSettings()->entryPdfTemplate);
            if ($template !== '') {
                $event->actions[] = DownloadPdf::class;
            }
        });
    }
}
