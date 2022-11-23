<?php

declare(strict_types=1);

namespace Mapper\Import;

use Component\Import\ValueObject\ImportStatisticsValueObject;
use InvalidArgumentException;
use Serializer\MapperInterface;

class ImportStatisticsMapper implements MapperInterface
{
    private ?\Component\Import\ValueObject\ImportStatisticsValueObject $object = null;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof ImportStatisticsValueObject;
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof ImportStatisticsValueObject) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException(
            'Invalid argument. It should be instance of ' . ImportStatisticsValueObject::class . ', ' . $object::class . ' given.'
        );
    }

    public function getTotalEntries(): int
    {
        return $this->object->getTotalEntries();
    }

    public function getAmountIntegrityCorrect(): int
    {
        return $this->object->getAmountIntegrityCorrect();
    }

    public function getAmountIntegrityFailed(): int
    {
        return $this->object->getAmountIntegrityFailed();
    }

    public function getAmountIdentityDuplicities(): int
    {
        return $this->object->getAmountIdentityDuplicities();
    }

    public function getAmountIdentityDuplicitiesResolved(): int
    {
        return $this->object->getAmountIdentityDuplicitiesResolved();
    }

    public function getAmountSimilarityDuplicities(): int
    {
        return $this->object->getAmountSimilarityDuplicities();
    }

    public function getAmountSimilarityDuplicitiesResolved(): int
    {
        return $this->object->getAmountSimilarityDuplicitiesResolved();
    }

    public function getAmountEntriesToImport(): int
    {
        return $this->object->getAmountEntriesToImport();
    }

    public function getStatus(): string
    {
        return $this->object->getStatus();
    }
}
