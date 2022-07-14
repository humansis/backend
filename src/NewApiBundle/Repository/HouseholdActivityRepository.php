<?php

namespace NewApiBundle\Repository;

use NewApiBundle\Entity\Household;
use NewApiBundle\Entity\HouseholdActivity;
use Doctrine\ORM\EntityRepository;

class HouseholdActivityRepository extends EntityRepository
{
    /**
     * @param Household $household
     *
     * @return HouseholdActivity[]
     */
    public function findByHousehold(Household $household)
    {
        return $this->findBy(['household' => $household->getId()], ['createdAt' => 'DESC']);
    }
}
