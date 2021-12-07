<?php

namespace ReportingBundle\Utils\DataFillers\Project;

use \BeneficiaryBundle\Entity\Beneficiary;

use \BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\Person;
use \BeneficiaryBundle\Entity\VulnerabilityCriterion;
use \DistributionBundle\Entity\AssistanceBeneficiary;

use NewApiBundle\DBAL\PersonGenderEnum;
use NewApiBundle\Enum\PersonGender;
use \ProjectBundle\Entity\Donor;
use \ProjectBundle\Entity\Project;
use \ReportingBundle\Entity\ReportingIndicator;
use \ReportingBundle\Entity\ReportingProject;
use \ReportingBundle\Entity\ReportingValue;
use Doctrine\ORM\EntityManager;
use Exception;

use ReportingBundle\Utils\Model\IndicatorInterface;

/**
 * Class DataFillersProject
 * @package ReportingBundle\Utils\DataFillers\Project
 */
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
    public function getReferenceId(string $code)
    {
        $repository = $this->em->getRepository(ReportingIndicator::class);
        $qb = $repository->createQueryBuilder('ri')
                               ->Where('ri.code = :code')
                                    ->setParameter('code', $code);
        return $qb->getQuery()->getSingleResult();
    }

    /**
     * @return array|object[]
     */
    public function getProjects()
    {
        return $this->em->getRepository(Project::class)->findAll();
    }

    /**
     * Fill in ReportingValue and ReportingProject with name of donors
     */
    public function BMS_Project_D()
    {
        $repository = $this->em->getRepository(Donor::class);
        $qb = $repository->createQueryBuilder('d')
            ->innerJoin('d.projects', 'p')
            ->select('d.shortname as donor', 'p.id as project')
            ->groupBy('donor, project');
        $results = $qb->getQuery()->getArrayResult();

        $this->em->getConnection()->beginTransaction();
        try {
            $reference = $this->getReferenceId("BMS_Project_D");
            foreach ($results as $result) {
                $new_value = new ReportingValue();
                $new_value->setValue(1);
                $new_value->setUnity($result['donor']);
                $new_value->setCreationDate(new \DateTime());

                $this->em->persist($new_value);

                $new_reportingProject = new ReportingProject();
                $new_reportingProject->setIndicator($reference);
                $new_reportingProject->setValue($new_value);
                $new_reportingProject->setProject($this->em->getRepository(Project::class)->find($result['project']));

                $this->em->persist($new_reportingProject);
                $this->em->flush();
            }
            $this->em->getConnection()->commit();
        } catch (Exception $e) {
            $this->em->getConnection()->rollback();
            throw $e;
        }
    }

    /**
     * Fill in ReportingValue and ReportingProject with number of Men in a project
     */
    public function BMSU_Project_NM()
    {
        $projects = $this->getProjects();
        $results = [];
        foreach ($projects as $project) {
            foreach ($project->getHouseholds() as $household) {
                $repository = $this->em->getRepository(Beneficiary::class);
                $qb = $repository->createQueryBuilder('b')
                                        ->leftjoin('b.household', 'h')
                                        ->where('h.id = :household')
                                            ->setParameter('household', $household->getId())
                                        ->andWhere('b.gender = :gender')
                                            ->setParameter('gender', PersonGenderEnum::valueToDB(PersonGender::MALE))
                                        ->select('Distinct count(b) AS value');
                $result = $qb->getQuery()->getArrayResult();
                //foreach men find, increment the counter
                if ((sizeof($result)) > 0) {
                    if ((sizeof($results)) == 0) {
                        $results = $result;
                    } else {
                        (int)$results[0]['value'] += (int)$result[0]['value'];
                    }
                }
            }
            $this->em->getConnection()->beginTransaction();
            try {
                $reference = $this->getReferenceId("BMSU_Project_NM");
                foreach ($results as $result) {
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
            } catch (Exception $e) {
                $this->em->getConnection()->rollback();
                throw $e;
            }
            $results = [];
        }
    }

    /**
     * Fill in ReportingValue and ReportingProject with number of women in a project
     */
    public function BMSU_Project_NW()
    {
        $projects = $this->getProjects();
        $results = [];
        foreach ($projects as $project) {
            foreach ($project->getHouseholds() as $household) {
                $repository = $this->em->getRepository(Beneficiary::class);
                $qb = $repository->createQueryBuilder('b')
                                        ->leftjoin('b.household', 'h')
                                        ->where('h.id = :household')
                                            ->setParameter('household', $household->getId())
                                        ->andWhere('b.gender = :gender')
                                            ->setParameter('gender', PersonGenderEnum::valueToDB(PersonGender::FEMALE))
                                        ->select('Distinct count(b) AS value');
                $result = $qb->getQuery()->getArrayResult();
                //foreach women find, increment the counter
                if ((sizeof($result)) > 0) {
                    if ((sizeof($results)) == 0) {
                        $results = $result;
                    } else {
                        (int)$results[0]['value'] += (int)$result[0]['value'];
                    }
                }
            }
            $this->em->getConnection()->beginTransaction();
            try {
                $reference = $this->getReferenceId("BMSU_Project_NW");
                foreach ($results as $result) {
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
            } catch (Exception $e) {
                $this->em->getConnection()->rollback();
                throw $e;
            }
            $results = [];
        }
    }

    /**
    * Fill in ReportingValue and ReportingProject with the total of vulnerabilities served by vulnerabily in a project
    */
    public function BMSU_Project_TVSV()
    {
        //Get all vulnerability criterion
        $repository = $this->em->getRepository(VulnerabilityCriterion::class);
        $vulnerabilityCriterion = $repository->findAll();

        $projects = $this->getProjects();

        //Search all vulnerability criterion foreach beneficiary in a project and put the result in a array
        $results = [];
        foreach ($projects as $project) {
            $byProject = [];
            foreach ($project->getHouseholds() as $household) {
                $repository = $this->em->getRepository(Beneficiary::class);
                $qb = $repository->createQueryBuilder('b')
                                        ->leftjoin('b.vulnerabilityCriteria', 'vc')
                                        ->leftjoin('b.household', 'h')
                                        ->where('h.id = :household')
                                            ->setParameter('household', $household->getId());
                foreach ($vulnerabilityCriterion as $vulnerabilityCriteria) {
                    $qb ->andWhere('vc.id = :criteria')
                            ->setParameter('criteria', $vulnerabilityCriteria->getId())
                        ->select('count(b) as value', 'vc.fieldString as unity')
                        ->groupBy('vc.id');
                    $result = $qb->getQuery()->getArrayResult();
                    $length = count($result);
                    if ($length > 0) {
                        array_push($results, $result);
                    }
                }
            }
            //Sort the vulnerability criterion and count the number of entry find foreach of them
            $found = false;
            foreach ($results as $result) {
                foreach ($result as $value) {
                    if ((sizeof($byProject)) == 0) {
                        array_push($byProject, $value);
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
            foreach ($byProject as $byProjectByVulnerability) {
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
                } catch (Exception $e) {
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
    public function BMSU_Project_TVS()
    {
        //Get all vulnerability criterion
        $repository = $this->em->getRepository(VulnerabilityCriterion::class);
        $vulnerabilityCriterion = $repository->findAll();

        $projects = $this->getProjects();
        $results = [];
        //Search all vulnerability criterion foreach beneficiary in a project  and count the vulnerability served
        foreach ($projects as $project) {
            foreach ($project->getHouseholds() as $household) {
                $repository = $this->em->getRepository(Beneficiary::class);
                $qb = $repository->createQueryBuilder('b')
                                        ->leftjoin('b.vulnerabilityCriteria', 'vc')
                                        ->leftjoin('b.household', 'h')
                                        ->where('h.id = :household')
                                            ->setParameter('household', $household->getId());
                foreach ($vulnerabilityCriterion as $vulnerabilityCriteria) {
                    $qb ->andWhere('vc.id = :criteria')
                            ->setParameter('criteria', $vulnerabilityCriteria->getId())
                        ->select('count(b) as value');
                    $result = $qb->getQuery()->getArrayResult();
                    //count the number of vulnerability served find in the project
                    if ((sizeof($result)) > 0) {
                        if ((sizeof($results)) == 0) {
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
                foreach ($results as $result) {
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
            } catch (Exception $e) {
                $this->em->getConnection()->rollback();
                throw $e;
            }
            $results = [];
        }
    }


    /**
     * Fill in ReportingValue and ReportingProject with number of household served in a project
     */
    public function BMS_Project_HS()
    {
        $projects = $this->getProjects();
        foreach ($projects as $project) {
            $results = [];
            foreach ($project->getHouseholds() as $household) {
                $repository = $this->em->getRepository(Household::class);
                $qb = $repository->createQueryBuilder('h')
                                        ->where('h.id = :household')
                                            ->setParameter('household', $household->getId())
                                        ->select("count(h.id) as value");
                $result = $qb->getQuery()->getArrayResult();
                //foreach household served in the project, increment counter
                if ((sizeof($result)) > 0) {
                    if ((sizeof($results)) == 0) {
                        $results = $result;
                    } else {
                        (int)$results[0]['value'] += (int)$result[0]['value'];
                    }
                }
            }

            $this->em->getConnection()->beginTransaction();
            try {
                $reference = $this->getReferenceId("BMS_Project_HS");
                foreach ($results as $result) {
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
            } catch (Exception $e) {
                $this->em->getConnection()->rollback();
                throw $e;
            }
        }
    }


    /**
     * Fill in ReportingValue and ReportingProject with number of beneficiaries served in a project
     */
    public function BMS_Project_BR()
    {
        $projects = $this->getProjects();
        foreach ($projects as $project) {
            $repository = $this->em->getRepository(AssistanceBeneficiary::class);
            $qb = $repository->createQueryBuilder('db')
                ->leftjoin('db.assistance', 'dd')
                ->leftJoin('dd.project', 'p')
                ->where('p.id = :project')
                ->setParameter('project', $project->getId())
                ->select('DISTINCT COUNT(db.id) AS value', 'p.id AS project', 'p.target AS target')
                ->groupBy('project');

            $results = $qb->getQuery()->getArrayResult();

            $this->em->getConnection()->beginTransaction();
            try {
                $reference = $this->getReferenceId("BMS_Project_HS");
                foreach ($results as $result) {
                    $new_value = new ReportingValue();
                    $new_value->setValue($result['value']/$result['target']*100);
                    $new_value->setUnity('% beneficiaries');
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
            } catch (Exception $e) {
                $this->em->getConnection()->rollback();
                throw $e;
            }
        }
    }


    /**
    * Fill in ReportingValue and ReportingProject with age breakdown in a project
    */
    public function BMS_Project_AB()
    {
        $projects = $this->getProjects();
        //Search the age of all beneficiary in all project and push the result of the query in a array
        foreach ($projects as $project) {
            $results = [];
            foreach ($project->getHouseholds() as $household) {
                foreach ($household->getBeneficiaries() as $beneficiary) {
                    $repository = $this->em->getRepository(Beneficiary::class);
                    $qb = $repository->createQueryBuilder('b')
                                        ->leftjoin('b.household', 'h')
                                        ->where('h.id = :household')
                                            ->setParameter('household', $household->getId())
                                        ->andWhere('b.id = :beneficiary')
                                            ->setParameter('beneficiary', $beneficiary->getId())
                                        ->select("TIMESTAMPDIFF(YEAR, b.dateOfBirth, CURRENT_DATE()) as value");
                    $result = $qb->getQuery()->getArrayResult();
                    if ((sizeof($result)) > 0) {
                        array_push($results, $result);
                    }
                }
            }
            //Call a function to sort age in corresponding interval
            $byInterval = $this->sortByAge($results);
            foreach ($byInterval as $ageBreakdown) {
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
                } catch (Exception $e) {
                    $this->em->getConnection()->rollback();
                    throw $e;
                }
            }
        }
    }

    /**
     * Use to sort beneficiary by age interval
     * If the age is in the interval, increment the corresponding counter
     * @param $ages
     * @return array
     */
    public function sortByAge($ages)
    {
        $byInterval= [];
        foreach ($ages as $age) {
            foreach ($age as $value) {
                if ((int)$value['value'] > 0 && (int)$value['value'] <= 10) {
                    if (!array_key_exists('zeroTen', $byInterval)) {
                        $byInterval['zeroTen'] = [];
                        $byInterval['zeroTen']['unity'] = '[0-10]';
                        $byInterval['zeroTen']['value'] = 1;
                    } else {
                        $byInterval['zeroTen']['value'] += 1;
                    }
                    break 1;
                } elseif ((int)$value['value'] > 10 && (int)$value['value'] <= 18) {
                    if (!array_key_exists('TenEighteen', $byInterval)) {
                        $byInterval['TenEighteen'] = [];
                        $byInterval['TenEighteen']['unity'] = '[11-18]';
                        $byInterval['TenEighteen']['value'] = 1;
                    } else {
                        $byInterval['TenEighteen']['value'] += 1;
                    }
                    break 1;
                } elseif ((int)$value['value'] > 18 && (int)$value['value'] <= 30) {
                    if (!array_key_exists('EighteenThirty', $byInterval)) {
                        $byInterval['EighteenThirty'] = [];
                        $byInterval['EighteenThirty']['unity'] = '[19-30]';
                        $byInterval['EighteenThirty']['value'] = 1;
                    } else {
                        $byInterval['EighteenThirty']['value'] += 1;
                    }
                    break 1;
                } elseif ((int)$value['value'] > 30 && (int)$value['value'] <= 50) {
                    if (!array_key_exists('ThirtyFifty', $byInterval)) {
                        $byInterval['ThirtyFifty'] = [];
                        $byInterval['ThirtyFifty']['unity'] = '[31-50]';
                        $byInterval['ThirtyFifty']['value'] = 1;
                    } else {
                        $byInterval['ThirtyFifty']['value'] += 1;
                    }
                    break 1;
                } elseif ((int)$value['value'] > 50 && (int)$value['value'] <= 70) {
                    if (!array_key_exists('FiftySeventy', $byInterval)) {
                        $byInterval['FiftySeventy'] = [];
                        $byInterval['FiftySeventy']['unity'] = '[51-70]';
                        $byInterval['FiftySeventy']['value'] = 1;
                    } else {
                        $byInterval['FiftySeventy']['value'] += 1;
                    }
                    break 1;
                } else {
                    if (!array_key_exists('MoreSeventy', $byInterval)) {
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
