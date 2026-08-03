<?php
/**
 * Super PDF plugin for Craft CMS 4.x / 5.x
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperPdf\controllers;

use Craft;
use craft\web\Controller as BaseController;
use yii\web\Response;

use amici\SuperPdf\Plugin;

/**
 * Control Panel settings controller.
 *
 * @author    Amici Infotech
 * @package   SuperPdf
 * @since     5.1.0
 */
class SettingsController extends BaseController
{
    // Public Properties
    // =========================================================================

    /**
     * @var mixed|Plugin Plugin instance.
     */
    public mixed $plugin = null;

    /**
     * @var array Settings navigation items from the settings model.
     */
    public array $settingsNav = [];

    /**
     * @var string|null Current CP URL segment used for nav selection.
     */
    public ?string $selectedNav = null;

    // Public Methods
    // =========================================================================

    /**
     * Initializes the controller and loads settings navigation.
     *
     * @return void
     */
    public function init(): void
    {
        parent::init();
        $this->requireAdmin(false);
        $this->plugin = Plugin::$plugin;
        $this->settingsNav = $this->plugin->getSettings()->getSettingsNavItems();
        $this->selectedNav = Craft::$app->getRequest()->getSegment(2);
    }

    /**
     * Renders the general settings form.
     *
     * @return Response|null
     */
    public function actionGeneral(): ?Response
    {
        return $this->_builtGeneralForm();
    }

    /**
     * Saves general plugin settings from a POST request.
     *
     * @return Response|null Redirect on success, or null when validation fails.
     */
    public function actionSaveGeneralSettings(): ?Response
    {
        $this->requirePostRequest();
        $this->requireAdmin();

        if (!Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            throw new \yii\web\ForbiddenHttpException('Administrative changes are disallowed in this environment.');
        }

        $postSettings = Craft::$app->getRequest()->getBodyParam('settings');
        $settings = Plugin::$app->settings->saveSettings($this->plugin, $postSettings);

        if ($settings->hasErrors()) {
            Craft::$app->getSession()->setError(Craft::t('super-pdf', 'Couldn’t save settings.'));
            Craft::$app->getUrlManager()->setRouteParams([
                'settings' => $settings,
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('super-pdf', 'Settings saved.'));

        return $this->redirectToPostedUrl();
    }

    // Private Methods
    // =========================================================================

    /**
     * Builds and renders the general settings template.
     *
     * @param array $meta Extra template meta values.
     * @return Response|null
     */
    private function _builtGeneralForm(array $meta = []): ?Response
    {
        $navigation = $this->settingsNav;
        $settings = Craft::$app->getUrlManager()->getRouteParams()['settings']
            ?? $this->plugin->getSettings();

        $meta['type'] = 'form';
        $meta['selectedNav'] = ($this->selectedNav == '' || $this->selectedNav == 'settings') ? 'local' : $this->selectedNav;
        $meta['action'] = $this->settingsNav[$meta['selectedNav']]['action'];
        $meta['redirect'] = $this->settingsNav[$meta['selectedNav']]['redirect'];
        $meta['sources'] = Plugin::$plugin->general->getSourceOptions();
        $meta['readOnly'] = !Craft::$app->getConfig()->getGeneral()->allowAdminChanges;

        return $this->renderTemplate($this->settingsNav[$meta['selectedNav']]['template'], [
            'settings' => $settings,
            'meta' => $meta,
            'navigation' => $navigation,
            'readOnly' => $meta['readOnly'],
        ]);
    }
}
