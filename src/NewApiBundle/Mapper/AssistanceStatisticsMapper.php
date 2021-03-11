<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper;

use NewApiBundle\Entity\AssistanceStatistics;
use NewApiBundle\Serializer\MapperInterface;

class AssistanceStatisticsMapper implements MapperInterface
{
    /** @var AssistanceStatistics */
    private $object;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof AssistanceStatistics && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof AssistanceStatistics) {
            $this->object = $object;

            return;
        }

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.AssistanceStatistics::class.', '.get_class($object).' given.');
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getNumberOfBeneficiaries(): int
    {
        return $this->object->getNumberOfBeneficiaries();
    }

    public function getSummaryOfTotalItems(): float
    {
        return $this->object->getSummaryOfTotalItems();
    }

    public function getSummaryOfDistributedItems(): float
    {
        return $this->object->getSummaryOfDistributedItems();
    }

    public function getSummaryOfUsedItems(): ?float
    {
        return $this->object->getSummaryOfUsedItems();
    }

}
