<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper\Assistance;

use BeneficiaryBundle\Entity\Beneficiary;
use TransactionBundle\Entity\Transaction;
use VoucherBundle\Entity\SmartcardDeposit;

class TargetBeneficiaryMapper extends AbstractTargetMapper
{
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return parent::supports($object, $format, $context) &&
            $object->getBeneficiary() instanceof Beneficiary &&
            !isset($context['offline-app']);
    }

    public function getBeneficiaryId(): int
    {
        return $this->object->getBeneficiary()->getId();
    }
}