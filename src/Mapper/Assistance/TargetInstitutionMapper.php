<?php

declare(strict_types=1);

namespace Mapper\Assistance;

use Entity\Institution;

class TargetInstitutionMapper extends AbstractTargetMapper
{
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return parent::supports($object, $format, $context) && $object->getBeneficiary() instanceof Institution;
    }

    public function getInstitutionId(): int
    {
        return $this->object->getBeneficiary()->getId();
    }
}
