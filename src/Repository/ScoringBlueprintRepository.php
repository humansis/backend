<?php

declare(strict_types=1);

namespace Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Entity\ScoringBlueprint;
use InputType\ScoringBlueprintFilterInputType;
use Request\Pagination;

class ScoringBlueprintRepository extends EntityRepository
{
    public function findByParams(
        ?string $countryIso3 = null,
        ?Pagination $pagination = null,
        ?ScoringBlueprintFilterInputType $filter = null
    ): Paginator {
        $qb = $this->createQueryBuilder('s');

        if (null !== $countryIso3) {
            $qb->andWhere('s.countryIso3 = :country')
                ->setParameter('country', $countryIso3);
        }

        if ($filter) {
            if ($filter->hasArchived()) {
                $qb->andWhere('s.archived = :archived')
                    ->setParameter('archived', $filter->isArchived());
            }
        }

        if ($pagination) {
            $qb->setMaxResults($pagination->getLimit());
            $qb->setFirstResult($pagination->getOffset());
        }

        return new Paginator($qb);
    }

    /**
     *
     * @return ScoringBlueprint|object|null
     */
    public function findActive(?int $id, string $iso3)
    {
        return $this->findOneBy(['id' => $id, 'archived' => false, 'countryIso3' => $iso3]);
    }
}
