<?php

namespace VoucherBundle\Repository;

use Doctrine\ORM\EntityRepository;
use VoucherBundle\Entity\Smartcard;

/**
 * Class SmartcardRepository.
 *
 * @method Smartcard find($id)
 */
class SmartcardRepository extends EntityRepository
{
    public function findBySerialNumber(string $serialNumber): ?Smartcard
    {
        $qb = $this->createQueryBuilder('s')
            ->andWhere('s.serialNumber = :serialNumber')
            ->setParameter('serialNumber', strtoupper($serialNumber));

        return $qb->getQuery()->getOneOrNullResult();
    }
}
