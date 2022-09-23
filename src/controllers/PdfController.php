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
		return SuperPdf::$plugin->pdf->renderFileFromStorage($filename);
	}

}
?>