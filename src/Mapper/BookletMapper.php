<?php

declare(strict_types=1);

namespace Mapper;

use Entity\Voucher;
use InvalidArgumentException;
use Serializer\MapperInterface;
use Entity\Booklet;

class BookletMapper implements MapperInterface
{
    private ?\Entity\Booklet $object = null;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof Booklet &&
            isset($context[self::NEW_API]) && true === $context[self::NEW_API] &&
            !isset($context['offline-app']);
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

    public function getTotalValue(): int
    {
        return $this->object->getTotalValue();
    }

    public function getIndividualValues(): array
    {
        $fn = fn(Voucher $item) => $item->getValue();

        return array_values(array_map($fn, $this->object->getVouchers()->toArray()));
    }

    public function getQuantityOfVouchers(): int
    {
        return $this->object->getNumberVouchers();
    }

    public function getQuantityOfUsedVouchers(): int
    {
        $fn = fn($ax, Voucher $dx) => $ax + ($dx->getUsedAt() ? 1 : 0);

        return array_reduce($this->object->getVouchers()->toArray(), $fn, 0);
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

    public function getDistributed(): bool
    {
        return Booklet::DISTRIBUTED === $this->object->getStatus();
    }
}
