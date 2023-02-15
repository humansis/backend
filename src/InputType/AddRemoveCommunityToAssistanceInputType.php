<?php

declare(strict_types=1);

namespace InputType;

use Symfony\Component\Validator\Constraints as Assert;

#[Assert\GroupSequence(['AddRemoveCommunityToAssistanceInputType', 'Strict'])]
class AddRemoveCommunityToAssistanceInputType extends AddRemoveAbstractBeneficiaryToAssistanceInputType
{
    #[Assert\All(constraints: [new Assert\Type('int', groups: ['Strict'])], groups: ['Strict'])]
    #[Assert\Type('array')]
    protected $communityIds;

    public function setCommunityIds($communityIds)
    {
        $this->communityIds = $communityIds;
    }

    public function getCommunityIds()
    {
        return $this->communityIds;
    }
}
