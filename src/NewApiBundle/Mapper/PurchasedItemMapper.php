<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper;

use NewApiBundle\Serializer\MapperInterface;
use TransactionBundle\Entity\PurchasedItem;

class PurchasedItemMapper implements MapperInterface
{
    /** @var PurchasedItem */
    private $object;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return
            $object instanceof PurchasedItem && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof PurchasedItem) {
            $this->object = $object;

            return;
        }

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.PurchasedItem::class.', '.get_class($object).' given.');
    }

    public function getBeneficiaryId(): int
    {
        return $this->object->getBeneficiary()->getId();
    }

    public function getProductId(): int
    {
        return $this->object->getProduct()->getId();
    }

    public function getValue(): string
    {
        return (string) $this->object->getValue();
    }

    public function getCurrency(): string
    {
        return $this->object->getCurrency();
    }

    public function getQuantity(): string
    {
        return (string) $this->object->getQuantity();
    }

    public function getSource(): string
    {
        return $this->object->getSource();
    }

    public function getDate(): string
    {
        return $this->object->getUsedAt()->format(\DateTime::ISO8601);
    }
}
