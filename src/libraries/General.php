<?php
namespace amici\SuperPdf\libraries;

use Craft;
use craft\helpers\FileHelper;
use amici\SuperPdf\SuperPdf;
use craft\base\Plugin;

class General
{

	public Plugin $plugin;

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

}