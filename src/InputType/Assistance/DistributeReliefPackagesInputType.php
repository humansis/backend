<?php

declare(strict_types=1);

namespace InputType\Assistance;

use Request\InputTypeInterface;
use Validator\Constraints\Iso8601;
use Symfony\Component\Validator\Constraints as Assert;

#[Assert\GroupSequence(['DistributeReliefPackagesInputType', 'Strict'])]
class DistributeReliefPackagesInputType implements InputTypeInterface
{
    #[Assert\Type(type: 'integer')]
    #[Assert\NotBlank]
    #[Assert\NotNull]
    private $id;

    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[Iso8601]
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

    public function getDateDistributed(): ?\DateTime
    {
        return $this->dateDistributed;
    }

    public function setDateDistributed(?\DateTime $dateDistributed): void
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
