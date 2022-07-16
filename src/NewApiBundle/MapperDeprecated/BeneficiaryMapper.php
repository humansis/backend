<?php

declare(strict_types=1);

namespace NewApiBundle\MapperDeprecated;

use NewApiBundle\Entity\Beneficiary;

/**
 * @deprecated
 */
class BeneficiaryMapper
{
    /**
     * @param Beneficiary|null $beneficiary
     *
     * @return array
     *
     * @deprecated its only for backward consistency, dont use it
     */
    public function toOldMobileArray(?Beneficiary $beneficiary): ?array
    {
        if (!$beneficiary) {
            return null;
        }

        $bnfArray = [
            'id' => $beneficiary->getId(),
            'local_given_name' => $beneficiary->getLocalGivenName(),
            'local_family_name' => $beneficiary->getLocalFamilyName(),
            'national_ids' => [],
            'referral' => null,
            'status' => $beneficiary->getStatus(),
            'vulnerability_criteria' => [],
        ];

        return $bnfArray;
    }

    /**
     * @deprecated its only for backward consistency, dont use it
     *
     * @param iterable $bnfs
     *
     * @return iterable
     */
    public function toOldMobileArrays(iterable $bnfs): iterable
    {
        foreach ($bnfs as $bnf) {
            yield $this->toOldMobileArray($bnf);
        }
    }
}
