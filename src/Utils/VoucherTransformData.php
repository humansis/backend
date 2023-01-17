<?php

declare(strict_types=1);

namespace Utils;

class VoucherTransformData
{
    /**
     * Returns an array representation of vouchers in order to prepare the export
     *
     * @param $vouchers
     *
     * @return array
     */
    public function transformData($vouchers): array
    {
        $exportableTable = [];
        foreach ($vouchers as $voucher) {
            $exportableTable [] = [
                'Booklet Number' => $voucher->getBooklet()->getCode(),
                'Voucher Codes' => $voucher->getCode(),
            ];
        }
        return $exportableTable;
    }
}
