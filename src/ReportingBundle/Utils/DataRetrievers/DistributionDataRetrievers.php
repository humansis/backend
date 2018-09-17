<?php

namespace ReportingBundle\Utils\DataRetrievers;

use Doctrine\ORM\EntityManager;

use ReportingBundle\Entity\ReportingDistribution;
use \ProjectBundle\Entity\Project;
use \DistributionBundle\Entity\DistributionData;

class DistributionDataRetrievers
{
    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var \Doctrine\Common\Persistence\ObjectRepository|\Doctrine\ORM\EntityRepository
     */
    private $reportingDistribution;
    /**
     * @var ProjectDataRetrievers
     */
    private $project;

    /**
     * DistributionDataRetrievers constructor.
     * @param EntityManager $em
     * @param ProjectDataRetrievers $project
     */
    public function __construct(EntityManager $em, ProjectDataRetrievers $project)
    {
        $this->em = $em;   
        $this->reportingDistribution = $em->getRepository(ReportingDistribution::class);
        $this->project = $project;
    }

    /**
     * Use to verify if a key project exist in filter
     * If this key exists, it means a project was selected in selector
     * In distribtuion mode, only one project could be selected
     * @param $qb
     * @param array $filters
     * @return mixed
     */
    public function ifInProject($qb, array $filters) {
        if(array_key_exists('project', $filters)) {
            $qb->andWhere('p.id IN (:projects)')
                    ->setParameter('projects', $filters['project']);
        }
        $qb = $this->ifInDistribution($qb, $filters);
        return $qb;
    }

    /**
     * Use to verify if a key distribution exist in filter
     * If this key exists, it means a distribution was selected in selector
     * @param $qb
     * @param array $filters
     * @return mixed
     */
    public function ifInDistribution($qb, array $filters) {
        if(array_key_exists('distribution', $filters)) {
            $qb->andWhere('d.id IN (:distributions)')
                    ->setParameter('distributions', $filters['distribution']);
        }
        return $qb;
    }

    /**
     * Use to make join and where in DQL
     * Use in all distribution data retrievers
     * @param string $code
     * @param array $filters
     * @return \Doctrine\ORM\QueryBuilder|mixed
     */
    public function getReportingValue(string $code, array $filters) {
        $qb = $this->reportingDistribution->createQueryBuilder('rd')
                                          ->leftjoin('rd.value', 'rv')
                                          ->leftjoin('rd.indicator', 'ri')
                                          ->leftjoin('rd.distribution', 'd')
                                          ->leftjoin('d.project', 'p')
                                          ->where('ri.code = :code')
                                          ->setParameter('code', $code)
                                          ->andWhere('p.iso3 = :country')
                                          ->setParameter('country', $filters['country'])
                                          ->select('rd.id', 'd.name as Name','rv.value as Value', "DATE_FORMAT(rv.creationDate, '%Y-%m-%d') AS date");

        $qb = $this->ifInProject($qb, $filters);
        return $qb;
    }

    /**
     * Get the data with the more recent values
     * If the frequency is quarter, select the value corresponding to the quarter
     * @param array $values
     * @return array
     */
    public function lastDate(array $values) {
        $moreRecentValues = [];
        $lastDate = $values[0]['date'];
        foreach($values as $value) {
            if ($value['date'] === 'Jan-Mar' || $value['date'] === 'Apr-Jun' || 
                $value['date'] === 'Jul-Sep' || $value['date'] === 'Oct-Dec' ) {
                    $lastDate = $value['date'];
                }
            else if ($value['date'] > $lastDate) {
                $lastDate = $value['date'];
            }
        }
        foreach($values as $value) {
            if ($value['date'] === $lastDate) {
                array_push($moreRecentValues, $value);
            }
        }
        return $moreRecentValues;
    }

    /**
     * Get the number of enrolled beneficiaries in a distribution
     * @param array $filters
     * @return array
     */
    public function BMS_Distribution_NEB(array $filters) {
        $qb = $this->getReportingValue('BMS_Distribution_NEB', $filters);
        //check if the DQL query return a result or not
        if (sizeof($qb->getQuery()->getArrayResult()) > 0) {
            //to filter data with the good frequency
            $frequency = $this->getByFrequency($qb, $filters, 'BMS_Distribution_NEB');
            if(sizeof($frequency) > 0) {
                $result = $this->lastDate($frequency);
                return $result;
            } else { 
                return [];
            }
        } else {
            return $qb->getQuery()->getArrayResult();
        }
        
    }

    /**
     * Get the total distribution value in a distribution
     * @param array $filters
     * @return array
     */
    public function BMS_Distribution_TDV(array $filters) {
        $qb = $this->getReportingValue('BMS_Distribution_TDV', $filters);
        //check if the DQL query return a result or not
        if (sizeof($qb->getQuery()->getArrayResult()) > 0) {
            //to filter data with the good frequency
            $frequency = $this->getByFrequency($qb, $filters, 'BMS_Distribution_TDV');
            if(sizeof($frequency) > 0) {
                $result = $this->lastDate($frequency);
                return $result;
            } else { 
                return [];
            }
        } else {
            return $qb->getQuery()->getArrayResult();
        }
    }

    /**
     * Get the modality(and it type) for a distribution
     * @param array $filters
     * @return array
     */
    public function BMS_Distribution_M(array $filters) {
        $qb = $this->getReportingValue('BMS_Distribution_M', $filters);
        //check if the DQL query return a result or not
        if (sizeof($qb->getQuery()->getArrayResult()) > 0) {
            //to filter data with the good frequency
            $frequency = $this->getByFrequency($qb, $filters, 'BMS_Distribution_M');
            if(sizeof($frequency) > 0) {
                $result = $this->lastDate($frequency);
                return $result;
            } else { 
                return [];
            }
        } else {
            return $qb->getQuery()->getArrayResult();
        }
    }

    /**
     * Get the age breakdown in a distribution
     * @param array $filters
     * @return array
     */
    public function BMS_Distribution_AB(array $filters) {
        $qb = $this->getReportingValue('BMS_Distribution_AB', $filters);
        //check if the DQL query return a result or not
        if (sizeof($qb->getQuery()->getArrayResult()) > 0) {
            //to filter data with the good frequency
            $frequency = $this->getByFrequency($qb, $filters, 'BMS_Distribution_AB');
            if(sizeof($frequency) > 0) {
                $result = $this->lastDate($frequency);
                return $result;
            } else { 
                return [];
            }
        } else {
            return $qb->getQuery()->getArrayResult();
        }
    }

    /**
     * Get the number of men in a distribution
     * @param array $filters
     * @return mixed
     */
    public function BMSU_Distribution_NM(array $filters) {
        $qb = $this->getReportingValue('BMSU_Distribution_NM', $filters);
        //to filter data with the good frequency
        $result = $this->getByFrequency($qb, $filters, 'BMSU_Distribution_NM' );
        return $result; 
    }

    /**
     * Get the number of women in a distribution
     * @param array $filters
     * @return mixed
     */
    public function BMSU_Distribution_NW(array $filters) {
        $qb = $this->getReportingValue('BMSU_Distribution_NW', $filters);
        //to filter data with the good frequency
        $result = $this->getByFrequency($qb, $filters, 'BMSU_Distribution_NW' );
        return $result; 
    }

    /**
     * Get the number of men and women in a project
     * @param array $filters
     * @return array
     */
    public function BMS_Distribution_NMW(array $filters) {

        $menAndWomen = [];
        //call function to get number of men and number of women
        $mens = $this->BMSU_Distribution_NM($filters);
        $womens = $this->BMSU_Distribution_NW($filters);

        //verify if there is no men or no women in the distribution
        if (sizeof($mens) > 0 && sizeof($womens) > 0) {
            //search the more recent date
            $lastDate = $mens[0]['date'];
            foreach($mens as $men) {
                if ($men['date'] > $lastDate) {
                    $lastDate = $men['date'];
                }
            }

            //Search the corresponding data and put them in an array after formatting them 
            foreach ($mens as $men) { 
                if ($men["date"] == $lastDate) {
                    $result = [
                        'name' => $men["name"],
                        'project' => substr($men["name"],4),
                        'value' => $men["value"],
                        'date' => $men['date']
                    ]; 
                    array_push($menAndWomen, $result);
                    foreach ($womens as $women) {

                        if (substr($women["name"],6) == substr($men["name"], 4)) {
                            if ($women["date"] == $lastDate) {
                                $result = [
                                    'name' => $women["name"],
                                    'project' => substr($women["name"],6),
                                    'value' => $women['value'],
                                    'date' => $women['date']
                                ]; 
                                array_push($menAndWomen, $result);
                                break 1;
                            }
                        }  
                    }                
                }   
            }
        }
        else if (sizeof($mens) === 0 && sizeof($womens) > 0) {
            $women = $this->lastDate($womens);
            $result = [
                'name' => $women[0]["name"],
                'project' => substr($women[0]["name"],6),
                'value' => $women[0]['value'],
                'date' => $women[0]['date']
            ]; 
            array_push($menAndWomen, $result);
        } else if (sizeof($womens) === 0 && sizeof($mens) > 0) {
            $men = $this->lastDate($mens);
            $result = [
                'name' => $men[0]["name"],
                'project' => substr($men[0]["name"],4),
                'value' => $men[0]['value'],
                'date' => $men[0]['date']
            ]; 
            array_push($menAndWomen, $result);
        }
        return $menAndWomen; 
    }

    /**
     * Get the total of vulnerabilities served
     * @param array $filters
     * @return mixed
     */
    public function BMSU_Distribution_TVS(array $filters) {
        $qb = $this->getReportingValue('BMSU_Distribution_TVS', $filters);
        //to filter data with the good frequency
        $result = $this->getByFrequency($qb, $filters, 'BMSU_Distribution_TVS' );
        return $result;   
    }

    /**
     * Get the total of vulnerabilities served by vulnerabilities
     * @param array $filters
     * @return mixed
     */
    public function BMSU_Distribution_TVSV(array $filters) {
        $qb = $this->getReportingValue('BMSU_Distribution_TVSV', $filters);
        //to filter data with the good frequency
        $result = $this->getByFrequency($qb, $filters, 'BMSU_Distribution_TVSV' );
        return $result;   
    }

    /**
     * Get the percentage of vulnerabilities served
     * @param array $filters
     * @return array
     */
    public function BMS_Distribution_PVS(array $filters) {
        $vulnerabilitiesPercentage = [];

        //call function to get the total of vulnerability and to get the total by vulnerability
        $totalVulnerabilities = $this->BMSU_Distribution_TVS($filters);
        $totalVulnerabilitiesByVulnerabilities = $this->BMSU_Distribution_TVSV($filters);

        if (sizeof($totalVulnerabilities)> 0 && sizeof($totalVulnerabilitiesByVulnerabilities) > 0) {
            //get the more recent data
            $lastDate = $totalVulnerabilities[0]['date'];
            foreach($totalVulnerabilities as $totalVulnerability) {
                if ($totalVulnerability['date'] > $lastDate) {
                    $lastDate = $totalVulnerability['date'];
                }
            }

            //Search the corresponding data and put them in an array after formatting them 
            foreach ($totalVulnerabilities as $totalVulnerability) { 
                if ($totalVulnerability["date"] == $lastDate) {
                    foreach ($totalVulnerabilitiesByVulnerabilities as $vulnerability) {
                        if ($vulnerability["date"] == $lastDate) {
                            $percent = ($vulnerability["value"]/$totalVulnerability["value"])*100;
                            $result = [
                                'name' => $vulnerability["unity"],
                                'value' => $percent,
                                'date' => $vulnerability['date']
                            ]; 
                            array_push($vulnerabilitiesPercentage, $result);
                        }   
                    }                
                }   
            }
        }
        return $vulnerabilitiesPercentage; 
    }

    /**
     * Get the percent of value used in the project by the distribution
     * @param array $filters
     * @return array
     */
    public function BMS_Distribution_PPV(array $filters) {
        $projectDistributionValue =[];
        $repositoryProject = $this->em->getRepository(Project::class);
        $projectValue = $this->project->BMSU_Project_PV($filters);
        $distributionValue = $this->BMS_Distribution_TDV($filters);
        $TotalDistributionValueUsed = 0;

        //Verify if one of them is empty
        if (sizeof($projectValue) > 0 && sizeof($distributionValue) > 0 ) {
            $moreRecentProject = $this->lastDate($projectValue);
            $moreRecentDistribution = $this->lastDate($distributionValue);

            //Search the corresponding data and put them in an array after formatting them 
            foreach($moreRecentProject as $project) { 
                $findProject = $repositoryProject->findOneBy(['id' => $project['id']]); 
                foreach($moreRecentDistribution as $distribution) {
                    foreach($findProject->getDistributions() as $findDistribution) {
                        if($distribution['id'] ===  $findDistribution->getId()) {

                            $TotalDistributionValueUsed = $TotalDistributionValueUsed + $distribution["value"];
                            $result = [
                                'name' =>$findDistribution->getName(),
                                'value' => (int)$distribution["value"],
                                'date' => $distribution['date']
                            ]; 
                            array_push($projectDistributionValue, $result);
                        }
                    }    
                }

                $valueProjectUsed = $project['value']-$TotalDistributionValueUsed;
                $result = [
                    'name' => 'Available',
                    'value' => $valueProjectUsed,
                    'date' => $project['date']
                ];
                array_push($projectDistributionValue, $result);
            }
        }
        return $projectDistributionValue;
        
    }

    /**
     * sort data by frequency
     * @param $qb
     * @param array $filters
     * @param string $nameFunction
     * @return mixed
     */
    public function getByFrequency($qb, array $filters, string $nameFunction) {
        if ($filters['frequency'] === "Month") {
          $qb ->andWhere("MONTH(rv.creationDate) = MONTH(CURRENT_DATE())");
          $qb = $this->conditionSelect($qb, $nameFunction, 'Month');
          $result = $qb->getQuery()->getArrayResult();
        }
        else if($filters['frequency'] === "Year") {
          $qb ->andWhere("YEAR(rv.creationDate) = YEAR(CURRENT_DATE())");
          $qb = $this->conditionSelect($qb, $nameFunction, 'Year');
          $result = $qb->getQuery()->getArrayResult();
        } 
        else if($filters['frequency'] === "Quarter") {
          $qb ->andWhere("QUARTER(rv.creationDate) = QUARTER(CURRENT_DATE())");
          $qb = $this->conditionSelect($qb, $nameFunction, 'Quarter');
          $byQuarter = $qb->getQuery()->getArrayResult();
          $result = $this->getNameQuarter($byQuarter);
        } 
        else {
            $period = explode('-', $filters['frequency']); 
            $qb ->andWhere("DATE_FORMAT(rv.creationDate, '%m/%d/%Y')  >= :from")
                    ->setParameter('from', $period[0])
                ->andWhere("DATE_FORMAT(rv.creationDate, '%m/%d/%Y')  <= :to")
                    ->setParameter('to', $period[1]);
            $qb = $this->conditionSelect($qb, $nameFunction, 'Period');
            $result = $qb->getQuery()->getArrayResult();
        }
        return $result;
    }

    /**
     * switch case to use the good select
     * each case is the name of the function to execute
     * in the body of each case, if allow to find which frequency is waiting
     *
     * Indicator with the same 'select' statement is grouped in the same case
     * @param $qb
     * @param $nameFunction
     * @param $frequency
     * @return mixed
     */
    public function conditionSelect($qb, $nameFunction, $frequency) {
        switch ($nameFunction) {
            case 'BMS_Distribution_NEB':
                if ($frequency === 'Month' || $frequency === "Period") {
                    $qb ->select('d.name AS name','rv.value AS value', "DATE_FORMAT(rv.creationDate, '%Y-%m-%d') AS date");
                } else if ($frequency === 'Year') {
                    $qb ->select('d.name AS name','MAX(rv.value) AS value', "DATE_FORMAT(rv.creationDate, '%Y') AS date")
                        ->groupBy('name', 'date');
                } else if ($frequency === 'Quarter') {
                    $qb ->select('d.name AS name','MAX(rv.value) AS value', "QUARTER(DATE_FORMAT(rv.creationDate, '%Y-%m-%d')) AS date")
                        ->groupBy('name', 'date');
                } 
                return $qb;
            case 'BMS_Distribution_TDV' :
                if ($frequency === 'Month' || $frequency === "Period") {
                    $qb ->select('Distinct d.name AS name', 'd.id AS id','rv.value AS value', "DATE_FORMAT(rv.creationDate, '%Y-%m-%d') AS date");
                } 
                else if ($frequency === 'Year') {
                    $qb ->select('Distinct d.name AS name', 'd.id AS id','rv.value AS value', "DATE_FORMAT(rv.creationDate, '%Y') AS date");
                } 
                else if ($frequency === 'Quarter') {
                    $qb ->select('DISTINCT rv.value AS value', 'd.name AS name', 'd.id AS id', "QUARTER(DATE_FORMAT(rv.creationDate, '%Y-%m-%d')) AS date");
                }
                return $qb;
            case 'BMSU_Distribution_NM' :
            case 'BMSU_Distribution_NW' :
                 if ($frequency === 'Month' || $frequency === "Period") {
                    $qb ->select("CONCAT(rv.unity, '/', d.name) AS name",'rv.value AS value', "DATE_FORMAT(rv.creationDate, '%Y-%m-%d') AS date");
                } else if ($frequency === 'Year') {
                    $qb ->select("CONCAT(rv.unity, '/', d.name) AS name",'MAX(rv.value) AS value', "DATE_FORMAT(rv.creationDate, '%Y') AS date")
                        ->groupBy('name, date');
                } else if ($frequency === 'Quarter') {
                    $qb ->select("CONCAT(rv.unity, '/', d.name) AS name",'MAX(rv.value) AS value', "QUARTER(DATE_FORMAT(rv.creationDate, '%Y-%m-%d')) AS date")
                        ->groupBy('name, date');
                }
                return $qb;
            case 'BMSU_Distribution_TVS':
            case 'BMSU_Distribution_TVSV' :
                if ($frequency === 'Month' || $frequency === "Period") {
                    $qb ->select('SUM(rv.value) AS value', 'rv.unity AS unity',  "DATE_FORMAT(rv.creationDate, '%Y-%m-%d') AS date")
                        ->groupBy('unity', 'date'); 
                } else if ($frequency === 'Year') {
                    $qb ->select('SUM(rv.value) AS value', 'rv.unity AS unity',  "DATE_FORMAT(rv.creationDate, '%Y') AS date")
                        ->groupBy('unity', 'date'); 
                } else if ($frequency === 'Quarter') {
                    $qb ->select('SUM(rv.value) AS value', 'rv.unity AS unity',  "QUARTER(DATE_FORMAT(rv.creationDate, '%Y-%m-%d')) AS date")
                        ->groupBy('unity', 'date'); 
                }  
                return $qb;
            case 'BMS_Distribution_AB' :
                if ($frequency === 'Month' || $frequency === "Period") {
                    $qb ->select('SUM(rv.value) AS value', 'rv.unity AS name', "DATE_FORMAT(rv.creationDate, '%Y-%m-%d') AS date")
                        ->groupBy('name', 'date');
                } else if ($frequency === 'Year') {
                    $qb ->select('SUM(rv.value) AS value', 'rv.unity AS name', "DATE_FORMAT(rv.creationDate, '%Y') AS date")
                        ->groupBy('name', 'date');
                } else if ($frequency === 'Quarter') {
                    $qb ->select('SUM(rv.value) AS value', 'rv.unity AS name', "QUARTER(DATE_FORMAT(rv.creationDate, '%Y-%m-%d')) AS date")
                        ->groupBy('name', 'date');
                }
                return $qb;
            case 'BMS_Distribution_M':
                if ($frequency === 'Month' || $frequency === "Period") {
                    $qb ->select('DISTINCT d.name AS name','rv.value AS value', "DATE_FORMAT(rv.creationDate, '%Y-%m-%d') AS date");
                } else if ($frequency === 'Year') {
                    $qb ->select('DISTINCT d.name AS name','rv.value AS value', "DATE_FORMAT(rv.creationDate, '%Y') AS date");
                } else if ($frequency === 'Quarter') {
                    $qb ->select('DISTINCT d.name AS name','rv.value AS value', "QUARTER(DATE_FORMAT(rv.creationDate, '%Y-%m-%d')) AS date");
                } 
                return $qb;     
        }
    }

    /**
     * get the name of month which delimit the quarter
     * @param $results
     * @return mixed
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

}