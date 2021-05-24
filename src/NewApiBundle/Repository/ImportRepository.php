<?php
declare(strict_types=1);

namespace NewApiBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use InvalidArgumentException;
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
}
