<?php declare(strict_types=1);

namespace CommonBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use NewApiBundle\InputType\AdmFilterInputType;

class AdmBaseRepository extends EntityRepository
{
    /**
     * @param AdmFilterInputType $filter
     *
     * @return Paginator
     */
    public function findByFilter(AdmFilterInputType $filter): Paginator
    {
        $qb = $this->createQueryBuilder('adm');

        if ($filter->hasIds()) {
            $qb->andWhere(
                $qb->expr()->in('adm.id', ':ids')
            );
            $qb->setParameter('ids', $filter->getIds());
        }

        if ($filter->hasFulltext()) {
            $orX = $qb->expr()->orX();
            $orX
                ->add($qb->expr()->eq('adm.id', ':id'))
                ->add($qb->expr()->like('adm.name', ':fulltext'))
                ->add($qb->expr()->like('adm.code', ':fulltext'));
            $qb->andWhere($orX);
            $qb->setParameter('id', $filter->getFulltext());
            $qb->setParameter('fulltext', '%'.$filter->getFulltext().'%');
        }

        return new Paginator($qb);
    }
}
