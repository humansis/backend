<?php

namespace Repository;

use Entity\AssistanceBeneficiary;
use Entity\Beneficiary;
use Entity\Community;
use Entity\CountrySpecific;
use Entity\Institution;
use Entity\Assistance;
use Enum\AssistanceTargetType;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Tools\Pagination\Paginator;
use InvalidArgumentException;
use Entity\Assistance\ReliefPackage;
use Enum\NationalIdType;
use InputType\BeneficiaryFilterInputType;
use InputType\BeneficiaryOrderInputType;
use InputType\CommunityFilterType;
use InputType\CommunityOrderInputType;
use InputType\InstitutionFilterInputType;
use InputType\InstitutionOrderInputType;
use Request\Pagination;
use Entity\Booklet;
use Entity\SmartcardDeposit;

/**
 * AssistanceBeneficiaryRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class AssistanceBeneficiaryRepository extends \Doctrine\ORM\EntityRepository
{
    public const SEARCH_CONTEXT_NOT_REMOVED = 'notRemoved';

    /**
     * @param int $assistanceId
     * @param int $beneficiaryId
     *
     * @return AssistanceBeneficiary|null
     */
    public function findByAssistanceAndBeneficiary(int $assistanceId, int $beneficiaryId): ?AssistanceBeneficiary
    {
        return $this->findOneBy([
            'assistance' => $assistanceId,
            'beneficiary' => $beneficiaryId,
        ], ['id' => 'asc']);
    }

    /**
     * @param Assistance $assistance
     *
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
     * @param array|null                      $context
     *
     * context values [
     *      notRemoved = show only not removed assistance-bnf
     * ]
     *
     * @return Paginator
     */
    public function findBeneficiariesByAssistance(
        Assistance                  $assistance,
        ?BeneficiaryFilterInputType $filter = null,
        ?BeneficiaryOrderInputType  $orderBy = null,
        ?Pagination                 $pagination = null,
        ?array                      $context = null
    ): Paginator {
        $qb = $this->createQueryBuilder('db')
            ->andWhere('db.assistance = :assistance')
            ->setParameter('assistance', $assistance)
            ->leftJoin('db.beneficiary', 'ab')
            ->innerJoin(Beneficiary::class, 'b', 'WITH', 'b.id = ab.id');

        if ($context) {
            if (in_array(self::SEARCH_CONTEXT_NOT_REMOVED, $context) && $context[self::SEARCH_CONTEXT_NOT_REMOVED]) {
                $qb
                    ->andWhere('db.removed = :removed')
                    ->setParameter('removed', false);
            }
        }

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
                            ->setParameter('type', NationalIdType::NATIONAL_ID)
                            ->orderBy('n.idNumber', $direction);
                        break;
                    case BeneficiaryOrderInputType::SORT_BY_DISTRIBUTION_DATE:
                        $qb
                            ->leftJoin(ReliefPackage::class, 'reliefPackage', 'WITH', 'reliefPackage.assistanceBeneficiary = db.id')
                            ->leftJoin(SmartcardDeposit::class, 'smartcardDeposit', 'WITH', 'smartcardDeposit.reliefPackage = reliefPackage.id')
                            ->orderBy('smartcardDeposit.distributedAt', $direction);
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
     * @param array|null                      $context
     *
     * @return Paginator
     */
    public function findInstitutionsByAssistance(
        Assistance                  $assistance,
        ?InstitutionFilterInputType $filter = null,
        ?InstitutionOrderInputType  $orderBy = null,
        ?Pagination                 $pagination = null,
        ?array                      $context = null
    ): Paginator {
        $qb = $this->createQueryBuilder('db')
            ->andWhere('db.assistance = :assistance')
            ->setParameter('assistance', $assistance)
            ->join('db.beneficiary', 'ab')
            ->innerJoin(Institution::class, 'i', 'WITH', 'i.id = ab.id');

        if ($context) {
            if (in_array(self::SEARCH_CONTEXT_NOT_REMOVED, $context) && $context[self::SEARCH_CONTEXT_NOT_REMOVED]) {
                $qb
                    ->andWhere('db.removed = :removed')
                    ->setParameter('removed', false);
            }
        }

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
     * @param array|null                   $context
     *
     * @return Paginator
     */
    public function findCommunitiesByAssistance(
        Assistance               $assistance,
        ?CommunityFilterType     $filter = null,
        ?CommunityOrderInputType $orderBy = null,
        ?Pagination              $pagination = null,
        ?array                   $context = null
    ): Paginator {
        $qb = $this->createQueryBuilder('db')
            ->andWhere('db.assistance = :assistance')
            ->setParameter('assistance', $assistance)
            ->join('db.beneficiary', 'ab')
            ->innerJoin(Community::class, 'c', 'WITH', 'c.id = ab.id');

        if ($context) {
            if (in_array(self::SEARCH_CONTEXT_NOT_REMOVED, $context) && $context[self::SEARCH_CONTEXT_NOT_REMOVED]) {
                $qb
                    ->andWhere('db.removed = :removed')
                    ->setParameter('removed', false);
            }
        }

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

    /**
     * @param Assistance           $assistance
     * @param CountrySpecific|null $countrySpecific
     *
     * @return float|int|mixed|string
     */
    public function getBeneficiaryReliefCompilation(Assistance $assistance, ?CountrySpecific $countrySpecific1, ?CountrySpecific $countrySpecific2) {
        $beneficiaryReliefData = $this->getAssistanceBeneficiaryReliefAmounts($assistance, $countrySpecific1, $countrySpecific2);
        $beneficiariesInfo = $this->getAssistanceBeneficiaryInformation($assistance);
        foreach($beneficiaryReliefData as  $id => $relief) {
            $personId = $relief['personId'];
            $beneficiaryInfo = key_exists($personId, $beneficiariesInfo) ?  $beneficiariesInfo[$personId] : [
                'idNumber' => null,
                'idType' => null,
                'phoneNumber' => null,
            ];
            $beneficiaryReliefData[$id] = array_merge($relief,$beneficiaryInfo);
        }
        return $beneficiaryReliefData;

    }

    /**
     * @param Assistance $assistance
     *
     * @return array
     */
    private function getAssistanceBeneficiaryInformation(Assistance $assistance) {
        $qb = $this->createQueryBuilder('db')
            ->select("person.id as personId")
            ->addSelect("ANY_VALUE(national.idNumber) as idNumber")
            ->addSelect("ANY_VALUE(national.idType) as idType")
            ->addSelect("ANY_VALUE(CONCAT(phone.prefix, phone.number)) AS phoneNumber")
            ->leftJoin('db.beneficiary', 'ab')
            ->innerJoin(Beneficiary::class, 'bnf', Join::WITH, 'bnf.id = ab.id')
            ->leftJoin('bnf.person', 'person')
            ->leftJoin('person.nationalIds', 'national', Join::WITH, 'national.idType = :nationalIdType')
            ->leftJoin('person.phones', 'phone')
            ->leftJoin('db.reliefPackages', 'relief')
            ->andWhere('db.assistance = :assistance')
            ->groupBy('person.id')
            ->orderBy('person.localFamilyName')
            ->setParameter('assistance', $assistance)
            ->setParameter('nationalIdType', NationalIdType::TAX_NUMBER);

        $result = $qb->getQuery()->getResult();
        $personInfo = [];
        foreach ($result as $row) {
            $personInfo[$row['personId']] = $row;
        }
        return $personInfo;
    }

    /**
     * @param Assistance           $assistance
     * @param CountrySpecific|null $countrySpecific
     *
     * @return float|int|mixed|string
     */
    private function getAssistanceBeneficiaryReliefAmounts(Assistance $assistance, ?CountrySpecific $countrySpecific1, ?CountrySpecific $countrySpecific2) {
        $qb = $this->createQueryBuilder('db')
            ->select("CONCAT(IDENTITY(db.assistance),'-', bnf.id) as distributionId")
            ->addSelect("person.id as personId")
            ->addSelect("person.localGivenName")
            ->addSelect("SUM(relief.amountToDistribute) as amountToDistribute")
            ->addSelect("ANY_VALUE(relief.unit) as currency")
            ->addSelect("person.localFamilyName")
            ->addSelect("person.localParentsName")
            ->addSelect("ANY_VALUE(countrySpecificAnswer1.answer) AS countrySpecificValue1")
            ->addSelect("ANY_VALUE(countrySpecificAnswer2.answer) AS countrySpecificValue2")
            ->leftJoin('db.beneficiary', 'ab')
            ->innerJoin(Beneficiary::class, 'bnf', Join::WITH, 'bnf.id = ab.id')
            ->leftJoin('bnf.person', 'person')
            ->leftJoin('db.reliefPackages', 'relief')
            ->leftJoin('bnf.household', 'household')
            ->leftJoin('household.countrySpecificAnswers', 'countrySpecificAnswer1', Join::WITH, 'IDENTITY(countrySpecificAnswer1.countrySpecific) = :countrySpecificId1')
            ->leftJoin('household.countrySpecificAnswers', 'countrySpecificAnswer2', Join::WITH, 'IDENTITY(countrySpecificAnswer2.countrySpecific) = :countrySpecificId2')
            ->andWhere('db.assistance = :assistance')
            ->andWhere('db.removed = :removed')
            ->andWhere('relief.modalityType = :modalityType')
            ->groupBy('ab.id')
            ->orderBy('person.localFamilyName')
            ->setParameter('assistance', $assistance)
            ->setParameter('countrySpecificId1',  $countrySpecific1 ? $countrySpecific1->getId() : null)
            ->setParameter('countrySpecificId2',  $countrySpecific2 ? $countrySpecific2->getId() : null)
            ->setParameter('removed', false)
            ->setParameter('modalityType', 'Cash');
        return $qb->getQuery()->getResult();
    }

}
