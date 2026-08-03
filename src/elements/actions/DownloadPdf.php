<?php
/**
 * Super PDF plugin for Craft CMS 4.x / 5.x
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperPdf\elements\actions;

use Craft;
use craft\base\ElementAction;

/**
 * Entry element action that downloads a PDF for the selected entry.
 *
 * Visible only when `entryPdfTemplate` is configured in plugin settings.
 *
 * @author    Amici Infotech
 * @package   SuperPdf
 * @since     5.1.0
 */
class DownloadPdf extends ElementAction
{
    // Public Methods
    // =========================================================================

    /**
     * Returns the action’s display name.
     *
     * @return string
     */
    public static function displayName(): string
    {
        return Craft::t('super-pdf', 'Download PDF');
    }

    /**
     * Registers the element action trigger JavaScript.
     *
     * @return string|null Always null; JS is registered directly.
     */
    public function getTriggerHtml(): ?string
    {
        Craft::$app->getView()->registerJsWithVars(fn($type) => <<<JS
(() => {
    new Craft.ElementActionTrigger({
        type: $type,
        batch: false,
        validateSelection: (\$selectedItems) => \$selectedItems.length === 1,
        activate: (\$selectedItems) => {
            const id = \$selectedItems.find('input').first().val();
            if (!id) {
                return;
            }
            window.location.href = Craft.getActionUrl('super-pdf/pdf/download-entry', {elementId: id});
        }
    });
})();
JS, [static::class]);

        return null;
    }
}
