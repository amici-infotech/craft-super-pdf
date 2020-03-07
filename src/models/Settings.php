<?php
namespace amici\SuperPdf\models;

use Craft;
use craft\base\Model;

class Settings extends Model
{

    public $forceDownload = false;
    public $compress = true;
    public $rootDir;
    public $tempDir;
    public $fontDir;
    public $fontCache;
    public $chroot;
    public $logOutputFile;
    public $defaultMediaType = "screen";
    public $defaultPaperSize = "A4";
    public $defaultPaperOrientation = "portrait";
    public $defaultFont = "arial";
    public $dpi = 96;
    public $fontHeightRatio = 1.1;
    public $isPhpEnabled = false;
    public $isRemoteEnabled = true;
    public $isJavascriptEnabled = false;
    public $isHtml5ParserEnabled = true;
    public $isFontSubsettingEnabled = true;
    public $debugPng = false;
    public $debugKeepTemp = false;
    public $debugCss = false;
    public $debugLayout = false;
    public $debugLayoutLines = true;
    public $debugLayoutBlocks = true;
    public $debugLayoutInline = true;
    public $debugLayoutPaddingBox = true;
    public $pdfBackend = "CPDF";
    public $pdflibLicense = "";
    public $password = "password";
    public $adminPassword = "";
    public $filename = "pdf";
    public $encrypt = false;
    public $print = false;
    public $modify = false;
    public $copy = false;
    public $add = false;
    public $type = 'render'; // url

}