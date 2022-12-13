<?php

declare(strict_types=1);

namespace Repository\Assistance;

use DateTime;
use DateTimeInterface;
use Doctrine\ORM\EntityRepository;
use Entity\Location;
use Entity\Beneficiary;
use Entity\Assistance;
use Entity\AssistanceBeneficiary;
use Enum\AssistanceTargetType;
use InputType\Assistance\ReliefPackageFilterInputType;
use InvalidArgumentException;
use Repository\LocationRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Entity\Assistance\ReliefPackage;
use Enum\ModalityType;
use Enum\ReliefPackageState;
use InputType\Assistance\VendorReliefPackageFilterInputType;
use Entity\Vendor;
use Enum\SmartcardStates;

class ReliefPackageRepository extends EntityRepository
{
    public function getForVendor(Vendor $vendor, string $country, VendorReliefPackageFilterInputType $filterInputType): Paginator
    {
        $vendorLocation = $vendor->getLocation();
        if (null === $vendorLocation) {
            throw new InvalidArgumentException("Vendor need to be in location");
        }

        /** @var LocationRepository $locationRepository */
        $locationRepository = $this->getEntityManager()->getRepository(Location::class);

        $qb = $this->createQueryBuilder('rp')
            ->join('rp.assistanceBeneficiary', 'ab', Join::WITH, 'ab.removed = 0')
            ->join('ab.assistance', 'a')
            ->join(Beneficiary::class, 'b', Join::WITH, 'b.id=IDENTITY(ab.beneficiary) AND b.archived = 0')
            ->join(
                'b.smartcards',
                's',
                Join::WITH,
                's.beneficiary=b AND s.state=:smartcardStateActive'
            ) //filter only bnf with active card
            ->join('a.location', 'l');

        //if vendor has adm >= 2 filled, try to filter by adm2
        if (null !== $vendorLocation->getAdm2Id()) {
            /** @var Location $vendorLocationAdm2 */
            $vendorLocationAdm2 = $vendorLocation->getLvl() === 2
                ? $vendorLocation
                : $vendorLocation->getLocationByLevel(2);

            $qbLoc = $locationRepository->addChildrenLocationsQueryBuilder($vendorLocationAdm2);

            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->eq('l.id', ':adm2Id'), //assistance in adm2
                    $qb->expr()->eq('l.id', ':adm1Id'),  //assistance in adm1 only
                    $qb->expr()->exists($qbLoc->getDQL()) //assistance in adm > 2
                )
            )->setParameters([
                'adm2Id' => $vendorLocationAdm2->getId(),
                'adm1Id' => $vendorLocationAdm2->getParentLocation()->getId(),
            ]);
        } else {  //vendor location is adm1, filter by assistance in same or children location
            $qbLoc = $locationRepository->addChildrenLocationsQueryBuilder($vendorLocation, 'lc', true);

            $qb->andWhere(
                $qb->expr()->exists($qbLoc->getDQL())
            );
        }

        $qb->andWhere('(a.dateExpiration > :currentDate OR a.dateExpiration IS NULL)')
            ->andWhere('a.remoteDistributionAllowed = true')
            ->andWhere('a.archived = false')
            ->andWhere('a.validatedBy IS NOT NULL')
            ->andWhere('a.completed = false')
            ->setParameter('smartcardStateActive', SmartcardStates::ACTIVE)
            ->setParameter('iso3', $country)
            ->setParameter('currentDate', new DateTime());

        if ($filterInputType->hasLastModifiedFrom()) {
            $qb->andWhere('rp.lastModifiedAt >= :lastModifiedFrom')
                ->setParameter('lastModifiedFrom', $filterInputType->getLastModifiedFrom());
        }
        if ($filterInputType->hasStates()) {
            $qb->andWhere('rp.state IN (:states)')
                ->setParameter('states', $filterInputType->getStates());
        } else {
            $qb->andWhere('rp.state = :state')
                ->setParameter('state', ReliefPackageState::TO_DISTRIBUTE);
        }

        foreach ($qbLoc->getParameters() as $parameter) {
            $qb->setParameter($parameter->getName(), $parameter->getValue());
        }


        return new Paginator($qb);
    }

    public function findByAssistance(Assistance $assistance, ?ReliefPackageFilterInputType $filter = null): Paginator
    {
        $qb = $this->createQueryBuilder('rp')
            ->join('rp.assistanceBeneficiary', 'ab')
            ->join('ab.beneficiary', 'abstB', Join::WITH, 'abstB.archived = 0')
            ->andWhere('IDENTITY(ab.assistance) = :assistance')
            ->setParameter('assistance', $assistance->getId());
        if ($filter && $filter->hasIds()) {
            $qb->andWhere('rp.id IN (:ids)')
                ->setParameter('ids', $filter->getIds());
        }

        return new Paginator($qb);
    }

    /**
     * @return float|int|mixed|string|null
     * @throws NonUniqueResultException
     */
    public function findByAssistanceAndBeneficiary(Assistance $assistance, Beneficiary $beneficiary)
    {
        return $this->createQueryBuilder('rp')
            ->join('rp.assistanceBeneficiary', 'ab', Join::WITH, 'ab.removed = 0')
            ->join('ab.beneficiary', 'abstB', Join::WITH, 'abstB.archived = 0')
            ->andWhere('ab.assistance = :assistance')
            ->andWhere('ab.beneficiary = :beneficiary')
            ->setParameter('assistance', $assistance)
            ->setParameter('beneficiary', $beneficiary)
            ->getQuery()->getResult();
    }

    public function save(ReliefPackage $package): void
    {
        $this->_em->persist($package);
        $this->_em->flush();
    }

    /**
     *
     * @return float|int|mixed|string
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function sumReliefPackagesAmountByAssistance(Assistance $assistance, ?array $reliefPackageStates = null)
    {
        $qb = $this->createQueryBuilder('rp');
        $qb->select('SUM(rp.amountToDistribute)')
            ->join('rp.assistanceBeneficiary', 'ab')
            ->andWhere('IDENTITY(ab.assistance) = :assistance')
            ->setParameter('assistance', $assistance->getId());

        if ($reliefPackageStates) {
            $qb->andWhere('rp.state IN (:states)')
                ->setParameter('states', $reliefPackageStates);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     *
     * @return float|int|mixed|string
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function sumDistributedReliefPackagesAmountByAssistance(
        Assistance $assistance,
        ?array $reliefPackageStates = null
    ) {
        $qb = $this->createQueryBuilder('rp');
        $qb->select('SUM(rp.amountDistributed)')
            ->join('rp.assistanceBeneficiary', 'ab')
            ->andWhere('IDENTITY(ab.assistance) = :assistance')
            ->setParameter('assistance', $assistance->getId());

        if ($reliefPackageStates) {
            $qb->andWhere('rp.state IN (:states)')
                ->setParameter('states', $reliefPackageStates);
        }

        $result = $qb->getQuery()->getSingleScalarResult();
        if (!$result) {
            return 0;
        }

        return $result;
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findRandomWithNotValidatedAssistance(): ?ReliefPackage
    {
        $qb1 = $this->createQueryBuilder('arp');
        $qb1->select('IDENTITY(arp.assistanceBeneficiary)')
            ->andWhere('arp.state != :state')
            ->groupBy('arp.assistanceBeneficiary')
            ->having('COUNT(arp.id) > 0');

        $qb = $this->createQueryBuilder('rp');
        $qb->leftJoin('rp.assistanceBeneficiary', 'ab')
            ->leftJoin('ab.assistance', 'a')
            ->andWhere('ab.removed = :removed')
            ->setParameter('removed', false)
            ->andWhere('a.validatedBy IS NULL')
            ->andWhere('a.completed = :completed')
            ->setParameter('completed', false)
            ->andWhere('a.archived = :archived')
            ->setParameter('archived', false)
            ->andWhere('a.targetType = :targetType')
            ->setParameter('targetType', AssistanceTargetType::INDIVIDUAL)
            ->andWhere('rp.amountToDistribute IS NOT NULL')
            ->andWhere('rp.state = :toDistributeState')
            ->setParameter('toDistributeState', ReliefPackageState::TO_DISTRIBUTE)
            ->setMaxResults(1)
            ->andWhere('ab.id NOT IN (' . $qb1->getDQL() . ')')
            ->setParameter('state', ReliefPackageState::TO_DISTRIBUTE);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
