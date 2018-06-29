<?php

namespace ReportingBundle\Utils\DataFillers\Project;

use ReportingBundle\Utils\Model\IndicatorInterface;

use Doctrine\ORM\EntityManager;

class DataFillersProject 
{

    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;   
    }


}
