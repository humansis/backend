<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper;

use NewApiBundle\Serializer\MapperInterface;
use VoucherBundle\Entity\Smartcard;

class SmartcardOfflineAppMapper implements MapperInterface
{
    use MapperContextTrait;

    /** @var Smartcard */
    private $object;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return ($object instanceof Smartcard)
            && $this->isNewApi($context)
            && $this->isOfflineApp($context)
            ;
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof Smartcard) {
            $this->object = $object;

            return;
        }

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.Smartcard::class.', '.get_class($object).' given.');
    }

    public function getId(): ?int
    {
        return $this->object instanceof Smartcard ? $this->object->getId() : null;
    }

    public function getState(): string
    {
        return $this->object->getState();
    }

    public function getCurrency(): ?string
    {
        return $this->object->getCurrency();
    }

    public function getSerialNumber(): ?string
    {
        return $this->object->getSerialNumber();
    }

    public function getCreatedAt(): string
    {
        return $this->object->getCreatedAt()->format(\DateTimeInterface::ISO8601);
    }

}
