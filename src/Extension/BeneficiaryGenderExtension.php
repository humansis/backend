<?php

declare(strict_types=1);

namespace Extension;

use Entity\Beneficiary;
use Entity\Person;
use Enum\PersonGender;
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
            case PersonGender::FEMALE:
                return 'Female';
            case PersonGender::MALE:
                return 'Male';
            default:
                return '~';
        }
    }
}
