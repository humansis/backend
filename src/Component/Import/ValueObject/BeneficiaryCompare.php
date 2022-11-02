<?php

declare(strict_types=1);

namespace Component\Import\ValueObject;

use Entity;
use Component\Import\Integrity;
use Entity\ImportBeneficiaryDuplicity;

class BeneficiaryCompare
{
    public function __construct(private readonly Integrity\ImportLine $importLine, private readonly Entity\Beneficiary $beneficiary, private readonly ImportBeneficiaryDuplicity $beneficiaryDuplicity)
    {
    }

    public function getImportLine(): Integrity\ImportLine
    {
        return $this->importLine;
    }

    public function getBeneficiary(): Entity\Beneficiary
    {
        return $this->beneficiary;
    }

    public function getBeneficiaryDuplicity(): ImportBeneficiaryDuplicity
    {
        return $this->beneficiaryDuplicity;
    }
}
