<?php

namespace ReportingBundle\Utils\DataFillers\Country;

use ReportingBundle\Utils\DataFillers\DataFillerInterface;
use ReportingBundle\Utils\Model\IndicatorInterface;

use Doctrine\ORM\EntityManager;

class DataFillersCountry
{

    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;   
    }


}
