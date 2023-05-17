<?php
namespace amici\SuperPdf\libraries;

use Craft;
use amici\SuperPdf\SuperPdf;
use amici\SuperPdf\models\Asset as PdfAsset;

use craft\helpers\DateTimeHelper;
use craft\helpers\FileHelper;
use craft\helpers\UrlHelper;
use craft\helpers\Assets;
use craft\elements\Asset;
use craft\elements\User;

use yii\base\Exception;
use Dompdf\Dompdf;

class Pdf
{
	public $asset;

	public string $html = "";
	public $settings = [];
	public Dompdf $dompdf;
	public bool $devMode = false;
	public $folder;
	public $path;

	function __construct()
	{
		$this->devMode = Craft::$app->getConfig()->getGeneral()->devMode;
		$this->settings = SuperPdf::$plugin->getSettings()->getAttributes();;
	}

	public function html($html = "", $settings = [])
	{
		$this->asset = null;
        $this->dompdf = null;

		foreach ($settings as $key => $value)
		{
			$this->settings[$key] = $value;
		}
		$this->fetchFile();

		$this->html = $html;

		return $this->_generate();

	}

	public function template($template = "", $settings = [], $vars = [])
	{
		$this->asset = null;
        $this->dompdf = null;

		foreach ($settings as $key => $value)
		{
			$this->settings[$key] = $value;
		}

		$this->fetchFile();

		if(
			! $this->asset ||
			$this->settings['resaveBehaviour'] != 'ignore' ||
			! in_array($this->settings['type'], ['url', 'object'])
		) {
			$this->html = Craft::$app->getView()->renderPageTemplate($template, $vars);
		}

		return $this->_generate();
	}

	private function _generate()
	{

		try
		{
			if($this->asset && $this->settings['resaveBehaviour'] == 'ignore')
			{
				// Type URL is deprecated
				if(in_array($this->settings['type'], ['url', 'object']))
				{
					return $this->asset;
				}

				// Code works but not need to
				/*if($this->settings['volume'] == "storage") {
					return $this->renderFileFromStorage($this->settings['filename'] . '.pdf');
				}

				return $this->renderFileFromAssetsElement();*/
			}

			$this->dompdf = new Dompdf($this->settings);
			$this->dompdf->setPaper($this->settings['defaultPaperSize'], $this->settings['defaultPaperOrientation']);

			if(! empty($this->settings['httpContext']))
			{
				$this->dompdf->setHttpContext($this->settings['httpContext']);
			}
			elseif(! empty($this->settings['streamContext']))
			{
				Craft::$app->getDeprecator()->log('streamContext', "PDF Settings `streamContext` is deprecated. Use `httpContext` instead.");
				$this->dompdf->setHttpContext(stream_context_create($this->settings['streamContext']));
			}

			$this->dompdf->loadHtml($this->html);
			$this->dompdf->render();

			if($this->settings['encrypt'])
			{
				$allow = [];
				if($this->settings['print'])  $allow[] = 'print';
				if($this->settings['modify']) $allow[] = 'modify';
				if($this->settings['copy'])   $allow[] = 'copy';
				if($this->settings['add']) 	  $allow[] = 'add';

				$this->dompdf->getCanvas()->get_cpdf()->setEncryption($this->settings['password'], $this->settings['adminPassword'], $allow);
			}

			if($this->settings['type'] == 'url')
			{
				return $this->url();
			}

			if($this->settings['type'] == 'object')
			{
				return $this->object();
			}

			return $this->render();
		}
		catch (Exception $e)
		{
			$error = $e->getMessage();
			SuperPdf::error($error);

			if ($this->devMode)
			{
				throw new Exception($error);
			}
			else
			{
				return false;
			}
		}

	}

	public function renderFileFromStorage($filename = "")
	{
		if($filename == "")
		{
			$error = SuperPdf::t("Filename field cannot be empty");
			SuperPdf::error($error);
			throw new Exception($error);
		}

		$this->path = $this->getStoragePath() . '/' . $filename;
		if(! file_exists($this->path))
		{
			$error = SuperPdf::t("PDF File does not exists.");
			SuperPdf::error($error);
			throw new Exception($error);
		}

		$response = Craft::$app->getResponse();
		return $response->sendFile($this->path, null, ['inline' => true]);
	}

	public function renderFileFromAssetsElement()
	{
		return Craft::$app->getResponse()
	        ->sendStreamAsFile($this->asset->asset->getStream(), $this->asset->asset->filename, [
	            'fileSize' => $this->asset->asset->size,
	            'mimeType' => $this->asset->asset->getMimeType(),
	            'inline' => true
	        ]);
	}

	private function render()
	{

		try
		{
			/*echo "<pre>";
			print_r($this->settings);
			exit();*/
			$options = array(
				'Attachment' => $this->settings['forceDownload'],
				'compress' 	 => $this->settings['compress']
			);

			/*echo "<pre>";
			print_r($options);
			exit();*/

			ob_end_clean();
			$this->dompdf->stream($this->settings['filename'], $options);
			exit();

		}
		catch (Exception $e)
		{

			$error = $e->getMessage();
			SuperPdf::error($error);

			if ($this->devMode)
			{
				throw new Exception($error);
			}
			else
			{
				return false;
			}

		}

	}

	private function url()
	{
		Craft::$app->getDeprecator()->log(__METHOD__, "PDF Settings `type: url` setting has been deprecated. Use `type: object` instead.");
		return $this->object();
	}

	private function object()
	{
		try
		{
			if($this->asset != '' && $this->settings['resaveBehaviour'] == "duplicate") {
				$this->settings['filename'] = $this->settings['filename'] . '_' . rand(9999, 99999);
			}

			$filename = $this->settings['filename'] . '.pdf';

			$this->path = $this->getStoragePath(). '/' . $filename;
			FileHelper::writeToFile($this->path, $this->dompdf->output());
			if($this->settings['volume'] == "storage")
			{
				return $this->createAssetObject();
				// return rtrim(UrlHelper::siteUrl(), '/') . '/super-pdf' . '/' . $filename;
			}

		    $assetsService = Craft::$app->getAssets();
			$folder = $this->getFolder();
			$asset = Asset::find()->filename($filename)->volumeId($folder->volumeId)->folderId($folder->id)->one();
			if($asset)
			{
				$filename = Assets::prepareAssetName($filename);
				$assetsService->replaceAssetFile($asset, $this->path, $filename);

				return $this->createAssetObject($asset);
			}
			else
			{

				$asset = new Asset();
				$asset->tempFilePath = $this->path;
				$asset->filename = $filename;
				// $asset->title = AssetsHelper::filename2Title(pathinfo($filename, PATHINFO_FILENAME));
				// $asset->newFolderId = $folder->id;
				$asset->newLocation = "{folder:{$folder->id}}{$filename}";

				// $asset->uploaderId = "1";
				$asset->folderId = $folder->id;
				$asset->volumeId = $folder->volumeId;
				$asset->avoidFilenameConflicts = true;
				$asset->setScenario(Asset::SCENARIO_CREATE);
				$result = Craft::$app->getElements()->saveElement($asset);

				if(! $result) {
					if(! empty($asset->getErrors())) {
						throw new Exception($this->getFirstError($asset->getErrors()), 1);
					}

					throw new Exception("We are not able to save the file at the moment. Make sure you have write permission to the folder", 1);
				}

				return $this->createAssetObject($asset);
			}

		}
		catch (Exception $e)
		{

			$error = $e->getMessage();
			SuperPdf::error($error);

			if ($this->devMode)
			{
				throw new Exception($error);
			}
			else
			{
				return false;
			}

		}

	}

	private function fetchFile()
	{
		$filename = $this->settings['filename'] . '.pdf';

		if($this->settings['volume'] == 'storage')
		{
			$this->path = $this->getStoragePath() . '/' . $filename;

			if(file_exists($this->path))
			{
				return $this->createAssetObject();
			}

			return null;
		}

		$folder = $this->getFolder();
		$asset = Asset::find()->filename($filename)->volumeId($folder->volumeId)->folderId($folder->id)->one();
		if($asset)
		{
			return $this->createAssetObject($asset);
		}

		return null;
	}

	private function createAssetObject($asset = null)
	{
		$this->asset = new PdfAsset();

		if($asset)
		{
			$this->asset->filename = $asset->filename;
			$this->asset->kind = $asset->kind;
			$this->asset->size = $asset->size;
			$this->asset->dateModified = $asset->dateModified;
			$this->asset->path = $asset->getPath();
			$this->asset->url = $asset->getUrl();
			$this->asset->asset = $asset;

			return $this->asset;
		}
		else
		{
			$lastUpdated = @filemtime($this->path);
			$this->asset = new PdfAsset();

			$this->asset->filename = $this->settings['filename'] . '.pdf';
			$this->asset->kind = "pdf";
			$this->asset->size = @filesize($this->path);
			$this->asset->dateModified = ($lastUpdated ? DateTimeHelper::toDateTime($lastUpdated) : null);
			$this->asset->path = $this->path;
			$this->asset->url = rtrim(UrlHelper::siteUrl(), '/') . '/super-pdf' . '/' . $this->asset->filename;
			$this->asset->asset = null;

			return $this->asset;
		}

		return null;
	}

	public function getStoragePath()
	{
		$path = SuperPdf::$plugin->general->getStoragePath();
		$path = rtrim($path, '/');

		if($this->settings['folder'] == '') {
			return $path;
		}

		$path = $path . '/' . $this->settings['folder'];
		$path = FileHelper::normalizePath($path);
		FileHelper::createDirectory($path);

		return rtrim($path, '/');
	}

	private function getFolder()
	{
		if($this->folder) {
			return $this->folder;
		}

		$volume = Craft::$app->getVolumes()->getVolumeByHandle($this->settings['volume']);

		if(! $volume) {
			throw new Exception("Volume \"{$this->settings['volume']}\" not found or no longer exists.", 1);
		}

		$assetsService = Craft::$app->getAssets();
		if($this->settings['folder'] == '')
		{
			$this->folder = $assetsService->getRootFolderByVolumeId($volume->id);
			return $this->folder;
		}

		$this->folder = $assetsService->findFolder([
		    'volumeId'  => $volume->id,
		    'path' 		=> $this->settings['folder'] . '/'
		]);

		if (! $this->folder) {
		    $this->folder = $assetsService->ensureFolderByFullPathAndVolume($this->settings['folder'], $volume);
		}

		return $this->folder;
	}

	private function getFirstError($errors)
	{
		foreach ($errors as $key => $value)
		{
			return $key . ": " . $value[0];

			break;
		}

		return null;
	}

}