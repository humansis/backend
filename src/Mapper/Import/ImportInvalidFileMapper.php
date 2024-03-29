<?php

declare(strict_types=1);

namespace Mapper\Import;

use DateTimeInterface;
use Entity\ImportInvalidFile;
use InvalidArgumentException;
use Serializer\MapperInterface;

class ImportInvalidFileMapper implements MapperInterface
{
    private ?\Entity\ImportInvalidFile $object = null;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof ImportInvalidFile && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof ImportInvalidFile) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException(
            'Invalid argument. It should be instance of ' . ImportInvalidFile::class . ', ' . $object::class . ' given.'
        );
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getName(): string
    {
        return $this->object->getFilename();
    }

    public function getUploadedDate(): string
    {
        return $this->object->getCreatedAt()->format(DateTimeInterface::ATOM);
    }

    public function getInvalidQueueCount(): int
    {
        return $this->object->getInvalidQueueCount();
    }
}
