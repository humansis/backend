<?php declare(strict_types=1);

namespace NewApiBundle\InputType\Assistance;

use NewApiBundle\Request\InputTypeInterface;
use NewApiBundle\Utils\DateTime\Iso8601Converter;
use NewApiBundle\Validator\Constraints\Iso8601;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Assert\GroupSequence({"DistributeBeneficiaryReliefPackagesInputType", "Strict"})
 */
class DistributeBeneficiaryReliefPackagesInputType implements InputTypeInterface
{
    /**
     * @Assert\Type(type="integer")
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private $beneficiaryId;

    /**
     * @Assert\Type(type="scalar")
     */
    private $amountDistributed;

    /**
     * @return mixed
     */
    public function getBeneficiaryId()
    {
        return $this->beneficiaryId;
    }

    /**
     * @param mixed $beneficiaryId
     */
    public function setBeneficiaryId($beneficiaryId): void
    {
        $this->beneficiaryId = $beneficiaryId;
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
