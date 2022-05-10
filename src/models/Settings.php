<?php
namespace amici\SuperPdf\models;

use Craft;
use craft\base\Model;

class Settings extends Model
{

    public bool $forceDownload = false;
    public bool $compress = true;
    public ?string $rootDir;
    public ?string $tempDir;
    public ?string $fontDir;
    public ?string $fontCache;
    public ?string $chroot;
    public ?string $logOutputFile;
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

}