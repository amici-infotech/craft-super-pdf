<?php
namespace amici\SuperPdf\models;

use Craft;
use craft\base\Model;

class Settings extends Model
{

    public bool $forceDownload = false;
    public bool $compress = true;
    public $rootDir;
    public $tempDir;
    public $fontDir;
    public $fontCache;
    public $chroot;
    public $logOutputFile;
    public ?string $defaultMediaType = "screen";
    public ?string $defaultPaperSize = "A4";
    public ?string $defaultPaperOrientation = "portrait";
    public ?string $defaultFont = "arial";
    public ?int $dpi = 96;
    public ?float $fontHeightRatio = 1.1;
    public bool $isPhpEnabled = false;
    public bool $isRemoteEnabled = true;
    public bool $isJavascriptEnabled = false;
    public bool $isHtml5ParserEnabled = true;
    public bool $isFontSubsettingEnabled = true;
    public bool $debugPng = false;
    public bool $debugKeepTemp = false;
    public bool $debugCss = false;
    public bool $debugLayout = false;
    public bool $debugLayoutLines = true;
    public bool $debugLayoutBlocks = true;
    public bool $debugLayoutInline = true;
    public bool $debugLayoutPaddingBox = true;
    public ?string $pdfBackend = "CPDF";
    public ?string $pdflibLicense = "";
    public ?string $password = "password";
    public ?string $adminPassword = "";
    public ?string $filename = "pdf";
    public bool $encrypt = false;
    public bool $print = false;
    public bool $modify = false;
    public bool $copy = false;
    public bool $add = false;
    public ?string $type = 'render'; // url
    public ?array $streamContext = [];
    public ?array $httpContext = [];

    // General Settings
    public $pluginName = "Super PDF";
    public $hasCpSection = false;
    public $volume = "storage";
    public $folder = "";
    public $resaveBehaviour = "duplicate";

    public function getSettingsNavItems(): array
    {
        $ret = [];
        if(Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            $ret +=  [
                'local' => [
                    'label'     => Craft::t('super-pdf', 'General Settings'),
                    'url'       => 'super-pdf',
                    'action'    => 'super-pdf/settings/save-general-settings',
                    'redirect'  => 'super-pdf/settings',
                    'selected'  => 'local',
                    'template'  => 'super-pdf/_templates/general'
                ]
            ];
        }

        /*$ret +=  [
            'truncate' => [
                'label'     => Craft::t('super-pdf', 'Truncate'),
                'url'       => 'super-pdf/truncate',
                'action'    => 'super-pdf/settings/truncate-queue',
                'redirect'  => 'super-pdf/truncate',
                'selected'  => 'truncate',
                'template'  => 'super-pdf/_templates/truncate'
            ],
        ];*/

        return $ret;

    }

    public function rules(): array
    {
        return [
            [['pluginName', 'volume', 'resaveBehaviour'], 'required'],
        ];
    }

}