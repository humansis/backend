<?php
declare(strict_types=1);

namespace NewApiBundle\Repository\Assistance;


use BeneficiaryBundle\Entity\Beneficiary;
use CommonBundle\Entity\Adm1;
use CommonBundle\Entity\Adm2;
use CommonBundle\Entity\Adm3;
use DistributionBundle\Entity\Assistance;
use DistributionBundle\Entity\AssistanceBeneficiary;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Tools\Pagination\Paginator;
use NewApiBundle\Entity\Assistance\ReliefPackage;
use NewApiBundle\Enum\ModalityType;
use NewApiBundle\Enum\ReliefPackageState;
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
     *
     * @return Paginator
     */
    public function getForVendor(Vendor $vendor): Paginator
    {
        $qb = $this->createQueryBuilder('rp')
            ->join('rp.assistanceBeneficiary', 'ab', Join::WITH, 'ab.removed = 0')
            ->join('ab.assistance', 'a')
            ->join('ab.beneficiary', 'abstB')
            ->join(Beneficiary::class,  'b', Join::WITH, 'b.id=abstB.id AND b.archived = 0')
            ->join('b.smartcards', 's', Join::WITH, 's.beneficiary=b AND s.state=:smartcardStateActive') //filter only bnf with active card
            ->join('a.location', 'l')
            ->leftJoin('l.adm4', 'adm4')
            ->leftJoin('l.adm3', 'locAdm3')
            ->leftJoin('l.adm2', 'locAdm2')
            ->leftJoin('l.adm1', 'locAdm1')
            ->leftJoin(Adm3::class, 'adm3', Join::WITH, 'adm3.id = COALESCE(IDENTITY(adm4.adm3, \'id\'), locAdm3.id)')
            ->leftJoin(Adm2::class, 'adm2', Join::WITH, 'adm2.id = COALESCE(IDENTITY(adm3.adm2, \'id\'), locAdm2.id)')
            ->leftJoin(Adm1::class, 'adm1', Join::WITH, 'adm1.id = COALESCE(IDENTITY(adm2.adm1, \'id\'), locAdm1.id)')
            ->setParameter('smartcardStateActive', SmartcardStates::ACTIVE)
            ;

        //if both vendor and assistance has at least adm2 filled, try to filter by adm2. If not, filter by adm1.
        if (null !== $vendor->getLocation()->getAdm2Id()) {
            $qb->andWhere('( (adm2.id IS NOT NULL AND adm2.id = :vendorAdm2Id) OR (adm2.id IS NULL AND adm1.id = :vendorAdm1Id) )')
                ->setParameter('vendorAdm1Id', $vendor->getLocation()->getAdm1Id())
                ->setParameter('vendorAdm2Id', $vendor->getLocation()->getAdm2Id());
        } else {
            $qb->andWhere('adm1.id = :vendorAdm1Id')
                ->setParameter('vendorAdm1Id', $vendor->getLocation()->getAdm1Id());
        }

        $qb->andWhere('rp.state = :state')
            ->andWhere('(a.dateExpiration > :currentDate OR a.dateExpiration IS NULL)')
            ->andWhere('a.remoteDistributionAllowed = true')
            ->setParameter('state', ReliefPackageState::TO_DISTRIBUTE)
            ->setParameter('currentDate', new \DateTime());

        return new Paginator($qb);
    }

    public function findByAssistance(Assistance $assistance): Paginator
    {
        $qb = $this->createQueryBuilder('rp')
            ->join('rp.assistanceBeneficiary', 'ab', Join::WITH, 'ab.removed = 0')
            ->join('ab.beneficiary', 'abstB',Join::WITH, 'abstB.archived = 0')
            ->andWhere('IDENTITY(ab.assistance) = :assistance')
            ->setParameter('assistance', $assistance->getId())
        ;

        return new Paginator($qb);
    }
}
