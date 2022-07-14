<?php

declare(strict_types=1);

namespace DistributionBundle\Extension;

use NewApiBundle\Entity\Beneficiary;
use NewApiBundle\Entity\Person;
use NewApiBundle\Enum\PersonGender;
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
            case PersonGender::FEMALE: return 'Female';
            case PersonGender::MALE: return 'Male';
            default: return '~';
        }
    }

}
