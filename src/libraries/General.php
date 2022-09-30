<?php
namespace amici\SuperPdf\libraries;

use Craft;
use craft\helpers\FileHelper;
use craft\elements\Asset;

use amici\SuperPdf\SuperPdf;

class General
{

	public $plugin;

	function __construct()
	{
		$this->plugin = SuperPdf::$plugin;
	}

	function getSettings($key = "")
	{

		$settings = $this->plugin->getSettings();

		if($key == "")
		{
			return (array) $settings;
		}
		else
		{
			return isset($settings->$key) ? $settings->$key : "";
		}

	}

	function getStoragePath()
	{

		$path = Craft::$app->getPath()->getStoragePath();
		$path = rtrim($path, '/') . '/super-pdf';
		$path = FileHelper::normalizePath($path);
		FileHelper::createDirectory($path);

		return $path;

	}

	public function getSourceOptions(): array
	{
	    $sourceOptions = [];
	    $sourceOptions[] = [
            'label' => "Craft Storage (Default)",
            'value' => "storage",
        ];

	    foreach (Asset::sources('settings') as $key => $volume) {
	        if (!isset($volume['heading'])) {
	        	$sourceOptions[] = [
	                'label' => $volume['label'],
	                'value' => $volume['data']['volume-handle'],
	            ];
	        }
	    }

	    return $sourceOptions;
	}
}