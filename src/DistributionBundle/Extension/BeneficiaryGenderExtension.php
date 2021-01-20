<?php

declare(strict_types=1);

namespace DistributionBundle\Extension;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Person;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class BeneficiaryGenderExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('gender', [$this, 'formatGender']),
        ];
    }

    public function formatGender(Beneficiary $beneficiary): string
    {
        switch ($beneficiary->getPerson()->getGender()) {
            case Person::GENDER_FEMALE: return 'Female';
            case Person::GENDER_MALE: return 'Male';
            default: return '~';
        }
    }

}
