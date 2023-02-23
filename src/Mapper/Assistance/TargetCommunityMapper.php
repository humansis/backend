<?php

declare(strict_types=1);

namespace Mapper\Assistance;

use Entity\Community;

class TargetCommunityMapper extends AbstractTargetMapper
{
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return parent::supports($object, $format, $context) && $object->getBeneficiary() instanceof Community;
    }

    public function getCommunityId(): int
    {
        return $this->object->getBeneficiary()->getId();
    }
}
