<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper\Import;

use NewApiBundle\Component\Import\Entity\InvalidFile;
use NewApiBundle\Serializer\MapperInterface;

class InvalidFileMapper implements MapperInterface
{
    /** @var InvalidFile */
    private $object;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof InvalidFile && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof InvalidFile) {
            $this->object = $object;

            return;
        }

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.InvalidFile::class.', '.get_class($object).' given.');
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
        return $this->object->getCreatedAt()->format(\DateTimeInterface::ISO8601);
    }

    public function getInvalidQueueCount(): int
    {
        return $this->object->getInvalidQueueCount();
    }
}
