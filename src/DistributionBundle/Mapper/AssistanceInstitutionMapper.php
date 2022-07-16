<?php

declare(strict_types=1);

namespace DistributionBundle\Mapper;

use NewApiBundle\Entity\Institution;
use NewApiBundle\MapperDeprecated\InstitutionMapper;
use DistributionBundle\Entity\AssistanceBeneficiary;
use TransactionBundle\Mapper\TransactionMapper;
use VoucherBundle\Mapper\BookletMapper;

class AssistanceInstitutionMapper extends AssistanceBeneficiaryMapper
{

    public function toFullArray(?AssistanceBeneficiary $assistanceInstitution): ?array
    {
        if (!$assistanceInstitution) {
            return null;
        }

        $institution = $assistanceInstitution->getBeneficiary();
        if (!$institution instanceof Institution) {
            $class = get_class($institution);
            throw new \InvalidArgumentException("AssistanceBeneficiary #{$assistanceInstitution->getId()} is $class instead of ".Institution::class);
        }

        $flatBase = $this->toBaseArray($assistanceInstitution);

        return array_merge($flatBase, [
            'institution' => [],
        ]);
    }

    public function toFullArrays(iterable $assistanceInstitutions): iterable
    {
        foreach ($assistanceInstitutions as $assistanceInstitution) {
            yield $this->toFullArray($assistanceInstitution);
        }
    }
}
