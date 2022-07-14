<?php

declare(strict_types=1);

namespace BeneficiaryBundle\Mapper;

use NewApiBundle\Entity\AbstractBeneficiary;
use NewApiBundle\Entity\Beneficiary;
use Symfony\Component\Serializer\Serializer;

class BeneficiaryMapper
{
    /** @var Serializer */
    private $serializer;

    /**
     * BookletMapper constructor.
     *
     * @param Serializer $serializer
     */
    public function __construct(Serializer $serializer)
    {
        $this->serializer = $serializer;
    }

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

    /**
     * @deprecated wrapper to symfony serialization group
     *
     * @param Beneficiary|null $beneficiary
     *
     * @return array
     */
    public function toFullBeneficiaryGroup(?Beneficiary $beneficiary): ?array
    {
        if (!$beneficiary) {
            return null;
        }

        return $this->serializer->normalize(
            $beneficiary,
            'json',
            ['groups' => ['ValidatedAssistance'], 'datetime_format' => 'd-m-Y H:i:s']
        );
    }

    /**
     * @deprecated wrapper to symfony serialization group
     *
     * @param iterable $beneficiaries
     *
     * @return \Generator
     */
    public function toFullBeneficiaryGroups(iterable $beneficiaries)
    {
        foreach ($beneficiaries as $beneficiary) {
            yield $this->toFullBeneficiaryGroup($beneficiary);
        }
    }
}
