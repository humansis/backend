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

    /**
     * Use to make join and where in DQL
     * Use in all project data retrievers
     */
    public function getReportingValue(string $code, array $filters) {
      $qb = $this->reportingCountry->createQueryBuilder('rc')
                                   ->leftjoin('rc.value', 'rv')
                                   ->leftjoin('rc.indicator', 'ri')
                                   ->where('ri.code = :code')
                                      ->setParameter('code', $code)
                                   ->andWhere('rc.country = :country')
                                      ->setParameter('country', $filters['country']);
      return $qb;
  }


    /**
     * Get total of household by country
     */
    public function BMS_Country_TH(array $filters)
    {
      $qb = $this->getReportingValue('BMS_Country_TH', $filters);
      $qb ->select('rc.country AS name','rv.value AS value', 'rv.unity AS unity', "DATE_FORMAT(rv.creationDate, '%Y-%m-%d') AS date");
      return $qb->getQuery()->getArrayResult();
    }

    /**
     * Get total of active projects by country
     */
    public function BMS_Country_AP(array $filters)
    {
      $qb = $this->getReportingValue('BMS_Country_AP', $filters);
      $qb ->select('rc.country AS name','rv.value AS value', 'rv.unity AS unity', "DATE_FORMAT(rv.creationDate, '%Y-%m-%d') AS date");
      return $qb->getQuery()->getArrayResult();
    }

    /**
     * Get total funding by country
     */
    public function BMS_Country_TF(array $filters)
    {
      $qb = $this->getReportingValue('BMS_Country_TF', $filters);
      $qb ->select('rc.country AS name','rv.value AS value', 'rv.unity AS unity', "DATE_FORMAT(rv.creationDate, '%Y-%m-%d') AS date");
      return $qb->getQuery()->getArrayResult();
    }

    /**
     * Get total of enrolled beneficiaries by country
     */
    public function BMS_Country_EB(array $filters)
    {
      $qb = $this->getReportingValue('BMS_Country_EB', $filters);
      $qb ->select('rc.country AS name','rv.value AS value', 'rv.unity AS unity', "DATE_FORMAT(rv.creationDate, '%Y-%m-%d') AS date");
      return $qb->getQuery()->getArrayResult();
    }

    /**
     * Get total number of distributions by country
     */
    public function BMS_Country_TND(array $filters)
    {
      $qb = $this->getReportingValue('BMS_Country_TND', $filters);
      $qb ->select('rc.country AS name','rv.value AS value', 'rv.unity AS unity', "DATE_FORMAT(rv.creationDate, '%Y-%m-%d') AS date");
      return $qb->getQuery()->getArrayResult();
    }


    /**
     * Get total transactions completed
     */
    public function BMS_Country_TTC(array $filters)
    {
      $qb = $this->getReportingValue('BMS_Country_TTC', $filters);
      $qb ->select('rc.country AS name','rv.value AS value', 'rv.unity AS unity', "DATE_FORMAT(rv.creationDate, '%Y-%m-%d') AS date");
      return $qb->getQuery()->getArrayResult();
    }



}