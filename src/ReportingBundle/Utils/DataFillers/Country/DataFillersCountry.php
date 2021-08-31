<?php

namespace ReportingBundle\Utils\DataFillers\Country;

use Doctrine\ORM\EntityManager;

use ReportingBundle\Utils\DataFillers\DataFillers;
use ReportingBundle\Entity\ReportingIndicator;
use ReportingBundle\Entity\ReportingValue;
use BeneficiaryBundle\Entity\Household;
use BeneficiaryBundle\Entity\Beneficiary;
use CommonBundle\Entity\Location;
use ReportingBundle\Entity\ReportingCountry;
use ProjectBundle\Entity\Project;
use DistributionBundle\Entity\Assistance;
use \TransactionBundle\Entity\Transaction;
use Exception;

/**
 * Class DataFillersCountry
 * @package ReportingBundle\Utils\DataFillers\Country
 */
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
    public function getReferenceId(string $code)
    {
        $this->repository = $this->em->getRepository(ReportingIndicator::class);
        $qb = $this->repository->createQueryBuilder('ri')
            ->Where('ri.code = :code')
            ->setParameter('code', $code);
        return $qb->getQuery()->getSingleResult();
    }

    /**
     * Fill in ReportingValue and ReportingCountry with total households
     */
    public function BMS_Country_TH()
    {
        $this->em->getConnection()->beginTransaction();
        try {
            $this->repository = $this->em->getRepository(Household::class);
            $qb = $this->repository->createQueryBuilder('hh');

            // Get household country as 'country'
            $this->repository->getHouseholdCountry($qb);
            $qb->addSelect('count(hh) as value')
                ->groupBy('country');

            $results = $this->sortByCountry($qb->getQuery()->getArrayResult());
            $reference = $this->getReferenceId("BMS_Country_TH");
            foreach ($results as $result) {
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
        } catch (Exception $e) {
            $this->em->getConnection()->rollback();
            throw $e;
        }
    }

    /**
     * Fill in ReportingValue and ReportingCountry with active projects
     */
    public function BMS_Country_AP()
    {
        $this->em->getConnection()->beginTransaction();
        try {
            $this->repository = $this->em->getRepository(Project::class);
            $qb = $this->repository->createQueryBuilder('p')
                ->select('count(p) AS value', 'p.iso3 AS country')
                ->where("DATE_FORMAT(p.endDate, '%Y-%m-%d') > DATE_FORMAT(CURRENT_DATE(), '%Y-%m-%d') ")
                ->groupBy('country');
            $results = $qb->getQuery()->getArrayResult();

            $reference = $this->getReferenceId("BMS_Country_AP");
            foreach ($results as $result) {
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
        } catch (Exception $e) {
            $this->em->getConnection()->rollback();
            throw $e;
        }
    }

    /**
     * Fill in ReportingValue and ReportingCountry with enrolled beneficiaries
     */
    public function BMS_Country_EB()
    {
        $this->em->getConnection()->beginTransaction();
        try {
            $this->repository = $this->em->getRepository(Beneficiary::class);
            $qb = $this->repository->createQueryBuilder('b')
                ->leftjoin('b.household', 'hh');

            // Get household country as 'country'
            $this->em->getRepository(Household::class)->getHouseholdCountry($qb);
            $qb->addSelect('count(b.id) as value')
                ->groupBy('country');

            $results = $this->sortByCountry($qb->getQuery()->getArrayResult());
            $reference = $this->getReferenceId("BMS_Country_EB");
            foreach ($results as $result) {
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
        } catch (Exception $e) {
            $this->em->getConnection()->rollback();
            throw $e;
        }
    }

    /**
     * Fill in ReportingValue and ReportingCountry with total number of distributions
     */
    public function BMS_Country_TND()
    {
        $this->em->getConnection()->beginTransaction();
        try {
            $this->repository = $this->em->getRepository(Assistance::class);
            $qb = $this->repository->createQueryBuilder('dd');

            $qb->leftJoin('dd.location', 'l');
            $this->em->getRepository(Location::class)->getCountry($qb);
            $qb ->addSelect('count(dd.id) as value')
                ->groupBy('country');

            $results = $this->sortByCountry($qb->getQuery()->getArrayResult());
            $reference = $this->getReferenceId("BMS_Country_TND");
            foreach ($results as $result) {
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
        } catch (Exception $e) {
            $this->em->getConnection()->rollback();
            throw $e;
        }
    }

    /**
     * TODO: Add group by country
     * Fill in ReportingValue and ReportingCountry with the total of completed transactions
     */
    public function BMS_Country_TTC()
    {
        $this->em->getConnection()->beginTransaction();
        try {
            $this->repository = $this->em->getRepository(Transaction::class);
            $qb = $this->repository->createQueryBuilder('t')
                ->where('t.transactionStatus = 1')
                ->select('count(t.id) AS value');
            $results = $qb->getQuery()->getArrayResult();
            $reference = $this->getReferenceId("BMS_Country_TTC");
            foreach ($results as $result) {
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
        } catch (Exception $e) {
            $this->em->getConnection()->rollback();
            throw $e;
        }
    }

    /**
     * search which data has the same country and add them together
     * @param array $byCountry
     * @return array
     */
    public function sortByCountry(array $byCountry)
    {
        $results = [];
        foreach ($byCountry as $data) {
            if (empty($results)) {
                array_push($results, $data);
            } else {
                $valueFound = false;
                foreach ($results as &$result) {
                    if ($result['country'] === $data['country']) {
                        $valueFound = true;
                        $result['value'] = $result['value'] + $data['value'];
                    }
                }
                if (!$valueFound) {
                    array_push($results, $data);
                }
            }
        }
        return $results;
    }
}
