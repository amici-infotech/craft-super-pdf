<?php
namespace amici\SuperPdf\variables;

use amici\SuperPdf\SuperPdf;

class SuperPdfVariables
{

    function getSettings($value = '')
    {

        if($value == "")
        {
            return "";
        }

        $data = SuperPdf::$plugin->general->getSettings($value);
        return $data;

    }

    public function html($html = "", $settings = [])
    {
    	return SuperPdf::$plugin->pdf->html($html, $settings);
    }

    public function template($template = "", $settings = [], $vars = [])
    {
        return SuperPdf::$plugin->pdf->template($template, $settings, $vars);
    }

}