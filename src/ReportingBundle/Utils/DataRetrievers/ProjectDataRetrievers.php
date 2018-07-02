<?php

namespace ReportingBundle\Utils\DataRetrievers;

use ReportingBundle\Utils\DataRetrievers\DataRetrieverInterface;

use Doctrine\ORM\EntityManager;

class ProjectDataRetrievers implements DataRetrieverInterface
{
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;   
    }
}