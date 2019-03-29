<?php

namespace ReportingBundle\Utils\DataFillers;

use ReportingBundle\Utils\Model\IndicatorInterface;

/**
 * Interface DataFillersInterface
 * @package ReportingBundle\Utils\DataFillers
 */
interface DataFillersInterface
{
    public function fill(IndicatorInterface $indicator);
}
