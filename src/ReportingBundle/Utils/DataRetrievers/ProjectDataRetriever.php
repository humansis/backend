<?php

namespace ReportingBundle\Utils\DataRetrievers;

use Doctrine\ORM\EntityManager;

use Doctrine\ORM\QueryBuilder;
use NewApiBundle\Entity\Project;
use ReportingBundle\Entity\ReportingProject;

/**
 * Class ProjectDataRetriever
 * @package ReportingBundle\Utils\DataRetrievers
 */
class ProjectDataRetriever extends AbstractDataRetriever
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * ProjectDataRetriever constructor.
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
     * @return \Doctrine\ORM\QueryBuilder|mixed
     */
    public function getReportingValue(string $code, array $filters)
    {
        $qb = $this->em->createQueryBuilder()
                        ->from(ReportingProject::class, 'rp')
                        ->leftjoin('rp.value', 'rv')
                        ->leftjoin('rp.indicator', 'ri')
                        ->leftjoin('rp.project', 'p')
                        ->where('ri.code = :code')
                        ->setParameter('code', $code)
                        ->andWhere('p.iso3 = :country')
                        ->setParameter('country', $filters['country']);

        $qb = $this->filterByProjects($qb, $filters['projects']);

        return $qb;
    }

    /**
     * switch case to use the right select
     * each case is the name of the function to execute
     *
     * Indicators with the same 'select' statement are grouped in the same case
     * @param $qb
     * @param $nameFunction
     * @param $frequency
     * @return mixed
     */
    public function conditionSelect(QueryBuilder $qb, $nameFunction)
    {
        switch ($nameFunction) {
            case 'BMS_Project_HS':
            case 'BMS_Project_D':
                $qb->select('p.name AS name')
                    ->groupBy('name');
                break;
            case 'BMS_Project_BR':
                $qb->select('p.name AS name', 'p.target AS target')
                    ->groupBy('name', 'target');
                break;
        }

        return $qb;
    }

    /**
     * Get the name of all donors
     * @param array $filters
     * @return array
     */
    public function BMS_Project_D(array $filters)
    {
        $qb = $this->getReportingValue('BMS_Project_D', $filters);
        $qb = $this->conditionSelect($qb, 'BMS_Project_D');

        $result = $this->formatByFrequency($qb, $filters['frequency'], $filters['period']);
        $formattedResult = [];
        // Count number of donor occurrences for each project for each period
        foreach ($result as $period => $periodResult) {
            $donorsProjectCount = [];
            // Count number of donor occurrences for each project
            foreach ($periodResult as $projectResult) {
                if (!array_key_exists($projectResult['unity'], $donorsProjectCount)) {
                    $donorsProjectCount[$projectResult['unity']] = [
                        'value' => 0,
                        'unity' => 'projects',
                        'name'  => $projectResult['unity']
                    ];
                }
                $donorsProjectCount[$projectResult['unity']]['value']++;
            }
            // Format results (remove donor keys)
            foreach ($donorsProjectCount as $donorProjectCount) {
                $formattedResult[$period][] = $donorProjectCount;
            }

        }
        return $formattedResult;
    }

    /**
     * Get the number of household served
     * @param array $filters
     * @return array
     */
    public function BMS_Project_HS(array $filters)
    {
        $qb = $this->getReportingValue('BMS_Project_HS', $filters);
        $qb = $this->conditionSelect($qb, 'BMS_Project_HS');
        $result = $this->formatByFrequency($qb, $filters['frequency'], $filters['period']);
        return $result;
    }

    /**
     * Get the beneficiaries age
     * @param array $filters
     * @return array
     */
    public function BMS_Project_AB(array $filters)
    {
        $qb = $this->getReportingValue('BMS_Project_AB', $filters);
        $qb = $this->conditionSelect($qb, 'BMS_Project_AB');
        $result = $this->formatByFrequency($qb, $filters['frequency'], $filters['period']);
        return $result;
    }

    /**
     * Get the number of men and women in a project
     * @param array $filters
     * @return array
     */
    public function BMS_Project_NMW(array $filters)
    {
        $men = $this->BMSU_Project_NM($filters);
        $women = $this->BMSU_Project_NW($filters);
        $menAndWomen = [];

        foreach(array_unique(array_merge(array_keys($men), array_keys($women))) as $period) {
            if(array_key_exists($period, $men)) {
                $menAndWomen[$period][] = $men[$period][0];
            }
            if(array_key_exists($period, $women)) {
                $menAndWomen[$period][] = $women[$period][0];
            }
        }
        return $menAndWomen;
    }

    /**
     * Get the percentage of vulnerabilities served
     * @param array $filters
     * @return array
     */
    //TODO: group this with the total amount of vulnerabilities
    public function BMS_Project_PVS(array $filters)
    {
        $vulnerabilitiesServedPerVulnerability = $this->BMSU_Project_TVSV($filters);
        return $this->pieValuesToPieValuePercentage($vulnerabilitiesServedPerVulnerability);
    }


    /**
     * Utils indicators
     */


    /**
     * Get the number of men
     * @param array $filters
     * @return mixed
     */
    public function BMSU_Project_NM(array $filters)
    {

        $qb = $this->getReportingValue('BMSU_Project_NM', $filters);
        $qb = $this->conditionSelect($qb, 'BMSU_Project_NM');
        $result = $this->formatByFrequency($qb, $filters['frequency'], $filters['period']);
        return $result;
    }

    /**
     * Get the number of women
     * @param array $filters
     * @return mixed
     */
    public function BMSU_Project_NW(array $filters)
    {
        $qb = $this->getReportingValue('BMSU_Project_NW', $filters);
        $qb = $this->conditionSelect($qb, 'BMSU_Project_NW');
        $result = $this->formatByFrequency($qb, $filters['frequency'], $filters['period']);
        return $result;
    }

    /**
     * Get the total of vulnerabilities served by vulnerabilities
     * @param array $filters
     * @return mixed
     */
    public function BMSU_Project_TVSV(array $filters)
    {
        $qb = $this->getReportingValue('BMSU_Project_TVSV', $filters);
        $qb = $this->conditionSelect($qb, 'BMSU_Project_TVSV');
        $result = $this->formatByFrequency($qb, $filters['frequency'], $filters['period']);

        return $result;
    }

    /**
     * Get the total of vulnerabilities served
     * @param array $filters
     * @return mixed
     */
    public function BMSU_Project_TVS(array $filters)
    {
        $qb = $this->getReportingValue('BMSU_Project_TVS', $filters);
        $qb = $this->conditionSelect($qb, 'BMSU_Project_TVS');
        $result = $this->formatByFrequency($qb, $filters['frequency'], $filters['period']);
        return $result;
    }

    /**
     * Get the percentage of target beneficiaries compared to actually served for project
     * @param array $filters
     * @return mixed
     */
    public function BMS_Project_BR(array $filters)
    {
        $qb = $this->getReportingValue('BMS_Project_BR', $filters);
        $qb = $this->conditionSelect($qb, 'BMS_Project_BR');
        $result = $this->formatByFrequency($qb, $filters['frequency'], $filters['period']);
        return $result;

    }
}
