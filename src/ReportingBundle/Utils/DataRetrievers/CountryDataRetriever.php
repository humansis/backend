<?php

namespace ReportingBundle\Utils\DataRetrievers;

use Doctrine\ORM\EntityManager;

use Doctrine\ORM\QueryBuilder;
use ReportingBundle\Entity\ReportingCountry;

/**
 * Class CountryDataRetrievers
 * @package ReportingBundle\Utils\DataRetrievers
 */
class CountryDataRetriever extends AbstractDataRetriever
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * CountryDataRetrievers constructor.
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * Use to make join and where in DQL
     * Use in all project data retrievers
     * @param string $code
     * @param array $filters
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getReportingValue(string $code, array $filters)
    {
        $qb = $this->em->createQueryBuilder()
                        ->from(ReportingCountry::class, 'rc')
                        ->leftjoin('rc.value', 'rv')
                        ->leftjoin('rc.indicator', 'ri')
                        ->where('ri.code = :code')
                        ->setParameter('code', $code)
                        ->andWhere('rc.country = :country')
                        ->setParameter('country', $filters['country']);

        return $qb;
    }

    /**
     * switch case to use the right select
     * each case is the name of the function to execute
     *
     * Indicators with the same 'select' statement are grouped in the same case
     * @param $qb
     * @return QueryBuilder
     */
    public function conditionSelect($qb)
    {
        $qb = $qb->select('rc.country AS name')
                 ->groupBy('name');

        return $qb;
    }

    /**
     * Get total of household by country
     * @param array $filters
     * @return mixed
     */
    public function BMS_Country_TH(array $filters)
    {
        $qb = $this->getReportingValue('BMS_Country_TH', $filters);
        $qb = $this->conditionSelect($qb);
        $result = $this->formatByFrequency($qb, $filters['frequency'], $filters['period']);
        return $result;
    }

    /**
     * Get total of active projects by country
     * @param array $filters
     * @return mixed
     */
    public function BMS_Country_AP(array $filters)
    {
        $qb = $this->getReportingValue('BMS_Country_AP', $filters);
        $qb = $this->conditionSelect($qb);
        $result = $this->formatByFrequency($qb, $filters['frequency'], $filters['period']);
        return $result;
    }

    /**
     * Get total of enrolled beneficiaries by country
     * @param array $filters
     * @return mixed
     */
    public function BMS_Country_EB(array $filters)
    {
        $qb = $this->getReportingValue('BMS_Country_EB', $filters);
        $qb = $this->conditionSelect($qb);
        $result = $this->formatByFrequency($qb, $filters['frequency'], $filters['period']);
        return $result;
    }

    /**
     * Get total number of distributions by country
     * @param array $filters
     * @return mixed
     */
    public function BMS_Country_TND(array $filters)
    {
        $qb = $this->getReportingValue('BMS_Country_TND', $filters);
        $qb = $this->conditionSelect($qb);
        $result = $this->formatByFrequency($qb, $filters['frequency'], $filters['period']);
        return $result;
    }

    /**
     * Get total transactions completed
     * @param array $filters
     * @return mixed
     */
    public function BMS_Country_TTC(array $filters)
    {
        $qb = $this->getReportingValue('BMS_Country_TTC', $filters);
        $qb = $this->conditionSelect($qb);
        $result = $this->formatByFrequency($qb, $filters['frequency'], $filters['period']);
        return $result;
    }
}
