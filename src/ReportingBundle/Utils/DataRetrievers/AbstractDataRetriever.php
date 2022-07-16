<?php

namespace ReportingBundle\Utils\DataRetrievers;

use Doctrine\ORM\EntityManager;

use Doctrine\ORM\QueryBuilder;
use phpDocumentor\Reflection\Types\Object_;
use ReportingBundle\Entity\ReportingAssistance;
use NewApiBundle\Entity\Project;
use \DistributionBundle\Entity\Assistance;

/**
 * Class AssistanceRetrievers
 * @package ReportingBundle\Utils\DataRetrievers
 */
abstract class AbstractDataRetriever
{

    private $monthMapper = [
        1 => 'Jan',
        2 => 'Feb',
        3 => 'Mar',
        4 => 'Apr',
        5 => 'May',
        6 => 'Jun',
        7 => 'Jul',
        8 => 'Aug',
        9 => 'Sep',
        10 => 'Oct',
        11 => 'Nov',
        12 => 'Dec'
    ];

    private $quarterMapper = [
        1 => 'Jan-Mar',
        2 => 'Apr-Jun',
        3 => 'Jul-Sep',
        4 => 'Oct-Dec',
    ];

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
     * @param array $periods
     * @return mixed
     */
    public function formatByFrequency(QueryBuilder $qb, string $frequency, array $periods) {
        if (!$frequency) {
            $frequency = "Month";
        }

        if(!empty($periods)) {
            $startDate = \DateTime::createFromFormat('d-m-Y', $periods[0]);
            $endDate = \DateTime::createFromFormat('d-m-Y', $periods[1]);
            $qb ->setParameter('startDate', $startDate)
                ->setParameter('endDate', $endDate)
                ->andwhere("rv.creationDate BETWEEN :startDate AND :endDate");
        }

        $result = array();

        if ($frequency === "Month") {
            $qb ->addSelect('AVG(rv.value) AS value', 'rv.unity AS unity', "MONTH(DATE_FORMAT(rv.creationDate, '%Y-%m-%d')) AS date", "YEAR(DATE_FORMAT(rv.creationDate, '%Y-%m-%d')) AS year")
                ->addGroupBy('unity', 'date', 'year');
            $result = $this->formatMonths($qb->getQuery()->getArrayResult());
        } elseif ($frequency === "Year") {
            $qb ->addSelect('AVG(rv.value) AS value', 'rv.unity AS unity', "YEAR(DATE_FORMAT(rv.creationDate, '%Y-%m-%d')) AS date")
                ->addGroupBy('unity', 'date');
            $result = $qb->getQuery()->getArrayResult();
        } elseif ($frequency === "Quarter") {
            $qb ->addSelect('AVG(rv.value) AS value', 'rv.unity AS unity', "QUARTER(DATE_FORMAT(rv.creationDate, '%Y-%m-%d')) AS date", "YEAR(DATE_FORMAT(rv.creationDate, '%Y-%m-%d')) AS year")
                ->addGroupBy('unity', 'date', 'year');
            $result = $this->formatQuarters($qb->getQuery()->getArrayResult());
        }

        return  $this->splitByPeriod($result);
    }

    /**
     * split values by period
     * @param $values
     * @return mixed
     */
    private function splitByPeriod($values) {
        $splitValues = [];

        foreach ($values as $value ) {
            $splitValues[$value["date"]][] = $value;
        }
        return $splitValues;
    }
    /**
     * get the name of month which delimits the quarter
     * @param $results
     * @return mixed
     */
    private function formatQuarters($results)
    {
        foreach ($results as &$result) {
            $result['date'] = $this->quarterMapper[$result['date']];
        }
        return $this->addYear($results);
    }


    private function formatMonths($results)
    {
        foreach ($results as &$result) {
            $result['date'] = $this->monthMapper[$result['date']];
        }
        return $this->addYear($results);
    }

    private function addYear($results)
    {
        foreach ($results as &$result)
        {
            $result['date'] = $result['date'].' '.$result['year'];
            unset($result['year']);
        }
        return $results;
    }

    protected function pieValuesToPieValuePercentage(Array $periodValues)
    {

        foreach ($periodValues as $period => $periodValue)
        {
            $periodTotalValue = 0;
            foreach ($periodValue as $value)
            {
                $periodTotalValue += $value['value'];
            }
            foreach ($periodValue as $index => $value)
            {
                $periodValues[$period][$index]['value'] = $value['value'] * 100 / $periodTotalValue;
            }
        }
        return $periodValues;
    }
}
