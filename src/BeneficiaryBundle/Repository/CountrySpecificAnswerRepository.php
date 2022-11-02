<?php

namespace BeneficiaryBundle\Repository;

use BeneficiaryBundle\Entity\Household;

/**
 * CountrySpecificAnswerRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class CountrySpecificAnswerRepository extends \Doctrine\ORM\EntityRepository
{
    public function hasValue(int $countrySpecificId, $answer, string $conditionString, Household $household)
    {
        $qb = $this->createQueryBuilder('csa');

        $q  = $qb->where('csa.countrySpecific = :countrySpecificId')
            ->setParameter('countrySpecificId', $countrySpecificId);
        
        $hasAnswers = $q->getQuery()->getResult();
        if (!$hasAnswers && $conditionString === "!=") {
            return true;
        } elseif ($hasAnswers) {
            $q = $q->andWhere('csa.answer '. $conditionString . ' :answer')
            ->setParameter('answer', $answer)
            ->andWhere('csa.household = :household')
            ->setParameter('household', $household);
            return $q->getQuery()->getResult();
        } else {
            return false;
        }
    }
}