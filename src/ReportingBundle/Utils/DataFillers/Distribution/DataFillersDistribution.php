<?php

namespace ReportingBundle\Utils\DataFillers\Distribution;

use ReportingBundle\Utils\DataFillers\DataFillerInterface;
use ReportingBundle\Utils\Model\IndicatorInterface;

use Doctrine\ORM\EntityManager;

class DataFillersDistribution 
{

    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;   
    }


}
