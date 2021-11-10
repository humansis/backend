<?php
declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class PurchaseProductInputType implements InputTypeInterface
{
    /**
     * @var int
     *
     * @Assert\NotNull
     * @Assert\Type("integer")
     * @Assert\GreaterThan(0)
     */
    private $id;

    /**
     * @var float|int|string|null
     *
     * @Assert\Type("numeric")
     * @Assert\GreaterThanOrEqual(0) //TODO Is it correct to allow 0?
     */
    private $quantity;

    /**
     * @var float|int|string
     *
     * @Assert\NotNull
     * @Assert\Type("numeric")
     * @Assert\GreaterThanOrEqual(0) //TODO Is it correct to allow 0?
     */
    private $value;

    /**
     * @var string
     *
     * @Assert\NotNull
     * @Assert\Type("string") //TODO check, if string is valid currency (we need to have proper currencies enum)
     */
    private $currency;

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

    /**
     * @return float|int|string
     */
    public function getQuantity()
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

    /**
     * @return float|int|string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param float|int|string $value
     */
    public function setValue($value)
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
