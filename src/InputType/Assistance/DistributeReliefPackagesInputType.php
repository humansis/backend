<?php

declare(strict_types=1);

namespace InputType\Assistance;

use DateTimeInterface;
use Request\InputTypeInterface;
use Utils\DateTime\Iso8601Converter;
use Validator\Constraints\Iso8601;
use Symfony\Component\Validator\Constraints as Assert;

#[Assert\GroupSequence(['DistributeReliefPackagesInputType', 'Strict'])]
class DistributeReliefPackagesInputType implements InputTypeInterface
{
    #[Assert\Type(type: 'integer')]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    private $id;

    /**
     * @Iso8601()
     */
    #[Assert\NotBlank]
    #[Assert\NotNull]
    private $dateDistributed;

    #[Assert\Type(type: 'scalar')]
    private $amountDistributed;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    public function setId(mixed $id): void
    {
        $this->id = $id;
    }

    public function getDateDistributed(): DateTimeInterface
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

    public function setAmountDistributed(mixed $amountDistributed): void
    {
        $this->amountDistributed = $amountDistributed;
    }
}
