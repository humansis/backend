<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper\Assistance;

use NewApiBundle\Entity\AssistanceStatistics;
use NewApiBundle\Serializer\MapperInterface;

class StatisticsMapper implements MapperInterface
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

    public function getAmountTotal(): float
    {
        return (float) $this->object->getAmountTotal();
    }

    public function getAmountDistributed(): float
    {
        return (float) $this->object->getAmountDistributed();
    }

    public function getAmountUsed(): ?float
    {
        return $this->object->getAmountUsed();
    }

    public function getAmountSent(): ?float
    {
        return $this->object->getAmountSent();
    }

    public function getAmountPickedUp(): ?float
    {
        return $this->object->getAmountPickedUp();
    }
}
