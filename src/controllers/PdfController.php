<?php
/**
 * Super PDF plugin for Craft CMS 4.x / 5.x
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperPdf\controllers;

use Craft;
use yii\base\Exception;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use craft\elements\Entry;
use craft\web\Controller as BaseController;

use amici\SuperPdf\Plugin;

/**
 * Front-end PDF controller.
 *
 * Serves storage PDFs and handles the CP entry download action.
 *
 * @author    Amici Infotech
 * @package   SuperPdf
 * @since     5.1.0
 */
class PdfController extends BaseController
{
    // Protected Properties
    // =========================================================================

    /**
     * @var array|bool|int Actions that may be accessed anonymously.
     */
    protected array|bool|int $allowAnonymous = [
        'index' => self::ALLOW_ANONYMOUS_LIVE,
    ];

    // Public Methods
    // =========================================================================

    /**
     * Serves a PDF from Craft storage by filename.
     *
     * Signature query params (`e`, `s`) are validated when present. When
     * `requireSignedUrls` is enabled, unsigned requests are rejected.
     *
     * @param string|null $filename PDF basename from the route.
     * @return Response File download / inline response.
     */
    public function actionIndex(?string $filename = ''): Response
    {
        if ($filename === '') {
            throw new BadRequestHttpException(Plugin::t('Filename field cannot be empty'));
        }

        $pdf = Plugin::$plugin->pdf;
        $filename = $pdf->sanitizeFilename($filename);
        $expires = Craft::$app->getRequest()->getQueryParam('e');
        $signature = Craft::$app->getRequest()->getQueryParam('s');
        $requireSigned = filter_var(
            Plugin::$plugin->getSettings()->requireSignedUrls,
            FILTER_VALIDATE_BOOLEAN
        );

        // BC: unsigned legacy links keep working unless requireSignedUrls is enabled.
        // If signature params are present, always validate them.
        $hasSignatureParams = $expires !== null && $expires !== '' && $signature !== null && $signature !== '';
        if ($hasSignatureParams) {
            if (!$pdf->validateSignature($filename, $expires, $signature)) {
                throw new ForbiddenHttpException(Plugin::t('This PDF link is invalid or has expired.'));
            }
        } elseif ($requireSigned) {
            throw new ForbiddenHttpException(Plugin::t('This PDF link is invalid or has expired.'));
        }

        $path = $pdf->getStoragePath() . '/' . $filename;

        if (!$pdf->isPathInsideStorage($path) || !is_file($path)) {
            throw new NotFoundHttpException(Plugin::t('PDF File does not exists.'));
        }

        return Craft::$app->getResponse()->sendFile($path, null, ['inline' => true]);
    }

    /**
     * Streams a PDF for a single entry using the configured entry PDF template.
     *
     * @return Response PDF download response (or exits via Dompdf stream).
     */
    public function actionDownloadEntry(): Response
    {
        $this->requireCpRequest();
        $this->requireLogin();

        $elementId = (int)Craft::$app->getRequest()->getRequiredParam('elementId');
        $entry = Entry::find()->id($elementId)->status(null)->one();

        if (!$entry) {
            throw new NotFoundHttpException(Plugin::t('Entry not found.'));
        }

        $template = trim((string)Plugin::$plugin->getSettings()->entryPdfTemplate);
        if ($template === '') {
            throw new Exception(Plugin::t('Set the Entry PDF Template in Super PDF settings before downloading.'));
        }

        $settings = [
            'filename' => $entry->slug ?: ('entry-' . $entry->id),
            'type' => 'download',
        ];

        return Plugin::$plugin->pdf->template($template, $settings, [
            'entry' => $entry,
            'element' => $entry,
        ]);
    }
}
