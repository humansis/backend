<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper;

use BeneficiaryBundle\Entity\Beneficiary;
use TransactionBundle\Entity\Transaction;
use VoucherBundle\Entity\SmartcardDeposit;

class AssistanceBeneficiaryMapper extends AbstractAssistanceBeneficiaryMapper
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

    public function getSmartcardDepositIds(): array
    {
        return array_map(function (SmartcardDeposit $smartcardDeposit) {
            return $smartcardDeposit->getId();
        }, $this->object->getSmartcardDeposits()->toArray());
    }

    public function getTransactionIds(): array
    {
        return []; // TODO: remove after PIN-3249
    }
}
