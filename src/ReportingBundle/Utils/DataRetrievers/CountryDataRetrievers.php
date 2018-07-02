<?php

namespace ReportingBundle\Utils\DataRetrievers;

use ReportingBundle\Utils\DataRetrievers\DataRetrieverInterface;

use Doctrine\ORM\EntityManager;

use ReportingBundle\Entity\ReportingCountry;

class CountryDataRetrievers implements DataRetrieverInterface
{
    private $em;
    private $reportingCountry;

    public function __construct(EntityManager $em)
    {
        $this->em = $em; 
        $this->reportingCountry = $em->getRepository(ReportingCountry::class);
    }



}