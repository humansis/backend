<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper;

use NewApiBundle\Component\Smartcard\EmptySmartcardDeposit;
use NewApiBundle\Serializer\MapperInterface;
use VoucherBundle\Entity\SmartcardDeposit;

class SmartcardDepositMapper implements MapperInterface
{
    /** @var SmartcardDeposit|EmptySmartcardDeposit */
    private $object;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return ($object instanceof SmartcardDeposit || $object instanceof EmptySmartcardDeposit) && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof SmartcardDeposit || $object instanceof EmptySmartcardDeposit) {
            $this->object = $object;

            return;
        }

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.SmartcardDeposit::class.', '.get_class($object).' given.');
    }

    public function getId(): ?int
    {
        return $this->object instanceof SmartcardDeposit ? $this->object->getId() : null;
    }

    public function getValue()
    {
        return $this->object->getValue();
    }

    public function getCurrency(): ?string
    {
        if ($this->object instanceof SmartcardDeposit && $this->object->getSmartcard()) {
            return $this->object->getSmartcard()->getCurrency();
        }

        return null;
    }

    public function getSmartcard(): ?string
    {
        if ($this->object instanceof SmartcardDeposit && $this->object->getSmartcard()) {
            return $this->object->getSmartcard()->getSerialNumber();
        }

        return null;
    }

    public function getDepositorId(): ?int
    {
        return $this->object instanceof SmartcardDeposit ? $this->object->getDistributedBy()->getId() : null;
    }

    public function getDistributed(): bool
    {
        return $this->object instanceof SmartcardDeposit;
    }

    public function getDateOfDistribution(): ?string
    {
        return $this->object instanceof SmartcardDeposit ? $this->object->getCreatedAt()->format(\DateTime::ISO8601) : null;
    }
}
