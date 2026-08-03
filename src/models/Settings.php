<?php
/**
 * Super PDF plugin for Craft CMS 4.x / 5.x
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperPdf\models;

use Craft;
use craft\base\Model;
use craft\helpers\FileHelper;

/**
 * Plugin and DomPDF settings model.
 *
 * @author    Amici Infotech
 * @package   SuperPdf
 * @since     5.1.0
 */
class Settings extends Model
{
    // Output / response
    // =========================================================================

    /**
     * @var bool Force attachment download when streaming.
     */
    public bool $forceDownload = false;

    /**
     * @var bool Whether DomPDF output compression is enabled.
     */
    public bool $compress = true;

    /**
     * @var string|null Output filename without `.pdf`.
     */
    public ?string $filename = 'pdf';

    /**
     * @var string|null Output mode: `render`, `object`, `url`, `download`, `string`, or `base64`.
     */
    public ?string $type = 'render';

    /**
     * @var int Signed storage URL lifetime in seconds.
     */
    public int $signedUrlExpiry = 3600;

    /**
     * @var bool|string|int When true, unsigned `/super-pdf/{file}` links are rejected.
     */
    public bool|string|int $requireSignedUrls = false;

    // DomPDF paths / options
    // =========================================================================

    /**
     * @var string|null DomPDF root directory.
     */
    public ?string $rootDir = null;

    /**
     * @var string|null Temporary files directory.
     */
    public ?string $tempDir = null;

    /**
     * @var string|null Font files directory.
     */
    public ?string $fontDir = null;

    /**
     * @var string|null Font metrics cache directory.
     */
    public ?string $fontCache = null;

    /**
     * @var string|array|null DomPDF chroot path(s).
     */
    public string|array|null $chroot = null;

    /**
     * @var string|null DomPDF log output file.
     */
    public ?string $logOutputFile = null;

    /**
     * @var string|null Default CSS media type.
     */
    public ?string $defaultMediaType = 'screen';

    /**
     * @var string|null Default paper size (e.g. `A4`).
     */
    public ?string $defaultPaperSize = 'A4';

    /**
     * @var string|null Default paper orientation.
     */
    public ?string $defaultPaperOrientation = 'portrait';

    /**
     * @var string|null DomPDF fallback font family.
     */
    public ?string $defaultFont = 'arial';

    /**
     * @var int|null Render DPI.
     */
    public ?int $dpi = 96;

    /**
     * @var float|null Font height ratio.
     */
    public ?float $fontHeightRatio = 1.1;

    /**
     * @var bool Whether inline PHP in HTML is enabled.
     */
    public bool $isPhpEnabled = false;

    /**
     * @var bool Whether remote CSS/images are allowed.
     */
    public bool $isRemoteEnabled = true;

    /**
     * @var bool Whether DomPDF JavaScript support is enabled.
     */
    public bool $isJavascriptEnabled = false;

    /**
     * @var bool Whether the HTML5 parser is enabled.
     */
    public bool $isHtml5ParserEnabled = true;

    /**
     * @var bool Whether font subsetting is enabled.
     */
    public bool $isFontSubsettingEnabled = true;

    /**
     * @var bool DomPDF debug flag.
     */
    public bool $debugPng = false;

    /**
     * @var bool DomPDF debug flag.
     */
    public bool $debugKeepTemp = false;

    /**
     * @var bool DomPDF debug flag.
     */
    public bool $debugCss = false;

    /**
     * @var bool DomPDF debug flag.
     */
    public bool $debugLayout = false;

    /**
     * @var bool DomPDF debug flag.
     */
    public bool $debugLayoutLines = true;

    /**
     * @var bool DomPDF debug flag.
     */
    public bool $debugLayoutBlocks = true;

    /**
     * @var bool DomPDF debug flag.
     */
    public bool $debugLayoutInline = true;

    /**
     * @var bool DomPDF debug flag.
     */
    public bool $debugLayoutPaddingBox = true;

    /**
     * @var string|null PDF backend (`CPDF`, `PDFLib`, …).
     */
    public ?string $pdfBackend = 'CPDF';

    /**
     * @var string|null PDFLib license key.
     */
    public ?string $pdflibLicense = '';

    /**
     * @var array|null Allowed remote hosts for DomPDF 3+.
     */
    public ?array $allowedRemoteHosts = null;

    /**
     * @var array|null HTTP context options for remote assets.
     */
    public ?array $httpContext = [];

    /**
     * @var array|null Deprecated stream context; use `$httpContext`.
     * @deprecated 5.1.0 Use `$httpContext`.
     */
    public ?array $streamContext = [];

    // Encryption
    // =========================================================================

    /**
     * @var bool Whether the PDF should be encrypted.
     */
    public bool $encrypt = false;

    /**
     * @var string|null User password.
     */
    public ?string $password = 'password';

    /**
     * @var string|null Owner / admin password.
     */
    public ?string $adminPassword = '';

    /**
     * @var bool Allow printing when encrypted.
     */
    public bool $print = false;

    /**
     * @var bool Allow modifying when encrypted.
     */
    public bool $modify = false;

    /**
     * @var bool Allow copying when encrypted.
     */
    public bool $copy = false;

    /**
     * @var bool Allow adding annotations when encrypted.
     */
    public bool $add = false;

    // Chrome
    // =========================================================================

    /**
     * @var string|null Twig template path for a fixed header.
     */
    public ?string $headerTemplate = null;

    /**
     * @var string|null Twig template path for a fixed footer.
     */
    public ?string $footerTemplate = null;

    /**
     * @var string|null Raw HTML for a fixed header.
     */
    public ?string $headerHtml = null;

    /**
     * @var string|null Raw HTML for a fixed footer.
     */
    public ?string $footerHtml = null;

    // General / CP
    // =========================================================================

    /**
     * @var string Plugin display name in the CP.
     */
    public string $pluginName = 'Super PDF';

    /**
     * @var bool|string|int Whether the plugin appears in the CP sidebar.
     */
    public bool|string|int $hasCpSection = false;

    /**
     * @var string Storage target: `storage` or a volume handle.
     */
    public string $volume = 'storage';

    /**
     * @var string Optional subfolder under storage / volume.
     */
    public string $folder = '';

    /**
     * @var string Behaviour when a file already exists: `duplicate`, `override`, or `ignore`.
     */
    public string $resaveBehaviour = 'duplicate';

    /**
     * @var string Twig template used by the entry Download PDF action.
     */
    public string $entryPdfTemplate = '';

    // Public Methods
    // =========================================================================

    /**
     * Applies path defaults and coerces legacy project-config boolean values.
     *
     * @return void
     */
    public function init(): void
    {
        parent::init();

        // Coerce legacy empty strings from project config
        if (!is_bool($this->hasCpSection)) {
            $this->hasCpSection = filter_var($this->hasCpSection, FILTER_VALIDATE_BOOLEAN);
        }
        if (!is_bool($this->requireSignedUrls)) {
            $this->requireSignedUrls = filter_var($this->requireSignedUrls, FILTER_VALIDATE_BOOLEAN);
        }

        $storage = Craft::$app->getPath()->getStoragePath() . '/super-pdf';
        $fonts = FileHelper::normalizePath($storage . '/fonts');
        $fontCache = FileHelper::normalizePath($storage . '/font-cache');
        $temp = FileHelper::normalizePath($storage . '/temp');

        if ($this->rootDir === null || $this->rootDir === '') {
            $this->rootDir = FileHelper::normalizePath(CRAFT_VENDOR_PATH . '/dompdf/dompdf');
        }

        if ($this->fontDir === null || $this->fontDir === '') {
            $this->fontDir = $fonts;
        }

        if ($this->fontCache === null || $this->fontCache === '') {
            $this->fontCache = $fontCache;
        }

        if ($this->tempDir === null || $this->tempDir === '') {
            $this->tempDir = $temp;
        }

        if ($this->chroot === null || $this->chroot === '') {
            $this->chroot = [
                FileHelper::normalizePath(CRAFT_BASE_PATH),
                FileHelper::normalizePath($storage),
            ];
        }
    }

    /**
     * Returns Control Panel settings navigation items.
     *
     * @return array<string, array<string, string>>
     */
    public function getSettingsNavItems(): array
    {
        return [
            'local' => [
                'label' => Craft::t('super-pdf', 'General Settings'),
                'url' => 'super-pdf',
                'action' => 'super-pdf/settings/save-general-settings',
                'redirect' => 'super-pdf/settings',
                'selected' => 'local',
                'template' => 'super-pdf/_templates/general',
            ],
        ];
    }

    /**
     * Defines validation rules for settings.
     *
     * @return array
     */
    protected function defineRules(): array
    {
        return [
            [['pluginName', 'volume', 'resaveBehaviour'], 'required'],
            [['signedUrlExpiry'], 'integer', 'min' => 60],
            [['type'], 'in', 'range' => ['render', 'object', 'url', 'download', 'string', 'base64']],
            [['resaveBehaviour'], 'in', 'range' => ['duplicate', 'override', 'ignore']],
        ];
    }
}
