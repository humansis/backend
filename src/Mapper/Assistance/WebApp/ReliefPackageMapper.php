<?php

declare(strict_types=1);

namespace Mapper\Assistance\WebApp;

use DateTimeInterface;
use Entity\Assistance\ReliefPackage;
use Enum\ProductCategoryType;
use InvalidArgumentException;
use Serializer\MapperInterface;

class ReliefPackageMapper implements MapperInterface
{
    private ?\Entity\Assistance\ReliefPackage $object = null;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof ReliefPackage
            && isset($context[self::WEB_API])
            && true === $context[self::WEB_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof ReliefPackage) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException(
            'Invalid argument. It should be instance of ' . ReliefPackage::class . ', ' . $object::class . ' given.'
        );
    }

    public function getId(): ?int
    {
        return $this->object->getId();
    }

    public function getState(): string
    {
        return $this->object->getState();
    }

    public function getModalityType(): string
    {
        return $this->object->getModalityType();
    }

    public function getNotes(): string
    {
        return $this->object->getNotes() ?? '';
    }

    public function getAmountDistributed(): string
    {
        return $this->object->getAmountDistributed();
    }

    public function getAmountToDistribute(): string
    {
        return $this->object->getAmountToDistribute();
    }

    public function getAmountSpent(): ?string
    {
        return $this->object->getAmountSpent();
    }

    public function getUnit(): string
    {
        return $this->object->getUnit();
    }

    public function getCreatedAt(): string
    {
        return $this->object->getCreatedAt()->format(DateTimeInterface::ATOM);
    }

    public function getLastModifiedAt(): string
    {
        return $this->object->getLastModifiedAt()->format(DateTimeInterface::ATOM);
    }

    public function getDistributedAt(): ?string
    {
        $distributionDate = $this->object->getDistributedAt();

        return $distributionDate ? $distributionDate->format(DateTimeInterface::ATOM) : null;
    }
}
