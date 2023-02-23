<?php

declare(strict_types=1);

namespace Mapper;

use DateTimeInterface;
use InvalidArgumentException;
use Serializer\MapperInterface;
use Entity\SmartcardBeneficiary;

class SmartcardMapper implements MapperInterface
{
    use MapperContextTrait;

    private ?\Entity\SmartcardBeneficiary $object = null;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return ($object instanceof SmartcardBeneficiary)
            && $this->isNewApi($context)
            && $this->isOfflineApp($context);
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof SmartcardBeneficiary) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException(
            'Invalid argument. It should be instance of ' . SmartcardBeneficiary::class . ', ' . $object::class . ' given.'
        );
    }

    public function getId(): ?int
    {
        return $this->object instanceof SmartcardBeneficiary ? $this->object->getId() : null;
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
        return $this->object->getCreatedAt()->format(DateTimeInterface::ATOM);
    }
}
