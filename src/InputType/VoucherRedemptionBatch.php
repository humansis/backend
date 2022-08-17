<?php

namespace InputType;

use InputType\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class VoucherRedemptionBatch implements InputTypeInterface
{
    /**
     * @var int[]
     *
     * @Assert\NotBlank()
     * @Assert\All({
     *     @Assert\Type("int")
     * })
     */
    private $vouchers;

    /**
     * @return array
     */
    public function getVouchers(): array
    {
        return $this->vouchers;
    }

    /**
     * @param array $vouchers
     */
    public function setVouchers(array $vouchers): void
    {
        $this->vouchers = $vouchers;
    }
}
