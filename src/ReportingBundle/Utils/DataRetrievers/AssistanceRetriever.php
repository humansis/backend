<?php

namespace ReportingBundle\Utils\DataRetrievers;

use Doctrine\ORM\EntityManager;

use ReportingBundle\Entity\ReportingAssistance;
use \ProjectBundle\Entity\Project;

/**
 * Class AssistanceRetrievers
 * @package ReportingBundle\Utils\DataRetrievers
 */
class AssistanceRetriever extends AbstractDataRetriever
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var ProjectDataRetriever
     */
    private $project;

    /**
     * AssistanceRetrievers constructor.
     * @param EntityManager $em
     * @param ProjectDataRetriever $project
     */
    public function __construct(EntityManager $em, ProjectDataRetriever $project)
    {
        $this->em = $em;
        $this->project = $project;
    }

    /**
     * Use to make join and where in DQL
     * Use in all distribution data retrievers
     * @param string $code
     * @param array $filters
     * @return \Doctrine\ORM\QueryBuilder|mixed
     */
    public function getReportingValue(string $code, array $filters)
    {
        $qb = $this->em->createQueryBuilder()
                        ->from(ReportingAssistance::class, 'rd')
                        ->leftjoin('rd.value', 'rv')
                        ->leftjoin('rd.indicator', 'ri')
                        ->leftjoin('rd.distribution', 'd')
                        ->leftjoin('d.project', 'p')
                        ->where('ri.code = :code')
                        ->setParameter('code', $code)
                        ->andWhere('p.iso3 = :country')
                        ->setParameter('country', $filters['country']);


        $qb = $this->filterByProjects($qb, $filters['projects']);
        $qb = $this->filterByDistributions($qb, $filters['distributions']);

        return $qb;
    }

    /**
     * switch case to use the right select
     * each case is the name of the function to execute
     *
     * Indicator with the same 'select' statement is grouped in the same case
     * @param $qb
     * @param $nameFunction
     * @return mixed
     */
    public function conditionSelect($qb, $nameFunction)
    {
        switch ($nameFunction) {
            case 'BMS_Distribution_NEB':
                $qb ->select('d.name AS name')
                    ->groupBy('name');
                break;
            case 'BMS_Distribution_TDV':
                $qb ->select('DISTINCT(d.name) AS name', 'd.id AS id')
                    ->groupBy('name', 'id');
                break;
            case 'BMSU_Distribution_NM':
            case 'BMSU_Distribution_NW':
                $qb ->select("CONCAT(rv.unity, '/', d.name) AS name")
                    ->groupBy('name');
                break;
            case 'BMS_Distribution_M':
                $qb ->select('DISTINCT(d.name) AS name')
                    ->groupBy('name');
                break;
        }

        return $qb;
    }

    /**
     * Get the number of enrolled beneficiaries in a distribution
     * @param array $filters
     * @return array
     */
    public function BMS_Distribution_NEB(array $filters)
    {
        $qb = $this->getReportingValue('BMS_Distribution_NEB', $filters);
        $qb = $this->conditionSelect($qb, 'BMS_Distribution_NEB');
        $result = $this->formatByFrequency($qb, $filters['frequency'], $filters['period']);
        return $result;
    }

    /**
     * Get the total distribution value in a distribution
     * @param array $filters
     * @return array
     */
    public function BMS_Distribution_TDV(array $filters)
    {
        $qb = $this->getReportingValue('BMS_Distribution_TDV', $filters);
        $qb = $this->conditionSelect($qb, 'BMS_Distribution_TDV');
        $result = $this->formatByFrequency($qb, $filters['frequency'], $filters['period']);
        return $result;

    }

    /**
     * Get the modality(and it type) for a distribution
     * @param array $filters
     * @return array
     */
    public function BMS_Distribution_M(array $filters)
    {
        $qb = $this->getReportingValue('BMS_Distribution_M', $filters);
        $qb = $this->conditionSelect($qb, 'BMS_Distribution_M');
        $result = $this->formatByFrequency($qb, $filters['frequency'], $filters['period']);
        return $result;
    }

    /**
     * Get the age breakdown in a distribution
     * @param array $filters
     * @return array
     */
    public function BMS_Distribution_AB(array $filters)
    {
        $qb = $this->getReportingValue('BMS_Distribution_AB', $filters);
        $qb = $this->conditionSelect($qb, 'BMS_Distribution_AB');
        $result = $this->formatByFrequency($qb, $filters['frequency'], $filters['period']);
        return $result;
    }

    /**
     * Get the number of men and women in a project
     * @param array $filters
     * @return array
     */
    public function BMS_Distribution_NMW(array $filters)
    {
        $men = $this->BMSU_Distribution_NM($filters);
        $women = $this->BMSU_Distribution_NW($filters);
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
    public function BMS_Distribution_PVS(array $filters)
    {
        return $this->pieValuesToPieValuePercentage($this->BMSU_Distribution_TVS($filters));
    }

    /**
     * Get the percentage of value used in the project by the distribution
     * @param array $filters
     * @return array
     */
    public function BMS_Distribution_BR(array $filters)
    {
        $beneficiariesEnrolled = $this->BMS_Distribution_NEB($filters);

        $projectTarget = $this->em->createQueryBuilder()
            ->from(Project::class, 'p')
            ->where('p.id = :id')
            ->setParameter('id', $filters['projects'][0])
            ->select('p.target')
            ->getQuery()->getSingleScalarResult();

        foreach ($beneficiariesEnrolled as $period => $periodValues) {
            $totalReached = 0;
            foreach ($periodValues as $index => $value) {
                $percentage = $value['value'] / $projectTarget * 100;

                $beneficiariesEnrolled[$period][$index]['unity'] = $value['name'];
                $beneficiariesEnrolled[$period][$index]['value'] = $percentage;

                $totalReached += $percentage;
                unset($beneficiariesEnrolled[$period][$index]['name']);
            }
            $beneficiariesEnrolled[$period][] = [
                'date' => $period,
                'unity' => "Not reached",
                'value' => max(100 - $totalReached, 0)
            ];
        }

        return $beneficiariesEnrolled;
    }


    /**
     * Utils indicators
     */


    /**
     * Get the number of men in a distribution
     * @param array $filters
     * @return mixed
     */
    public function BMSU_Distribution_NM(array $filters)
    {
        $qb = $this->getReportingValue('BMSU_Distribution_NM', $filters);
        $qb = $this->conditionSelect($qb, 'BMSU_Distribution_NM');
        $result = $this->formatByFrequency($qb, $filters['frequency'], $filters['period']);
        return $result;
    }

    /**
     * Get the number of women in a distribution
     * @param array $filters
     * @return mixed
     */
    public function BMSU_Distribution_NW(array $filters)
    {
        $qb = $this->getReportingValue('BMSU_Distribution_NW', $filters);
        $qb = $this->conditionSelect($qb, 'BMSU_Distribution_NW');
        $result = $this->formatByFrequency($qb, $filters['frequency'], $filters['period']);
        return $result;
    }

    /**
     * Get the total of vulnerabilities served
     * @param array $filters
     * @return mixed
     */
    public function BMSU_Distribution_TVS(array $filters)
    {
        $qb = $this->getReportingValue('BMSU_Distribution_TVS', $filters);
        $qb = $this->conditionSelect($qb, 'BMSU_Distribution_TVS');
        $result = $this->formatByFrequency($qb, $filters['frequency'], $filters['period']);
        return $result;
    }

    /**
     * Get the total of vulnerabilities served by vulnerabilities
     * @param array $filters
     * @return mixed
     */
    public function BMSU_Distribution_TVSV(array $filters)
    {
        $qb = $this->getReportingValue('BMSU_Distribution_TVSV', $filters);
        $qb = $this->conditionSelect($qb, 'BMSU_Distribution_TVSV');
        $result = $this->formatByFrequency($qb, $filters['frequency'], $filters['period']);
        return $result;
    }
}
