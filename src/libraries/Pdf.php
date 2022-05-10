<?php
namespace amici\SuperPdf\libraries;

use Craft;
use amici\SuperPdf\SuperPdf;
use Dompdf\Dompdf;
use amici\SuperPdf\models\Settings;
use craft\helpers\FileHelper;
use craft\helpers\UrlHelper;
use yii\base\Exception;

class Pdf
{

	public ?string $html = "";
	public Settings $settings;
	public Dompdf $dompdf;
	public bool $devMode = false;

	function __construct()
	{
		$this->devMode = Craft::$app->getConfig()->getGeneral()->devMode;
		$this->settings = SuperPdf::$plugin->getSettings();
	}

	function html($html = "", $settings = [])
	{

		foreach ($settings as $key => $value)
		{
			if(isset($this->settings->{$key})) {
				$this->settings->{$key} = $value;
			}
		}

		$this->html = $html;
		return $this->_generate();

	}

	function template($template = "", $settings = [], $vars = [])
	{

		foreach ($settings as $key => $value)
		{
			if(isset($this->settings->{$key})) {
				$this->settings->{$key} = $value;
			}
		}

		// Craft::$app->getView()->setTemplatesPath(Craft::$app->getPath()->getSiteTemplatesPath());
		$this->html = Craft::$app->getView()->renderTemplate($template, $vars);
		return $this->_generate();

	}

	private function _generate()
	{

		try
		{

			$this->dompdf = new Dompdf($this->settings);
			$this->dompdf->setPaper($this->settings->defaultPaperSize, $this->settings->defaultPaperOrientation);

			if(! empty($this->settings->streamContext))
			{
				$this->dompdf->setHttpContext(stream_context_create($this->settings->streamContext));
			}

			$this->dompdf->loadHtml($this->html);
			$this->dompdf->render();

			if($this->settings->encrypt)
			{

				$allow = [];
				if($this->settings->print) 	$allow[] = 'print';
				if($this->settings->modify) $allow[] = 'modify';
				if($this->settings->copy) 	$allow[] = 'copy';
				if($this->settings->add) 	$allow[] = 'add';

				$this->dompdf->getCanvas()->get_cpdf()->setEncryption($this->settings->password, $this->settings->adminPassword, $allow);

			}

			if($this->settings->type == 'url')
			{
				return $this->url();
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

	function render()
	{

		try
		{

			$options = array(
				'Attachment' => $this->settings->forceDownload,
				'compress' 	 => $this->settings->compress
			);

			ob_end_clean();
			$this->dompdf->stream($this->settings->filename, $options);
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

	function url()
	{

		try
		{
			$filename = $this->settings->filename . '_' . rand(9999, 99999) . '.pdf';
			$path = SuperPdf::$plugin->general->getStoragePath();
			$path = $path . '/' . $filename;
			FileHelper::writeToFile($path, $this->dompdf->output());

			$pdfUrl = rtrim(UrlHelper::siteUrl(), '/') . '/super-pdf' . '/' . $filename;
			// return $path . "<br>" . $pdfUrl;
			return $pdfUrl;
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

}