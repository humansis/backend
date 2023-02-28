<?php

declare(strict_types=1);

namespace Utils\Exception;

use Entity\Beneficiary;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

class RemoveBeneficiaryWithReliefException extends HttpException
{
    public function __construct(
        private readonly Beneficiary $beneficiary,
        private readonly TranslatorInterface $translator,
    ) {
        parent::__construct(400, $this->message());
    }

    private function message(): string
    {
        return $this->translator->trans(
            "Beneficiary %name% can't be removed from assistance. He has already received a relief package.",
            [
                '%name%' => $this->beneficiary->getPerson()->getLocalGivenName()
                    . ' ' . $this->beneficiary->getPerson()->getLocalFamilyName()
            ],
        );
    }
}
