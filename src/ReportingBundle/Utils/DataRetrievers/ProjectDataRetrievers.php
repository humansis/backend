<?php

namespace ReportingBundle\Utils\DataRetrievers;

use Doctrine\ORM\EntityManager;

use ReportingBundle\Entity\ReportingProject;

class ProjectDataRetrievers 
{
    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var \Doctrine\Common\Persistence\ObjectRepository|\Doctrine\ORM\EntityRepository
     */
    private $reportingProject;

    /**
     * ProjectDataRetrievers constructor.
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;   
        $this->reportingProject = $em->getRepository(ReportingProject::class);
    }

    /**
     * Get the name of all donors
     * @param array $filters
     * @return array
     */
    public function BMS_Project_D(array $filters) {
        $qb = $this->getReportingValue('BMS_Project_D', $filters);
        $qb->select('p.name AS name','rv.value AS value', "DATE_FORMAT(rv.creationDate, '%Y-%m-%d') AS date");
        if (sizeof($qb->getQuery()->getArrayResult()) > 0) {
            $result = $this->lastDate($qb->getQuery()->getArrayResult());
            return $result;
        } else {
            return $qb->getQuery()->getArrayResult();
        }
    }

    /**
     * Get the number of household served
     * @param array $filters
     * @return array
     */
    public function BMS_Project_HS(array $filters) {
        $qb = $this->getReportingValue('BMS_Project_HS', $filters);
        //check if the DQL query return a result or not
        if (sizeof($qb->getQuery()->getArrayResult()) > 0) {
            //to filter data with the good frequency
            $frequency = $this->getByFrequency($qb, $filters, 'BMS_Project_HS');
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
     * Get the beneficiaries age
     * @param array $filters
     * @return array
     */
    public function BMS_Project_AB(array $filters) {
        $qb = $this->getReportingValue('BMS_Project_AB', $filters);
        //check if the DQL query return a result or not
        if (sizeof($qb->getQuery()->getArrayResult()) > 0) {
            //to filter data with the good frequency
            $frequency = $this->getByFrequency($qb, $filters, 'BMS_Project_AB');
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
     * Get the number of men and women in a project
     * @param array $filters
     * @return array
     */
    public function BMS_Project_NMW(array $filters) {

        $menAndWomen = [];
        //call function to get number of men and number of women
        $mens = $this->BMSU_Project_NM($filters);
        $womens = $this->BMSU_Project_NW($filters);

        //verify if there is no men or no women in the distribution
        if (sizeof($mens) > 0 || sizeof($womens) > 0) {
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
        return $menAndWomen; 
    }

    /**
     * Get the number of men
     * @param array $filters
     * @return mixed
     */
    public function BMSU_Project_NM(array $filters) {
        $qb = $this->getReportingValue('BMSU_Project_NM', $filters);
        //to filter data with the good frequency
        $result = $this->getByFrequency($qb, $filters, 'BMSU_Project_NM' );
        return $result;    
    }

    /**
     * Get the number of women
     * @param array $filters
     * @return mixed
     */
    public function BMSU_Project_NW(array $filters) {
        $qb = $this->getReportingValue('BMSU_Project_NW', $filters);
        //to filter data with the good frequency
        $result = $this->getByFrequency($qb, $filters, 'BMSU_Project_NW');
        return $result;   
    }

    /**
     * Get the percentage of vulnerabilities served
     * @param array $filters
     * @return array
     */
    public function BMS_Project_PVS(array $filters) {
        $vulnerabilitiesPercentage = [];

        //call function to get total vulnerability and total by vulnerability
        $totalVulnerabilities = $this->BMSU_Project_TVS($filters);
        $totalVulnerabilitiesByVulnerabilities = $this->BMSU_Project_TVSV($filters);

        if (sizeof($totalVulnerabilities) > 0 && sizeof($totalVulnerabilitiesByVulnerabilities) >0  ) {
            //Get the more recent date
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
     * Get the total of vulnerabilities served by vulnerabilities
     * @param array $filters
     * @return mixed
     */
    public function BMSU_Project_TVSV(array $filters) {
        $qb = $this->getReportingValue('BMSU_Project_TVSV', $filters);
        //to filter data with the good frequency
        $result = $this->getByFrequency($qb, $filters, 'BMSU_Project_TVSV' );
        return $result;
    }

    /**
     * Get the total of vulnerabilities served
     * @param array $filters
     * @return mixed
     */
    public function BMSU_Project_TVS(array $filters) {
        $qb = $this->getReportingValue('BMSU_Project_TVS', $filters);
        //to filter data with the good frequency
        $result = $this->getByFrequency($qb, $filters, 'BMSU_Project_TVS' );
        return $result;
    }

    /**
     * Get the total of value in a project
     * @param array $filters
     * @return mixed
     */
    public function BMSU_Project_PV(array $filters) {
        $qb = $this->getReportingValue('BMSU_Project_PV', $filters);
        //to filter data with the good frequency
        $result = $this->getByFrequency($qb, $filters, 'BMSU_Project_PV' );
        return $result;
    }

    /**
     * Use to verify if a key project exist in filter
     * If this key exists, it means a project was selected in selector
     * @param $qb
     * @param array $filters
     * @return mixed
     */
    public function ifInProject($qb, array $filters) {
        if(array_key_exists('project', $filters)) {
            $qb->andWhere('p.id IN (:projects)')
                    ->setParameter('projects', $filters['project']);
        }
        return $qb;
    }

    /**
     * Use to make join and where in DQL
     * Use in all project data retrievers
     * @param string $code
     * @param array $filters
     * @return \Doctrine\ORM\QueryBuilder|mixed
     */
    public function getReportingValue(string $code, array $filters) {
        $qb = $this->reportingProject->createQueryBuilder('rp')
                                    ->leftjoin('rp.value', 'rv')
                                    ->leftjoin('rp.indicator', 'ri')
                                    ->leftjoin('rp.project', 'p')
                                    ->where('ri.code = :code')
                                    ->setParameter('code', $code)
                                    ->andWhere('p.iso3 = :country')
                                    ->setParameter('country', $filters['country']);
        $qb = $this->ifInProject($qb, $filters);
        return $qb;
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
     * Indicators with the same 'select' statement are grouped in the same case
     * @param $qb
     * @param $nameFunction
     * @param $frequency
     * @return mixed
     */
    public function conditionSelect($qb, $nameFunction, $frequency) {
        switch ($nameFunction) {
            case 'BMS_Project_AB':
                if ($frequency === 'Month' || $frequency === "Period") {
                    $qb ->select('rv.unity AS name','SUM(rv.value) AS value', "DATE_FORMAT(rv.creationDate, '%Y-%m-%d') AS date")
                        ->groupBy('name', 'date');
                } else if ($frequency === 'Year') {
                    $qb ->select('rv.unity AS name','SUM(rv.value) AS value', "DATE_FORMAT(rv.creationDate, '%Y') AS date")
                        ->groupBy('name', 'date');
                } else if ($frequency === 'Quarter') {
                    $qb ->select('rv.unity AS name','SUM(rv.value) AS value', "QUARTER(DATE_FORMAT(rv.creationDate, '%Y-%m-%d')) AS date")
                        ->groupBy('name', 'date');
                } 
                return $qb;
            case 'BMS_Project_HS' :
                if ($frequency === 'Month' || $frequency === "Period") {
                    $qb ->select('p.name AS name','rv.value AS value', "DATE_FORMAT(rv.creationDate, '%Y-%m-%d') AS date");    
                } else if ($frequency === 'Year') {
                    $qb ->select('p.name AS name','MAX(rv.value) AS value', "DATE_FORMAT(rv.creationDate, '%Y') AS date")
                        ->groupBy('name', 'date');
                } else if ($frequency === 'Quarter') {
                    $qb ->select('p.name AS name','MAX(rv.value)  AS value', "QUARTER(DATE_FORMAT(rv.creationDate, '%Y-%m-%d')) AS date")
                        ->groupBy('name', 'date');
                }
                return $qb;
            case 'BMSU_Project_NM' :
            case 'BMSU_Project_NW' :
                 if ($frequency === 'Month' || $frequency === "Period") {
                    $qb ->select('rv.value AS value', "CONCAT(rv.unity, '/', p.name) AS name", "DATE_FORMAT(rv.creationDate, '%Y-%m-%d') AS date");
                } else if ($frequency === 'Year') {
                    $qb ->select('MAX(rv.value)  AS value', "CONCAT(rv.unity, '/', p.name) AS name", "DATE_FORMAT(rv.creationDate, '%Y') AS date")
                        ->groupBy('name', 'date');
                } else if ($frequency === 'Quarter') {
                    $qb ->select('MAX(rv.value)  AS value', "CONCAT(rv.unity, '/', p.name) AS name", "QUARTER(DATE_FORMAT(rv.creationDate, '%Y-%m-%d')) AS date")
                        ->groupBy('name', 'date');
                }
                return $qb;
            case 'BMSU_Project_TVS':
            case 'BMSU_Project_TVSV' :
                if ($frequency === 'Month' || $frequency === "Period") {
                    $qb->select('SUM(rv.value) AS value', 'rv.unity AS unity',  "DATE_FORMAT(rv.creationDate, '%Y-%m-%d') AS date")
                    ->groupBy('unity', 'date');           
                } else if ($frequency === 'Year') {
                    $qb->select('SUM(rv.value) AS value', 'rv.unity AS unity',  "DATE_FORMAT(rv.creationDate, '%Y') AS date")
                    ->groupBy('unity', 'date');
                } else if ($frequency === 'Quarter') {
                    $qb->select('SUM(rv.value) AS value', 'rv.unity AS unity',  "QUARTER(DATE_FORMAT(rv.creationDate, '%Y-%m-%d')) AS date")
                    ->groupBy('unity', 'date');
                }  
                return $qb;
            case 'BMSU_Project_PV' :
                if ($frequency === 'Month' || $frequency === "Period") {
                    $qb ->select('rv.value AS value', 'p.name AS name', 'p.id as id',  "DATE_FORMAT(rv.creationDate, '%Y-%m-%d') AS date");          
                } else if ($frequency === 'Year') {
                    $qb ->select('MAX(rv.value)  AS value', 'p.name AS name', 'p.id as id',  "DATE_FORMAT(rv.creationDate, '%Y') AS date")
                        ->groupBy('name', 'date');
                } else if ($frequency === 'Quarter') {
                    $qb ->select('MAX(rv.value)  AS value', 'p.name AS name', 'p.id as id',  "QUARTER(DATE_FORMAT(rv.creationDate, '%Y-%m-%d')) AS date")
                        ->groupBy('name', 'date');
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


    /**
     * Get the data with the more recent values
     * @param array $values
     * @return array
     */
    public function lastDate(array $values) {
        $moreRecentValues = [];
        $lastDate = $values[0]['date'];
        foreach($values as $value) {
            if ($value['date'] > $lastDate) {
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
}