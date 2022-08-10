<?php

namespace NewApiBundle\Repository;

use NewApiBundle\Entity\Modality;

/**
 * @method Modality|null findOneByName(string $name)
 */
class ModalityRepository extends \Doctrine\ORM\EntityRepository
{
    public function getNames(): array
    {
        $resultArray = $this->createQueryBuilder('m')
            ->select('m.name')
            ->getQuery()
            ->getArrayResult();

        return array_column($resultArray, 'name');
    }
}
