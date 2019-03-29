<?php

namespace ReportingBundle\Utils\Computers;

use ReportingBundle\Utils\Model\IndicatorInterface;

/**
 * Interface ComputerInterface
 * @package ReportingBundle\Utils\Computers
 */
interface ComputerInterface
{
    public function compute(IndicatorInterface $indicator, array $filters = []);
}
