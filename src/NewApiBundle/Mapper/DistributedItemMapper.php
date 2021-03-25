<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper;

use DistributionBundle\Entity\Commodity;
use DistributionBundle\Entity\DistributedItem;
use InvalidArgumentException;
use NewApiBundle\Serializer\MapperInterface;

class DistributedItemMapper implements MapperInterface
{
    /** @var DistributedItem */
    private $object;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof DistributedItem && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof DistributedItem) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException('Invalid argument. It should be instance of '.DistributedItem::class.', '.get_class($object).' given.');
    }

    public function getBeneficiaryId(): int
    {
        return $this->object->getBeneficiary()->getId();
    }

    public function getAssistanceId(): int
    {
        return $this->object->getAssistance()->getId();
    }

    public function getCommodityIds()
    {
        return array_map(function (Commodity $commodity) {
            return $commodity->getId();
        }, $this->object->getCommodities()->toArray());
    }

    public function getDateOfDistribution(): ?string
    {
        return $this->object->getDateOfDistribution() ? $this->object->getDateOfDistribution()->format(\DateTime::ISO8601) : null;
    }
}
