<?php

namespace ReportingBundle\Utils\DataFillers\Distribution;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Validator\Constraints\DateTime;

use ReportingBundle\Utils\DataFillers\DataFillers;
use ReportingBundle\Entity\ReportingIndicator;
use ReportingBundle\Entity\ReportingValue;
use ReportingBundle\Entity\ReportingDistribution;
use \DistributionBundle\Entity\DistributionBeneficiary;
use \DistributionBundle\Entity\DistributionData;
use \DistributionBundle\Entity\Commodity;
use \BeneficiaryBundle\Entity\VulnerabilityCriterion;


class DataFillersDistribution  extends DataFillers
{

    private $em;
    private $repository;


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
     * Fill in ReportingValue and ReportingDistribution with total of enrolled beneficiaires
     */
    public function BMS_Distribution_NEB() {
        $this->em->getConnection()->beginTransaction();
        try {
            $this->repository = $this->em->getRepository(DistributionBeneficiary::class);
            $qb = $this->repository->createQueryBuilder('db')
                                   ->leftjoin('db.distributionData', 'dd')
                                   ->select('count(db.id) AS value', 'dd.id as distribution')
                                   ->groupBy('distribution');
            $results = $qb->getQuery()->getArrayResult();
            $reference = $this->getReferenceId("BMS_Distribution_NEB");
            foreach ($results as $result) 
            {
                $new_value = new ReportingValue();
                $new_value->setValue($result['value']);
                $new_value->setUnity('enrolled beneficiaries');
                $new_value->setCreationDate(new \DateTime());

                $this->em->persist($new_value);
                $this->em->flush();

                $this->repository = $this->em->getRepository(DistributionData::class);
                $distribution = $this->repository->findOneBy(['id' => $result['distribution']]); 

                $new_reportingDistribution = new ReportingDistribution();
                $new_reportingDistribution->setIndicator($reference);
                $new_reportingDistribution->setValue($new_value);
                $new_reportingDistribution->setDistribution($distribution);

                $this->em->persist($new_reportingDistribution);
                $this->em->flush();   
            }
            $this->em->getConnection()->commit();
        }catch (Exception $e) {
            $this->em->getConnection()->rollback();
            throw $e;
        }
    }

     /**
     * Fill in ReportingValue and ReportingDistribution with total value of distribution
     */
    public function BMS_Distribution_TTV() {
        $this->em->getConnection()->beginTransaction();
        try {
            $this->repository = $this->em->getRepository(Commodity::class);
            $qb = $this->repository->createQueryBuilder('c')
                                   ->leftjoin('c.distributionData', 'dd')
                                   ->select('c.value AS value', 'c.unit as unity', 'dd.id as distribution');
            $results = $qb->getQuery()->getArrayResult();
            $reference = $this->getReferenceId("BMS_Distribution_TTV");
            foreach ($results as $result) 
            {
                $new_value = new ReportingValue();
                $new_value->setValue($result['value']);
                $new_value->setUnity($result['unity']);
                $new_value->setCreationDate(new \DateTime());

                $this->em->persist($new_value);
                $this->em->flush();

                $this->repository = $this->em->getRepository(DistributionData::class);
                $distribution = $this->repository->findOneBy(['id' => $result['distribution']]); 

                $new_reportingDistribution = new ReportingDistribution();
                $new_reportingDistribution->setIndicator($reference);
                $new_reportingDistribution->setValue($new_value);
                $new_reportingDistribution->setDistribution($distribution);

                $this->em->persist($new_reportingDistribution);
                $this->em->flush();   
            }
            $this->em->getConnection()->commit();
        }catch (Exception $e) {
            $this->em->getConnection()->rollback();
            throw $e;
        }
    }

    /**
     * Fill in ReportingValue and ReportingDistribution with modality
     */
    public function BMS_Distribution_M() {
        $this->em->getConnection()->beginTransaction();
        try {
            $this->repository = $this->em->getRepository(Commodity::class);
            $qb = $this->repository->createQueryBuilder('c')
                                   ->leftjoin('c.distributionData', 'dd')
                                   ->select("CONCAT(c.modality, '-', c.type) AS value", 'dd.id as distribution');
            $results = $qb->getQuery()->getArrayResult();
            $reference = $this->getReferenceId("BMS_Distribution_M");
            foreach ($results as $result) 
            {
                $new_value = new ReportingValue();
                $new_value->setValue($result['value']);
                $new_value->setUnity('modality');
                $new_value->setCreationDate(new \DateTime());

                $this->em->persist($new_value);
                $this->em->flush();

                $this->repository = $this->em->getRepository(DistributionData::class);
                $distribution = $this->repository->findOneBy(['id' => $result['distribution']]); 

                $new_reportingDistribution = new ReportingDistribution();
                $new_reportingDistribution->setIndicator($reference);
                $new_reportingDistribution->setValue($new_value);
                $new_reportingDistribution->setDistribution($distribution);

                $this->em->persist($new_reportingDistribution);
                $this->em->flush();   
            }
            $this->em->getConnection()->commit();
        }catch (Exception $e) {
            $this->em->getConnection()->rollback();
            throw $e;
        }
    }

    /**
     * Fill in ReportingValue and ReportingDistribution with age breakdown
     */
    public function BMS_Distribution_AB() {
        $this->em->getConnection()->beginTransaction();
        try {
            $this->repository = $this->em->getRepository(DistributionBeneficiary::class);
            $qb = $this->repository->createQueryBuilder('db')
                                   ->leftjoin('db.beneficiary', 'b')
                                   ->leftjoin('db.distributionData', 'dd')
                                   ->select("TIMESTAMPDIFF(YEAR, b.dateOfBirth, CURRENT_DATE()) as value", 'dd.id as distribution');
            $results = $qb->getQuery()->getArrayResult();
            $reference = $this->getReferenceId("BMS_Distribution_AB");
            foreach ($results as $result) 
            {
                $new_value = new ReportingValue();
                $new_value->setValue($result['value']);
                $new_value->setUnity('ans');
                $new_value->setCreationDate(new \DateTime());

                $this->em->persist($new_value);
                $this->em->flush();

                $this->repository = $this->em->getRepository(DistributionData::class);
                $distribution = $this->repository->findOneBy(['id' => $result['distribution']]); 

                $new_reportingDistribution = new ReportingDistribution();
                $new_reportingDistribution->setIndicator($reference);
                $new_reportingDistribution->setValue($new_value);
                $new_reportingDistribution->setDistribution($distribution);

                $this->em->persist($new_reportingDistribution);
                $this->em->flush();   
            }
            $this->em->getConnection()->commit();
        }catch (Exception $e) {
            $this->em->getConnection()->rollback();
            throw $e;
        }
    }

    /**
     * Fill in ReportingValue and ReportingDistribution with number of men
     */
    public function BMSU_Distribution_NM() {
        $this->em->getConnection()->beginTransaction();
        try {
            $this->repository = $this->em->getRepository(DistributionBeneficiary::class);
            $qb = $this->repository->createQueryBuilder('db')
                                   ->leftjoin('db.beneficiary', 'b')
                                   ->leftjoin('db.distributionData', 'dd')
                                   ->where('b.gender = :gender')
                                        ->setParameter('gender', 1)
                                   ->select("count(b.id) as value", 'dd.id as distribution')
                                   ->groupBy('distribution');
            $results = $qb->getQuery()->getArrayResult();
            $reference = $this->getReferenceId("BMSU_Distribution_NM");
            foreach ($results as $result) 
            {
                $new_value = new ReportingValue();
                $new_value->setValue($result['value']);
                $new_value->setUnity('Men');
                $new_value->setCreationDate(new \DateTime());

                $this->em->persist($new_value);
                $this->em->flush();

                $this->repository = $this->em->getRepository(DistributionData::class);
                $distribution = $this->repository->findOneBy(['id' => $result['distribution']]); 

                $new_reportingDistribution = new ReportingDistribution();
                $new_reportingDistribution->setIndicator($reference);
                $new_reportingDistribution->setValue($new_value);
                $new_reportingDistribution->setDistribution($distribution);

                $this->em->persist($new_reportingDistribution);
                $this->em->flush();   
            }
            $this->em->getConnection()->commit();
        }catch (Exception $e) {
            $this->em->getConnection()->rollback();
            throw $e;
        }
    }

     /**
     * Fill in ReportingValue and ReportingDistribution with number of women
     */
    public function BMSU_Distribution_NW() {
        $this->em->getConnection()->beginTransaction();
        try {
            $this->repository = $this->em->getRepository(DistributionBeneficiary::class);
            $qb = $this->repository->createQueryBuilder('db')
                                   ->leftjoin('db.beneficiary', 'b')
                                   ->leftjoin('db.distributionData', 'dd')
                                   ->where('b.gender = :gender')
                                        ->setParameter('gender', 0)
                                   ->select("count(b.id) as value", 'dd.id as distribution')
                                   ->groupBy('distribution');
            $results = $qb->getQuery()->getArrayResult();
            $reference = $this->getReferenceId("BMSU_Distribution_NW");
            foreach ($results as $result) 
            {
                $new_value = new ReportingValue();
                $new_value->setValue($result['value']);
                $new_value->setUnity('Women');
                $new_value->setCreationDate(new \DateTime());

                $this->em->persist($new_value);
                $this->em->flush();

                $this->repository = $this->em->getRepository(DistributionData::class);
                $distribution = $this->repository->findOneBy(['id' => $result['distribution']]); 

                $new_reportingDistribution = new ReportingDistribution();
                $new_reportingDistribution->setIndicator($reference);
                $new_reportingDistribution->setValue($new_value);
                $new_reportingDistribution->setDistribution($distribution);

                $this->em->persist($new_reportingDistribution);
                $this->em->flush();   
            }
            $this->em->getConnection()->commit();
        }catch (Exception $e) {
            $this->em->getConnection()->rollback();
            throw $e;
        }
    }

     /**
     * Fill in ReportingValue and ReportingDistribution with total of vulnerability served
     */
    public function BMSU_Distribution_TVS() {
        
        $this->repository = $this->em->getRepository(VulnerabilityCriterion::class);
        $vulnerabilityCriterion = $this->repository->findAll();

        $this->repository = $this->em->getRepository(DistributionBeneficiary::class);
        $beneficiaries = $this->repository->findAll();

        $this->repository = $this->em->getRepository(DistributionData::class);
        $distributions = $this->repository->findAll();

        $results = [];

        foreach($distributions as $distribution) {
            foreach($beneficiaries as $beneficiary) {
                if( $distribution->getId() === $beneficiary->getDistributionData()->getId()) {
                    $this->repository = $this->em->getRepository(DistributionBeneficiary::class);
                    $qb = $this->repository->createQueryBuilder('db')
                                            ->leftjoin('db.beneficiary', 'b')
                                            ->leftjoin('db.distributionData', 'dd')
                                            ->leftjoin('b.vulnerabilityCriteria', 'vc')
                                            ->where('b.id = :beneficiary')
                                                ->setParameter('beneficiary', $beneficiary->getBeneficiary()->getId())
                                            ->andWhere('dd.id = :distribution')
                                                ->setParameter('distribution', $distribution->getId());
                    foreach($vulnerabilityCriterion as $vulnerabilityCriteria) { 
                    $qb ->andWhere('vc.id = :criteria')
                            ->setParameter('criteria', $vulnerabilityCriteria->getId())
                        ->select("count(b.id) as value", 'dd.id as distribution')
                        ->groupBy('distribution');
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
            }
            $this->em->getConnection()->beginTransaction();
            try {
                $reference = $this->getReferenceId("BMSU_Distribution_TVS");
                foreach ($results as $result) 
                {
                    $new_value = new ReportingValue();
                    $new_value->setValue($result['value']);
                    $new_value->setUnity('vulnerability served');
                    $new_value->setCreationDate(new \DateTime());
    
                    $this->em->persist($new_value);
                    $this->em->flush();
    
                    $this->repository = $this->em->getRepository(DistributionData::class);
                    $distribution = $this->repository->findOneBy(['id' => $result['distribution']]); 
    
                    $new_reportingDistribution = new ReportingDistribution();
                    $new_reportingDistribution->setIndicator($reference);
                    $new_reportingDistribution->setValue($new_value);
                    $new_reportingDistribution->setDistribution($distribution);
    
                    $this->em->persist($new_reportingDistribution);
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
     * Fill in ReportingValue and ReportingDistribution with total of vulnerability served by vulnerability
     */
    public function BMSU_Distribution_TVSV() {
        
        $this->repository = $this->em->getRepository(VulnerabilityCriterion::class);
        $vulnerabilityCriterion = $this->repository->findAll();

        $this->repository = $this->em->getRepository(DistributionBeneficiary::class);
        $beneficiaries = $this->repository->findAll();

        $this->repository = $this->em->getRepository(DistributionData::class);
        $distributions = $this->repository->findAll();

        $results = [];

        foreach($distributions as $distribution) {
            $byDistribution = [];
            foreach($beneficiaries as $beneficiary) {
                if( $distribution->getId() === $beneficiary->getDistributionData()->getId()) {
                    $this->repository = $this->em->getRepository(DistributionBeneficiary::class);
                    $qb = $this->repository->createQueryBuilder('db')
                                        ->leftjoin('db.beneficiary', 'b')
                                        ->leftjoin('db.distributionData', 'dd')
                                        ->leftjoin('b.vulnerabilityCriteria', 'vc')
                                        ->where('b.id = :beneficiary')
                                            ->setParameter('beneficiary', $beneficiary->getBeneficiary()->getId())
                                        ->andWhere('dd.id = :distribution')
                                            ->setParameter('distribution', $distribution->getId());
                    foreach($vulnerabilityCriterion as $vulnerabilityCriteria) { 
                    $qb ->andWhere('vc.id = :criteria')
                            ->setParameter('criteria', $vulnerabilityCriteria->getId())
                        ->select("count(db.id) as value",'vc.value as unity', 'dd.id as distribution')
                        ->groupBy('unity, distribution');
                        $result = $qb->getQuery()->getArrayResult();
                        if((sizeof($result)) > 0) {
                            array_push($results, $result);
                        }      
                    }
                }
            }
            $found = false;
            foreach($results as $result) {
                foreach($result as $value) {
                    if ((sizeof($byDistribution)) == 0) {
                        array_push($byDistribution, $value );
                    } else {
                        for ($i=0; $i<(sizeof($byDistribution)); $i++) {
                            if ($byDistribution[$i]['unity'] == $value['unity']) {
                                (int)$byDistribution[$i]['value'] += (int)$value['value'];
                                $found = true;
                                break 1;
                            }
                        }
                        if (!$found) {
                            array_push($byDistribution, $value);
                        }
                        $found = false;
                    }
                }
            }
            $this->em->getConnection()->beginTransaction();
            try {
                $reference = $this->getReferenceId("BMSU_Distribution_TVSV");
                foreach ($byDistribution as $result) 
                {
                    $new_value = new ReportingValue();
                    $new_value->setValue($result['value']);
                    $new_value->setUnity($result['unity']);
                    $new_value->setCreationDate(new \DateTime());
    
                    $this->em->persist($new_value);
                    $this->em->flush();
    
                    $this->repository = $this->em->getRepository(DistributionData::class);
                    $distribution = $this->repository->findOneBy(['id' => $result['distribution']]); 
    
                    $new_reportingDistribution = new ReportingDistribution();
                    $new_reportingDistribution->setIndicator($reference);
                    $new_reportingDistribution->setValue($new_value);
                    $new_reportingDistribution->setDistribution($distribution);
    
                    $this->em->persist($new_reportingDistribution);
                    $this->em->flush();   
                }
                $this->em->getConnection()->commit();
                
            }catch (Exception $e) {
                $this->em->getConnection()->rollback();
                throw $e;
            }
            $byDistribution = [];
            $results = [];

        }
    }



}
