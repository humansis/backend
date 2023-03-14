<?php

declare(strict_types=1);

namespace Repository;

use Doctrine\ORM\EntityRepository;
use Entity\CountrySpecific;

class CountrySpecificAnswerRepository extends EntityRepository
{
    /**
     * Returns true if there is at least one household with multiple answers for the given country specific.
     */
    public function hasMoreAnswers(CountrySpecific $countrySpecific): bool
    {
        $result = $this->createQueryBuilder('csa')
            ->select('COUNT(csa.household)')
            ->where('csa.countrySpecific = :countrySpecific')
            ->setParameter('countrySpecific', $countrySpecific)
            ->groupBy('csa.household')
            ->having('COUNT(csa.household) > 1')
            ->getQuery()
            ->getResult();

        return !empty($result);
    }
}
