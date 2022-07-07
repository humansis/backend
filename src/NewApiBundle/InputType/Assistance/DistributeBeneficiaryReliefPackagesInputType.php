<?php declare(strict_types=1);

namespace NewApiBundle\InputType\Assistance;

use NewApiBundle\Request\InputTypeInterface;
use NewApiBundle\Utils\DateTime\Iso8601Converter;
use NewApiBundle\Validator\Constraints\Iso8601;
use Symfony\Component\Validator\Constraints as Assert;

class DistributeBeneficiaryReliefPackagesInputType implements InputTypeInterface
{
    /**
     * @Assert\Type(type="string")
     * @Assert\NotBlank
     */
    private $idNumber;

    /**
     * @Assert\Type(type="scalar")
     */
    private $amountDistributed;

    /**
     * @return mixed
     */
    public function getIdNumber()
    {
        return $this->idNumber;
    }

    /**
     * @param mixed $idNumber
     */
    public function setIdNumber($idNumber): void
    {
        $this->idNumber = trim($idNumber);
    }

    /**
     * @return mixed
     */
    public function getAmountDistributed()
    {
        return $this->amountDistributed;
    }

    /**
     * @param mixed $amountDistributed
     */
    public function setAmountDistributed($amountDistributed): void
    {
        $this->amountDistributed = $amountDistributed;
    }

}
