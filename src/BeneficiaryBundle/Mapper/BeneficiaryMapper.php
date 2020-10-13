<?php
namespace BeneficiaryBundle\Mapper;

use BeneficiaryBundle\Entity\AbstractBeneficiary;
use BeneficiaryBundle\Entity\Address;
use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Household;
use CommonBundle\Mapper\LocationMapper;

class BeneficiaryMapper
{
    public function toMinimalArray(?AbstractBeneficiary $beneficiary): ?array
    {
        if (!$beneficiary) {
            return null;
        }

        return [
            'id' => $beneficiary->getId(),
        ];
    }

    public function toMinimalArrays(iterable $beneficiaries): iterable
    {
        foreach ($beneficiaries as $beneficiary) {
            yield $this->toMinimalArray($beneficiary);
        }
    }

    /**
     * @param Beneficiary|null $beneficiary
     *
     * @return array
     * @deprecated its only for backward consistency, dont use it
     *
     */
    public function toOldMobileArray(?Beneficiary $beneficiary): ?array
    {
        if (!$beneficiary) {
            return null;
        }

        $bnfArray = [
            'id' => $beneficiary->getId(),
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
