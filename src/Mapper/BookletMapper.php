<?php
declare(strict_types=1);

namespace Mapper;

use Serializer\MapperInterface;
use Entity\Booklet;

class BookletMapper implements MapperInterface
{
    /** @var Booklet */
    private $object;

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

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.Booklet::class.', '.get_class($object).' given.');
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
        $fn = function (\Entity\Voucher $item) {
            return $item->getValue();
        };

        return array_values(array_map($fn, $this->object->getVouchers()->toArray()));
    }

    public function getQuantityOfVouchers(): int
    {
        return $this->object->getNumberVouchers();
    }

    public function getQuantityOfUsedVouchers(): int
    {
        $fn = function ($ax, \Entity\Voucher $dx) {
            return $ax + ($dx->getUsedAt() ? 1 : 0);
        };

        return array_reduce($this->object->getVouchers()->toArray(), $fn, 0);
    }

    public function getProjectId(): ?int
    {
        return $this->object->getProject() ? $this->object->getProject()->getId() : null;
    }

    public function getBeneficiaryId(): ?int
    {
        return $this->object->getAssistanceBeneficiary() ? $this->object->getAssistanceBeneficiary()->getBeneficiary()->getId() : null;
    }

    public function getAssistanceId(): ?int
    {
        return $this->object->getAssistanceBeneficiary() ? $this->object->getAssistanceBeneficiary()->getAssistance()->getId() : null;
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
