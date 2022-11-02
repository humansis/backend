<?php

declare(strict_types=1);

namespace Tests\Validator\Constraints;

use Validator\Constraints\Country;
use Validator\Constraints\ImportDate;
use Validator\Constraints\Iso8601;
use Symfony\Component\Validator\Constraints as Assert;

#[Assert\GroupSequence(['TestGroupedObject'])]
class TestGroupedObject
{
    public function __construct(private readonly ?string $isoDate, private readonly ?string $importDate, private readonly ?string $countryISO3)
    {
    }

    public function getIsoDate(): string
    {
        return $this->isoDate;
    }

    public function getImportDate(): string
    {
        return $this->importDate;
    }

    public function getCountryISO3(): string
    {
        return $this->countryISO3;
    }
}
