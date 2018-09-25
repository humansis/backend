<?php

namespace ReportingBundle\Utils\Formatters;

/**
 * Interface FormatterInterface
 * @package ReportingBundle\Utils\Formatters
 */
interface FormatterInterface
{
    /**
     * @param $type
     * @param $dataComputed
     * @param $typeGraph
     * @return mixed
     */
    public function format($type, $dataComputed, $typeGraph);
}