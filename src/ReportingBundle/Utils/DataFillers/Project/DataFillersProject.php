<?php

namespace ReportingBundle\Utils\DataFillers\Project;

use ReportingBundle\Utils\Model\IndicatorInterface;

use \BeneficiaryBundle\Entity\Beneficiary;
use \ReportingBundle\Entity\ReportingIndicator;
use \ReportingBundle\Entity\ReportingValue;
use \ReportingBundle\Entity\ReportingProject;

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

    /**
     * Fill in ReportingValue and ReportingCountry with number of Men
     */
    public function BMSU_Project_NM() {
        $this->em->getConnection()->beginTransaction();
        try {
            $this->repository = $this->em->getRepository(Beneficiary::class);
            $qb = $this->repository->createQueryBuilder('b')
                                   ->leftjoin('b.household', 'h')
                                //    ->leftjoin('p.id', 'p')
                                   ->where('b.gender = :gender')
                                        ->setParameter('gender', 1)
                                   ->select('Distinct count(b) AS value');
                                //    ->groupBy('project');
            $results = $qb->getQuery()->getArrayResult();
            dump($results);
            // $reference = $this->getReferenceId("BMS_Project_NM");
            // foreach ($results as $result) 
            // {
            //     $new_value = new ReportingValue();
            //     $new_value->setValue($result['value']);
            //     $new_value->setUnity('men');
            //     $new_value->setCreationDate(new \DateTime());

            //     $this->em->persist($new_value);
            //     $this->em->flush();

            //     $this->repository = $this->em->getRepository(Project::class);
            //     $project = $this->repository->findOneBy(['id' => $result['project']]); 

            //     $new_reportingProject = new ReportingProject();
            //     $new_reportingProject->setIndicator($reference);
            //     $new_reportingProject->setValue($new_value);
            //     $new_reportingProject->setProject($project);

            //     $this->em->persist($new_reportingProject);
            //     $this->em->flush();   
            // }
            $this->em->getConnection()->commit();
        }catch (Exception $e) {
            $this->em->getConnection()->rollback();
            throw $e;
        }
    }



}
