<?php

declare(strict_types=1);

namespace InputType\Assistance;

use Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class DistributeBeneficiaryReliefPackagesInputType implements InputTypeInterface
{
    #[Assert\Type(type: 'string')]
    #[Assert\NotBlank]
    private $idNumber;

    #[Assert\Type(type: 'scalar')]
    private $amountDistributed;

    /**
     * @return mixed
     */
    public function getIdNumber()
    {
        return $this->idNumber;
    }

    public function setIdNumber(mixed $idNumber): void
    {
        $this->idNumber = trim((string) $idNumber);
    }

    /**
     * @return mixed
     */
    public function getAmountDistributed()
    {
        return $this->amountDistributed;
    }

    public function setAmountDistributed(mixed $amountDistributed): void
    {
        $this->amountDistributed = $amountDistributed;
    }
}
