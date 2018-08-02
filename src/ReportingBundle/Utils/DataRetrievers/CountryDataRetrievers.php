<?php

namespace ReportingBundle\Utils\DataRetrievers;

use ReportingBundle\Utils\DataRetrievers\DataRetrieverInterface;

use Doctrine\ORM\EntityManager;

use ReportingBundle\Entity\ReportingCountry;

class CountryDataRetrievers
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
   * sort data by frequency
   */
  public function getByFrequency($qb, array $filters) {
    if ($filters['frequency'] === "Month") {
      $qb ->select('rc.country AS name','rv.value AS value', 'rv.unity AS unity', "DATE_FORMAT(rv.creationDate, '%Y-%m-%d') AS date");
      $result = $qb->getQuery()->getArrayResult();
    }
    else if($filters['frequency'] === "Year") {
      $qb ->select('rc.country AS name','MAX(rv.value) AS value', 'rv.unity AS unity', "DATE_FORMAT(rv.creationDate, '%Y') AS date")
          ->groupBy('name', 'unity', 'date');
      $result = $qb->getQuery()->getArrayResult();
    } 
    else if($filters['frequency'] === "Quarter") {
      $qb ->select('rc.country AS name','MAX(rv.value) AS value', 'rv.unity AS unity', "QUARTER(DATE_FORMAT(rv.creationDate, '%Y-%m-%d')) AS date")
          ->groupBy('name', 'unity', 'date');
      $byQuarter = $qb->getQuery()->getArrayResult();
      $result = $this->getNameQuarter($byQuarter);
    } 
    else {
      $period = explode('-', $filters['frequency']); 
      $qb ->andWhere("DATE_FORMAT(rv.creationDate, '%m/%d/%Y')  >= :from")
              ->setParameter('from', $period[0])
          ->andWhere("DATE_FORMAT(rv.creationDate, '%m/%d/%Y')  <= :to")
              ->setParameter('to', $period[1])
          ->select('rc.country AS name','rv.value AS value', 'rv.unity AS unity', "DATE_FORMAT(rv.creationDate, '%Y-%m-%d') AS date");
      $result = $qb->getQuery()->getArrayResult();
    }
    
    return $result;
  }

  /**
   * get the name of month which delimit the quarter
   */
  public function getNameQuarter($results) {
    foreach($results as &$result) {
        if ($result['date'] === "1") {
          $result["date"] = "Jan-Mar";
        } else if ($result['date'] === "2") {
          $result["date"] = "Apr-Jun";
        } else if ($result['date'] === "3") {
          $result["date"] = "Jul-Sep";
        } else {
          $result["date"] = "Oct-Dec";
        }
    }
    return $results;
  }


  /**
   * Get total of household by country
   */
  public function BMS_Country_TH(array $filters)
  {
    $qb = $this->getReportingValue('BMS_Country_TH', $filters);
    $result = $this->getByFrequency($qb, $filters);
    return $result;
  }

  /**
   * Get total of active projects by country
   */
  public function BMS_Country_AP(array $filters)
  {
    $qb = $this->getReportingValue('BMS_Country_AP', $filters);
    $qb ->select('rc.country AS name','rv.value AS value', 'rv.unity AS unity', "DATE_FORMAT(rv.creationDate, '%Y-%m-%d') AS date");
    $result = $this->getByFrequency($qb, $filters);
    return $result;
  }

  /**
   * Get total funding by country
   */
  public function BMS_Country_TF(array $filters)
  {
    $qb = $this->getReportingValue('BMS_Country_TF', $filters);
    $qb ->select('rc.country AS name','rv.value AS value', 'rv.unity AS unity', "DATE_FORMAT(rv.creationDate, '%Y-%m-%d') AS date");
    $result = $this->getByFrequency($qb, $filters);
    return $result;
  }

  /**
   * Get total of enrolled beneficiaries by country
   */
  public function BMS_Country_EB(array $filters)
  {
    $qb = $this->getReportingValue('BMS_Country_EB', $filters);
    $qb ->select('rc.country AS name','rv.value AS value', 'rv.unity AS unity', "DATE_FORMAT(rv.creationDate, '%Y-%m-%d') AS date");
    $result = $this->getByFrequency($qb, $filters);
    return $result;
  }

  /**
   * Get total number of distributions by country
   */
  public function BMS_Country_TND(array $filters)
  {
    $qb = $this->getReportingValue('BMS_Country_TND', $filters);
    $qb ->select('rc.country AS name','rv.value AS value', 'rv.unity AS unity', "DATE_FORMAT(rv.creationDate, '%Y-%m-%d') AS date");
    $result = $this->getByFrequency($qb, $filters);
    return $result;
  }


  /**
   * Get total transactions completed
   */
  public function BMS_Country_TTC(array $filters)
  {
    $qb = $this->getReportingValue('BMS_Country_TTC', $filters);
    $qb ->select('rc.country AS name','rv.value AS value', 'rv.unity AS unity', "DATE_FORMAT(rv.creationDate, '%Y-%m-%d') AS date");
    $result = $this->getByFrequency($qb, $filters);
    return $result;
  }



}