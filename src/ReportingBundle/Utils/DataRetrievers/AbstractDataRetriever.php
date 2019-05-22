<?php

namespace ReportingBundle\Utils\DataRetrievers;

use Doctrine\ORM\EntityManager;

use ReportingBundle\Entity\ReportingDistribution;
use \ProjectBundle\Entity\Project;
use \DistributionBundle\Entity\DistributionData;

/**
 * Class DistributionDataRetrievers
 * @package ReportingBundle\Utils\DataRetrievers
 */
abstract class AbstractDataRetriever
{

    /**
     * Use to verify if a key project exist in filter
     * If this key exists, it means a project was selected in selector
     * @param $qb
     * @param array $projects
     * @return mixed
     */
    public function filterByProjects($qb, array $projects)
    {
        if ($projects !== '') {
            $qb->andWhere('p.id IN (:projects)')
                ->setParameter('projects', $projects);
        }

        return $qb;
    }

    /**
     * Use to verify if a key distribution exist in filter
     * If this key exists, it means a distribution was selected in selector
     * @param $qb
     * @param array $distributions
     * @return mixed
     */
    public function filterByDistributions($qb, array $distributions)
    {
        if ($distributions !== '') {
            $qb->andWhere('d.id IN (:distributions)')
                ->setParameter('distributions', $distributions);
        }

        return $qb;
    }

    /**
     * sort data by frequency
     * take the query like parameter and according to the frequency filters
     * make action to return data corresponding to this frequency
     * @param $qb
     * @param string $frequency
     * @return mixed
     */
    public function formatByFrequency($qb, string $frequency)
    {
        if (!$frequency) {
            $frequency = "Month";
        }

        $result = array();

        if ($frequency === "Month") {
            $qb ->addSelect('AVG(rv.value) AS value', 'rv.unity AS unity', "MONTH(DATE_FORMAT(rv.creationDate, '%Y-%m-%d')) AS date")
                ->addGroupBy('unity', 'date');
            $result = $qb->getQuery()->getArrayResult();
        } elseif ($frequency === "Year") {
            $qb ->addSelect('AVG(rv.value) AS value', 'rv.unity AS unity', "YEAR(DATE_FORMAT(rv.creationDate, '%Y-%m-%d')) AS date")
                ->addGroupBy('unity', 'date');
            $result = $qb->getQuery()->getArrayResult();
        } elseif ($frequency === "Quarter") {
            $qb ->addSelect('AVG(rv.value) AS value', 'rv.unity AS unity', "QUARTER(DATE_FORMAT(rv.creationDate, '%Y-%m-%d')) AS date")
                ->addGroupBy('unity', 'date');
            $result = $this->getNameQuarter($qb->getQuery()->getArrayResult());
        }

        return $result;
    }

    /**
     * filter data by period
     * @param $qb
     * @param array $period
     * @return mixed
     */
    public function filterByPeriod($qb, array $period) {
        // TODO check date formats
        if (!empty($period)) {
            $qb ->andWhere("DATE_FORMAT(rv.creationDate, '%m/%d/%Y')  >= :from")
                ->setParameter('from', $period[0])
                ->andWhere("DATE_FORMAT(rv.creationDate, '%m/%d/%Y')  <= :to")
                ->setParameter('to', $period[1]);
        }

        return $qb;
    }

    /**
     * get the name of month which delimits the quarter
     * @param $results
     * @return mixed
     */
    public function getNameQuarter($results)
    {
        foreach ($results as &$result) {
            if ($result['date'] === "1") {
                $result["date"] = "Jan-Mar";
            } elseif ($result['date'] === "2") {
                $result["date"] = "Apr-Jun";
            } elseif ($result['date'] === "3") {
                $result["date"] = "Jul-Sep";
            } else {
                $result["date"] = "Oct-Dec";
            }
        }
        return $results;
    }
}
