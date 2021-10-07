<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper;

use BeneficiaryBundle\Entity\Beneficiary;

class AssistanceBeneficiaryOfflineAppMapper extends AbstractAssistanceBeneficiaryMapper
{
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return parent::supports($object, $format, $context) &&
            $object->getBeneficiary() instanceof Beneficiary &&
            isset($context['offline-app']) && $context['offline-app'] === true;
    }

    public function getBeneficiaryId(): int
    {
        return $this->object->getBeneficiary()->getId();
    }

    public function getLastSmartcardDepositId(): ?int
    {
        return $this->object->getSmartcardDeposits()->last() ?: null;
    }
}
