<?php

namespace DistributionBundle\Repository;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Community;
use BeneficiaryBundle\Entity\Institution;
use DistributionBundle\Entity\AssistanceBeneficiary;
use DistributionBundle\Entity\GeneralReliefItem;
use DistributionBundle\Entity\Assistance;
use BeneficiaryBundle\Entity\Household;
use DistributionBundle\Enum\AssistanceTargetType;
use Doctrine\ORM\Tools\Pagination\Paginator;
use InvalidArgumentException;
use NewApiBundle\InputType\BeneficiaryFilterInputType;
use NewApiBundle\InputType\BeneficiaryOrderInputType;
use NewApiBundle\InputType\CommunityFilterType;
use NewApiBundle\InputType\CommunityOrderInputType;
use NewApiBundle\InputType\InstitutionFilterInputType;
use NewApiBundle\InputType\InstitutionOrderInputType;
use NewApiBundle\Request\Pagination;
use VoucherBundle\Entity\Booklet;

/**
 * AssistanceBeneficiaryRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class AssistanceBeneficiaryRepository extends \Doctrine\ORM\EntityRepository
{
    public function countAll(string $iso3)
    {
        $qb = $this->createQueryBuilder("db");
        $q = $qb->select("COUNT(DISTINCT db.beneficiary)")
                ->leftJoin("db.beneficiary", "b")
                ->leftJoin("b.projects", "p")
                ->andWhere('p.iso3 = :country')
                ->andWhere('b.archived = 0')
        ;
        $q->setParameter('country', $iso3);

        return $q->getQuery()->getSingleScalarResult();
    }
    
    public function getByGRI(GeneralReliefItem $gri)
    {
        $qb = $this->createQueryBuilder("db");
        $q = $qb->leftJoin("db.generalReliefs", "gr")
                    ->where("gr.id = :gri")
                    ->setParameter('gri', $gri->getId());
        
        return $q->getQuery()->getOneOrNullResult();
    }

    public function findAssignable(Assistance $assistance)
    {
        $qb = $this->createQueryBuilder("db");
        $q = $qb->where("db.assistance = :dd")
                ->setParameter("dd", $assistance)
                ->leftJoin("db.booklets", "b")
                ->andWhere('b IS NULL')
                ->orWhere("b.status = :s")
                ->setParameter(':s', Booklet::UNASSIGNED);
        
        return $q->getQuery()->getResult();
    }

    public function countWithoutBooklet(Assistance $assistance)
    {
        $qb = $this->createQueryBuilder("db");
        $q = $qb->select("COUNT(db)")
                ->where("db.assistance = :dd")
                ->setParameter("dd", $assistance)
                ->leftJoin("db.booklets", "b")
                ->andWhere('b IS NULL');
        
        return $q->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Assistance $assistance
     * @return int
     */
    public function countActive(Assistance $assistance)
    {
        $result = $this->count([
            'assistance' => $assistance,
            'removed' => false,
        ]);
        return (int) $result;
    }

    public function findActiveByAssistance(Assistance $assistance): iterable
    {
        $qb = $this->createQueryBuilder('db')
            ->andWhere('db.assistance = :assistance')
            ->andWhere('db.removed = false')
            ->setParameter('assistance', $assistance)
            ->leftJoin("db.beneficiary", "beneficiary")
        ;

        switch ($assistance->getTargetType()) {
            case AssistanceTargetType::INDIVIDUAL:
            case AssistanceTargetType::HOUSEHOLD:
                $qb->andWhere($qb->expr()->isInstanceOf('beneficiary', Beneficiary::class));
                break;
            case AssistanceTargetType::COMMUNITY:
                $qb->andWhere($qb->expr()->isInstanceOf('beneficiary', Community::class));
                break;
            case AssistanceTargetType::INSTITUTION:
                $qb->andWhere($qb->expr()->isInstanceOf('beneficiary', Institution::class));
                break;
        }

        return $qb->getQuery()->getResult();
    }

    public function findByAssistance(Assistance $assistance): iterable
    {
        $qb = $this->createQueryBuilder('db')
            ->andWhere('db.assistance = :assistance')
            ->setParameter('assistance', $assistance)
            ->leftJoin("db.beneficiary", "beneficiary")
            ;

        switch ($assistance->getTargetType()) {
            case AssistanceTargetType::INDIVIDUAL:
            case AssistanceTargetType::HOUSEHOLD:
                $qb->andWhere($qb->expr()->isInstanceOf('beneficiary', Beneficiary::class));
                break;
                // $qb->andWhere($qb->expr()->isInstanceOf('beneficiary', Household::class));
                // break;
            case AssistanceTargetType::COMMUNITY:
                $qb->andWhere($qb->expr()->isInstanceOf('beneficiary', Community::class));
                break;
            case AssistanceTargetType::INSTITUTION:
                $qb->andWhere($qb->expr()->isInstanceOf('beneficiary', Institution::class));
                break;
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param Assistance                      $assistance
     * @param BeneficiaryFilterInputType|null $filter
     * @param BeneficiaryOrderInputType|null  $orderBy
     * @param Pagination|null                 $pagination
     *
     * @return Paginator
     */
    public function findBeneficiariesByAssistance(Assistance $assistance, ?BeneficiaryFilterInputType $filter = null, ?BeneficiaryOrderInputType $orderBy = null, ?Pagination $pagination = null): Paginator
    {
        $qb = $this->createQueryBuilder('db')
            ->andWhere('db.assistance = :assistance')
            ->setParameter('assistance', $assistance)
            ->leftJoin('db.beneficiary', 'ab')
            ->innerJoin(Beneficiary::class, 'b', 'WITH', 'b.id = ab.id');


        if ($pagination) {
            $qb->setMaxResults($pagination->getLimit());
            $qb->setFirstResult($pagination->getOffset());
        }

        if ($filter) {
            if ($filter->hasFulltext()) {
                $qb->leftJoin('b.person', 'p');

                $qb->andWhere('(p.localGivenName LIKE :fulltext OR 
                                p.localFamilyName LIKE :fulltext OR
                                p.localParentsName LIKE :fulltext OR
                                p.enGivenName LIKE :fulltext OR
                                p.enFamilyName LIKE :fulltext OR
                                p.enParentsName LIKE :fulltext OR
                                p.enParentsName LIKE :fulltext)')
                    ->setParameter('fulltext', '%'.$filter->getFulltext().'%');
            }
        }

        if ($orderBy) {
            foreach ($orderBy->toArray() as $name => $direction) {
                switch ($name) {
                    case BeneficiaryOrderInputType::SORT_BY_ID:
                        $qb->orderBy('b.id', $direction);
                        break;
                    case BeneficiaryOrderInputType::SORT_BY_LOCAL_FAMILY_NAME:
                        if (!in_array('p', $qb->getAllAliases())) {
                            $qb->leftJoin('b.person', 'p');
                        }
                        $qb->orderBy('p.localFamilyName', $direction);
                        break;
                    case BeneficiaryOrderInputType::SORT_BY_LOCAL_GIVEN_NAME:
                        if (!in_array('p', $qb->getAllAliases())) {
                            $qb->leftJoin('b.person', 'p');
                        }
                        $qb->orderBy('p.localGivenName', $direction);
                        break;
                    case BeneficiaryOrderInputType::SORT_BY_NATIONAL_ID:
                        if (!in_array('p', $qb->getAllAliases())) {
                            $qb->leftJoin('b.person', 'p');
                        }
                        $qb->leftJoin('p.nationalIds', 'n', 'WITH', 'n.idType = :type')
                            ->setParameter('type', \BeneficiaryBundle\Entity\NationalId::TYPE_NATIONAL_ID)
                            ->orderBy('n.idNumber', $direction);
                        break;
                    default:
                        throw new \InvalidArgumentException('Invalid order by directive '.$name);
                }
            }
        }

        return new Paginator($qb);
    }

    /**
     * @param Assistance                      $assistance
     * @param InstitutionFilterInputType|null $filter
     * @param InstitutionOrderInputType|null  $orderBy
     * @param Pagination|null                 $pagination
     *
     * @return Paginator
     */
    public function findInstitutionsByAssistance(Assistance $assistance, ?InstitutionFilterInputType $filter = null, ?InstitutionOrderInputType $orderBy = null, ?Pagination $pagination = null): Paginator
    {
        $qb = $this->createQueryBuilder('db')
            ->andWhere('db.assistance = :assistance')
            ->setParameter('assistance', $assistance)
            ->join('db.beneficiary', 'ab')
            ->innerJoin(Institution::class, 'i', 'WITH', 'i.id = ab.id');


        if ($filter) {
            if ($filter->hasProjects()) {
                $qb->leftJoin('i.projects', 'pro')
                    ->andWhere('pro.id IN (:ids)')
                    ->setParameter('ids', $filter->getProjects());
            }

            if ($filter->hasFulltext()) {
                $qb->leftJoin('i.contact', 'c');
                $qb->andWhere('(
                    i.id LIKE :fulltextId OR
                    i.name LIKE :fulltext OR
                    i.latitude LIKE :fulltext OR
                    i.longitude LIKE :fulltext OR
                    c.localGivenName LIKE :fulltext OR 
                    c.localFamilyName LIKE :fulltext OR
                    c.localParentsName LIKE :fulltext OR
                    c.enGivenName LIKE :fulltext OR
                    c.enFamilyName LIKE :fulltext OR
                    c.enParentsName LIKE :fulltext OR
                    c.enParentsName LIKE :fulltext
                )');
                $qb->setParameter('fulltextId', $filter->getFulltext());
                $qb->setParameter('fulltext', '%'.$filter->getFulltext().'%');
            }
        }

        if ($pagination) {
            $qb->setMaxResults($pagination->getLimit());
            $qb->setFirstResult($pagination->getOffset());
        }

        if ($orderBy) {
            foreach ($orderBy->toArray() as $name => $direction) {
                switch ($name) {
                    case InstitutionOrderInputType::SORT_BY_ID:
                        $qb->orderBy('i.id', $direction);
                        break;
                    case InstitutionOrderInputType::SORT_BY_NAME:
                        $qb->orderBy('i.name', $direction);
                        break;
                    case InstitutionOrderInputType::SORT_BY_LONGITUDE:
                        $qb->orderBy('i.longitude', $direction);
                        break;
                    case InstitutionOrderInputType::SORT_BY_LATITUDE:
                        $qb->orderBy('i.latitude', $direction);
                        break;
                    case InstitutionOrderInputType::SORT_BY_CONTACT_GIVEN_NAME:
                        if (!in_array('c', $qb->getAllAliases())) {
                            $qb->leftJoin('i.contact', 'c');
                        }
                        $qb->orderBy('c.enGivenName', $direction);
                        break;
                    case InstitutionOrderInputType::SORT_BY_CONTACT_FAMILY_NAME:
                        if (!in_array('c', $qb->getAllAliases())) {
                            $qb->leftJoin('i.contact', 'c');
                        }
                        $qb->orderBy('c.enFamilyName', $direction);
                        break;
                    case InstitutionOrderInputType::SORT_BY_TYPE:
                        $qb->orderBy('i.type', $direction);
                        break;
                    default:
                        throw new InvalidArgumentException('Invalid order by directive '.$name);
                }
            }
        }

        return new Paginator($qb);
    }

    /**
     * @param Assistance                   $assistance
     * @param CommunityFilterType|null     $filter
     * @param CommunityOrderInputType|null $orderBy
     * @param Pagination|null              $pagination
     *
     * @return Paginator
     */
    public function findCommunitiesByAssistance(Assistance $assistance, ?CommunityFilterType $filter = null, ?CommunityOrderInputType $orderBy = null, ?Pagination $pagination = null): Paginator
    {
        $qb = $this->createQueryBuilder('db')
            ->andWhere('db.assistance = :assistance')
            ->setParameter('assistance', $assistance)
            ->join('db.beneficiary', 'ab')
            ->innerJoin(Community::class, 'c', 'WITH', 'c.id = ab.id');


        if ($filter) {
            if ($filter->hasFulltext()) {
                $qb->leftJoin('c.contact', 'per');

                $qb->andWhere('(c.id LIKE :fulltextId OR
                                c.name LIKE :fulltext OR
                                c.latitude LIKE :fulltext OR
                                c.longitude LIKE :fulltext OR
                                per.localGivenName LIKE :fulltext OR 
                                per.localFamilyName LIKE :fulltext OR
                                per.localParentsName LIKE :fulltext OR
                                per.enGivenName LIKE :fulltext OR
                                per.enFamilyName LIKE :fulltext OR
                                per.enParentsName LIKE :fulltext OR
                                per.enParentsName LIKE :fulltext)')
                    ->setParameter('fulltextId', $filter->getFulltext())
                    ->setParameter('fulltext', '%'.$filter->getFulltext().'%');
            }

            if ($filter->hasProjects()) {
                $qb->leftJoin('c.projects', 'p');

                $qb->andWhere('p.id IN (:projects)')
                    ->setParameter('projects', $filter->getProjects());
            }
        }

        if ($pagination) {
            $qb->setMaxResults($pagination->getLimit());
            $qb->setFirstResult($pagination->getOffset());
        }

        if ($orderBy) {
            foreach ($orderBy->toArray() as $name => $direction) {
                switch ($name) {
                    case CommunityOrderInputType::SORT_BY_ID:
                        $qb->orderBy('c.id', $direction);
                        break;
                    case CommunityOrderInputType::SORT_BY_NAME:
                        $qb->orderBy('c.name', $direction);
                        break;
                    case CommunityOrderInputType::SORT_BY_LONGITUDE:
                        $qb->orderBy('c.longitude', $direction);
                        break;
                    case CommunityOrderInputType::SORT_BY_LATITUDE:
                        $qb->orderBy('c.latitude', $direction);
                        break;
                    case CommunityOrderInputType::SORT_BY_CONTACT_GIVEN_NAME:
                        if (!in_array('per', $qb->getAllAliases())) {
                            $qb->leftJoin('c.contact', 'per');
                        }
                        $qb->orderBy('per.enGivenName', $direction);
                        break;
                    case CommunityOrderInputType::SORT_BY_CONTACT_FAMILY_NAME:
                        if (!in_array('per', $qb->getAllAliases())) {
                            $qb->leftJoin('c.contact', 'per');
                        }
                        $qb->orderBy('per.enFamilyName', $direction);
                        break;
                    default:
                        throw new InvalidArgumentException('Invalid order by directive '.$name);
                }
            }
        }

        return new Paginator($qb);
    }
}
