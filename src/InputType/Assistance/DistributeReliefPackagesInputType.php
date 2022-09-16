<?php declare(strict_types=1);

namespace InputType\Assistance;

use Request\InputTypeInterface;
use Utils\DateTime\Iso8601Converter;
use Validator\Constraints\Iso8601;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @Assert\GroupSequence({"DistributeReliefPackagesInputType", "Strict"})
 */
class DistributeReliefPackagesInputType implements InputTypeInterface
{
    /**
     * @Assert\Type(type="integer")
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private $id;

    /**
     * @Iso8601()
     * @Assert\NotBlank
     * @Assert\NotNull
     */
    private $dateDistributed;

    /**
     * @Assert\Type(type="scalar")
     */
    private $amountDistributed;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getDateDistributed(): \DateTimeInterface
    {
        return Iso8601Converter::toDateTime($this->dateDistributed);
    }

    /**
     * @param string $dateDistributed
     */
    public function setDateDistributed($dateDistributed): void
    {
        $this->dateDistributed = $dateDistributed;
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
