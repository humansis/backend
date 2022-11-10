<?php

declare(strict_types=1);

namespace MapperDeprecated;

use Entity\Beneficiary;

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
            'local_given_name' => $beneficiary->getPerson()->getLocalGivenName(),
            'local_family_name' => $beneficiary->getPerson()->getLocalFamilyName(),
            'national_ids' => [],
            'referral' => null,
            'status' => $beneficiary->isHead(),
            'vulnerability_criteria' => [],
        ];

        return $bnfArray;
    }

    /**
     * @param iterable $bnfs
     *
     * @return iterable
     * @deprecated its only for backward consistency, dont use it
     *
     */
    public function toOldMobileArrays(iterable $bnfs): iterable
    {
        foreach ($bnfs as $bnf) {
            yield $this->toOldMobileArray($bnf);
        }
    }
}
