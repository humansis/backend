<?php
declare(strict_types=1);

namespace NewApiBundle\Repository;


use BeneficiaryBundle\Entity\Beneficiary;
use DistributionBundle\Entity\Assistance;
use NewApiBundle\Entity\ReliefPackage;
use NewApiBundle\Enum\ModalityType;

class ReliefPackageRepository extends \Doctrine\ORM\EntityRepository
{
    public function findForSmartcardByAssistanceBeneficiary(Assistance $assistance, Beneficiary $beneficiary): ?ReliefPackage
    {
        $qb = $this->createQueryBuilder('abc')
            ->join('abc.assistanceBeneficiary', 'ab')
            ->andWhere('ab.assistance = :assistance')
            ->andWhere('ab.beneficiary = :beneficiary')
            ->andWhere('abc.modalityType = :smartcardModality')
            ->setParameter('assistance', $assistance)
            ->setParameter('beneficiary', $beneficiary)
            ->setParameter('smartcardModality', ModalityType::SMART_CARD);

        return $qb->getQuery()->getOneOrNullResult();
    }
}
