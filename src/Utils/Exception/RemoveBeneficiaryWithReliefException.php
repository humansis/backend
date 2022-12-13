<?php

declare(strict_types=1);

namespace Utils\Exception;

use Entity\Beneficiary;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RemoveBeneficiaryWithReliefException extends HttpException
{
    public function __construct(private readonly Beneficiary $beneficiary)
    {
        parent::__construct(400, $this->message());
    }

    private function message(): string
    {
        return "Beneficiary {$this->getBeneficiaryName()} can\'t be removed from assistance. He has already received a relief.";
    }

    private function getBeneficiaryName(): string
    {
        return $this->beneficiary->getPerson()->getLocalGivenName() . ' ' . $this->beneficiary->getPerson(
        )->getLocalFamilyName();
    }
}
