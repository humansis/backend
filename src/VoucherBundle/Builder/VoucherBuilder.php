<?php

namespace VoucherBundle\Builder;

use VoucherBundle\Entity\Booklet;
use VoucherBundle\Entity\Voucher;

class VoucherBuilder
{
    /** @var Booklet */
    private $booklet;
    /** @var string iso format [USD, CZK, ...] */
    private $currency;
    /** @var int */
    private $lastId = 0;

    /**
     * VoucherBuilder constructor.
     * @param Booklet $booklet
     * @param string $currency
     */
    public function __construct(Booklet $booklet, string $currency)
    {
        $this->booklet = $booklet;
        $this->currency = $currency;
    }

    public function createOne(int $value, int $voucherId) {
        return new Voucher($this->generateCode($this->booklet, $this->currency, $value, $voucherId), $value, $this->booklet);
    }

    public function createByValueSequence(int $firstValue, int $lastValue) {
        $vouchers = [];
        $id = $this->lastId;
        for ($value = $firstValue; $value <= $lastValue; $value++) {
            $vouchers[] = $this->createOne($value, $id++);
        }
        return $vouchers;
    }

    public function createByValueList(array $values) {
        $vouchers = [];
        $id = $this->lastId;
        foreach($values as $value) {
            $vouchers[] = $this->createOne($value, $id++);
        }
        return $vouchers;
    }

    /**
     * @param int $lastId
     */
    public function setLastId(int $lastId): void
    {
        $this->lastId = $lastId;
    }

    /**
     * Generate a new random code for a voucher
     *
     * @param array $voucherData
     * @param int $voucherId
     * @return string
     */
    private function generateCode(Booklet $booklet, string $currency, int $value, int $voucherId)
    {
        // CREATE VOUCHER CODE CurrencyValue*BookletBatchNumber-lastBatchNumber-BookletId-VoucherId
        $fullCode = $currency . $value . '*' . $this->booklet->getCode() . '-' . $voucherId;
        $fullCode = $booklet->getPassword() ? $fullCode . '-' . $booklet->getPassword() : $fullCode;

        return $fullCode;
    }
}