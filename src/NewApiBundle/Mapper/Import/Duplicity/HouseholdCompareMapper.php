<?php declare(strict_types=1);

namespace NewApiBundle\Mapper\Import\Duplicity;

use NewApiBundle\Component\Import\ValueObject\HouseholdCompare;
use NewApiBundle\Serializer\MapperInterface;

class HouseholdCompareMapper implements MapperInterface
{
    use CompareTrait;

    /** @var HouseholdCompare */
    private $object;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof HouseholdCompare && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof HouseholdCompare) {
            $this->object = $object;

            return;
        }

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.HouseholdCompare::class.', '.get_class($object).' given.');
    }



}
