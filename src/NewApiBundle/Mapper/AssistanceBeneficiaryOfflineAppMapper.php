<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper;

use BeneficiaryBundle\Entity\Beneficiary;

class AssistanceBeneficiaryOfflineAppMapper extends AbstractAssistanceBeneficiaryMapper
{
    use MapperContextTrait;

    public function supports(object $object, $format = null, array $context = null): bool
    {
        return parent::supports($object, $format, $context) &&
            $object->getBeneficiary() instanceof Beneficiary &&
            $this->isOfflineApp($context);
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
