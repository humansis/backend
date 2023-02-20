<?php

namespace Repository;

use DateTimeImmutable;
use DateTimeInterface;
use Entity\Beneficiary;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Entity\Smartcard;
use Enum\SmartcardStates;
use Repository\Helper\TRepositoryHelper;

/**
 * Class SmartcardRepository.
 *
 * @method Smartcard find($id)
 */
class SmartcardRepository extends EntityRepository
{
    use TRepositoryHelper;

    public function findBySerialNumberAndBeneficiary(string $serialNumber, ?Beneficiary $beneficiary = null): ?Smartcard
    {
        $qb = $this->createQueryBuilder('s')
            ->andWhere('s.serialNumber = :serialNumber')
            ->setParameter('serialNumber', strtoupper($serialNumber))
            ->orderBy('s.disabledAt', 'desc')
            ->orderBy('s.createdAt', 'desc')
            ->orderBy('s.id', 'desc')
            ->setMaxResults(1);
        if (null !== $beneficiary) {
            $qb
                ->andWhere('s.beneficiary = :beneficiary')
                ->setParameter('beneficiary', $beneficiary);
        } else {
            $qb->andWhere('s.beneficiary IS NULL');
        }

        try {
            return $qb->getQuery()->getSingleResult();
        } catch (NoResultException) {
            return null;
        }
    }

    public function disable(Smartcard $smartcard): void
    {
        $smartcard->setState(SmartcardStates::INACTIVE);
        $smartcard->setDisabledAt(new DateTimeImmutable());
        $this->_em->persist($smartcard);
    }

    public function disableBySerialNumber(
        string $serialNumber,
        string $state = SmartcardStates::REUSED,
        ?DateTimeInterface $timeOfEvent = null
    ): void {
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
     *
     * @return string[] list of smartcard serial numbers
     */
    public function findBlocked(string $countryCode): array
    {
        $qb = $this->createQueryBuilder('s')
            ->distinct(true)
            ->select(['s.serialNumber'])
            ->join('s.beneficiary', 'b')
            ->join('b.household', 'h')
            ->join('h.projects', 'p')
            ->andWhere('p.countryIso3 = :countryCode')
            ->andWhere('s.state IN (:smartcardBlockedStates)')
            ->orderBy('s.id', 'desc')
            ->setParameter('countryCode', $countryCode)
            ->setParameter(
                'smartcardBlockedStates',
                [SmartcardStates::UNASSIGNED, SmartcardStates::INACTIVE, SmartcardStates::CANCELLED]
            );

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
            if ((is_countable($smartcards) ? count($smartcards) : 0) > 1) {
                //TODO log
                //$this->logger->error("There is inconsistency in the database. Smartcard '$serialNumber' has " . count($smartcardBeneficiaries) . ' active entries.');
            }

            return $smartcards[0];
        }
    }

    public function findBySerialNumberAndBeneficiaryId(string $serialNumber, int $beneficiary): ?Smartcard
    {
        $smartcards = $this->createQueryBuilder('s')
            ->andWhere('s.serialNumber = :serialNumber')
            ->andWhere('s.beneficiary = :beneficiary')
            ->setParameter('beneficiary', $beneficiary)
            ->setParameter('serialNumber', strtoupper($serialNumber))
            ->orderBy('s.disabledAt', 'desc')
            ->orderBy('s.createdAt', 'desc')
            ->orderBy('s.id', 'desc')
            ->setMaxResults(1)
            ->getQuery()->getResult();

        return empty($smartcards) ? null : $smartcards[0];
    }
}
