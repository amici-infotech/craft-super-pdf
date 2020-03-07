<?php
namespace amici\SuperPdf\base;

use Craft;
use yii\log\Logger;

// use amici\SuperPdf\SuperPdf;
use amici\SuperPdf\libraries\General;
use amici\SuperPdf\libraries\Pdf;

trait PluginTrait
{

    public static $plugin;

    private function _setPluginComponents()
    {
        $this->setComponents([
            'general' => General::class,
            'pdf' 	  => Pdf::class,
        ]);
    }

    public static function t($message, array $params = [])
    {
    	return Craft::t('super-pdf', $message, $params);
    }

    public static function log($message, $type = 'info')
    {
    	Craft::$type(self::t($message), __METHOD__);
    }

    public static function info($message)
    {
    	Craft::info(self::t($message), __METHOD__);
    }

    public static function error($message)
    {
    	Craft::error(self::t($message), __METHOD__);
    }

}