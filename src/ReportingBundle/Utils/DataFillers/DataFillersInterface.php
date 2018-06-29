<?php

namespace ReportingBundle\Utils\DataFillers;

use ReportingBundle\Utils\Model\IndicatorInterface;


interface DataFillerInterface
{
    public function fill(IndicatorInterface $indicator );
}