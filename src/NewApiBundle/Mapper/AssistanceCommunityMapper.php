<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper;

use BeneficiaryBundle\Entity\Community;
use TransactionBundle\Entity\Transaction;

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
        return array_map(function (Transaction $transaction) {
            return $transaction->getId();
        }, $this->object->getTransactions()->toArray());
    }
}
