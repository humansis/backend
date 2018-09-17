<?php

namespace ReportingBundle\Utils\DataFillers\Project;

use ReportingBundle\Utils\Model\IndicatorInterface;

use \ReportingBundle\Entity\ReportingIndicator;
use \ReportingBundle\Entity\ReportingValue;
use \ReportingBundle\Entity\ReportingProject;

use \DistributionBundle\Entity\DistributionBeneficiary;
use \ProjectBundle\Entity\Project;
use \ProjectBundle\Entity\Donor;
use \BeneficiaryBundle\Entity\Beneficiary;
use \BeneficiaryBundle\Entity\Household;
use \BeneficiaryBundle\Entity\VulnerabilityCriterion;

use Doctrine\ORM\EntityManager;

class DataFillersProject
{

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * DataFillersProject constructor.
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;   
    }

    /**
     * find the id of reference code
     * @param string $code
     * @return mixed
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getReferenceId(string $code) {
        $this->repository = $this->em->getRepository(ReportingIndicator::class);
        $qb = $this->repository->createQueryBuilder('ri')
                               ->Where('ri.code = :code')
                                    ->setParameter('code', $code);
        return $qb->getQuery()->getSingleResult();
    }

    /**
     * @return array|object[]
     */
    public function getProject() {
        $this->repository = $this->em->getRepository(Project::class);
        return $this->repository->findAll();

    }

    /**
     * Fill in ReportingValue and ReportingProject with number of Men in a project
     */
    public function BMSU_Project_NM() {
        $projects = $this->getProject();
        $results = [];
        foreach($projects as $project) {
            foreach($project->getHouseholds() as $household) {
                $this->repository = $this->em->getRepository(Beneficiary::class);
                $qb = $this->repository->createQueryBuilder('b')
                                        ->leftjoin('b.household', 'h')
                                        ->where('h.id = :household')
                                            ->setParameter('household', $household->getId())
                                        ->andWhere('b.gender = :gender')
                                            ->setParameter('gender', 1)
                                        ->select('Distinct count(b) AS value');
                $result = $qb->getQuery()->getArrayResult();
                //foreach men find, increment the counter
                if((sizeof($result)) > 0) {
                    if((sizeof($results)) == 0) {
                        $results = $result;
                    } else {
                        (int)$results[0]['value'] += (int)$result[0]['value'];
                    }   
                }                         
            }
            $this->em->getConnection()->beginTransaction();
            try {
                $reference = $this->getReferenceId("BMSU_Project_NM");
                foreach ($results as $result) 
                {
                    $new_value = new ReportingValue();
                    $new_value->setValue($result['value']);
                    $new_value->setUnity('Men');
                    $new_value->setCreationDate(new \DateTime());

                    $this->em->persist($new_value);
                    $this->em->flush();

                    $new_reportingProject = new ReportingProject();
                    $new_reportingProject->setIndicator($reference);
                    $new_reportingProject->setValue($new_value);
                    $new_reportingProject->setProject($project);

                    $this->em->persist($new_reportingProject);
                    $this->em->flush();   
                }
                $this->em->getConnection()->commit();
            }catch (Exception $e) {
                $this->em->getConnection()->rollback();
                throw $e;
            }
            $results = [];
        }
    }

    /**
     * Fill in ReportingValue and ReportingProject with number of women in a project
     */
    public function BMSU_Project_NW() {
        $projects = $this->getProject();
        $results = [];
        foreach($projects as $project) {
            foreach($project->getHouseholds() as $household) {
                $this->repository = $this->em->getRepository(Beneficiary::class);
                $qb = $this->repository->createQueryBuilder('b')
                                        ->leftjoin('b.household', 'h')
                                        ->where('h.id = :household')
                                            ->setParameter('household', $household->getId())
                                        ->andWhere('b.gender = :gender')
                                            ->setParameter('gender', 0)
                                        ->select('Distinct count(b) AS value');
                $result = $qb->getQuery()->getArrayResult();
                //foreach women find, increment the counter
                if((sizeof($result)) > 0) {
                    if((sizeof($results)) == 0) {
                        $results = $result;
                    } else {
                        (int)$results[0]['value'] += (int)$result[0]['value'];
                    }   
                }                         
            }
            $this->em->getConnection()->beginTransaction();
            try {
                $reference = $this->getReferenceId("BMSU_Project_NW");
                foreach ($results as $result) 
                {
                    $new_value = new ReportingValue();
                    $new_value->setValue($result['value']);
                    $new_value->setUnity('Women');
                    $new_value->setCreationDate(new \DateTime());

                    $this->em->persist($new_value);
                    $this->em->flush();

                    $new_reportingProject = new ReportingProject();
                    $new_reportingProject->setIndicator($reference);
                    $new_reportingProject->setValue($new_value);
                    $new_reportingProject->setProject($project);

                    $this->em->persist($new_reportingProject);
                    $this->em->flush();   
                }
                $this->em->getConnection()->commit();
            }catch (Exception $e) {
                $this->em->getConnection()->rollback();
                throw $e;
            }
            $results = [];
        }
    }

     /**
     * Fill in ReportingValue and ReportingProject with the total of vulnerabilities served by vulnerabily in a project
     */
    public function BMSU_Project_TVSV() {
        //Get all vulnerability criterion
        $this->repository = $this->em->getRepository(VulnerabilityCriterion::class);
        $vulnerabilityCriterion = $this->repository->findAll();

        $projects = $this->getProject();

        //Search all vulnerability criterion foreach beneficiary in a project and put the result in a array
        $results = [];
        foreach($projects as $project) {
            $byProject = [];
            foreach($project->getHouseholds() as $household) {
                $this->repository = $this->em->getRepository(Beneficiary::class);
                $qb = $this->repository->createQueryBuilder('b')
                                        ->leftjoin('b.vulnerabilityCriteria', 'vc')
                                        ->leftjoin('b.household', 'h')
                                        ->where('h.id = :household')
                                            ->setParameter('household', $household->getId());
                foreach($vulnerabilityCriterion as $vulnerabilityCriteria) {
                    $qb ->andWhere('vc.id = :criteria')
                            ->setParameter('criteria', $vulnerabilityCriteria->getId())
                        ->select('count(b) as value', 'vc.fieldString as unity')
                        ->groupBy('vc.id'); 
                    $result = $qb->getQuery()->getArrayResult();
                    $length = count($result);
                    if($length > 0) {
                        array_push($results, $result);
                    }                    
                }
            }
            //Sort the vulnerability criterion and count the number of entry find foreach of them
            $found = false;
            foreach($results as $result) {
                foreach($result as $value) {
                    if ((sizeof($byProject)) == 0) {
                        array_push($byProject, $value );
                    } else {
                        for ($i=0; $i<(sizeof($byProject)); $i++) {
                            if ($byProject[$i]['unity'] == $value['unity']) {
                                (int)$byProject[$i]['value'] += (int)$value['value'];
                                $found = true;
                                break 1;
                            }
                        }
                        if (!$found) {
                            array_push($byProject, $value);
                        }
                        $found = false;
                    }
                }
            }
            foreach($byProject as $byProjectByVulnerability) {
                $this->em->getConnection()->beginTransaction();
                try {
                    $reference = $this->getReferenceId("BMSU_Project_TVSV");
                    $new_value = new ReportingValue();
                    $new_value->setValue($byProjectByVulnerability['value']);
                    $new_value->setUnity($byProjectByVulnerability['unity']);
                    $new_value->setCreationDate(new \DateTime());

                    $this->em->persist($new_value);
                    $this->em->flush();

                    $new_reportingProject = new ReportingProject();
                    $new_reportingProject->setIndicator($reference);
                    $new_reportingProject->setValue($new_value);
                    $new_reportingProject->setProject($project);

                    $this->em->persist($new_reportingProject);
                    $this->em->flush();   
                    
                    $this->em->getConnection()->commit();
                }catch (Exception $e) {
                    $this->em->getConnection()->rollback();
                    throw $e;
                }
            }
            $results = [];  
        }  
    }

         /**
     * Fill in ReportingValue and ReportingProject with the total of vulnerabilities served in a project
     */
    public function BMSU_Project_TVS() {
        //Get all vulnerability criterion
        $this->repository = $this->em->getRepository(VulnerabilityCriterion::class);
        $vulnerabilityCriterion = $this->repository->findAll();

        $projects = $this->getProject();
        $results = [];
        //Search all vulnerability criterion foreach beneficiary in a project  and count the vulnerability served
        foreach($projects as $project) {
            foreach($project->getHouseholds() as $household) {
                $this->repository = $this->em->getRepository(Beneficiary::class);
                $qb = $this->repository->createQueryBuilder('b')
                                        ->leftjoin('b.vulnerabilityCriteria', 'vc')
                                        ->leftjoin('b.household', 'h')
                                        ->where('h.id = :household')
                                            ->setParameter('household', $household->getId());
                foreach($vulnerabilityCriterion as $vulnerabilityCriteria) {
                    $qb ->andWhere('vc.id = :criteria')
                            ->setParameter('criteria', $vulnerabilityCriteria->getId())
                        ->select('count(b) as value');
                        $result = $qb->getQuery()->getArrayResult();
                        //count the number of vulnerability served find in the project
                        if((sizeof($result)) > 0) {
                            if((sizeof($results)) == 0) {
                                $results = $result;
                            } else {
                                (int)$results[0]['value'] += (int)$result[0]['value'];
                            }   
                        }                               
                }
            }
            $this->em->getConnection()->beginTransaction();
            try {
                $reference = $this->getReferenceId("BMSU_Project_TVS");
                foreach ($results as $result) 
                {
                    $new_value = new ReportingValue();
                    $new_value->setValue($result['value']);
                    $new_value->setUnity('vulnerability served');
                    $new_value->setCreationDate(new \DateTime());

                    $this->em->persist($new_value);
                    $this->em->flush();

                    $new_reportingProject = new ReportingProject();
                    $new_reportingProject->setIndicator($reference);
                    $new_reportingProject->setValue($new_value);
                    $new_reportingProject->setProject($project);

                    $this->em->persist($new_reportingProject);
                    $this->em->flush();   
                }
                $this->em->getConnection()->commit();
            }catch (Exception $e) {
                $this->em->getConnection()->rollback();
                throw $e;
            }
            $results = [];
        }  
    }

    /**
     * Fill in ReportingValue and ReportingProject with name of donors
     */
    public function BMS_Project_D() {
        $projects = $this->getProject();
        foreach($projects as $project) {
            foreach($project->getDonors() as $donor) {
                $this->repository = $this->em->getRepository(Donor::class);
                $qb = $this->repository->createQueryBuilder('d')
                                        ->where('d.id = :donor')
                                            ->setParameter('donor', $donor->getId())
                                        ->select("CONCAT(d.fullname,' ',d.shortname) as value");
                $results = $qb->getQuery()->getArrayResult();  
                $this->em->getConnection()->beginTransaction();
                try {
                    $reference = $this->getReferenceId("BMS_Project_D");
                    foreach ($results as $result) 
                    {
                        $new_value = new ReportingValue();
                        $new_value->setValue($result['value']);
                        $new_value->setUnity($project->getName());
                        $new_value->setCreationDate(new \DateTime());

                        $this->em->persist($new_value);
                        $this->em->flush();

                        $new_reportingProject = new ReportingProject();
                        $new_reportingProject->setIndicator($reference);
                        $new_reportingProject->setValue($new_value);
                        $new_reportingProject->setProject($project);

                        $this->em->persist($new_reportingProject);
                        $this->em->flush();   
                    }
                    $this->em->getConnection()->commit();
                }catch (Exception $e) {
                    $this->em->getConnection()->rollback();
                    throw $e;
                }              
            }
            
        }
    }

    /**
     * Fill in ReportingValue and ReportingProject with number of household served in a project
     */
    public function BMS_Project_HS() {
        $projects = $this->getProject();
        foreach($projects as $project) {
            $results = [];
            foreach($project->getHouseholds() as $household) {
                $this->repository = $this->em->getRepository(Household::class);
                $qb = $this->repository->createQueryBuilder('h')
                                        ->where('h.id = :household')
                                            ->setParameter('household', $household->getId())
                                        ->select("count(h.id) as value"); 
                $result = $qb->getQuery()->getArrayResult();
                //foreach household served in the project, increment counter
                if((sizeof($result)) > 0) {
                    if((sizeof($results)) == 0) {
                        $results = $result;
                    } else {
                        (int)$results[0]['value'] += (int)$result[0]['value'];
                    }   
                }    
                            
            }

            $this->em->getConnection()->beginTransaction();
                try {
                    $reference = $this->getReferenceId("BMS_Project_HS");
                    foreach ($results as $result) 
                    {
                        $new_value = new ReportingValue();
                        $new_value->setValue($result['value']);
                        $new_value->setUnity('households');
                        $new_value->setCreationDate(new \DateTime());

                        $this->em->persist($new_value);
                        $this->em->flush();

                        $new_reportingProject = new ReportingProject();
                        $new_reportingProject->setIndicator($reference);
                        $new_reportingProject->setValue($new_value);
                        $new_reportingProject->setProject($project);

                        $this->em->persist($new_reportingProject);
                        $this->em->flush();   
                    }
                    $this->em->getConnection()->commit();
                }catch (Exception $e) {
                    $this->em->getConnection()->rollback();
                    throw $e;
                }
        }
    }

     /**
     * Fill in ReportingValue and ReportingProject with number of household served in a project
     */
    public function BMS_Project_AB() {
        $projects = $this->getProject();
        //Search the age of all beneficiary in all project and push the result of the query in a array
        foreach($projects as $project) {
            $results = [];
            foreach($project->getHouseholds() as $household) {
                foreach($household->getBeneficiaries() as $beneficiary) {
                    $this->repository = $this->em->getRepository(Beneficiary::class);
                    $qb = $this->repository->createQueryBuilder('b')
                                        ->leftjoin('b.household', 'h')
                                        ->where('h.id = :household')
                                            ->setParameter('household', $household->getId())
                                        ->andWhere('b.id = :beneficiary')
                                            ->setParameter('beneficiary', $beneficiary->getId())
                                        ->select("TIMESTAMPDIFF(YEAR, b.dateOfBirth, CURRENT_DATE()) as value"); 
                     $result = $qb->getQuery()->getArrayResult();
                        if((sizeof($result)) > 0) {
                         array_push($results, $result);
                        }        
                }                      
            }
            //Call a function to sort age in corresponding interval
            $byInterval = $this->sortByAge($results);
            foreach($byInterval as $ageBreakdown) {
                $this->em->getConnection()->beginTransaction();
                try {
                    $reference = $this->getReferenceId("BMS_Project_AB");
                    $new_value = new ReportingValue();
                    $new_value->setValue($ageBreakdown['value']);
                    $new_value->setUnity($ageBreakdown['unity']);
                    $new_value->setCreationDate(new \DateTime());

                    $this->em->persist($new_value);
                    $this->em->flush();

                    $new_reportingProject = new ReportingProject();
                    $new_reportingProject->setIndicator($reference);
                    $new_reportingProject->setValue($new_value);
                    $new_reportingProject->setProject($project);

                    $this->em->persist($new_reportingProject);
                    $this->em->flush();   
                    $this->em->getConnection()->commit();
                }catch (Exception $e) {
                    $this->em->getConnection()->rollback();
                    throw $e;
                }    
            }
        }
    }

      /**
     * Fill in ReportingValue and ReportingProject with total value of project
     */
    public function BMSU_Project_PV() {
        $this->em->getConnection()->beginTransaction();
        try {
            $this->repository = $this->em->getRepository(Project::class);
            $qb = $this->repository->createQueryBuilder('p')
                                   ->select('p.value AS value', 'p.id as project');
            $results = $qb->getQuery()->getArrayResult();
            $reference = $this->getReferenceId("BMSU_Project_PV");
            foreach ($results as $result) 
            {
                $new_value = new ReportingValue();
                $new_value->setValue($result['value']);
                $new_value->setUnity('project value');
                $new_value->setCreationDate(new \DateTime());

                $this->em->persist($new_value);
                $this->em->flush();

                $this->repository = $this->em->getRepository(Project::class);
                $project = $this->repository->findOneBy(['id' => $result['project']]); 

                $new_reportingProject = new ReportingProject();
                $new_reportingProject->setIndicator($reference);
                $new_reportingProject->setValue($new_value);
                $new_reportingProject->setProject($project);

                $this->em->persist($new_reportingProject);
                $this->em->flush();   
            }
            $this->em->getConnection()->commit();
        }catch (Exception $e) {
            $this->em->getConnection()->rollback();
            throw $e;
        }
    }

    /**
     * Use to sort beneficiary by age interval
     * If the age is in the interval, increment the corresponding counter
     * @param $ages
     * @return array
     */
    public function sortByAge($ages) {
        $byInterval= []; 
        foreach($ages as $age) {
            foreach($age as $value) {
                if ((int)$value['value'] > 0 && (int)$value['value'] <= 10 ) {
                    if(!array_key_exists('zeroTen', $byInterval)) {
                        $byInterval['zeroTen'] = [];
                        $byInterval['zeroTen']['unity'] = '[0-10]';
                        $byInterval['zeroTen']['value'] = 1;
                    } else {
                        $byInterval['zeroTen']['value'] += 1;
                    }
                    break 1;
                } else if ((int)$value['value'] > 10 && (int)$value['value'] <= 18 ) {
                    if(!array_key_exists('TenEighteen', $byInterval)) {
                        $byInterval['TenEighteen'] = [];
                        $byInterval['TenEighteen']['unity'] = '[11-18]';
                        $byInterval['TenEighteen']['value'] = 1;
                    } else {
                        $byInterval['TenEighteen']['value'] += 1;
                    }
                    break 1;
                } else if ((int)$value['value'] > 18 && (int)$value['value'] <= 30 ) {
                    if(!array_key_exists('EighteenThirty', $byInterval)) {
                        $byInterval['EighteenThirty'] = [];
                        $byInterval['EighteenThirty']['unity'] = '[19-30]';
                        $byInterval['EighteenThirty']['value'] = 1;
                    } else {
                        $byInterval['EighteenThirty']['value'] += 1;
                    }
                    break 1;
                } else if ((int)$value['value'] > 30 && (int)$value['value'] <= 50 ) {
                    if(!array_key_exists('ThirtyFifty', $byInterval)) {
                        $byInterval['ThirtyFifty'] = [];
                        $byInterval['ThirtyFifty']['unity'] = '[31-50]';
                        $byInterval['ThirtyFifty']['value'] = 1;
                    } else {
                        $byInterval['ThirtyFifty']['value'] += 1;
                    }
                    break 1;
                } else if ((int)$value['value'] > 50 && (int)$value['value'] <= 70 ) {
                    if(!array_key_exists('FiftySeventy', $byInterval)) {
                        $byInterval['FiftySeventy'] = [];
                        $byInterval['FiftySeventy']['unity'] = '[51-70]';
                        $byInterval['FiftySeventy']['value'] = 1;
                    } else {
                        $byInterval['FiftySeventy']['value'] += 1;
                    }
                    break 1;
                } else {
                    if(!array_key_exists('MoreSeventy', $byInterval)) {
                        $byInterval['MoreSeventy'] = [];
                        $byInterval['MoreSeventy']['unity'] = '[70+]';
                        $byInterval['MoreSeventy']['value'] = 1;
                    } else {
                        $byInterval['MoreSeventy']['value'] += 1;
                    }
                    break 1;
                }
            }
        }
        return $byInterval;
    }
}
