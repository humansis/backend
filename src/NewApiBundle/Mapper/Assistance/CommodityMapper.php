<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper\Assistance;

use NewApiBundle\Entity\Commodity;
use NewApiBundle\Component\Assistance\DTO\DivisionSummary;
use NewApiBundle\Serializer\MapperInterface;

class CommodityMapper implements MapperInterface
{
    /** @var Commodity */
    private $object;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof Commodity && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof Commodity) {
            $this->object = $object;

            return;
        }

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.Commodity::class.', '.get_class($object).' given.');
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getModalityType(): string
    {
        return (string) $this->object->getModalityType()->getName();
    }

    public function getUnit(): string
    {
        return $this->object->getUnit();
    }

    public function getValue(): float
    {
        return $this->object->getValue();
    }

    public function getDescription(): ?string
    {
        return $this->object->getDescription();
    }

    public function getDivision(): ?DivisionSummary
    {
        $summary = $this->object->getDivisionSummary();

        return $summary->getDivision() === null ? null : $summary;
    }
}
