<?php

namespace VoucherBundle\Repository;

use Doctrine\ORM\EntityRepository;
use VoucherBundle\Entity\Smartcard;
use VoucherBundle\Enum\SmartcardStates;

/**
 * Class SmartcardRepository.
 *
 * @method Smartcard find($id)
 */
class SmartcardRepository extends EntityRepository
{
    public function findActiveBySerialNumber(string $serialNumber): ?Smartcard
    {
        $qb = $this->createQueryBuilder('s')
            ->andWhere('s.serialNumber = :serialNumber')
            ->andWhere('s.state = :state')
            ->setParameter('serialNumber', strtoupper($serialNumber))
            ->setParameter('state', SmartcardStates::ACTIVE);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Returns list of blocked smardcards.
     *
     * @param string $countryCode
     *
     * @return string[] list of smartcard serial numbers
     */
    public function findBlocked(string $countryCode)
    {
        $qb = $this->createQueryBuilder('s')
            ->distinct(true)
            ->select(['s.serialNumber'])
            ->join('s.beneficiary', 'b')
            ->join('b.household', 'h')
            ->join('h.projects', 'p')
            ->andWhere('p.iso3 = :countryCode')
            ->andWhere('s.state != :smartcardState')
            ->setParameter('countryCode', $countryCode)
            ->setParameter('smartcardState', SmartcardStates::ACTIVE);

        return $qb->getQuery()->getResult('plain_values_hydrator');
    }
}
