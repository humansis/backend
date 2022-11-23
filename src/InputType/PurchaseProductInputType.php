<?php

declare(strict_types=1);

namespace InputType;

use Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class PurchaseProductInputType implements InputTypeInterface
{
    #[Assert\NotNull]
    #[Assert\Type('integer')]
    #[Assert\GreaterThan(0)]
    private ?int $id = null;

    #[Assert\Type('numeric')]
    #[Assert\GreaterThanOrEqual(0)]
    private float|int|string|null $quantity = null;

    #[Assert\NotNull]
    #[Assert\Type('numeric')]
    #[Assert\GreaterThanOrEqual(0)]
    private float|int|string|null $value = null;

    #[Assert\NotNull]
    #[Assert\Type('string')]
    private ?string $currency = null;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    public function getQuantity(): float|int|string
    {
        if (null === $this->quantity) {
            return 1;
        }

        return $this->quantity;
    }

    /**
     * @param float|int|string|null $quantity
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
    }

    public function getValue(): float|int|string
    {
        return $this->value;
    }

    public function setValue(float|int|string $value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }
}
