<?php

namespace ReportingBundle\Utils\Formatters;

interface FormatterInterface 
{
    public function format($dataComputed, $typeGraph);
}