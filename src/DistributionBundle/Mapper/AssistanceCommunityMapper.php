<?php

declare(strict_types=1);

namespace DistributionBundle\Mapper;

use BeneficiaryBundle\Entity\Community;
use BeneficiaryBundle\Mapper\CommunityMapper;
use DistributionBundle\Entity\DistributionBeneficiary;

class AssistanceCommunityMapper extends AssistanceBeneficiaryMapper
{
    /** @var CommunityMapper */
    private $communityMapper;

    /**
     * AssistanceCommunityMapper constructor.
     *
     * @param CommunityMapper $communityMapper
     */
    public function __construct(CommunityMapper $communityMapper)
    {
        $this->communityMapper = $communityMapper;
    }

    public function toFullArray(?DistributionBeneficiary $assistanceCommunity): ?array
    {
        if (!$assistanceCommunity) {
            return null;
        }

        $community = $assistanceCommunity->getBeneficiary();
        if (!$community instanceof Community) {
            return $this->toFlatArray($assistanceCommunity);;
        }

        $flatBase = $this->toFlatArray($assistanceCommunity);

        return array_merge($flatBase, [
            'community' => $this->communityMapper->toFullArray($community),
        ]);
    }

    public function toFullArrays(iterable $assistanceCommunities): iterable
    {
        foreach ($assistanceCommunities as $assistanceCommunity) {
            $ac = $this->toFullArray($assistanceCommunity);
            if ($ac) yield $ac;
        }
    }
}
