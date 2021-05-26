<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper;

use NewApiBundle\Component\Import\ValueObject\ImportStatisticsValueObject;
use NewApiBundle\Serializer\MapperInterface;

class ImportStatisticsMapper implements MapperInterface
{
    /** @var ImportStatisticsValueObject */
    private $object;

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

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.ImportStatisticsValueObject::class.', '.get_class($object).' given.');
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

    public function getAmountDuplicities(): int
    {
        return $this->object->getAmountDuplicities();
    }

    public function getAmountDuplicitiesResolved(): int
    {
        return $this->object->getAmountDuplicitiesResolved();
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
