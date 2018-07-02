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
     * Get total of household by country
     */
    public function BMS_Country_TH(array $filters)
    {
       $qb = $this->reportingCountry->createQueryBuilder('rc')
                                  ->leftjoin('rc.value', 'rv')
                                  ->leftjoin('rc.indicator', 'ri')
                                  ->where('ri.code = :code')
                                    ->setParameter('code', 'BMS_Country_TH')
                                  ->andWhere('rc.country = :country')
                                    ->setParameter('country', $filters['country'])
                                  ->select('rc.country AS name','rv.value AS value', 'rv.unity AS unity', 'rv.creationDate AS date');
        return $qb->getQuery()->getArrayResult();
    }


    /**
     * Get total of beneficiaries by country
     */
    public function BMS_Country_TB(array $filters)
    {
       $qb = $this->reportingCountry->createQueryBuilder('rc')
                                  ->leftjoin('rc.value', 'rv')
                                  ->leftjoin('rc.indicator', 'ri')
                                  ->where('ri.code = :code')
                                    ->setParameter('code', 'BMS_Country_TB')
                                  ->andWhere('rc.country = :country')
                                    ->setParameter('country', $filters['country'])
                                  ->select('rc.country AS name','rv.value AS value', 'rv.unity AS unity', 'rv.creationDate AS date');
        return $qb->getQuery()->getArrayResult();
    }

    /**
     * Get total of active projects by country
     */
    public function BMS_Country_AP(array $filters)
    {
       $qb = $this->reportingCountry->createQueryBuilder('rc')
                                  ->leftjoin('rc.value', 'rv')
                                  ->leftjoin('rc.indicator', 'ri')
                                  ->where('ri.code = :code')
                                    ->setParameter('code', 'BMS_Country_AP')
                                  ->andWhere('rc.country = :country')
                                    ->setParameter('country', $filters['country'])
                                  ->select('rc.country AS name','rv.value AS value', 'rv.unity AS unity', 'rv.creationDate AS date');
        return $qb->getQuery()->getArrayResult();
    }

    /**
     * Get total funding by country
     */
    public function BMS_Country_TF(array $filters)
    {
       $qb = $this->reportingCountry->createQueryBuilder('rc')
                                  ->leftjoin('rc.value', 'rv')
                                  ->leftjoin('rc.indicator', 'ri')
                                  ->where('ri.code = :code')
                                    ->setParameter('code', 'BMS_Country_TF')
                                  ->andWhere('rc.country = :country')
                                    ->setParameter('country', $filters['country'])
                                  ->select('rc.country AS name','rv.value AS value', 'rv.unity AS unity', 'rv.creationDate AS date');
        return $qb->getQuery()->getArrayResult();
    }

    /**
     * Get total of enrolled beneficiaries by country
     */
    public function BMS_Country_EB(array $filters)
    {
       $qb = $this->reportingCountry->createQueryBuilder('rc')
                                  ->leftjoin('rc.value', 'rv')
                                  ->leftjoin('rc.indicator', 'ri')
                                  ->where('ri.code = :code')
                                    ->setParameter('code', 'BMS_Country_EB')
                                  ->andWhere('rc.country = :country')
                                    ->setParameter('country', $filters['country'])
                                  ->select('rc.country AS name','rv.value AS value', 'rv.unity AS unity', 'rv.creationDate AS date');
        return $qb->getQuery()->getArrayResult();
    }

    /**
     * Get total number of distributions by country
     */
    public function BMS_Country_TND(array $filters)
    {
       $qb = $this->reportingCountry->createQueryBuilder('rc')
                                  ->leftjoin('rc.value', 'rv')
                                  ->leftjoin('rc.indicator', 'ri')
                                  ->where('ri.code = :code')
                                    ->setParameter('code', 'BMS_Country_TND')
                                  ->andWhere('rc.country = :country')
                                    ->setParameter('country', $filters['country'])
                                  ->select('rc.country AS name','rv.value AS value', 'rv.unity AS unity', 'rv.creationDate AS date');
        return $qb->getQuery()->getArrayResult();
    }



}