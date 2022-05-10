<?php
namespace amici\SuperPdf\base;

use Craft;
use yii\log\Logger;

// use amici\SuperPdf\SuperPdf;
use amici\SuperPdf\libraries\General;
use amici\SuperPdf\libraries\Pdf;

trait PluginTrait
{

    private function _setPluginComponents(): void
    {
        $this->setComponents([
            'general' => General::class,
            'pdf' 	  => Pdf::class,
        ]);
    }

    public static function t($message, array $params = []): ?string
    {
    	return Craft::t('super-pdf', $message, $params);
    }

    public static function log($message, $type = 'info'): void
    {
    	Craft::$type(self::t($message), __METHOD__);
    }

    public static function info($message): void
    {
    	Craft::info(self::t($message), __METHOD__);
    }

    public static function error($message): void
    {
    	Craft::error(self::t($message), __METHOD__);
    }

}