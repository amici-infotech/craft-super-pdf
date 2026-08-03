<?php
/**
 * Super PDF plugin for Craft CMS 4.x / 5.x
 *
 * @link      https://amiciinfotech.com
 * @copyright Copyright (c) 2026 Amici Infotech
 */

namespace amici\SuperPdf\models;

use craft\base\Model;
use craft\elements\Asset as CraftAsset;

/**
 * Wrapper returned when a PDF is saved with `type: object`.
 *
 * String casting returns the URL for Twig convenience.
 *
 * @author    Amici Infotech
 * @package   SuperPdf
 * @since     5.1.0
 *
 * @property-read string|null $filename
 * @property-read string|null $path
 * @property-read string|null $url
 * @property-read CraftAsset|null $asset
 */
class Asset extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var mixed PDF filename including `.pdf`.
     */
    public $filename;

    /**
     * @var mixed Asset kind (`pdf`).
     */
    public $kind;

    /**
     * @var mixed File size in bytes.
     */
    public $size;

    /**
     * @var mixed Last modified date/time.
     */
    public $dateModified;

    /**
     * @var mixed Absolute filesystem path.
     */
    public $path;

    /**
     * @var mixed Public or signed URL.
     */
    public $url;

    /**
     * @var CraftAsset|null Craft asset element when stored in a volume.
     */
    public $asset;

    // Public Methods
    // =========================================================================

    /**
     * Returns the URL when the object is cast to string.
     *
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getUrl();
    }

    /**
     * Returns the PDF filename.
     *
     * @return mixed
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * Returns the filesystem path.
     *
     * @return mixed
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Returns the public or signed URL.
     *
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Returns the Craft asset element when the PDF was saved to a volume.
     *
     * @return CraftAsset|null
     */
    public function getAsset()
    {
        return $this->asset;
    }
}
