<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper;

use BeneficiaryBundle\Entity\Community;
use TransactionBundle\Entity\Transaction;
use VoucherBundle\Entity\SmartcardDeposit;

class AssistanceCommunityMapper extends AbstractAssistanceBeneficiaryMapper
{
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return parent::supports($object, $format, $context) && $object->getBeneficiary() instanceof Community;
    }

    public function getCommunityId(): int
    {
        return $this->object->getBeneficiary()->getId();
    }

    public function getTransactionIds(): array
    {
        return []; // TODO: remove after PIN-3249
    }

    public function getSmartcardDepositIds(): array
    {
        return array_map(function (SmartcardDeposit $smartcardDeposit) {
            return $smartcardDeposit->getId();
        }, $this->object->getSmartcardDeposits()->toArray());
    }
}
