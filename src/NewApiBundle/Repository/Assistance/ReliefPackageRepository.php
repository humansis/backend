<?php
declare(strict_types=1);

namespace NewApiBundle\Repository\Assistance;

use CommonBundle\Entity\Location;
use NewApiBundle\Entity\Beneficiary;
use NewApiBundle\Entity\Assistance;
use NewApiBundle\Entity\AssistanceBeneficiary;
use CommonBundle\Entity\Adm1;
use CommonBundle\Entity\Adm2;
use CommonBundle\Entity\Adm3;
use BeneficiaryBundle\Entity\Beneficiary;
use CommonBundle\Entity\Location;
use CommonBundle\Repository\LocationRepository;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\AssistanceBeneficiary;
use NewApiBundle\Enum\AssistanceTargetType;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Tools\Pagination\Paginator;
use NewApiBundle\Entity\Assistance\ReliefPackage;
use NewApiBundle\Enum\ModalityType;
use NewApiBundle\Enum\ReliefPackageState;
use NewApiBundle\InputType\Assistance\ReliefPackageFilterInputType;
use VoucherBundle\Entity\Vendor;
use VoucherBundle\Enum\SmartcardStates;

class ReliefPackageRepository extends \Doctrine\ORM\EntityRepository
{
    /**
     * @param AssistanceBeneficiary   $assistanceBeneficiary
     * @param string|null             $reliefPackageStatus
     * @param \DateTimeInterface|null $beforeDate
     *
     * @return ReliefPackage|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findForSmartcardByAssistanceBeneficiary(
        AssistanceBeneficiary $assistanceBeneficiary,
        ?string               $reliefPackageStatus = null,
        ?\DateTimeInterface   $beforeDate = null
    ): ?ReliefPackage {
        $qb = $this->createQueryBuilder('rp')
            ->andWhere('rp.modalityType = :smartcardModality')
            ->andWhere('rp.assistanceBeneficiary = :ab')
            ->setParameter('smartcardModality', ModalityType::SMART_CARD)
            ->setParameter('ab', $assistanceBeneficiary);
        if ($reliefPackageStatus) {
            $qb->andWhere('rp.state = :state')
                ->setParameter('state', $reliefPackageStatus);
        }

        if ($beforeDate) {
            $qb->andWhere('rp.createdAt < :before')
                ->setParameter('before', $beforeDate)
                ->orderBy('rp.createdAt', 'DESC');
        } else {
            $qb->orderBy('rp.id', 'DESC');
        }
        $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param Vendor $vendor
     * @param string $country
     * 
     * @return Paginator
     */

    public function getForVendor(Vendor $vendor, string $country): Paginator
    {
        $vendorLocation = $vendor->getLocation();
        if (null === $vendorLocation) {
            throw new \InvalidArgumentException("Vendor need to be in location");
        }

        /** @var LocationRepository $locationRepository */
        $locationRepository = $this->getEntityManager()->getRepository(Location::class);

        $qb = $this->createQueryBuilder('rp')
            ->join('rp.assistanceBeneficiary', 'ab', Join::WITH, 'ab.removed = 0')
            ->join('ab.assistance', 'a')
            ->join('ab.beneficiary', 'abstB')
            ->join(Beneficiary::class, 'b', Join::WITH, 'b.id=abstB.id AND b.archived = 0')
            ->join('b.smartcards', 's', Join::WITH, 's.beneficiary=b AND s.state=:smartcardStateActive') //filter only bnf with active card
            ->join('a.location', 'l');

        //if vendor has adm >= 2 filled, try to filter by adm2
        if (null !== $vendorLocation->getAdm2Id()) {

            /** @var Location $vendorLocationAdm2 */
            $vendorLocationAdm2 = $vendorLocation->getLvl() === 2
                ? $vendorLocation
                : $vendorLocation->getLocationByLevel(2);

            $qbLoc = $locationRepository->addChildrenLocationsQueryBuilder($vendorLocationAdm2);

            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->eq('l.id', ':adm2Id'), //assistance in adm2
                $qb->expr()->eq('l.id', ':adm1Id'),  //assistance in adm1 only
                $qb->expr()->exists($qbLoc->getDQL()) //assistance in adm > 2
            ))->setParameters([
                'adm2Id' => $vendorLocationAdm2->getId(),
                'adm1Id' => $vendorLocationAdm2->getParentLocation()->getId(),
            ]);
        }
        //vendor location is adm1, filter by assistance in same or children location
        else {

            $qbLoc = $locationRepository->addChildrenLocationsQueryBuilder($vendorLocation, 'lc', true);

            $qb->andWhere(
                $qb->expr()->exists($qbLoc->getDQL())
            );
        }

        $qb->andWhere('rp.state = :state')
            ->andWhere('(a.dateExpiration > :currentDate OR a.dateExpiration IS NULL)')
            ->andWhere('a.remoteDistributionAllowed = true')
            ->andWhere('a.archived = false')
            ->andWhere('a.validatedBy IS NOT NULL')
            ->andWhere('a.completed = false')
            ->setParameter('smartcardStateActive', SmartcardStates::ACTIVE)
            ->setParameter('iso3', $country)
            ->setParameter('state', ReliefPackageState::TO_DISTRIBUTE)
            ->setParameter('currentDate', new \DateTime());

        foreach ($qbLoc->getParameters() as $parameter) {
            $qb->setParameter($parameter->getName(), $parameter->getValue());
        }

        return new Paginator($qb);
    }

    /**
     * @param Assistance                        $assistance
     * @param ReliefPackageFilterInputType|null $filter
     *
     * @return Paginator
     */
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
     * @param Assistance  $assistance
     * @param Beneficiary $beneficiary
     *
     * @return float|int|mixed|string|null
     * @throws \Doctrine\ORM\NonUniqueResultException
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
     * @param Assistance $assistance
     * @param array|null $reliefPackageStates
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
     * @param Assistance $assistance
     * @param array|null $reliefPackageStates
     *
     * @return float|int|mixed|string
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function sumDistributedReliefPackagesAmountByAssistance(Assistance $assistance, ?array $reliefPackageStates = null)
    {
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
     * @return ReliefPackage|null
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
            ->andWhere('ab.id NOT IN ('.$qb1->getDQL().')')
            ->setParameter('state', ReliefPackageState::TO_DISTRIBUTE);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
