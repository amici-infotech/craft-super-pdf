<?php
namespace amici\SuperPdf\models;

use Craft;
use craft\base\Model;

class Asset extends Model
{
    public $filename;
    public $kind;
    public $size;
    public $dateModified;
    public $path;
    public $url;

    public $asset;

    public function __toString()
    {
        return (string)$this->getUrl();
    }

    public function getFilename()
    {
        return $this->filename;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getAsset()
    {
        return $this->asset;
    }
}