<?php

namespace ReportingBundle\Utils\DataFillers;

use ReportingBundle\Utils\Model\IndicatorInterface;


interface DataFillersInterface
{
    public function fill(IndicatorInterface $indicator );
}