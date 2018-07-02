<?php

namespace ReportingBundle\Utils\Computers;

use ReportingBundle\Utils\Model\IndicatorInterface;

interface ComputerInterface
 {
     public function compute(IndicatorInterface $indicator, array $filters = []);
 }