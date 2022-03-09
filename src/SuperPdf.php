<?php
namespace amici\SuperPdf;

use Craft;
use yii\base\Event;
use craft\base\Plugin;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterCacheOptionsEvent;
use craft\helpers\FileHelper;
use craft\services\Plugins;
use craft\utilities\ClearCaches;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;

use amici\SuperPdf\base\PluginTrait;
use amici\SuperPdf\models\Settings;
use amici\SuperPdf\variables\SuperPdfVariables;

class SuperPdf extends Plugin
{
	use PluginTrait;

	public static $app;
	public static $plugin;
	public $hasCpSection 		= false;
	public $hasCpSettings 		= false;
    public static $pluginHandle = 'super-pdf';
	public $schemaVersion 		= '1.0.7';

	public function init()
	{
	    parent::init();

	    self::$plugin = $this;
	    // self::$app = new App();
	    $this->_registerRoutes();
	    $this->_registerVariables();
	    $this->_setPluginComponents();

		Event::on(Plugins::class, Plugins::EVENT_AFTER_LOAD_PLUGINS, function () {
			// Register cache options
			$this->_registerCacheOptions();
        });
	}

	private function _registerRoutes()
    {
    	Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_SITE_URL_RULES, function (RegisterUrlRulesEvent $event) {
	        $event->rules['super-pdf/<filename>'] = 'super-pdf/pdf';
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
					'info' => Craft::t('super-pdf', 'Local copies of Super PDF generated PDFs in storage folder. <br> <code>' . self::$plugin->general->getStoragePath() . '</code>'),
                    'action' => FileHelper::normalizePath(self::$plugin->general->getStoragePath())
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

	protected function afterInstall()
	{

	}

	/*public function getCpNavItem()
	{
		$parent = parent::getCpNavItem();
		return $parent;
	}*/

	public function beforeUninstall(): bool
	{
		return true;
	}

}
?>