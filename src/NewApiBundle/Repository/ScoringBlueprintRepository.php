<?php
declare(strict_types=1);

namespace NewApiBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use NewApiBundle\Entity\ScoringBlueprint;
use NewApiBundle\InputType\ScoringBlueprintFilterInputType;
use NewApiBundle\Request\Pagination;

class ScoringBlueprintRepository extends EntityRepository
{

    public function findByParams(?string $countryIso3,
                                 ?Pagination $pagination = null,
                                 ?ScoringBlueprintFilterInputType $filter = null): Paginator
    {
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
     * @param $id
     * @param $iso3
     *
     * @return ScoringBlueprint|object|null
     */
    public function findActive($id, $iso3)
    {
        return $this->findOneBy(['id' => $id, 'archived' => false, 'countryIso3' => $iso3]);
    }

}
