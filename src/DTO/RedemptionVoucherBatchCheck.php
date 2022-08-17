<?php

namespace DTO;

use Entity\Voucher;

class RedemptionVoucherBatchCheck implements \JsonSerializable
{
    /** @var Voucher[] */
    private $validVouchers = [];
    /** @var Voucher[] */
    private $alreadyRedeemedVouchers = [];
    /** @var Voucher[] */
    private $unassignedVouchers = [];
    /** @var Voucher[] */
    private $unusedVouchers = [];
    /** @var Voucher[] */
    private $vendorInconsistentVouchers = [];
    /** @var string[] */
    private $notExistedIds = [];

    public function addValidVoucher(Voucher $voucher): void
    {
        $this->validVouchers[] = $voucher;
    }

    public function addAlreadyRedeemedVoucher(Voucher $voucher): void
    {
        $this->alreadyRedeemedVouchers[] = $voucher;
    }

    public function addVendorInconsistentVoucher(Voucher $voucher): void
    {
        $this->vendorInconsistentVouchers[] = $voucher;
    }

    public function addUnassignedVoucher(Voucher $voucher): void
    {
        $this->unassignedVouchers[] = $voucher;
    }

    public function addUnusedVoucher(Voucher $voucher): void
    {
        $this->unusedVouchers[] = $voucher;
    }

    public function addNotExistedId($id): void
    {
        $this->notExistedIds[] = $id;
    }

    /**
     * @return Voucher[]
     */
    public function getValidVouchers(): array
    {
        return $this->validVouchers;
    }

    /**
     * @return Voucher[]
     */
    public function getAlreadyRedeemedVouchers(): array
    {
        return $this->alreadyRedeemedVouchers;
    }

    /**
     * @return Voucher[]
     */
    public function getUnusedVouchers(): array
    {
        return $this->unusedVouchers;
    }

    public function hasInvalidVouchers(): bool
    {
        return !empty($this->notExistedIds)
            || !empty($this->unusedVouchers)
            || !empty($this->vendorInconsistentVouchers)
            || !empty($this->unassignedVouchers)
            || !empty($this->alreadyRedeemedVouchers)
            ;
    }

    public function jsonSerialize()
    {
        return [
            'valid' => $this->toIdArray($this->validVouchers),
            'unassigned' => $this->toIdArray($this->unassignedVouchers),
            'unused' => $this->toIdArray($this->unusedVouchers),
            'redeemed' => $this->toIdArray($this->alreadyRedeemedVouchers),
            'inconsistent' => $this->toIdArray($this->vendorInconsistentVouchers),
            'not_exists' => $this->notExistedIds,
        ];
    }

    private function toIdArray(array $vouchers): array
    {
        return array_values(array_map(function (Voucher $voucher) {
            return $voucher->getId();
        }, $vouchers));
    }
}
