<?php
declare(strict_types=1);

namespace NewApiBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use InvalidArgumentException;
use NewApiBundle\Entity\Import;
use NewApiBundle\Enum\ImportState;
use NewApiBundle\InputType\ImportFilterInputType;
use NewApiBundle\InputType\ImportOrderInputType;
use NewApiBundle\Request\Pagination;

class ImportRepository extends EntityRepository
{
    public function findByParams(?Pagination $pagination = null, ?ImportFilterInputType $filter = null, ?ImportOrderInputType $orderBy = null): Paginator
    {
        $qb = $this->createQueryBuilder('i');

        if ($filter) {
            if ($filter->hasFulltext()) {
                $qb->leftJoin('i.project', 'p')
                    ->leftJoin('i.createdBy', 'u');
                $qb->andWhere('(
                    i.id LIKE :fulltextId OR
                    i.title LIKE :fulltext OR
                    i.notes LIKE :fulltext OR
                    p.name LIKE :fulltext OR
                    i.state LIKE :fulltext OR
                    u.email LIKE :fulltext OR
                    i.createdAt LIKE :fulltext
                )');
                $qb->setParameter('fulltextId', $filter->getFulltext());
                $qb->setParameter('fulltext', '%'.$filter->getFulltext().'%');
            }

            if ($filter->hasStatus()) {
                $qb->andWhere('i.state IN (:states)')
                ->setParameter('states', $filter->getStatus());
            }

            if ($filter->hasProjects()) {
                if (!in_array('p', $qb->getAllAliases())) {
                    $qb->leftJoin('i.project', 'p');
                }

                $qb->andWhere('p.id IN (:projectIds)')
                    ->setParameter('projectIds', $filter->getProjects());
            }
        }

        if ($orderBy) {
            foreach ($orderBy->toArray() as $name => $direction) {
                switch ($name) {
                    case ImportOrderInputType::SORT_BY_ID:
                        $qb->orderBy('i.id', $direction);
                        break;
                    case ImportOrderInputType::SORT_BY_TITLE:
                        $qb->orderBy('i.title', $direction);
                        break;
                    case ImportOrderInputType::SORT_BY_DESCRIPTION:
                        $qb->orderBy('i.notes', $direction);
                        break;
                    case ImportOrderInputType::SORT_BY_PROJECT:
                        if (!in_array('p', $qb->getAllAliases())) {
                            $qb->leftJoin('i.project', 'p');
                        }

                        $qb->orderBy('p.name', $direction);
                        break;
                    case ImportOrderInputType::SORT_BY_STATUS:
                        $qb->orderBy('i.state', $direction);
                        break;
                    case ImportOrderInputType::SORT_BY_CREATED_BY:
                        if (!in_array('u', $qb->getAllAliases())) {
                            $qb->leftJoin('i.createdBy', 'u');
                        }

                        $qb->orderBy('u.email', $direction);
                        break;
                    case ImportOrderInputType::SORT_BY_CREATED_AT:
                        $qb->orderBy('i.createdAt', $direction);
                        break;
                    default:
                        throw new InvalidArgumentException('Invalid order by directive '.$name);
                }
            }
        }

        if ($pagination) {
            $qb->setMaxResults($pagination->getLimit());
            $qb->setFirstResult($pagination->getOffset());
        }

        return new Paginator($qb);
    }

    public function isCountryFreeFromImporting(Import $importCandidate, string $countryIso3): bool
    {
        $qb = $this->createQueryBuilder('i');
        $qb->select('count(i.id)')
            ->innerJoin('i.project', 'p')
            ->where('i.state = :importingState')
            ->andWhere('i <> :importCandidate')
            ->andWhere('p.iso3 = :country')
            ->setParameter('importingState', ImportState::IMPORTING)
            ->setParameter('importCandidate', $importCandidate)
            ->setParameter('country', $countryIso3)
            ;

        return $qb->getQuery()->getSingleScalarResult() > 0 ? false : true;
    }

    /**
     * @param Import $import
     *
     * @return Import[]
     */
    public function getConflictingImports(Import $import): iterable
    {
        $qb = $this->createQueryBuilder('i');
        $qb->select('i')
            ->innerJoin('i.project', 'p')
            ->where('i.state IN (:conflictingStates)')
            ->andWhere('p.iso3 = :country')
            ->setParameter('conflictingStates', [
                ImportState::IDENTITY_CHECKING,
                ImportState::IDENTITY_CHECK_CORRECT,
                ImportState::INTEGRITY_CHECK_FAILED,
                ImportState::SIMILARITY_CHECKING,
                ImportState::SIMILARITY_CHECK_CORRECT,
                ImportState::SIMILARITY_CHECK_FAILED,
            ])
            ->setParameter('country', $import->getProject()->getIso3())
        ;

        return $qb->getQuery()->getResult();
    }

    public function getFinishedWithInvalidFiles(): array
    {
        return $this->createQueryBuilder('i')
            ->join('i.invalidFiles', 'if')
            ->where('i.state IN (:states)')
            ->setParameter('states',  [
                ImportState::FINISHED,
                ImportState::CANCELED,
            ])
            ->getQuery()->getResult();
    }
}
