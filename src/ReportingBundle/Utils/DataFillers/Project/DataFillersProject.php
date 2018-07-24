<?php

namespace ReportingBundle\Utils\DataFillers\Project;

use ReportingBundle\Utils\Model\IndicatorInterface;

use \ReportingBundle\Entity\ReportingIndicator;
use \ReportingBundle\Entity\ReportingValue;
use \ReportingBundle\Entity\ReportingProject;

use \DistributionBundle\Entity\DistributionBeneficiary;
use \ProjectBundle\Entity\Project;
use \BeneficiaryBundle\Entity\Beneficiary;
use \BeneficiaryBundle\Entity\VulnerabilityCriterion;

use Doctrine\ORM\EntityManager;

class DataFillersProject 
{

    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;   
    }

    /**
     * find the id of reference code
     */
    public function getReferenceId(string $code) {
        $this->repository = $this->em->getRepository(ReportingIndicator::class);
        $qb = $this->repository->createQueryBuilder('ri')
                               ->Where('ri.code = :code')
                                    ->setParameter('code', $code);
        return $qb->getQuery()->getSingleResult();
    }

    public function getProject() {
        $this->repository = $this->em->getRepository(Project::class);
        return $this->repository->findAll();

    }

    /**
     * Fill in ReportingValue and ReportingCountry with number of Men in a project
     */
    public function BMSU_Project_NM() {
        $projects = $this->getProject();
        $results = [];
        foreach($projects as $project) {
            $byProject = [];
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
                    $new_value->setUnity('men');
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
     * Fill in ReportingValue and ReportingCountry with number of women in a project
     */
    public function BMSU_Project_NW() {
        $projects = $this->getProject();
        $results = [];
        foreach($projects as $project) {
            $byProject = [];
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
                    $new_value->setUnity('women');
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
     * Fill in ReportingValue and ReportingCountry with the total of vulnerabilities served by vulnerabily in a project
     */
    public function BMSU_Project_TVSV() {
        $this->repository = $this->em->getRepository(VulnerabilityCriterion::class);
        $vulnerabilityCriterion = $this->repository->findAll();

        $projects = $this->getProject();
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
                        ->select('count(b) as value', 'vc.value as unity')
                        ->groupBy('vc.id'); 
                        $result = $qb->getQuery()->getArrayResult();
                        $length = count($result);
                        if($length > 0) {
                            array_push($results, $result);
                        }                    
                }
            }
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
     * Fill in ReportingValue and ReportingCountry with the total of vulnerabilities served in a project
     */
    public function BMSU_Project_TVS() {
        $this->repository = $this->em->getRepository(VulnerabilityCriterion::class);
        $vulnerabilityCriterion = $this->repository->findAll();

        $projects = $this->getProject();
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
                        ->select('count(b) as value');
                        $result = $qb->getQuery()->getArrayResult();
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



}
