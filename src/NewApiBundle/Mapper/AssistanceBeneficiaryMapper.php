<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper;

use BeneficiaryBundle\Entity\Beneficiary;

class AssistanceBeneficiaryMapper extends AbstractAssistanceBeneficiaryMapper
{
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return parent::supports($object, $format, $context) && $object->getBeneficiary() instanceof Beneficiary;
    }

    public function getBeneficiaryId(): int
    {
        return $this->object->getBeneficiary()->getId();
    }
}
