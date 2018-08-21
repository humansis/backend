<?php

namespace ReportingBundle\Utils\Formatters;

interface FormatterInterface 
{
    public function format($type, $dataComputed, $typeGraph);
}