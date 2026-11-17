<?php
/**
 * Super PDF plugin for Craft CMS 4.x / 5.x
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperPdf\services;

use Craft;
use amici\SuperPdf\Plugin;
use amici\SuperPdf\drivers\DriverFactory;
use amici\SuperPdf\drivers\PdfDriverInterface;
use amici\SuperPdf\drivers\RenderResult;
use amici\SuperPdf\events\PdfEvent;
use amici\SuperPdf\jobs\GeneratePdfJob;
use amici\SuperPdf\models\Asset as PdfAsset;

use craft\base\Component;
use craft\elements\Asset;
use craft\helpers\Assets;
use craft\helpers\DateTimeHelper;
use craft\helpers\FileHelper;
use craft\helpers\UrlHelper;
use craft\web\Response;

use Dompdf\Dompdf;
use yii\base\Exception;

/**
 * PDF generation service (multi-driver render, storage, signing, queue, email helpers).
 *
 * @author    Amici Infotech
 * @package   SuperPdf
 * @since     5.1.0
 */
class Pdf extends Component
{
    /**
     * @event PdfEvent Fired before the PDF driver loads and renders HTML.
     *                 Set `isValid` to false to cancel. Mutate `html` / `settings` as needed.
     */
    public const EVENT_BEFORE_RENDER = 'beforeRender';

    /**
     * @event PdfEvent Fired after the PDF has been rendered (before output/save branching).
     */
    public const EVENT_AFTER_RENDER = 'afterRender';

    /**
     * @event PdfEvent Fired before a PDF is written to storage or a volume (`type: object`).
     */
    public const EVENT_BEFORE_SAVE = 'beforeSave';

    /**
     * @event PdfEvent Fired after a PDF has been saved and the asset wrapper is ready.
     */
    public const EVENT_AFTER_SAVE = 'afterSave';

    // Constants
    // =========================================================================

    /**
     * Setting keys that belong to Super PDF and must not be passed into Dompdf Options.
     *
     * @var list<string>
     */
    public const PLUGIN_SETTING_KEYS = [
        'forceDownload',
        'compress',
        'filename',
        'type',
        'signedUrlExpiry',
        'requireSignedUrls',
        'encrypt',
        'password',
        'adminPassword',
        'print',
        'modify',
        'copy',
        'add',
        'streamContext',
        'httpContext',
        'headerTemplate',
        'footerTemplate',
        'headerHtml',
        'footerHtml',
        'pluginName',
        'hasCpSection',
        'volume',
        'folder',
        'resaveBehaviour',
        'entryPdfTemplate',
        'allowedRemoteHosts',
        'driver',
        'tagged',
        'browsershotNodeBinary',
        'browsershotNpmBinary',
        'browsershotChromePath',
        'browsershotNoSandbox',
        'browsershotTimeout',
        'browsershotMargins',
    ];

    // Public Properties
    // =========================================================================

    /**
     * @var mixed|PdfAsset|null Current PDF asset wrapper when generating or reusing a file.
     */
    public mixed $asset = null;

    /**
     * @var string HTML being rendered.
     */
    public string $html = '';

    /**
     * @var array Merged plugin + per-call settings for the current generation.
     */
    public array $settings = [];

    /**
     * @var Dompdf|null Active Dompdf instance when using the DomPDF driver.
     */
    public ?Dompdf $dompdf = null;

    /**
     * @var string|null Raw PDF bytes from the last successful render.
     */
    public ?string $pdfBytes = null;

    /**
     * @var string|null Driver id used for the last render.
     */
    public ?string $driver = null;

    /**
     * @var bool Whether Craft Dev Mode is enabled.
     */
    public bool $devMode = false;

    /**
     * @var mixed Cached asset folder when saving to a volume.
     */
    public mixed $folder = null;

    /**
     * @var string|null Absolute path to the generated or existing PDF file.
     */
    public ?string $path = null;

    /**
     * @var string|null Template path for the current generation, when applicable.
     */
    public ?string $template = null;

    /**
     * @var array Template variables for the current generation.
     */
    public array $vars = [];

    // Public Methods
    // =========================================================================

    /**
     * Initializes the service and loads default settings.
     *
     * @return void
     */
    public function init(): void
    {
        parent::init();
        $this->devMode = Craft::$app->getConfig()->getGeneral()->devMode;
        $this->resetSettings();
    }

    /**
     * Resets runtime state and reloads settings from the plugin.
     *
     * @return void
     */
    public function resetSettings(): void
    {
        $this->settings = Plugin::$plugin->getSettings()->getAttributes();
        $this->folder = null;
        $this->path = null;
        $this->asset = null;
        $this->dompdf = null;
        $this->pdfBytes = null;
        $this->driver = null;
        $this->html = '';
        $this->template = null;
        $this->vars = [];
    }

    /**
     * Alias of {@see html()} for a clearer PHP API.
     *
     * @param string $html HTML document markup.
     * @param array $settings Per-call settings overrides.
     * @return mixed Generation result for the configured `type`.
     */
    public function fromHtml(string $html = '', array $settings = []): mixed
    {
        return $this->html($html, $settings);
    }

    /**
     * Alias of {@see template()} for a clearer PHP API.
     *
     * @param string $template Twig template path.
     * @param array $settings Per-call settings overrides.
     * @param array $vars Variables passed into the template.
     * @return mixed Generation result for the configured `type`.
     */
    public function fromTemplate(string $template = '', array $settings = [], array $vars = []): mixed
    {
        return $this->template($template, $settings, $vars);
    }

    /**
     * Generates a PDF from an HTML string.
     *
     * @param string $html HTML document markup.
     * @param array $settings Per-call settings overrides.
     * @return mixed Streamed PDF, asset object, string/base64, or false on failure.
     */
    public function html(string $html = '', array $settings = []): mixed
    {
        $this->resetSettings();
        $this->mergeSettings($settings);
        $this->fetchFile();

        $this->html = $html;
        $this->template = null;
        $this->vars = [];
        $this->prepareHtmlPlaceholders();
        $this->prepareChrome();

        return $this->_generate();
    }

    /**
     * Generates a PDF from a Twig page template.
     *
     * @param string $template Twig template path.
     * @param array $settings Per-call settings overrides.
     * @param array $vars Variables passed into the template.
     * @return mixed Streamed PDF, asset object, string/base64, or false on failure.
     */
    public function template(string $template = '', array $settings = [], array $vars = []): mixed
    {
        $this->resetSettings();
        $this->mergeSettings($settings);
        $this->template = $template;
        $this->vars = $vars;
        $this->fetchFile();

        $needsRender = !$this->asset
            || ($this->settings['resaveBehaviour'] ?? null) !== 'ignore'
            || !in_array($this->settings['type'] ?? null, ['url', 'object'], true);

        if ($needsRender) {
            $this->html = Craft::$app->getView()->renderPageTemplate($template, $vars);
            $this->prepareHtmlPlaceholders();
            $this->prepareChrome($vars);
        }

        return $this->_generate();
    }

    /**
     * Queues asynchronous PDF generation from HTML.
     *
     * @param string $html HTML document markup.
     * @param array $settings Per-call settings overrides.
     * @param int $priority Queue priority.
     * @return string|int|null Queue push ID.
     */
    public function queueHtml(string $html, array $settings = [], int $priority = 10): string|int|null
    {
        return $this->queueJob([
            'mode' => 'html',
            'html' => $html,
            'settings' => $settings,
        ], $priority);
    }

    /**
     * Queues asynchronous PDF generation from a Twig template.
     *
     * @param string $template Twig template path.
     * @param array $settings Per-call settings overrides.
     * @param array $vars Serializable template variables.
     * @param int $priority Queue priority.
     * @return string|int|null Queue push ID.
     */
    public function queueTemplate(string $template, array $settings = [], array $vars = [], int $priority = 10): string|int|null
    {
        return $this->queueJob([
            'mode' => 'template',
            'template' => $template,
            'settings' => $settings,
            'vars' => $vars,
        ], $priority);
    }

    // Private Methods
    // =========================================================================

    /**
     * Pushes a {@see GeneratePdfJob} onto the queue.
     *
     * @param array $config Job config (`mode`, HTML/template, settings, vars).
     * @param int $priority Queue priority.
     * @return string|int|null Queue push ID.
     */
    private function queueJob(array $config, int $priority): string|int|null
    {
        $settings = array_merge($this->settings, $config['settings'] ?? []);
        if (!in_array($settings['type'] ?? 'object', ['object', 'url'], true)) {
            $settings['type'] = 'object';
        }
        $config['settings'] = $settings;

        $job = new GeneratePdfJob($config);
        return Craft::$app->getQueue()->priority($priority)->push($job);
    }

    /**
     * Merges per-call settings into the current runtime settings array.
     *
     * @param array $settings Overrides to apply.
     * @return void
     */
    private function mergeSettings(array $settings): void
    {
        foreach ($settings as $key => $value) {
            $this->settings[$key] = $value;
        }
    }

    /**
     * Normalizes page-total placeholders before DomPDF render.
     *
     * @return void
     */
    private function prepareHtmlPlaceholders(): void
    {
        $this->html = str_replace(
            ['SUPER_PDF_TOTAL_PAGES', '{PAGE_COUNT}'],
            '{TP}',
            $this->html
        );
    }

    /**
     * Resolves header/footer for the active driver.
     *
     * DomPDF: injects fixed-position chrome into the HTML.
     * Browsershot: stores resolved HTML on settings for native header/footer APIs.
     *
     * @param array $vars Variables for header/footer Twig templates.
     * @return void
     */
    private function prepareChrome(array $vars = []): void
    {
        $header = $this->resolveChromeHtml('header', $vars);
        $footer = $this->resolveChromeHtml('footer', $vars);
        $driver = strtolower((string)($this->settings['driver'] ?? DriverFactory::DRIVER_DOMPDF));

        if ($driver === DriverFactory::DRIVER_BROWSERSHOT) {
            if ($header !== '') {
                $this->settings['headerHtml'] = $header;
            }
            if ($footer !== '') {
                $this->settings['footerHtml'] = $footer;
            }
            return;
        }

        $this->injectChromeHtml($header, $footer);
    }

    /**
     * Injects fixed header/footer HTML so DomPDF repeats them on each page.
     *
     * @param string $header Header HTML snippet.
     * @param string $footer Footer HTML snippet.
     * @return void
     */
    private function injectChromeHtml(string $header, string $footer): void
    {
        if ($header !== '') {
            $this->html = '<div class="super-pdf-header" style="position:fixed;top:0;left:0;right:0;">'
                . $header
                . '</div>'
                . $this->html;
        }

        if ($footer !== '') {
            $this->html .= '<div class="super-pdf-footer" style="position:fixed;bottom:0;left:0;right:0;">'
                . $footer
                . '</div>';
        }
    }

    /**
     * Resolves header or footer HTML from settings (`*Html` or `*Template`).
     *
     * @param string $which Either `header` or `footer`.
     * @param array $vars Variables for Twig partials.
     * @return string HTML snippet, or an empty string when unset.
     */
    private function resolveChromeHtml(string $which, array $vars): string
    {
        $htmlKey = $which . 'Html';
        $templateKey = $which . 'Template';

        if (!empty($this->settings[$htmlKey])) {
            return (string)$this->settings[$htmlKey];
        }

        if (!empty($this->settings[$templateKey])) {
            return Craft::$app->getView()->renderTemplate((string)$this->settings[$templateKey], $vars);
        }

        return '';
    }

    /**
     * Renders the current HTML via the configured driver and returns the output.
     *
     * @return mixed Generation result for the configured `type`, or false on failure.
     */
    private function _generate(): mixed
    {
        try {
            if ($this->asset && ($this->settings['resaveBehaviour'] ?? null) === 'ignore') {
                if (in_array($this->settings['type'] ?? null, ['url', 'object'], true)) {
                    return $this->asset;
                }
            }

            $before = new PdfEvent([
                'html' => $this->html,
                'settings' => $this->settings,
                'template' => $this->template,
                'vars' => $this->vars,
                'driver' => (string)($this->settings['driver'] ?? DriverFactory::DRIVER_DOMPDF),
            ]);
            $this->trigger(self::EVENT_BEFORE_RENDER, $before);
            if (!$before->isValid) {
                return false;
            }
            $this->html = $before->html;
            $this->settings = $before->settings;

            $started = microtime(true);
            $driverId = strtolower((string)($this->settings['driver'] ?? DriverFactory::DRIVER_DOMPDF));
            if ($driverId === '') {
                $driverId = DriverFactory::DRIVER_DOMPDF;
            }

            /** @var PdfDriverInterface $driver */
            $driver = DriverFactory::create($driverId);
            $result = $driver->render($this->html, $this->settings);
            $this->applyRenderResult($result);

            $after = new PdfEvent([
                'html' => $this->html,
                'settings' => $this->settings,
                'template' => $this->template,
                'vars' => $this->vars,
                'dompdf' => $this->dompdf,
                'driver' => $this->driver,
                'pdf' => $this->pdfBytes,
            ]);
            $this->trigger(self::EVENT_AFTER_RENDER, $after);
            if (!$after->isValid) {
                return false;
            }

            if ($this->devMode) {
                Plugin::info(sprintf(
                    'Rendered PDF driver=%s type=%s paper=%s/%s in %.3fs',
                    $this->driver ?? $driverId,
                    $this->settings['type'] ?? 'render',
                    $this->settings['defaultPaperSize'] ?? 'A4',
                    $this->settings['defaultPaperOrientation'] ?? 'portrait',
                    microtime(true) - $started
                ));
            }

            $type = $this->settings['type'] ?? 'render';

            return match ($type) {
                'url' => $this->url(),
                'object' => $this->object(),
                'download' => $this->streamResponse(true),
                'string' => $this->pdfBytes,
                'base64' => base64_encode((string)$this->pdfBytes),
                default => $this->streamResponse((bool)($this->settings['forceDownload'] ?? false)),
            };
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            Plugin::error($error);

            if ($this->devMode) {
                throw new Exception($error, 0, $e);
            }

            return false;
        }
    }

    /**
     * Stores driver render output on the service for downstream type handlers.
     *
     * @param RenderResult $result Driver render result.
     * @return void
     */
    private function applyRenderResult(RenderResult $result): void
    {
        $this->pdfBytes = $result->bytes;
        $this->dompdf = $result->dompdf;
        $this->driver = $result->driver;
    }

    /**
     * Replaces `{TP}` tokens in the rendered DomPDF document with the total page count.
     *
     * @return void
     * @deprecated 5.2.0 Handled inside DompdfDriver; kept for BC when `$dompdf` is set.
     */
    public function injectPageCount(): void
    {
        if ($this->dompdf === null) {
            return;
        }

        $canvas = $this->dompdf->getCanvas();
        $pdf = $canvas->get_cpdf();
        $totalPages = $canvas->get_page_count();
        $paddedTotal = str_pad((string)$totalPages, 3, ' ', STR_PAD_LEFT);

        foreach ($pdf->objects as &$o) {
            if (($o['t'] ?? null) === 'contents') {
                $o['c'] = str_replace('{TP}', $paddedTotal, $o['c']);
            }
        }
        unset($o);
    }

    /**
     * Applies CPDF encryption and permission flags from settings.
     *
     * @return void
     * @deprecated 5.2.0 Handled inside DompdfDriver; kept for BC when `$dompdf` is set.
     */
    public function injectEncryption(): void
    {
        if ($this->dompdf === null) {
            return;
        }

        $allow = [];
        if (!empty($this->settings['print'])) {
            $allow[] = 'print';
        }
        if (!empty($this->settings['modify'])) {
            $allow[] = 'modify';
        }
        if (!empty($this->settings['copy'])) {
            $allow[] = 'copy';
        }
        if (!empty($this->settings['add'])) {
            $allow[] = 'add';
        }

        $this->dompdf->getCanvas()->get_cpdf()->setEncryption(
            (string)($this->settings['password'] ?? ''),
            (string)($this->settings['adminPassword'] ?? ''),
            $allow
        );
    }

    /**
     * Stream a PDF to the browser and stop the request.
     *
     * Must not use Craft::$app->end() while a Twig template response is being
     * sent — Craft throws ExitException and the browser gets a blank HTML page.
     * Dompdf stream() + exit() matches pre-5.1 Twig behaviour for the DomPDF driver.
     */
    private function streamResponse(bool $attachment): mixed
    {
        $filename = pathinfo(($this->settings['filename'] ?? 'pdf') . '.pdf', PATHINFO_FILENAME);
        $bytes = (string)$this->pdfBytes;

        // Console / queue callers cannot stream a browser response
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return $bytes;
        }

        // Prefer Dompdf stream when available (preserves compress option behaviour)
        if ($this->dompdf !== null) {
            while (ob_get_level() > 0) {
                @ob_end_clean();
            }

            $this->dompdf->stream($filename, [
                'Attachment' => $attachment || !empty($this->settings['forceDownload']),
                'compress' => !empty($this->settings['compress']),
            ]);
            exit();
        }

        while (ob_get_level() > 0) {
            @ob_end_clean();
        }

        $disposition = ($attachment || !empty($this->settings['forceDownload']))
            ? 'attachment'
            : 'inline';
        $safeName = str_replace(['"', "\r", "\n"], '', $filename) . '.pdf';

        header('Content-Type: application/pdf');
        header('Content-Disposition: ' . $disposition . '; filename="' . $safeName . '"');
        header('Content-Length: ' . strlen($bytes));
        echo $bytes;
        exit();
    }

    /**
     * Streams an existing storage PDF inline.
     *
     * @param string $filename PDF basename.
     * @return Response File response.
     */
    public function renderFileFromStorage(string $filename = ''): Response
    {
        if ($filename === '') {
            $error = Plugin::t('Filename field cannot be empty');
            Plugin::error($error);
            throw new Exception($error);
        }

        $filename = $this->sanitizeFilename($filename);
        $this->path = $this->getStoragePath() . '/' . $filename;

        if (!$this->isPathInsideStorage($this->path) || !file_exists($this->path)) {
            $error = Plugin::t('PDF File does not exists.');
            Plugin::error($error);
            throw new Exception($error);
        }

        return Craft::$app->getResponse()->sendFile($this->path, null, ['inline' => true]);
    }

    /**
     * Streams the current Craft asset PDF inline.
     *
     * @return Response Stream response.
     */
    public function renderFileFromAssetsElement(): Response
    {
        return Craft::$app->getResponse()
            ->sendStreamAsFile($this->asset->asset->getStream(), $this->asset->asset->filename, [
                'fileSize' => $this->asset->asset->size,
                'mimeType' => $this->asset->asset->getMimeType(),
                'inline' => true,
            ]);
    }

    /**
     * Deprecated `type: url` handler; delegates to {@see object()}.
     *
     * @return mixed Asset wrapper.
     */
    private function url(): mixed
    {
        Craft::$app->getDeprecator()->log(
            __METHOD__,
            'PDF Settings `type: url` setting has been deprecated. Use `type: object` instead.'
        );

        return $this->object();
    }

    /**
     * Persists the PDF to storage or a volume and returns an asset wrapper.
     *
     * @return mixed|PdfAsset|false
     */
    private function object(): mixed
    {
        if ($this->asset != '' && ($this->settings['resaveBehaviour'] ?? null) === 'duplicate') {
            $this->settings['filename'] = $this->settings['filename'] . '_' . random_int(9999, 99999);
        }

        $filename = ($this->settings['filename'] ?? 'pdf') . '.pdf';
        $this->path = $this->getStoragePath() . '/' . $filename;

        $beforeSave = new PdfEvent([
            'html' => $this->html,
            'settings' => $this->settings,
            'template' => $this->template,
            'vars' => $this->vars,
            'dompdf' => $this->dompdf,
            'driver' => $this->driver,
            'pdf' => $this->pdfBytes,
            'path' => $this->path,
        ]);
        $this->trigger(self::EVENT_BEFORE_SAVE, $beforeSave);
        if (!$beforeSave->isValid) {
            return false;
        }
        $this->settings = $beforeSave->settings;
        if (!empty($beforeSave->path)) {
            $this->path = $beforeSave->path;
        }

        FileHelper::writeToFile($this->path, (string)$this->pdfBytes);

        if (($this->settings['volume'] ?? 'storage') === 'storage') {
            $result = $this->createAssetObject();
            $this->trigger(self::EVENT_AFTER_SAVE, $this->buildAfterSaveEvent($result));
            return $result;
        }

        $assetsService = Craft::$app->getAssets();
        $folder = $this->getFolder();
        $asset = Asset::find()
            ->filename($filename)
            ->volumeId($folder->volumeId)
            ->folderId($folder->id)
            ->one();

        if ($asset) {
            $filename = Assets::prepareAssetName($filename);
            $assetsService->replaceAssetFile($asset, $this->path, $filename);

            $result = $this->createAssetObject($asset);
            $this->trigger(self::EVENT_AFTER_SAVE, $this->buildAfterSaveEvent($result));
            return $result;
        }

        $asset = new Asset();
        $asset->tempFilePath = $this->path;
        $asset->filename = $filename;
        $asset->newLocation = "{folder:{$folder->id}}{$filename}";
        $asset->folderId = $folder->id;
        $asset->volumeId = $folder->volumeId;
        $asset->avoidFilenameConflicts = true;
        $asset->setScenario(Asset::SCENARIO_CREATE);

        if (!Craft::$app->getElements()->saveElement($asset)) {
            if (!empty($asset->getErrors())) {
                throw new Exception($this->formatFirstError($asset->getErrors()));
            }

            throw new Exception('We are not able to save the file at the moment. Make sure you have write permission to the folder');
        }

        $result = $this->createAssetObject($asset);
        $this->trigger(self::EVENT_AFTER_SAVE, $this->buildAfterSaveEvent($result));
        return $result;
    }

    /**
     * Builds an after-save event payload for the current generation.
     *
     * @param mixed $result Saved asset wrapper.
     * @return PdfEvent
     */
    private function buildAfterSaveEvent(mixed $result): PdfEvent
    {
        return new PdfEvent([
            'html' => $this->html,
            'settings' => $this->settings,
            'template' => $this->template,
            'vars' => $this->vars,
            'dompdf' => $this->dompdf,
            'driver' => $this->driver,
            'pdf' => $this->pdfBytes,
            'path' => $this->path,
            'result' => $result,
        ]);
    }

    /**
     * Loads an existing PDF for ignore/resave behaviour when present.
     *
     * @return mixed|PdfAsset|null
     */
    private function fetchFile(): mixed
    {
        $filename = ($this->settings['filename'] ?? 'pdf') . '.pdf';

        if (($this->settings['volume'] ?? 'storage') === 'storage') {
            $this->path = $this->getStoragePath() . '/' . $filename;

            if (file_exists($this->path)) {
                return $this->createAssetObject();
            }

            return null;
        }

        $folder = $this->getFolder();
        $asset = Asset::find()
            ->filename($filename)
            ->volumeId($folder->volumeId)
            ->folderId($folder->id)
            ->one();

        if ($asset) {
            return $this->createAssetObject($asset);
        }

        return null;
    }

    /**
     * Builds the Super PDF asset wrapper for a storage file or Craft asset.
     *
     * @param Asset|null $asset Craft asset when stored in a volume.
     * @return PdfAsset|null
     */
    private function createAssetObject(?Asset $asset = null): ?PdfAsset
    {
        $this->asset = new PdfAsset();

        if ($asset) {
            $this->asset->filename = $asset->filename;
            $this->asset->kind = $asset->kind;
            $this->asset->size = $asset->size;
            $this->asset->dateModified = $asset->dateModified;
            $this->asset->path = $asset->getPath();
            $this->asset->url = $asset->getUrl();
            $this->asset->asset = $asset;

            return $this->asset;
        }

        $lastUpdated = @filemtime($this->path);
        $this->asset->filename = ($this->settings['filename'] ?? 'pdf') . '.pdf';
        $this->asset->kind = 'pdf';
        $this->asset->size = @filesize($this->path);
        $this->asset->dateModified = ($lastUpdated ? DateTimeHelper::toDateTime($lastUpdated) : null);
        $this->asset->path = $this->path;
        $this->asset->url = $this->getSignedUrl($this->asset->filename);
        $this->asset->asset = null;

        return $this->asset;
    }

    /**
     * Builds a signed front-end URL for a storage PDF.
     *
     * @param string $filename PDF basename.
     * @param int|null $expires Unix expiry timestamp; null uses `signedUrlExpiry`.
     * @return string Absolute site URL with `e` and `s` query params.
     */
    /**
     * Generates a PDF and returns mailer-friendly attachment data.
     *
     * Accepts either HTML (`$html`) or a Twig `$template` (pass one of them).
     *
     * @param string|null $html HTML markup when not using a template.
     * @param string|null $template Twig template path when not using HTML.
     * @param array $settings Per-call settings overrides.
     * @param array $vars Template variables when using `$template`.
     * @return array{path: string, content: string, filename: string, mimeType: string}|false
     */
    public function asEmailAttachment(?string $html = null, ?string $template = null, array $settings = [], array $vars = []): array|false
    {
        $settings = array_merge($settings, ['type' => 'string']);

        if ($template !== null && $template !== '') {
            $content = $this->template($template, $settings, $vars);
        } elseif ($html !== null) {
            $content = $this->html($html, $settings);
        } else {
            throw new Exception('asEmailAttachment requires $html or $template.');
        }

        if ($content === false || !is_string($content)) {
            return false;
        }

        $filename = ($this->settings['filename'] ?? 'pdf') . '.pdf';
        $tempPath = FileHelper::normalizePath(Craft::$app->getPath()->getTempPath() . '/super-pdf');
        FileHelper::createDirectory($tempPath);
        $path = $tempPath . '/' . $this->sanitizeFilename($filename);
        FileHelper::writeToFile($path, $content);

        return [
            'path' => $path,
            'content' => $content,
            'filename' => $this->sanitizeFilename($filename),
            'mimeType' => 'application/pdf',
        ];
    }

    public function getSignedUrl(string $filename, ?int $expires = null): string
    {
        $filename = $this->sanitizeFilename($filename);
        $defaultExpiry = (int)($this->settings['signedUrlExpiry'] ?? 3600);
        if ($defaultExpiry < 60) {
            $defaultExpiry = 3600;
        }
        $expiry = $expires ?? (time() + $defaultExpiry);
        $signature = $this->createSignature($filename, $expiry);

        return rtrim(UrlHelper::siteUrl(), '/') . '/super-pdf/' . rawurlencode($filename)
            . '?e=' . $expiry
            . '&s=' . rawurlencode($signature);
    }

    /**
     * Creates an HMAC signature for a storage PDF filename and expiry.
     *
     * @param string $filename PDF basename.
     * @param int $expires Unix expiry timestamp.
     * @return string Hex-encoded HMAC-SHA256 digest.
     */
    public function createSignature(string $filename, int $expires): string
    {
        $filename = $this->sanitizeFilename($filename);
        $key = Craft::$app->getConfig()->getGeneral()->securityKey;

        return hash_hmac('sha256', $filename . '|' . $expires, $key);
    }

    /**
     * Validates a signed storage PDF URL.
     *
     * @param string $filename PDF basename.
     * @param int|string|null $expires Expiry from the query string.
     * @param string|null $signature Signature from the query string.
     * @return bool Whether the signature is valid and not expired.
     */
    public function validateSignature(string $filename, int|string|null $expires, ?string $signature): bool
    {
        if ($expires === null || $signature === null || $signature === '') {
            return false;
        }

        $expires = (int)$expires;
        if ($expires < time()) {
            return false;
        }

        $filename = $this->sanitizeFilename($filename);
        $expected = $this->createSignature($filename, $expires);

        return hash_equals($expected, $signature);
    }

    /**
     * Returns a safe PDF basename (no path traversal; ensures `.pdf` suffix).
     *
     * @param string $filename Raw filename from settings or the request.
     * @return string Sanitized basename.
     */
    public function sanitizeFilename(string $filename): string
    {
        $filename = basename(str_replace(['\\', "\0"], '/', $filename));
        if (!str_ends_with(strtolower($filename), '.pdf')) {
            $filename .= '.pdf';
        }

        return $filename;
    }

    /**
     * Returns whether a filesystem path is inside the Super PDF storage root.
     *
     * @param string $path Absolute path to check.
     * @return bool
     */
    public function isPathInsideStorage(string $path): bool
    {
        $storage = FileHelper::normalizePath($this->getStoragePath());
        $realStorage = realpath($storage) ?: $storage;
        $realPath = realpath($path);

        if ($realPath === false) {
            $normalized = FileHelper::normalizePath($path);
            return str_starts_with($normalized, $realStorage);
        }

        return str_starts_with(FileHelper::normalizePath($realPath), FileHelper::normalizePath($realStorage));
    }

    /**
     * Returns the storage directory for the current settings (including subfolder).
     *
     * @return string Absolute filesystem path.
     */
    public function getStoragePath(): string
    {
        $path = Plugin::$plugin->general->getStoragePath();
        $path = rtrim($path, '/');

        if (($this->settings['folder'] ?? '') === '') {
            return $path;
        }

        $path = $path . '/' . $this->settings['folder'];
        $path = FileHelper::normalizePath($path);
        FileHelper::createDirectory($path);

        return rtrim($path, '/');
    }

    /**
     * Returns the DomPDF font cache directory, creating it when missing.
     *
     * @return string Absolute filesystem path.
     */
    public function getFontCachePath(): string
    {
        $settings = Plugin::$plugin->getSettings();
        $path = $settings->fontCache ?: (Craft::$app->getPath()->getStoragePath() . '/super-pdf/font-cache');
        $path = FileHelper::normalizePath($path);
        FileHelper::createDirectory($path);

        return $path;
    }

    /**
     * Resolves the Craft asset folder for volume storage.
     *
     * @return mixed Asset folder model.
     */
    private function getFolder()
    {
        if ($this->folder) {
            return $this->folder;
        }

        $volume = Craft::$app->getVolumes()->getVolumeByHandle((string)$this->settings['volume']);

        if (!$volume) {
            throw new Exception("Volume \"{$this->settings['volume']}\" not found or no longer exists.");
        }

        $assetsService = Craft::$app->getAssets();
        if (($this->settings['folder'] ?? '') === '') {
            $this->folder = $assetsService->getRootFolderByVolumeId($volume->id);

            return $this->folder;
        }

        $this->folder = $assetsService->findFolder([
            'volumeId' => $volume->id,
            'path' => $this->settings['folder'] . '/',
        ]);

        if (!$this->folder) {
            $this->folder = $assetsService->ensureFolderByFullPathAndVolume($this->settings['folder'], $volume);
        }

        return $this->folder;
    }

    /**
     * Returns the first Craft element validation error as a readable string.
     *
     * @param array $errors Element errors keyed by attribute.
     * @return string|null
     */
    private function formatFirstError(array $errors): ?string
    {
        foreach ($errors as $key => $value) {
            return $key . ': ' . $value[0];
        }

        return null;
    }
}
