<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper\Assistance;

use NewApiBundle\Entity;
use NewApiBundle\Component\Assistance\AssistanceFactory;
use NewApiBundle\Component\Assistance\Domain;
use NewApiBundle\Utils\AssistanceService;
use NewApiBundle\Component\Assistance\DTO\CommoditySummary;
use NewApiBundle\Serializer\MapperInterface;

class CommoditySummaryMapper implements MapperInterface
{
    /** @var CommoditySummary */
    private $object;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return ($object instanceof CommoditySummary)
            && isset($context[self::NEW_API])
            && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof CommoditySummary) {
            $this->object = $object;

            return;
        }

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.CommoditySummary::class.', '.get_class($object).' given.');
    }

    public function getModalityType(): string
    {
        return $this->object->getModalityType();
    }

    public function getUnit(): string
    {
        return $this->object->getUnit();
    }

    public function getValue(): float
    {
        return $this->object->getAmount();
    }

}
