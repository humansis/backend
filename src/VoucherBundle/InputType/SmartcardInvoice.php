<?php

namespace VoucherBundle\InputType;

use CommonBundle\InputType\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class SmartcardInvoice implements InputTypeInterface
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
}
