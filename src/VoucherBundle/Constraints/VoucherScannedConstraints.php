<?php

namespace VoucherBundle\Constraints;

use RA\RequestValidatorBundle\RequestValidator\Constraints as RequestValidatorConstraints;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\DateTime;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\Type;

class VoucherScannedConstraints extends RequestValidatorConstraints
{
    protected function configure(): array
    {
        return [
            "voucher_scanned" => new Collection([
                "id" => new Type('integer'),
                "productId" => new Type('integer'),
                "vendorId" => new Type('integer'),
                "booklet" => new Type('string'),
                "usedAt" => new Optional(new DateTime(['format' => 'd-m-Y H:i:s'])),
                "value" => new Optional(new Type('numeric')),
                "quantity" => new Optional(new Type('numeric')),
            ])
        ];
    }
}
