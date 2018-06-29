<?php

namespace ReportingBundle\Utils\DataFillers;

use ReportingBundle\Utils\DataFillers\DataFillerInterface;
use ReportingBundle\Utils\Model\IndicatorInterface;

use Doctrine\ORM\EntityManager;

class DataFillersCountry implements DataFillerInterface
{

    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;   
    }

    public function fill(IndicatorInterface $indicator)
    {

    }

}
