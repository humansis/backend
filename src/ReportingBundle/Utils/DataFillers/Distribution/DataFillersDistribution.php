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

    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var
     */
    private $repository;


    /**
     * DataFillersDistribution constructor.
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
    public function BMS_Distribution_TDV() {
        $this->em->getConnection()->beginTransaction();
        try {
            $this->repository = $this->em->getRepository(Commodity::class);
            $qb = $this->repository->createQueryBuilder('c')
                                   ->leftjoin('c.distributionData', 'dd')
                                   ->select('c.value AS value', 'c.id as id', 'c.unit as unity', 'dd.id as distribution');
            $commodityValues = $qb->getQuery()->getArrayResult();
            $results = [];
            foreach($commodityValues as $commodityValue) {
                $valueFind = false;
                if (sizeof($results) === 0) {
                    array_push($results, $commodityValue);
                }else {
                    foreach($results as &$dataResult) {
                        if ($commodityValue['distribution'] === $dataResult['distribution']) {
                            $dataResult['value'] = $dataResult['value'] + $commodityValue['value'];
                            $valueFind = true;
                        }
                    }
                    if (!$valueFind) {
                        array_push($results, $commodityValue);
                    }
                }
            }
            $reference = $this->getReferenceId("BMS_Distribution_TDV");
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
                                   ->leftjoin('c.modalityType', 'm')
                                   ->leftjoin('m.modality', 'mt')
                                   ->select("CONCAT(mt.name, '-', m.name) AS value", 'dd.id as distribution');
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
        //Get all distribution beneficiary
        $this->repository = $this->em->getRepository(DistributionBeneficiary::class);
        $beneficiaries = $this->repository->findAll();

        //Get all distribution
        $this->repository = $this->em->getRepository(DistributionData::class);
        $distributions = $this->repository->findAll();

        //Search the age of all beneficiary in all distribution and push the result of the query in a array
        foreach($distributions as $distribution) {
            $results = [];
            foreach($beneficiaries as $beneficiary) {
                if( $distribution->getId() === $beneficiary->getDistributionData()->getId()) {
                    $this->repository = $this->em->getRepository(DistributionBeneficiary::class);
                    $qb = $this->repository->createQueryBuilder('db')
                                        ->leftjoin('db.beneficiary', 'b')
                                        ->leftjoin('db.distributionData', 'dd')
                                        ->where('b.id = :beneficiary')
                                            ->setParameter('beneficiary', $beneficiary->getBeneficiary()->getId())
                                        ->andWhere('dd.id = :distribution')
                                            ->setParameter('distribution', $distribution->getId())
                                        ->select("TIMESTAMPDIFF(YEAR, b.dateOfBirth, CURRENT_DATE()) as value", 'dd.id as distribution');
                    $result = $qb->getQuery()->getArrayResult();
                    if((sizeof($result)) > 0) {
                        array_push($results, $result);
                    }
                }
            }
            //Call function to sort all age in different interval
            $byInterval = $this->sortByAge($results);
            
            foreach ($byInterval as $ageBreakdown) 
            {
                $this->em->getConnection()->beginTransaction();
                try {
                    $reference = $this->getReferenceId("BMS_Distribution_AB");
                    $new_value = new ReportingValue();
                    $new_value->setValue($ageBreakdown['value']);
                    $new_value->setUnity($ageBreakdown['unity']);
                    $new_value->setCreationDate(new \DateTime());

                    $this->em->persist($new_value);
                    $this->em->flush();

                    $this->repository = $this->em->getRepository(DistributionData::class);
                    $distribution = $this->repository->findOneBy(['id' => $results[0][0]['distribution']]); 

                    $new_reportingDistribution = new ReportingDistribution();
                    $new_reportingDistribution->setIndicator($reference);
                    $new_reportingDistribution->setValue($new_value);
                    $new_reportingDistribution->setDistribution($distribution);

                    $this->em->persist($new_reportingDistribution);
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
        
        //Get all vulnerability criterion
        $this->repository = $this->em->getRepository(VulnerabilityCriterion::class);
        $vulnerabilityCriterion = $this->repository->findAll();


        //Get all dsitribution beneficiary
        $this->repository = $this->em->getRepository(DistributionBeneficiary::class);
        $beneficiaries = $this->repository->findAll();

        //Get all distribution
        $this->repository = $this->em->getRepository(DistributionData::class);
        $distributions = $this->repository->findAll();

        $results = [];

        //Search all vulnerability criterion foreach beneficiary in a distribution and count the vulnerability served
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
                        //count the number of vulnerability served find in the distribution
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
        
        //Get all vulnerability Criterion
        $this->repository = $this->em->getRepository(VulnerabilityCriterion::class);
        $vulnerabilityCriterion = $this->repository->findAll();

        //get all distribution beneficiary
        $this->repository = $this->em->getRepository(DistributionBeneficiary::class);
        $beneficiaries = $this->repository->findAll();

        //get all distribution
        $this->repository = $this->em->getRepository(DistributionData::class);
        $distributions = $this->repository->findAll();

        $results = [];

        //Search all vulnerability criterion foreach beneficiary in a distribution and put the result in a array
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
                        ->select("count(db.id) as value",'vc.fieldString as unity', 'dd.id as distribution')
                        ->groupBy('unity, distribution');
                        $result = $qb->getQuery()->getArrayResult();
                        if((sizeof($result)) > 0) {
                            array_push($results, $result);
                        }      
                    }
                }
            }
            //Sort the vulnerability criterion and count the number of entry find foreach of them
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
            $results = [];

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
