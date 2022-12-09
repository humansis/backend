<?php

declare(strict_types=1);

namespace Utils\Exception;

use Entity\Beneficiary;
use InvalidArgumentException;
use Symfony\Contracts\Translation\TranslatorInterface;

class AddBeneficiaryWithReliefException extends InvalidArgumentException
{
    public function __construct(
        protected Beneficiary $beneficiary,
        private readonly TranslatorInterface $translator
    ) {
        parent::__construct();
        $this->message = $this->translator->trans(
            "Beneficiary %name% can't be added to assistance. His relief is not in appropriate state.",
            [
                '%name%' => $this->beneficiary->getPerson()->getLocalGivenName()
                    . ' ' . $this->beneficiary->getPerson()->getLocalFamilyName()
            ],
        );
    }
}
