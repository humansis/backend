<?php declare(strict_types=1);

namespace NewApiBundle\Mapper\Assistance;

use Doctrine\Common\Collections\Collection;
use NewApiBundle\Component\Assistance\DTO\DivisionSummary;
use NewApiBundle\Serializer\MapperInterface;

class DivisionSummaryMapper implements MapperInterface
{
    /** @var DivisionSummary */
    private $object;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof DivisionSummary && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof DivisionSummary) {
            $this->object = $object;

            return;
        }

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.DivisionSummary::class.', '.get_class($object).' given.');
    }

    public function getCode(): ?string
    {
        return $this->object->getDivision();
    }

    public function getQuantities(): ?Collection
    {
        return $this->object->getDivisionGroups()->count() ? $this->object->getDivisionGroups() : null;
    }
}
