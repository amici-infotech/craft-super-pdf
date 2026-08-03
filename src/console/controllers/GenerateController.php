<?php
/**
 * Super PDF plugin for Craft CMS 4.x / 5.x
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperPdf\console\controllers;

use Craft;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

use amici\SuperPdf\Plugin;
use craft\elements\Entry;

/**
 * Console commands for generating PDFs.
 *
 * Examples:
 * - `php craft super-pdf/generate --template=pdf-3 --filename=smoke`
 * - `php craft super-pdf/generate --html="<h1>Hi</h1>" --filename=hi`
 * - `php craft super-pdf/generate --template=_pdf/invoice --elementId=123 --filename=invoice-123`
 *
 * @author    Amici Infotech
 * @package   SuperPdf
 * @since     5.1.0
 */
class GenerateController extends Controller
{
    /**
     * @var string|null Twig template path.
     */
    public ?string $template = null;

    /**
     * @var string|null Raw HTML string.
     */
    public ?string $html = null;

    /**
     * @var string Output filename without `.pdf`.
     */
    public string $filename = 'craft-super-pdf';

    /**
     * @var int|null Entry ID passed to the template as `entry` / `element`.
     */
    public ?int $elementId = null;

    /**
     * @var string Output mode (`object` recommended for console).
     */
    public string $type = 'object';

    /**
     * @inheritdoc
     */
    public function options($actionID): array
    {
        return array_merge(parent::options($actionID), [
            'template',
            'html',
            'filename',
            'elementId',
            'type',
        ]);
    }

    /**
     * Generates a PDF from a Twig template or HTML string.
     *
     * @return int Exit code.
     */
    public function actionIndex(): int
    {
        if (($this->template === null || $this->template === '') && ($this->html === null || $this->html === '')) {
            $this->stderr("Provide --template=path or --html=\"<markup>\".\n", Console::FG_RED);
            return ExitCode::USAGE;
        }

        $settings = [
            'filename' => $this->filename,
            'type' => $this->type ?: 'object',
        ];

        $vars = [];
        if ($this->elementId) {
            $entry = Entry::find()->id($this->elementId)->status(null)->one();
            if (!$entry) {
                $this->stderr("Entry #{$this->elementId} not found.\n", Console::FG_RED);
                return ExitCode::DATAERR;
            }
            $vars['entry'] = $entry;
            $vars['element'] = $entry;
        }

        $pdf = Plugin::$plugin->getPdf();

        if ($this->template) {
            $result = $pdf->template($this->template, $settings, $vars);
        } else {
            $result = $pdf->html((string)$this->html, $settings);
        }

        if ($result === false) {
            $this->stderr("PDF generation failed. Check logs.\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if (is_object($result) && method_exists($result, 'getPath')) {
            $this->stdout("PDF written: {$result->getPath()}\n", Console::FG_GREEN);
            if (method_exists($result, 'getUrl')) {
                $this->stdout("URL: {$result->getUrl()}\n");
            }
        } elseif (is_string($result)) {
            $this->stdout('PDF generated (' . strlen($result) . " bytes).\n", Console::FG_GREEN);
        } else {
            $this->stdout("PDF generated.\n", Console::FG_GREEN);
        }

        return ExitCode::OK;
    }
}
