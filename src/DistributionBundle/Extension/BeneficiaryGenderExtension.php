<?php

declare(strict_types=1);

namespace DistributionBundle\Extension;

use BeneficiaryBundle\Entity\Beneficiary;
use BeneficiaryBundle\Entity\Person;
use BeneficiaryBundle\Enum\PersonGender;
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
        if (null !== PersonGender::getByKey($beneficiary->getPerson()->getGender())) {
            return PersonGender::getByKey($beneficiary->getPerson()->getGender());
        }
        return '~';
    }

}
