<?php

namespace VoucherBundle\Repository;

use BeneficiaryBundle\Entity\Beneficiary;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use NewApiBundle\InputType\Smartcard\ChangeSmartcardInputType;
use VoucherBundle\Entity\Smartcard;
use VoucherBundle\Enum\SmartcardStates;

/**
 * Class SmartcardRepository.
 *
 * @method Smartcard find($id)
 */
class SmartcardRepository extends EntityRepository
{
    public function findBySerialNumberAndBeneficiary(string $serialNumber, ?Beneficiary $beneficiary = null): ?Smartcard
    {
        $qb = $this->createQueryBuilder('s')
            ->andWhere('s.serialNumber = :serialNumber')
            ->setParameter('serialNumber', strtoupper($serialNumber))
            ->orderBy('s.disabledAt', 'desc')
            ->orderBy('s.createdAt', 'desc')
            ->orderBy('s.id', 'desc')
            ->setMaxResults(1)
        ;
        if (null !== $beneficiary) {
            $qb
                ->andWhere('s.beneficiary = :beneficiary')
                ->setParameter('beneficiary', $beneficiary)
                ;
        } else {
            $qb->andWhere('s.beneficiary IS NULL');
        }

        try {
            return $qb->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            return null;
        }
    }

    public function disableBySerialNumber(string $serialNumber, string $state = SmartcardStates::REUSED, ?\DateTimeInterface $timeOfEvent = null): void
    {
        $this->createQueryBuilder('s')
            ->update()
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
            ->andWhere('s.state IN (:smartcardBlockedStates)')
            ->orderBy('s.id', 'desc')
            ->setParameter('countryCode', $countryCode)
            ->setParameter('smartcardBlockedStates', [SmartcardStates::UNASSIGNED, SmartcardStates::INACTIVE, SmartcardStates::CANCELLED]);

        return $qb->getQuery()->getResult('plain_values_hydrator');
    }

    public function findActiveBySerialNumber(string $serialNumber): ?Smartcard
    {
        $smartcards = $this->createQueryBuilder('s')
            ->andWhere('s.serialNumber = :serialNumber')
            ->andWhere('s.state = :stateActive')
            ->setParameter('serialNumber', strtoupper($serialNumber))
            ->setParameter('stateActive', SmartcardStates::ACTIVE)
            ->orderBy('s.id', 'desc')
            ->getQuery()->getResult();

        if (empty($smartcards)) {
            return null;
        } else {
            if (count($smartcards) > 1) {
                //TODO log
                //$this->logger->error("There is inconsistency in the database. Smartcard '$serialNumber' has " . count($smartcards) . ' active entries.');
            }

            return $smartcards[0];
        }
    }

    /**
     * @param Smartcard $smartcard
     *
     * @return void
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function save(Smartcard $smartcard): void
    {
        $this->_em->persist($smartcard);
        $this->_em->flush();
    }

    /**
     * @param string                   $serialNumber
     * @param ChangeSmartcardInputType $changeSmartcardInputType
     *
     * @return null|Smartcard
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findBySerialNumberAndChangeParameters(
        string                   $serialNumber,
        ChangeSmartcardInputType $changeSmartcardInputType
    ): ?Smartcard {
        $qb = $this->createQueryBuilder('s');
        $qb->andWhere('s.serialNumber = :serialNumber')
            ->andWhere('s.state = :state')
            ->andWhere('s.changedAt = :changedAt')
            ->setMaxResults(1)
            ->setParameters([
                'serialNumber' => $serialNumber,
                'state' => $changeSmartcardInputType->getState(),
                'changedAt' => $changeSmartcardInputType->getCreatedAt(),
            ]);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
