<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper;

use BeneficiaryBundle\Entity\Institution;
use TransactionBundle\Entity\Transaction;
use VoucherBundle\Entity\SmartcardDeposit;

class AssistanceInstitutionMapper extends AbstractAssistanceBeneficiaryMapper
{
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return parent::supports($object, $format, $context) && $object->getBeneficiary() instanceof Institution;
    }

    public function getInstitutionId(): int
    {
        return $this->object->getBeneficiary()->getId();
    }

    public function getTransactionIds(): array
    {
        return array_map(function (Transaction $transaction) {
            return $transaction->getId();
        }, $this->object->getTransactions()->toArray());
    }

    public function getSmartcardDepositIds(): array
    {
        return array_map(function (SmartcardDeposit $smartcardDeposit) {
            return $smartcardDeposit->getId();
        }, $this->object->getSmartcardDeposits()->toArray());
    }
}
