<?php
namespace amici\SuperPdf;

use Craft;
use yii\base\Event;
use craft\base\Plugin;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterCacheOptionsEvent;
use craft\helpers\FileHelper;
use craft\helpers\UrlHelper;
use craft\services\Plugins;
use craft\utilities\ClearCaches;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;

use amici\SuperPdf\base\PluginTrait;
use amici\SuperPdf\services\App;
use amici\SuperPdf\models\Settings;
use amici\SuperPdf\variables\SuperPdfVariables;

class SuperPdf extends Plugin
{
	use PluginTrait;

	public static $app;
	public static Plugin $plugin;
	public string $schemaVersion = '2.0.2.5';
	// public string $minVersionRequired = '1.0.0';
	public bool $hasCpSection 		= false;
	public bool $hasCpSettings 		= true;
    public static string $pluginHandle = 'super-pdf';

	public function init()
	{
	    parent::init();

	    self::$plugin = $this;
	    self::$app = new App();
        $this->_registerCpRoutes();
	    $this->_registerWebRoutes();
	    $this->_registerVariables();
	    $this->_setPluginComponents();

        $this->hasCpSection = $this->getSettings()->hasCpSection;

		Event::on(Plugins::class, Plugins::EVENT_AFTER_LOAD_PLUGINS, function () {
			// Register cache options
			$this->_registerCacheOptions();
        });
	}

	private function _registerWebRoutes()
    {
    	Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES, function (RegisterUrlRulesEvent $event) {
	        $event->rules['super-pdf/<filename>'] = 'super-pdf/pdf';
	    });
	}

    private function _registerCpRoutes(): void
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function(RegisterUrlRulesEvent $event) {
            $event->rules = array_merge($event->rules, [
                'super-pdf'          => 'super-pdf/settings/general',
                'super-pdf/settings' => 'super-pdf/settings/general',
            ]);
        });
    }

	private function _registerCacheOptions()
    {
    	// Adds PDF storage path to the list of things the Clear Caches tool can delete
        Event::on(ClearCaches::class, ClearCaches::EVENT_REGISTER_CACHE_OPTIONS,
            static function (RegisterCacheOptionsEvent $event) {
                $event->options[] = [
                    'key' => 'super-pdf-cache',
                    'label' => Craft::t('super-pdf', 'Super PDF Cache'),
					'info' => Craft::t('super-pdf', 'Local copies of Super PDF generated PDFs in storage folder. <br> <code>' . self::$plugin->pdf->getStoragePath() . '</code>'),
                    'action' => FileHelper::normalizePath(self::$plugin->pdf->getStoragePath())
                ];
            }
        );
	}

	private function _registerVariables()
	{
	    Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function(Event $event) {
	        $event->sender->set('superpdf', SuperPdfVariables::class);
	    });
	}

	protected function createSettingsModel(): Settings
	{
	    return new Settings();
	}

    public function getCpNavItem(): ?array
    {

        $parent = parent::getCpNavItem();
        $parent['label'] = $this->getSettings()->pluginName;
        $parent['url'] = 'super-pdf';

        if($parent['label'] == "")
        {
            $parent['label'] = "Super PDF";
        }

        return $parent;

    }

    protected function cpNavIconPath(): ?string
    {
        $path = $this->getBasePath() . DIRECTORY_SEPARATOR . 'icon-mask.svg';
        return is_file($path) ? $path : null;
    }

	protected function afterInstall() : void
	{

	}

    public function getSettingsResponse(): mixed
    {
        return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('super-pdf/settings'));
    }

	/*public function getCpNavItem()
	{
		$parent = parent::getCpNavItem();
		return $parent;
	}*/

	public function beforeUninstall(): void
	{

	}

}
?>