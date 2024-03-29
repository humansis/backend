<?php

declare(strict_types=1);

namespace Mapper;

use Entity\Voucher;
use InvalidArgumentException;
use Serializer\MapperInterface;
use Entity\Booklet;

class BookletOfflineAppMapper implements MapperInterface
{
    use MapperContextTrait;

    private ?\Entity\Booklet $object = null;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof Booklet && $this->isOfflineApp($context);
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof Booklet) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException(
            'Invalid argument. It should be instance of ' . Booklet::class . ', ' . $object::class . ' given.'
        );
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getCurrency(): string
    {
        return $this->object->getCurrency();
    }

    public function getCode(): string
    {
        return $this->object->getCode();
    }

    public function getStatus(): string
    {
        return (string) $this->object->getStatus();
    }

    public function getVoucherValues(): array
    {
        $fn = fn(Voucher $item) => $item->getValue();

        return array_values(array_map($fn, $this->object->getVouchers()->toArray()));
    }

    public function getProjectId(): ?int
    {
        return $this->object->getProject()?->getId();
    }

    public function getBeneficiaryId(): ?int
    {
        return $this->object->getAssistanceBeneficiary()?->getBeneficiary()->getId();
    }

    public function getAssistanceId(): ?int
    {
        return $this->object->getAssistanceBeneficiary()?->getAssistance()->getId();
    }

    public function getDeletable(): bool
    {
        foreach ($this->object->getVouchers() as $voucher) {
            if (null !== $voucher->getVoucherPurchase()) {
                return false;
            }
        }

        return true;
    }
}
