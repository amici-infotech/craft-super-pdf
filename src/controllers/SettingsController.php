<?php
namespace amici\SuperPdf\controllers;

use Craft;
use craft\web\Controller as BaseController;
use craft\elements\Entry;

use amici\SuperPdf\SuperPdf;

class SettingsController extends BaseController
{

	public $settingsSection;
	public $selectedSidebarItem;
    public $plugin;
    public $settingsNav;
    public $selectedNav;

    /*protected $allowAnonymous = [
        'test-function-handle' => self::ALLOW_ANONYMOUS_LIVE
    ];*/

	public function init(): void
    {
        parent::init();
        $this->plugin = SuperPdf::$plugin;
        $this->settingsNav = $this->plugin->getSettings()->getSettingsNavItems();
        $this->selectedNav = Craft::$app->getRequest()->getSegment(2);
    }

    public function actionGeneral()
    {
        $this->_builtGeneralForm();
    }

    public function actionSaveGeneralSettings()
    {

        $this->requirePostRequest();
        $postSettings = Craft::$app->getRequest()->getBodyParam('settings');

        $settings = $this->plugin->getSettings();
        $settings = SuperPdf::$app->settings->saveSettings($this->plugin, $postSettings);

        if ($settings->hasErrors())
        {
            Craft::$app->getSession()->setError(Craft::t('super-pdf', 'Couldn’t save settings.'));
            Craft::$app->getUrlManager()->setRouteParams([
                'settings' => $settings
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('super-pdf', 'Settings saved.'));
        return $this->redirectToPostedUrl();

    }

    public function _builtGeneralForm($meta = [])
    {
        $navigation    = $this->settingsNav;
        $settings      = $this->plugin->getSettings();
        $meta['type']        = "form";
        $meta['selectedNav'] = ($this->selectedNav == '' || $this->selectedNav == 'settings') ? 'local' : $this->selectedNav;
        $meta['action']      = $this->settingsNav[$meta['selectedNav']]['action'];
        $meta['redirect']    = $this->settingsNav[$meta['selectedNav']]['redirect'];
        $meta['sources']     = SuperPdf::$plugin->general->getSourceOptions();

        $this->renderTemplate($this->settingsNav[$meta['selectedNav']]['template'], array(
            'settings'  => $settings,
            'meta'      => $meta,
            'navigation'=> $navigation
        ));
    }

}