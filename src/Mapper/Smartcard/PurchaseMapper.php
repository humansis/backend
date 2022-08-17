<?php declare(strict_types=1);

namespace Mapper\Smartcard;

use Serializer\MapperInterface;
use Entity\SmartcardPurchase;
use Entity\SmartcardPurchaseRecord;

class PurchaseMapper implements MapperInterface
{
    /** @var SmartcardPurchase */
    private $object;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof SmartcardPurchase && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof SmartcardPurchase) {
            $this->object = $object;

            return;
        }

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.SmartcardPurchase::class.', '.get_class($object).' given.');
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getValue()
    {
        $fn = function ($ax, SmartcardPurchaseRecord $dx) {
            return $ax + $dx->getValue();
        };

        return array_reduce($this->object->getRecords()->toArray(), $fn, 0);
    }

    public function getCurrency(): string
    {
        return $this->object->getCurrency();
    }

    public function getBeneficiaryId(): ?int
    {
        return $this->object->getSmartcard()->getBeneficiary() ? $this->object->getSmartcard()->getBeneficiary()->getId() : null;
    }

    public function getDateOfPurchase(): string
    {
        return $this->object->getCreatedAt()->format(\DateTimeInterface::ISO8601);
    }
}
