<?php

namespace VoucherBundle\InputType;

use CommonBundle\InputType\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class SmartcardRedemtionBatch implements InputTypeInterface
{
    /**
     * @var int[]
     *
     * @Assert\Valid()
     * @Assert\NotBlank()
     * @Assert\All({
     *     @Assert\Type("int")
     * })
     */
    private $purchases;

    /**
     * @var string
     *
     * @Assert\NotBlank()
     * @Assert\Regex(pattern="|\d\d-\d\d-\d\d\d\d \d\d:\d\d:\d\d|")
     */
    private $redeemedAt;

    /**
     * @return array
     */
    public function getPurchases(): array
    {
        return $this->purchases;
    }

    /**
     * @param array $purchases
     */
    public function setPurchases(array $purchases): void
    {
        $this->purchases = $purchases;
    }

    /**
     * @return \DateTimeInterface
     */
    public function getRedeemedAt(): \DateTimeInterface
    {
        return new \DateTime($this->redeemedAt);
    }

    /**
     * @param string $redeemedAt
     */
    public function setRedeemedAt(string $redeemedAt): void
    {
        $this->redeemedAt = $redeemedAt;
    }
}
