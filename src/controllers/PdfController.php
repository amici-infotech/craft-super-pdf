<?php
namespace amici\SuperPdf\controllers;

use Craft;
use yii\base\Exception;
use yii\web\Response;
use craft\web\Controller as BaseController;
use amici\SuperPdf\SuperPdf;

class PdfController extends BaseController
{

	protected $allowAnonymous = [
        'index' => self::ALLOW_ANONYMOUS_LIVE
    ];

	public function init()
	{
		parent::init();
	}

	public function actionIndex($filename = "")
	{

		if($filename == "")
		{
			$error = SuperPdf::t("Filename field cannot be empty");
			SuperPdf::error($error);
			throw new Exception($error);
		}

		$path = SuperPdf::$plugin->general->getStoragePath() . '/' . $filename;
		if(! file_exists($path))
		{
			$error = SuperPdf::t("PDF File does not exists.");
			SuperPdf::error($error);
			throw new Exception($error);
		}

		$response = Craft::$app->getResponse();
		return $response->sendFile($path, null, ['inline' => true]);

	}

}
?>