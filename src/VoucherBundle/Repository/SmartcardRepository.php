<?php

namespace VoucherBundle\Repository;

use BeneficiaryBundle\Entity\Beneficiary;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use VoucherBundle\Entity\Smartcard;
use VoucherBundle\Enum\SmartcardStates;

/**
 * Class SmartcardRepository.
 *
 * @method Smartcard find($id)
 */
class SmartcardRepository extends EntityRepository
{
    public function findBySerialNumber(string $serialNumber, Beneficiary $beneficiary): ?Smartcard
    {
        $qb = $this->createQueryBuilder('s')
            ->andWhere('s.serialNumber = :serialNumber')
            ->andWhere('s.beneficiary = :beneficiary')
            ->setParameter('serialNumber', strtoupper($serialNumber))
            ->setParameter('beneficiary', $beneficiary)
            ->orderBy('s.disabledAt', 'desc')
            ->setMaxResults(1)
        ;

        try {
            return $qb->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            return null;
        }
    }

    public function disableBySerialNumber(string $serialNumber, string $state = SmartcardStates::REUSED, ?\DateTimeInterface $timeOfEvent = null): void
    {
        $this->createQueryBuilder('s')
            ->set('s.state', ':disableState')
            ->set('s.disabledAt', ':when')
            ->andWhere('s.serialNumber = :serialNumber')
            ->setParameter('serialNumber', strtoupper($serialNumber))
            ->setParameter('disableState', $state)
            ->setParameter('when', $timeOfEvent)
            ->getQuery()
            ->execute();
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
