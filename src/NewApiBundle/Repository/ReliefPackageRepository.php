<?php
declare(strict_types=1);

namespace NewApiBundle\Repository;


use BeneficiaryBundle\Entity\Beneficiary;
use CommonBundle\Entity\Adm1;
use CommonBundle\Entity\Adm2;
use CommonBundle\Entity\Adm3;
use DistributionBundle\Entity\Assistance;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\Tools\Pagination\Paginator;
use NewApiBundle\Entity\ReliefPackage;
use NewApiBundle\Enum\ModalityType;
use NewApiBundle\Enum\ReliefPackageState;
use VoucherBundle\Entity\Vendor;

class ReliefPackageRepository extends \Doctrine\ORM\EntityRepository
{
    public function findForSmartcardByAssistanceBeneficiary(Assistance $assistance, Beneficiary $beneficiary): ?ReliefPackage
    {
        $qb = $this->createQueryBuilder('rp')
            ->join('rp.assistanceBeneficiary', 'ab')
            ->andWhere('ab.assistance = :assistance')
            ->andWhere('ab.beneficiary = :beneficiary')
            ->andWhere('rp.modalityType = :smartcardModality')
            ->setParameter('assistance', $assistance)
            ->setParameter('beneficiary', $beneficiary)
            ->setParameter('smartcardModality', ModalityType::SMART_CARD);

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
            ->join('rp.assistanceBeneficiary', 'ab')
            ->join('ab.assistance', 'a')
            ->join('a.location', 'l')
            ->leftJoin('l.adm4', 'adm4')
            ->leftJoin('l.adm3', 'locAdm3')
            ->leftJoin('l.adm2', 'locAdm2')
            ->leftJoin('l.adm1', 'locAdm1')
            ->leftJoin(Adm3::class, 'adm3', Join::WITH, 'adm3.id = COALESCE(IDENTITY(adm4.adm3, \'id\'), locAdm3.id)')
            ->leftJoin(Adm2::class, 'adm2', Join::WITH, 'adm2.id = COALESCE(IDENTITY(adm3.adm2, \'id\'), locAdm2.id)')
            ->leftJoin(Adm1::class, 'adm1', Join::WITH, 'adm1.id = COALESCE(IDENTITY(adm2.adm1, \'id\'), locAdm1.id)');

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
            ->andWhere('(a.dateExpiration < :currentDate OR a.dateExpiration IS NULL)')
            ->setParameter('state', ReliefPackageState::TO_DISTRIBUTE)
            ->setParameter('currentDate', new \DateTime());

        return new Paginator($qb);
    }
}
