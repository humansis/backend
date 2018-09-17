<?php

namespace ReportingBundle\Utils\DataFillers\Country;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Validator\Constraints\DateTime;

use ReportingBundle\Utils\DataFillers\DataFillers;
use ReportingBundle\Entity\ReportingIndicator;
use ReportingBundle\Entity\ReportingValue;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\Beneficiary;
use CommonBundle\Entity\Location;
use ReportingBundle\Entity\ReportingCountry;
use ProjectBundle\Entity\Project;
use DistributionBundle\Entity\DistributionData;
use \TransactionBundle\Entity\Transaction;

class DataFillersCountry extends DataFillers
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
     * DataFillersCountry constructor.
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
     * Fill in ReportingValue and ReportingCountry with total households
     */
    public function BMS_Country_TH() {
        $this->em->getConnection()->beginTransaction();
        try {
            $this->repository = $this->em->getRepository(Household::class);
            $qb = $this->repository->createQueryBuilder('h');
            $qb = $this->getCountry($qb, 'h');
            $qb ->select('Distinct count(h)as value', "adm1.countryISO3 as CountryAdm1", "adm1d.countryISO3 as CountryAdm2", "adm1c.countryISO3 as CountryAdm3", "adm1b.countryISO3 as CountryAdm4")
                ->groupBy('CountryAdm1', 'CountryAdm2', 'CountryAdm3', 'CountryAdm4');
            $results = $this->sortByCountry($qb->getQuery()->getArrayResult());
            $reference = $this->getReferenceId("BMS_Country_TH");
            foreach ($results as $result) 
            {
                $new_value = new ReportingValue();
                $new_value->setValue($result['value']);
                $new_value->setUnity('household');
                $new_value->setCreationDate(new \DateTime());

                $this->em->persist($new_value);
                $this->em->flush();

                $new_reportingCountry = new ReportingCountry();
                $new_reportingCountry->setIndicator($reference);
                $new_reportingCountry->setValue($new_value);
                $new_reportingCountry->setcountry($result['country']);

                $this->em->persist($new_reportingCountry);
                $this->em->flush();   
            }
            $this->em->getConnection()->commit();
        }catch (Exception $e) {
            $this->em->getConnection()->rollback();
            throw $e;
        }
    }

    /**
     * Fill in ReportingValue and ReportingCountry with active projects
     */
    public function BMS_Country_AP() {
        $this->em->getConnection()->beginTransaction();
        try {
            $this->repository = $this->em->getRepository(Project::class);
            $qb = $this->repository->createQueryBuilder('p')
                                   ->where("DATE_FORMAT(p.endDate, '%Y-%m-%d') < DATE_FORMAT(CURRENT_DATE(), '%Y-%m-%d') ")
                                   ->select('count(p) AS value', 'p.iso3 AS country')
                                   ->groupBy('country');
            $results = $qb->getQuery()->getArrayResult();

            $reference = $this->getReferenceId("BMS_Country_AP");
            foreach ($results as $result) 
            {
                $new_value = new ReportingValue();
                $new_value->setValue($result['value']);
                $new_value->setUnity('active project');
                $new_value->setCreationDate(new \DateTime());

                $this->em->persist($new_value);
                $this->em->flush();

                $new_reportingCountry = new ReportingCountry();
                $new_reportingCountry->setIndicator($reference);
                $new_reportingCountry->setValue($new_value);
                $new_reportingCountry->setcountry($result['country']);

                $this->em->persist($new_reportingCountry);
                $this->em->flush();   
            }
            $this->em->getConnection()->commit();
        }catch (Exception $e) {
            $this->em->getConnection()->rollback();
            throw $e;
        }
    }

     /**
     * Fill in ReportingValue and ReportingCountry with total funding
     */
    public function BMS_Country_TF() {
        $this->em->getConnection()->beginTransaction();
        try {
            $this->repository = $this->em->getRepository(Project::class);
            $qb = $this->repository->createQueryBuilder('p')
                                   ->select('SUM(p.value) AS value', 'p.iso3 AS country')
                                   ->groupBy('country');
            $results = $qb->getQuery()->getArrayResult();

            $reference = $this->getReferenceId("BMS_Country_TF");
            foreach ($results as $result) 
            {
                $new_value = new ReportingValue();
                $new_value->setValue($result['value']);
                $new_value->setUnity('dollar');
                $new_value->setCreationDate(new \DateTime());

                $this->em->persist($new_value);
                $this->em->flush();

                $new_reportingCountry = new ReportingCountry();
                $new_reportingCountry->setIndicator($reference);
                $new_reportingCountry->setValue($new_value);
                $new_reportingCountry->setcountry($result['country']);

                $this->em->persist($new_reportingCountry);
                $this->em->flush();   
            }
            $this->em->getConnection()->commit();
        }catch (Exception $e) {
            $this->em->getConnection()->rollback();
            throw $e;
        }
    }

    /**
     * Fill in ReportingValue and ReportingCountry with enrolled beneficiaries
     */
    public function BMS_Country_EB() {
        $this->em->getConnection()->beginTransaction();
        try {
            $this->repository = $this->em->getRepository(Beneficiary::class);
            $qb = $this->repository->createQueryBuilder('b')
                                   ->leftjoin('b.household', 'h');
            $qb = $this->getCountry($qb, 'h');
            $qb ->select('Distinct count(b.id) as value', "adm1.countryISO3 as CountryAdm1", "adm1d.countryISO3 as CountryAdm2", "adm1c.countryISO3 as CountryAdm3", "adm1b.countryISO3 as CountryAdm4")
                ->groupBy('CountryAdm1', 'CountryAdm2', 'CountryAdm3', 'CountryAdm4');
            $results = $this->sortByCountry($qb->getQuery()->getArrayResult());
            $reference = $this->getReferenceId("BMS_Country_EB");
            foreach ($results as $result) 
            {
                $new_value = new ReportingValue();
                $new_value->setValue($result['value']);
                $new_value->setUnity('enrolled beneficiary');
                $new_value->setCreationDate(new \DateTime());

                $this->em->persist($new_value);
                $this->em->flush();

                $new_reportingCountry = new ReportingCountry();
                $new_reportingCountry->setIndicator($reference);
                $new_reportingCountry->setValue($new_value);
                $new_reportingCountry->setcountry($result['country']);

                $this->em->persist($new_reportingCountry);
                $this->em->flush();   
            }
            $this->em->getConnection()->commit();
        }catch (Exception $e) {
            $this->em->getConnection()->rollback();
            throw $e;
        }
    }

     /**
     * Fill in ReportingValue and ReportingCountry with total number of distributions
     */
    public function BMS_Country_TND() {
        $this->em->getConnection()->beginTransaction();
        try {
            $this->repository = $this->em->getRepository(DistributionData::class);
            $qb = $this->repository->createQueryBuilder('dd');
            $qb = $this->getCountry($qb, 'dd');
            $qb ->select('count(dd.id) as value', "adm1.countryISO3 as CountryAdm1", "adm1d.countryISO3 as CountryAdm2", "adm1c.countryISO3 as CountryAdm3", "adm1b.countryISO3 as CountryAdm4")
                ->groupBy('CountryAdm1', 'CountryAdm2', 'CountryAdm3', 'CountryAdm4');
            $results = $this->sortByCountry($qb->getQuery()->getArrayResult());
            $reference = $this->getReferenceId("BMS_Country_TND");
            foreach ($results as $result) 
            {
                $new_value = new ReportingValue();
                $new_value->setValue($result['value']);
                $new_value->setUnity('distributions');
                $new_value->setCreationDate(new \DateTime());

                $this->em->persist($new_value);
                $this->em->flush();

                $new_reportingCountry = new ReportingCountry();
                $new_reportingCountry->setIndicator($reference);
                $new_reportingCountry->setValue($new_value);
                $new_reportingCountry->setcountry($result['country']);

                $this->em->persist($new_reportingCountry);
                $this->em->flush();   
            }
            $this->em->getConnection()->commit();
        }catch (Exception $e) {
            $this->em->getConnection()->rollback();
            throw $e;
        }
    }

    /**
     * TODO: Add group by country
     * Fill in ReportingValue and ReportingCountry with the total of completed transactions
     */
    public function BMS_Country_TTC() {
        $this->em->getConnection()->beginTransaction();
        try {
            $this->repository = $this->em->getRepository(Transaction::class);
            $qb = $this->repository->createQueryBuilder('t')
                                   ->where ('t.pickupdate < CURRENT_DATE() ')
                                   ->select('count(t.id) AS value');
            $results = $qb->getQuery()->getArrayResult();
            $reference = $this->getReferenceId("BMS_Country_TTC");
            foreach ($results as $result) 
            {
                $new_value = new ReportingValue();
                $new_value->setValue($result['value']);
                $new_value->setUnity('transactions completed');
                $new_value->setCreationDate(new \DateTime());

                $this->em->persist($new_value);
                $this->em->flush();

                $new_reportingCountry = new ReportingCountry();
                $new_reportingCountry->setIndicator($reference);
                $new_reportingCountry->setValue($new_value);
                $new_reportingCountry->setcountry('KHM');

                $this->em->persist($new_reportingCountry);
                $this->em->flush();   
            }
            $this->em->getConnection()->commit();
        }catch (Exception $e) {
            $this->em->getConnection()->rollback();
            throw $e;
        }
    }


    /**
     * Set the country iso3 in the query on Household (with alias 'hh{id}'
     *
     * @param QueryBuilder $qb
     * @param string $alias
     * @return QueryBuilder
     */
    public function getCountry($qb, string $alias)
    {
        $qb->leftJoin("$alias.location", "l")

            ->leftJoin("l.adm1", "adm1")

            ->leftJoin("l.adm4", "adm4")
            ->leftJoin("adm4.adm3", "adm3b")
            ->leftJoin("adm3b.adm2", "adm2b")
            ->leftJoin("adm2b.adm1", "adm1b")

            ->leftJoin("l.adm3", "adm3")
            ->leftJoin("adm3.adm2", "adm2c")
            ->leftJoin("adm2c.adm1", "adm1c")

            ->leftJoin("l.adm2", "adm2")
            ->leftJoin("adm2.adm1", "adm1d");
            return $qb;
    }

    /**
     * search after delete null adm which data as the same adm and add them together
     * @param array $byCountry
     * @return array
     */
    public function sortByCountry(Array $byCountry) {
        $results = [];
        $withoutNull = $this->deleteNullAdm($byCountry);
        foreach($withoutNull as $data) {
            if (empty($results)) {
                array_push($results, $data);
            }
            else {
                $valueFound = false;
                foreach($results as &$result){
                    if ($result['country'] === $data['country']) {
                        $valueFound = true;
                        $result['value'] = $result['value'] + $data['value'];
                    }
                }
                if (!$valueFound){
                    array_push($results, $data);
                }
            }
           
        }
        return $results;
    }

    /**
     * Search which adm isn't empty and keep only this adm
     * @param $byCountry
     * @return array
     */
    public function deleteNullAdm($byCountry) {
        $results = [];
        foreach($byCountry as $data) {
            if(!empty($data['CountryAdm1'])) {
                $result = [
                    'value' => $data['value'],
                    'country' => $data['CountryAdm1'],
                ]; 
                array_push($results, $result);
            } 
            else if(!empty($data['CountryAdm2'])) {
                $result = [
                    'value' => $data['value'],
                    'country' => $data['CountryAdm2'],
                ]; 
                array_push($results, $result);
            } 
            else if(!empty($data['CountryAdm3'])) {
                $result = [
                    'value' => $data['value'],
                    'country' => $data['CountryAdm3'],
                ]; 
                array_push($results, $result);
            } 
            else if(!empty($data['CountryAdm4'])) {
                $result = [
                    'value' => $data['value'],
                    'country' => $data['CountryAdm4'],
                ]; 
                array_push($results, $result);
            } 
        }
        return $results;
        
    }
}
