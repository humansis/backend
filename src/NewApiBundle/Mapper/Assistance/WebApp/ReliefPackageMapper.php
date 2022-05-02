<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper\Assistance\WebApp;

use NewApiBundle\Entity\Assistance\ReliefPackage;
use NewApiBundle\Enum\ProductCategoryType;
use NewApiBundle\Serializer\MapperInterface;

class ReliefPackageMapper implements MapperInterface
{
    /** @var ReliefPackage */
    private $object;

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

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.ReliefPackage::class.', '.get_class($object).' given.');
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getModalityType(): string
    {
        return $this->object->getModalityType();
    }

    public function getNotes(): string
    {
        return "";
        // return $this->object->getAssistanceBeneficiary()->getNotes();
    }

    public function getAmountDistributed(): string
    {
        return $this->object->getAmountToDistribute();
    }

    public function getAmountToDistribute(): string
    {
        return $this->object->getAmountToDistribute();
    }

    public function getUnit(): string
    {
        return $this->object->getUnit();
    }

    public function getCreatedAt(): string
    {
        return $this->object->getCreatedAt()->format(\DateTimeInterface::ISO8601);
    }

    public function getLastModifiedAt(): string
    {
        return "";
        // return $this->object->getLastModifiedAt()->format(\DateTimeInterface::ISO8601);
    }

    public function getDistributedAt(): ?string
    {
        $distributionDate = null;
        // $distributionDate = $this->object->getDistributedAt();

        return $distributionDate ? $distributionDate->format(\DateTimeInterface::ISO8601) : null;
    }
}
