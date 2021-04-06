<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper;

use NewApiBundle\Serializer\MapperInterface;
use VoucherBundle\Entity\SmartcardDeposit;

class SmartcardDepositMapper implements MapperInterface
{
    /** @var SmartcardDeposit */
    private $object;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof SmartcardDeposit && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof SmartcardDeposit) {
            $this->object = $object;

            return;
        }

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.SmartcardDeposit::class.', '.get_class($object).' given.');
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getValue()
    {
        return $this->object->getValue();
    }

    public function getCurrency(): string
    {
        return $this->object->getSmartcard()->getCurrency();
    }

    public function getSmartcard(): string
    {
        return $this->object->getSmartcard()->getSerialNumber();
    }

    public function getDepositorId(): int
    {
        return $this->object->getDepositor()->getId();
    }
}
