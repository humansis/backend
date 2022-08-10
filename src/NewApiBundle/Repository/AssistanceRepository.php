<?php

namespace NewApiBundle\Repository;

use NewApiBundle\Entity\Beneficiary;
use NewApiBundle\Entity\Location;
use NewApiBundle\Enum\AssistanceTargetType;
use Doctrine\ORM\Query\Expr\Join;
use \DateTime;
use NewApiBundle\Entity\Assistance;
use Doctrine\ORM\Tools\Pagination\Paginator;
use NewApiBundle\DBAL\PersonGenderEnum;
use NewApiBundle\Enum\ReliefPackageState;
use NewApiBundle\InputType\AssistanceByProjectOfflineAppFilterInputType;
use NewApiBundle\InputType\AssistanceFilterInputType;
use NewApiBundle\InputType\AssistanceOrderInputType;
use NewApiBundle\InputType\ProjectsAssistanceFilterInputType;
use NewApiBundle\Request\Pagination;
use NewApiBundle\Entity\Project;

/**
 * AssistanceRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class AssistanceRepository extends \Doctrine\ORM\EntityRepository
{
    public function countCompleted(string $countryISO3): int
    {
        $qb = $this->createQueryBuilder('dd');
        $qb->select('COUNT(dd)')
            ->leftJoin("dd.location", "l");
        $locationRepository = $this->getEntityManager()->getRepository(Location::class);
        $locationRepository->whereCountry($qb, $countryISO3);
        $qb->andWhere("dd.completed = 1");

        return intval($qb->getQuery()->getSingleScalarResult());

    }

    public function getNoServed(int $distributionId, string $modalityType) {
        $qb = $this->createQueryBuilder('dd');
        $qb
            ->andWhere('dd.id = :distributionId')
                ->setParameter('distributionId', $distributionId)
                ->leftJoin('dd.distributionBeneficiaries', 'db', Join::WITH, 'db.removed = 0')
                ->select('COUNT(DISTINCT db)');

                if ($modalityType === 'Mobile Money') {
                    $qb->innerJoin('db.transactions', 't', Join::WITH, 't.transactionStatus = 1');
                } else if ($modalityType === 'QR Code Voucher') {
                    $qb->innerJoin('db.booklets', 'b', Join::WITH, 'b.status = 1 OR b.status = 2');
                } else {
                    $qb->innerJoin('db.reliefPackages', 'rp', Join::WITH, 'rp.state = :undistributedState')
                        ->setParameter('undistributedState', ReliefPackageState::TO_DISTRIBUTE)
                    ;
                }
        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @param Project|null                           $project
     * @param string|null                            $iso3
     * @param ProjectsAssistanceFilterInputType|null $filter
     * @param AssistanceOrderInputType|null          $orderBy
     * @param Pagination|null                        $pagination
     *
     * @return Paginator|Assistance[]
     */
    public function findByProject(
        Project $project,
        ?string $iso3 = null,
        ?ProjectsAssistanceFilterInputType $filter = null,
        ?AssistanceOrderInputType $orderBy = null,
        ?Pagination $pagination = null
    ): Paginator
    {
        $qb = $this->createQueryBuilder('dd')
            ->andWhere('dd.archived = 0')
            ->andWhere('dd.project = :project')
            ->setParameter('project', $project);

        if ($iso3) {
            $qb->leftJoin('dd.project', 'p')
                ->andWhere('p.iso3 = :iso3')
                ->setParameter('iso3', $iso3);
        }

        if ($filter) {
            if ($filter->hasFulltext()) {
                $qb->andWhere('(dd.id = :id OR
                               dd.name LIKE :fulltext OR
                               dd.description LIKE :fulltext)')
                    ->setParameter('id', $filter->getFulltext())
                    ->setParameter('fulltext', '%'.$filter->getFulltext().'%');
            }
        }

        if ($pagination) {
            $qb->setMaxResults($pagination->getLimit());
            $qb->setFirstResult($pagination->getOffset());
        }

        if ($orderBy) {
            foreach ($orderBy->toArray() as $name => $direction) {
                switch ($name) {
                    case AssistanceOrderInputType::SORT_BY_ID:
                        $qb->orderBy('dd.id', $direction);
                        break;
                    case AssistanceOrderInputType::SORT_BY_LOCATION:
                        $qb->leftJoin('dd.location', 'l');
                        $qb->orderBy('l.id', $direction);
                        break;
                    case AssistanceOrderInputType::SORT_BY_DATE:
                        $qb->orderBy('dd.dateDistribution', $direction);
                        break;
                    case AssistanceOrderInputType::SORT_BY_DATE_EXPIRATION:
                        $qb->orderBy('dd.dateExpiration', $direction);
                        break;
                    case AssistanceOrderInputType::SORT_BY_NAME:
                        $qb->orderBy('dd.name', $direction);
                        break;
                    case AssistanceOrderInputType::SORT_BY_TARGET:
                        $qb->orderBy('dd.targetType', $direction);
                        break;
                    case AssistanceOrderInputType::SORT_BY_NUMBER_OF_BENEFICIARIES:
                        $qb->orderBy('SIZE(dd.distributionBeneficiaries)', $direction);
                        break;
                    case AssistanceOrderInputType::SORT_BY_ROUND:
                        $qb->orderBy('dd.round', $direction);
                        break;
                    case AssistanceOrderInputType::SORT_BY_TYPE:
                        $qb->orderBy('dd.assistanceType', $direction);
                        break;
                    default:
                        throw new \InvalidArgumentException('Invalid order by directive '.$name);
                }
            }
        }

        return new Paginator($qb);
    }

    /**
     * @param Project                                      $project
     * @param string                                       $iso3
     * @param AssistanceByProjectOfflineAppFilterInputType $filter
     *
     * @return Assistance[]
     */
    public function findByProjectInOfflineApp(
        Project $project,
        string $iso3,
        AssistanceByProjectOfflineAppFilterInputType $filter
    ): iterable
    {
        $qbr = $this->createQueryBuilder('dd')
            ->leftJoin('dd.project', 'p')
            ->andWhere('dd.archived = 0')
            ->andWhere('dd.validatedBy IS NOT NULL')
            ->andWhere('dd.project = :project')
            ->andWhere('p.iso3 = :iso3')
            ->andWhere('dd.targetType IN (:targetTypes)')
            ->setParameter('project', $project)
            ->setParameter('iso3', $iso3)
            ->setParameter('targetTypes', [AssistanceTargetType::HOUSEHOLD, AssistanceTargetType::INDIVIDUAL]);

        if ($filter->hasType()) {
            $qbr->andWhere('dd.assistanceType = :type')
                ->setParameter('type', $filter->getType());
        }

        if ($filter->hasCompleted()) {
            $qbr->andWhere('dd.completed = :completed')
                ->setParameter('completed', $filter->getCompleted());
        }

        if ($filter->hasModalityTypes()) {
            $qbr->join('dd.commodities', 'c')
                ->join('c.modalityType', 'm', 'WITH', 'm.name IN (:modalityTypes)')
                ->setParameter('modalityTypes', $filter->getModalityTypes());
        }

        if ($filter->hasNotModalityTypes()) {
            $qbr->join('dd.commodities', 'c')
                ->join('c.modalityType', 'm', 'WITH', 'm.name NOT IN (:modalityTypes)')
                ->setParameter('modalityTypes', $filter->getNotModalityTypes());
        }

        return $qbr->getQuery()->getResult();
    }

    /**
     * @param string                         $iso3
     * @param AssistanceFilterInputType|null $filter
     * @param AssistanceOrderInputType|null  $orderBy
     * @param Pagination|null                $pagination
     *
     * @return Paginator|Assistance[]
     */
    public function findByParams(
        string $iso3,
        ?AssistanceFilterInputType $filter = null,
        ?AssistanceOrderInputType $orderBy = null,
        ?Pagination $pagination = null
    ): Paginator {
        $qb = $this->createQueryBuilder('dd')
            ->andWhere('dd.archived = 0');

        if ($iso3) {
            $qb->leftJoin('dd.project', 'p')
                ->andWhere('p.iso3 = :iso3')
                ->setParameter('iso3', $iso3);
        }

        if (($filter && $filter->hasModalityTypes()) ||
            ($orderBy && (
                    $orderBy->has(AssistanceOrderInputType::SORT_BY_MODALITY_TYPE) ||
                    $orderBy->has(AssistanceOrderInputType::SORT_BY_UNIT) ||
                    $orderBy->has(AssistanceOrderInputType::SORT_BY_VALUE)))) {
            $qb->leftJoin('dd.commodities', 'c');

            if ($filter->hasModalityTypes() || $orderBy->has(AssistanceOrderInputType::SORT_BY_MODALITY_TYPE)) {
                $qb->leftJoin('c.modalityType', 'mt');
            }
        }

        if ($filter) {
            if ($filter->hasIds()) {
                $qb->andWhere('dd.id IN (:ids)')
                    ->setParameter('ids', $filter->getIds());
            }
            if ($filter->hasUpcomingOnly() && $filter->getUpcomingOnly()) {
                $qb->andWhere('p.startDate > :now')
                    ->setParameter('now', new DateTime('now'));
            }
            if ($filter->hasType()) {
                $qb->andWhere('dd.assistanceType = :assistanceType')
                    ->setParameter('assistanceType', $filter->getType());
            }
            if ($filter->hasProjects()) {
                $qb->andWhere('dd.project IN (:projects)')
                    ->setParameter('projects', $filter->getProjects());
            }
            if ($filter->hasLocations()) {
                $this->createQueryBuilder('l')
                    ->andWhere('dd.location IN (:locations)')
                    ->setParameter('locations', $filter->getLocations());
            }
            if ($filter->hasModalityTypes()) {
                $qb->andWhere('mt.name IN (:modalityTypes)')
                    ->setParameter('modalityTypes', $filter->getModalityTypes());
            }
        }

        if ($pagination) {
            $qb->setMaxResults($pagination->getLimit());
            $qb->setFirstResult($pagination->getOffset());
        }

        if ($orderBy) {
            foreach ($orderBy->toArray() as $name => $direction) {
                switch ($name) {
                    case AssistanceOrderInputType::SORT_BY_ID:
                        $qb->orderBy('dd.id', $direction);
                        break;
                    case AssistanceOrderInputType::SORT_BY_LOCATION:
                        $qb->leftJoin('dd.location', 'l');
                        $qb->orderBy('l.id', $direction);
                        break;
                    case AssistanceOrderInputType::SORT_BY_DATE:
                        $qb->orderBy('dd.dateDistribution', $direction);
                        break;
                    case AssistanceOrderInputType::SORT_BY_NAME:
                        $qb->orderBy('dd.name', $direction);
                        break;
                    case AssistanceOrderInputType::SORT_BY_TARGET:
                        $qb->orderBy('dd.targetType', $direction);
                        break;
                    case AssistanceOrderInputType::SORT_BY_NUMBER_OF_BENEFICIARIES:
                        $qb->orderBy('SIZE(dd.distributionBeneficiaries)', $direction);
                        break;
                    case AssistanceOrderInputType::SORT_BY_PROJECT:
                        $qb->leftJoin('dd.project', 'p')
                            ->orderBy('p.name', $direction);
                        break;
                    case AssistanceOrderInputType::SORT_BY_MODALITY_TYPE:
                        $qb->orderBy('mt.name', $direction);
                        break;
                    case AssistanceOrderInputType::SORT_BY_VALUE:
                        $qb->orderBy('c.value', $direction);
                        break;
                    case AssistanceOrderInputType::SORT_BY_UNIT:
                        $qb->orderBy('c.unit', $direction);
                        break;
                    case AssistanceOrderInputType::SORT_BY_TYPE:
                        $qb->orderBy('dd.assistanceType', $direction);
                        break;
                    default:
                        throw new \InvalidArgumentException('Invalid order by directive '.$name);
                }
            }
        }

        return new Paginator($qb);
    }

    public function save(\NewApiBundle\Component\Assistance\Domain\Assistance $assistance): void
    {
        $this->_em->persist($assistance->getAssistanceRoot());
        $this->_em->flush();
    }
}
