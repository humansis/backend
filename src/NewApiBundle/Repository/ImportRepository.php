<?php
declare(strict_types=1);

namespace NewApiBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use InvalidArgumentException;
use NewApiBundle\Entity\Import;
use NewApiBundle\Enum\ImportState;
use NewApiBundle\InputType\Import\FilterInputType;
use NewApiBundle\InputType\Import\OrderInputType;
use NewApiBundle\Request\Pagination;

class ImportRepository extends EntityRepository
{
    public function findByParams(?string $countryIso3, ?Pagination $pagination = null, ?FilterInputType $filter = null, ?OrderInputType $orderBy = null): Paginator
    {
        $qb = $this->createQueryBuilder('i');
        $qb->leftJoin('i.project', 'p');

        if (null !== $countryIso3) {
            $qb
                ->andWhere('i.countryIso3 = :country')
                ->setParameter('country', $countryIso3)
            ;
        }

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
                $qb->andWhere('p.id IN (:projectIds)')
                    ->setParameter('projectIds', $filter->getProjects());
            }
        }

        if ($orderBy) {
            foreach ($orderBy->toArray() as $name => $direction) {
                switch ($name) {
                    case OrderInputType::SORT_BY_ID:
                        $qb->orderBy('i.id', $direction);
                        break;
                    case OrderInputType::SORT_BY_TITLE:
                        $qb->orderBy('i.title', $direction);
                        break;
                    case OrderInputType::SORT_BY_DESCRIPTION:
                        $qb->orderBy('i.notes', $direction);
                        break;
                    case OrderInputType::SORT_BY_PROJECT:
                        $qb->orderBy('p.name', $direction);
                        break;
                    case OrderInputType::SORT_BY_STATUS:
                        $qb->orderBy('i.state', $direction);
                        break;
                    case OrderInputType::SORT_BY_CREATED_BY:
                        if (!in_array('u', $qb->getAllAliases())) {
                            $qb->leftJoin('i.createdBy', 'u');
                        }

                        $qb->orderBy('u.email', $direction);
                        break;
                    case OrderInputType::SORT_BY_CREATED_AT:
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
            ->where('i.state = :importingState')
            ->andWhere('i <> :importCandidate')
            ->andWhere('i.countryIso3 = :country')
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
            ->andWhere('i.countryIso3 = :country')
            ->setParameter('country', $import->getCountryIso3())
        ;

        return $qb->getQuery()->getResult();
    }

    public function getFinishedWithInvalidFiles(): array
    {
        return $this->createQueryBuilder('i')
            ->join('i.importInvalidFiles', 'if')
            ->where('i.state IN (:states)')
            ->setParameter('states',  [
                ImportState::FINISHED,
                ImportState::CANCELED,
            ])
            ->getQuery()->getResult();
    }
}
