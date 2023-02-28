<?php

namespace Mapper;

use InvalidArgumentException;
use Serializer\MapperInterface;
use Entity\Voucher;
use Entity\VoucherRedemptionBatch;

class VoucherRedemptionBatchMapper implements MapperInterface
{
    private ?\Entity\VoucherRedemptionBatch $object = null;

    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof VoucherRedemptionBatch;
    }

    public function populate(object $object)
    {
        if ($object instanceof VoucherRedemptionBatch) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException(
            'Invalid argument. It should be instance of ' . VoucherRedemptionBatch::class . ', ' . $object::class . ' given.'
        );
    }

    public function getDatetime(): string
    {
        return (string) $this->object->getRedeemedAt()->getTimestamp();
    }

    public function getDate(): string
    {
        return $this->object->getRedeemedAt()->format('d-m-Y H:i');
    }

    public function getCount(): int
    {
        return $this->object->getVouchers()->count();
    }

    public function getValue(): float
    {
        return $this->object->getValue();
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getVendor(): int
    {
        return $this->object->getVendor()->getId();
    }

    public function getRedeemedAt(): string
    {
        return $this->object->getRedeemedAt()->format('d-m-Y H:i');
    }

    public function getRedeemedBy(): ?int
    {
        $redeemedBy = $this->object->getRedeemedBy();

        return $redeemedBy?->getId();
    }

    public function getVoucherIds(): array
    {
        return array_values(
            array_map(fn(Voucher $item) => $item->getId(), $this->object->getVouchers()->toArray())
        );
    }
}
