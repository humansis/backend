<?php

declare(strict_types=1);

namespace DistributionBundle\Mapper;

use NewApiBundle\Entity\Community;
use NewApiBundle\MapperDeprecated\CommunityMapper;
use DistributionBundle\Entity\AssistanceBeneficiary;
use TransactionBundle\Mapper\TransactionMapper;
use VoucherBundle\Mapper\BookletMapper;

class AssistanceCommunityMapper extends AssistanceBeneficiaryMapper
{

    public function toFullArray(?AssistanceBeneficiary $assistanceCommunity): ?array
    {
        if (!$assistanceCommunity) {
            return null;
        }

        $community = $assistanceCommunity->getBeneficiary();
        if (!$community instanceof Community) {
            $class = get_class($community);
            throw new \InvalidArgumentException("AssistanceBeneficiary #{$assistanceCommunity->getId()} is $class instead of ".Community::class);
        }

        $flatBase = $this->toBaseArray($assistanceCommunity);

        return array_merge($flatBase, [
            'community' => [],
        ]);
    }

    public function toFullArrays(iterable $assistanceCommunities): iterable
    {
        foreach ($assistanceCommunities as $assistanceCommunity) {
            yield $this->toFullArray($assistanceCommunity);
        }
    }
}
