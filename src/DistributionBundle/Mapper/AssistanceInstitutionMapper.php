<?php

declare(strict_types=1);

namespace DistributionBundle\Mapper;

use BeneficiaryBundle\Entity\Institution;
use BeneficiaryBundle\Mapper\InstitutionMapper;
use DistributionBundle\Entity\DistributionBeneficiary;

class AssistanceInstitutionMapper extends AssistanceBeneficiaryMapper
{
    /** @var InstitutionMapper */
    private $institutionMapper;

    /**
     * AssistanceInstitutionMapper constructor.
     *
     * @param InstitutionMapper $communityMapper
     */
    public function __construct(InstitutionMapper $communityMapper)
    {
        $this->institutionMapper = $communityMapper;
    }

    public function toFullArray(?DistributionBeneficiary $assistanceInstitution): ?array
    {
        if (!$assistanceInstitution) {
            return null;
        }

        $institution = $assistanceInstitution->getBeneficiary();
        if (!$institution instanceof Institution) {
            $class = get_class($assistanceInstitution);
            throw new \InvalidArgumentException("DistributionBeneficiary #{$assistanceInstitution->getId()} is $class instead of ".Institution::class);
        }

        $flatBase = $this->toFlatArray($assistanceInstitution);

        return array_merge($flatBase, [
            'institution' => $this->institutionMapper->toFullArray($institution),
        ]);
    }

    public function toFullArrays(iterable $assistanceInstitutions): iterable
    {
        foreach ($assistanceInstitutions as $assistanceInstitution) {
            $this->toFullArray($assistanceInstitution);
        }
    }
}
