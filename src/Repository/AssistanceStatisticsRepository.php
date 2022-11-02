<?php

declare(strict_types=1);

namespace Repository;

use Entity\Assistance;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Entity\AssistanceStatistics;
use InputType\AssistanceStatisticsFilterInputType;

class AssistanceStatisticsRepository extends EntityRepository
{
    /**
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function findByAssistance(Assistance $assistance, ?string $countryIso3 = null): AssistanceStatistics
    {
        $qb = $this->createQueryBuilder('stat')
            ->andWhere('stat.assistance = :assistance')
            ->setParameter('assistance', $assistance);

        if ($countryIso3) {
            $qb->join('stat.assistance', 'a')
                ->join('a.project', 'p')
                ->andWhere('p.countryIso3 = :iso3')
                ->setParameter('iso3', $countryIso3);
        }

        return $qb
            ->getQuery()
            ->getSingleResult();
    }

    /**
     *
     * @return AssistanceStatistics[]|Paginator
     */
    public function findByParams(string $countryIso3, AssistanceStatisticsFilterInputType $filter): iterable
    {
        $qbr = $this->createQueryBuilder('stat')
            ->join('stat.assistance', 'a')
            ->join('a.project', 'p')
            ->andWhere('p.countryIso3 = :iso3')
            ->setParameter('iso3', $countryIso3);

        if ($filter) {
            if ($filter->hasIds()) {
                $qbr->andWhere('stat.assistance IN (:assistances)')
                    ->setParameter('assistances', $filter->getIds());
            }
        }

        return $qbr->getQuery()->getResult();
    }
}
