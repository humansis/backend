<?php

namespace VoucherBundle\Repository;

use BeneficiaryBundle\Entity\Beneficiary;
use DistributionBundle\Entity\Assistance;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use NewApiBundle\InputType\BookletFilterInputType;
use NewApiBundle\InputType\BookletOrderInputType;
use NewApiBundle\Request\Pagination;
use VoucherBundle\Entity\Booklet;

/**
 * BookletRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class BookletRepository extends \Doctrine\ORM\EntityRepository
{
     /**
     * Finds booklets with same code prefix and return latest
     *
     * @param string $prefix
     * @return Booklet|null
     */
    public function findMaxByCodePrefix(string $prefix): ?Booklet
    {
        try {
            $qb = $this->createQueryBuilder('b')
                ->andWhere('b.code LIKE :prefix')
                ->setParameter('prefix', $prefix . '%')
                ->orderBy('b.code', 'DESC');

            return $qb->getQuery()->setMaxResults(1)->getSingleResult();
        } catch (NonUniqueResultException | NoResultException $ex) {
            return null;
        }
    }

    public function getActiveBooklets($countryISO3)
    {
        $qb = $this->createQueryBuilder('b');
        $q = $qb->where('b.status != :status')
                ->andWhere('b.countryISO3 = :country')
                ->setParameter('country', $countryISO3)
                ->setParameter('status', 3);

        return $q->getQuery()->getResult();
    }

    // We dont care about this function and probably we should remove it from controller, test, service and repo (it has nothing related in the front)
    public function getProtectedBooklets()
    {
        $qb = $this->createQueryBuilder('b');
        $q = $qb->where('b.password IS NOT NULL');
        
        
        return $q->getQuery()->getResult();
    }

    public function getActiveBookletsByAssistanceBeneficiary(int $assistanceBeneficiaryId) {
        $qb = $this->createQueryBuilder('b');
        
        $qb->andWhere('db.id = :id')
                ->setParameter('id', $assistanceBeneficiaryId)
                ->leftJoin('b.distribution_beneficiary', 'db')
                ->andWhere('b.status != :status')
                    ->setParameter('status', 3);
        
        return $qb->getQuery()->getResult();
    }

    /**
     * Get all Household by country
     * @param $countryISO3
     * @param $begin
     * @param $pageSize
     * @param $sort
     * @param array $filters
     * @return mixed
     */
    public function getAllBy($countryISO3, $begin, $pageSize, $sort, $filters = [])
    {
        // Recover global information for the page
        $qb = $this->createQueryBuilder('b');

        // We join information that is needed for the filters
        $q = $qb->leftJoin('b.distribution_beneficiary', 'db')
                ->leftJoin('b.vouchers', 'v')
                ->leftJoin('db.beneficiary', 'bf')
                ->leftJoin('db.assistance', 'd')
                ->where('b.status != :status')
                ->andWhere('b.countryISO3 = :country')
                ->setParameter('country', $countryISO3)
                ->setParameter('status', Booklet::DEACTIVATED);
          
        // If there is a sort, we recover the direction of the sort and the field that we want to sort
        if (array_key_exists("sort", $sort) && array_key_exists("direction", $sort)) {
            $value = $sort->sort;
            $direction = $sort->direction;

            // If the field is the code, we sort it by the direction sent
            if ($value == "code") {
                $q->addGroupBy("b.code")->addOrderBy("b.code", $direction);
            }
            // If the field is the quantity of vouchers, we sort it by the direction sent
            elseif ($value == "numberVouchers") {
                $q->addGroupBy("b.numberVouchers")->addOrderBy("b.numberVouchers", $direction);
            }
            // If the field is the individual value, we sort it by the direction sent
            elseif ($value == "value") {
                $qb->addOrderBy('SUM(v.value)');
            }
            // If the field is the currency, we sort it by the direction sent
            elseif ($value == "currency") {
                $q->addGroupBy("b.currency")->addOrderBy("b.currency", $direction);
            }
            // If the field is the status, we sort it by the direction sent
            elseif ($value == "status") {
                $q->addGroupBy("b.status")->addOrderBy("b.status", $direction);
            }
            // If the field is the beneficiaries, we sort it by the direction sent
            elseif ($value == "beneficiary") {
                // this isn't good but it is too much work for hotfix
                $q->addGroupBy("db.beneficiary")->addOrderBy("IDENTITY(db.beneficiary)", $direction);
            }
            // If the field is the distributions, we sort it by the direction sent
            elseif ($value == "distribution") {
                $q->addGroupBy("d")->addOrderBy("d.name", $direction);
            }

            $q->addGroupBy("b.id");
        }

        // If there is a filter array in the request
        $subSelectIndex = 0;
        if (count($filters) > 0) {
            // For each filter in our array, we recover an index (to avoid parameters' repetitions in the WHERE clause) and the filters
            foreach ($filters as $indexFilter => $filter) {
                // We recover the category of the filter chosen and the value of the filter
                $category = $filter["category"];
                /** @var string[] $filterValues */
                $filterValues = $filter["filter"];

                if ($category === "any" && count($filterValues) > 0) {
                    foreach ($filterValues as $filterValue) {
                        $aliasBnf = 'bnf'.++$subSelectIndex;
                        $aliasPerson = 'p'.$subSelectIndex;
                        $subQueryForName = $this->_em->createQueryBuilder()
                            ->select("$aliasPerson.id")
                            ->from(Beneficiary::class, $aliasBnf)
                            ->leftJoin("$aliasBnf.person", $aliasPerson)
                            ->andWhere("$aliasBnf.id = IDENTITY(db.beneficiary)")
                            ->andWhere("(
                                $aliasPerson.localGivenName LIKE '%$filterValue%' OR
                                $aliasPerson.localFamilyName LIKE '%$filterValue%' OR
                                $aliasPerson.enGivenName LIKE '%$filterValue%' OR
                                $aliasPerson.enFamilyName LIKE '%$filterValue%'
                            ) ")
                            ->setParameter('filter', strtolower($filterValue))
                            ->getDQL()
                        ;
                        $q->andWhere("CONCAT(
                            COALESCE(b.code, ''),
                            COALESCE(b.currency, ''),
                            COALESCE(b.status, ''),
                            COALESCE(d.name, '')
                        ) LIKE '%$filterValue%' OR EXISTS ($subQueryForName)");
                    }
                } elseif ($category === "currency" && count($filterValues) > 0) {
                    $orStatement = $q->expr()->orX();
                    foreach ($filterValues as $indexValue => $filterValue) {
                        $q->setParameter("filter" . $indexFilter . $indexValue, $filterValue);
                        $orStatement->add($q->expr()->eq("b.currency", ":filter" . $indexFilter . $indexValue));
                    }
                    $q->andWhere($orStatement);
                } elseif ($category === "status" && count($filterValues) > 0) {
                    $orStatement = $q->expr()->orX();
                    foreach ($filterValues as $indexValue => $filterValue) {
                        $q->setParameter("filter" . $indexFilter . $indexValue, $filterValue);
                        $orStatement->add($q->expr()->eq("b.status", ":filter" . $indexFilter . $indexValue));
                    }
                    $q->andWhere($orStatement);
                } elseif ($category === "distribution" && count($filterValues) > 0) {
                    $orStatement = $q->expr()->orX();
                    foreach ($filterValues as $indexValue => $filterValue) {
                        $q->setParameter("filter" . $indexFilter . $indexValue, $filterValue);
                        $orStatement->add($q->expr()->eq("d.id", ":filter" . $indexFilter . $indexValue));
                    }
                    $q->andWhere($orStatement);
                } elseif ($category === "beneficiary" && count($filterValues) > 0) {
                    $orStatement = $q->expr()->orX();
                    foreach ($filterValues as $indexValue => $filterValue) {
                        $q->setParameter("filter" . $indexFilter . $indexValue, $filterValue);
                        $orStatement->add($q->expr()->eq("bf.id", ":filter" . $indexFilter . $indexValue));
                    }
                    $q->andWhere($orStatement);
                }  
            }
        }

        if (is_null($begin)) {
            $begin = 0;
        }
        if (is_null($pageSize)) {
            $pageSize = 0;
        }

        if ($pageSize > -1) {
            $q->setFirstResult($begin)
            ->setMaxResults($pageSize);
        }

        $paginator = new Paginator($q, $fetchJoinCellection = true);

        return [count($paginator), $q->getQuery()->getResult()];
    }

    public function getInsertedBooklets($countryISO3, $lastId) {
        $qb = $this->createQueryBuilder('b');
        $q = $qb->where('b.id >= :lastId')
                ->andWhere('b.countryISO3 = :country')
                ->setParameter('lastId', $lastId)
                ->setParameter('country', $countryISO3);

        return $q->getQuery()->getResult();
    }

    /**
     * @param string                     $iso3
     * @param BookletFilterInputType     $filter
     * @param BookletOrderInputType|null $orderBy
     * @param Pagination|null            $pagination
     *
     * @return Paginator|Booklet[]
     */
    public function findByParams(
        string $iso3,
        BookletFilterInputType $filter,
        ?BookletOrderInputType $orderBy = null,
        ?Pagination $pagination = null
    ): Paginator {
        $qb = $this->createQueryBuilder('b')
            ->leftJoin('b.distribution_beneficiary', 'db')
            ->leftJoin('b.vouchers', 'v')
            ->leftJoin('db.beneficiary', 'bf')
            ->leftJoin('db.assistance', 'd')
            ->andWhere('b.status != :status')
            ->andWhere('b.countryISO3 = :country')
            ->setParameter('status', Booklet::DEACTIVATED)
            ->setParameter('country', $iso3);

        if ($pagination) {
            $qb->setMaxResults($pagination->getLimit());
            $qb->setFirstResult($pagination->getOffset());
        }

        if ($filter->hasFulltext()) {
            $subQueryForName = $this->_em->createQueryBuilder()
                ->select('p.id')
                ->from(Beneficiary::class, 'bnf1')
                ->leftJoin('bnf1.person', 'p')
                ->andWhere('p.id = IDENTITY(db.beneficiary)')
                ->andWhere('(p.localGivenName LIKE :fulltext OR
                                p.localFamilyName LIKE :fulltext OR
                                p.enGivenName LIKE :fulltext OR
                                p.enFamilyName LIKE :fulltext
                            )')
                ->setParameter('fulltext', '%'.$filter->getFulltext().'%')
                ->getDQL();

            $qb->andWhere("CONCAT(
                             COALESCE(b.code, ''),
                             COALESCE(b.currency, ''),
                             COALESCE(b.status, ''),
                             COALESCE(d.name, '')
                         ) LIKE :fulltext OR EXISTS ($subQueryForName)")
                ->setParameter('fulltext', '%'.$filter->getFulltext().'%');
        }

        if ($filter->hasCurrencies()) {
            $qb->andWhere('b.currency IN (:currencies)')
                ->setParameter('currencies', $filter->getCurrencies());
        }

        if ($filter->hasStatuses()) {
            $qb->andWhere('b.status IN (:statuses)')
                ->setParameter('statuses', $filter->getStatuses());
        }

        if ($filter->hasDistributions()) {
            $qb->andWhere('d.id IN (:distributions)')
                ->setParameter('distributions', $filter->getDistributions());
        }

        if ($filter->hasBeneficiaries()) {
            $qb->andWhere('bf.id  IN (:beneficiaries)')
                ->setParameter('beneficiaries', $filter->getBeneficiaries());
        }

        if ($orderBy) {
            foreach ($orderBy->toArray() as $name => $direction) {
                switch ($name) {
                    case BookletOrderInputType::SORT_BY_CODE:
                        $qb->addGroupBy('b.code')->addOrderBy('b.code', $direction);
                        break;
                    case BookletOrderInputType::SORT_BY_NUMBER_VOUCHERS:
                        $qb->addGroupBy('b.numberVouchers')->addOrderBy('b.numberVouchers', $direction);
                        break;
                    case BookletOrderInputType::SORT_BY_VALUE:
                        $qb->addOrderBy('SUM(v.value)', $direction);
                        break;
                    case BookletOrderInputType::SORT_BY_CURRENCY:
                        $qb->addGroupBy('b.currency')->addOrderBy('b.currency', $direction);
                        break;
                    case BookletOrderInputType::SORT_BY_STATUS:
                        $qb->addGroupBy('b.status')->addOrderBy('b.status', $direction);
                        break;
                    case BookletOrderInputType::SORT_BY_BENEFICIARY:
                        $qb->addGroupBy('db.beneficiary')->addOrderBy('IDENTITY(db.beneficiary)', $direction);
                        break;
                    case BookletOrderInputType::SORT_BY_DISTRIBUTION:
                        $qb->addGroupBy('d')->addOrderBy('d.name', $direction);
                        break;
                }
            }

            $qb->addGroupBy('b.id');
        }

        return new Paginator($qb, false);
    }
}
