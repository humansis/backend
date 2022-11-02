<?php

namespace InputType;

use InputType\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class VoucherRedemptionBatch implements InputTypeInterface
{
    /**
     * @var int[]
     *
     * @Assert\All({
     *     @Assert\Type("int")
     * })
     */
    #[Assert\NotBlank]
    private $vouchers;

    public function getVouchers(): array
    {
        return $this->vouchers;
    }

    public function setVouchers(array $vouchers): void
    {
        $this->vouchers = $vouchers;
    }
}
