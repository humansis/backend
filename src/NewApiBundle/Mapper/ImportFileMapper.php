<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper;

use NewApiBundle\Entity\ImportFile;
use NewApiBundle\Serializer\MapperInterface;

class ImportFileMapper implements MapperInterface
{
    /** @var ImportFile */
    private $object;

    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof ImportFile && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    public function populate(object $object)
    {
        if ($object instanceof ImportFile) {
            $this->object = $object;

            return;
        }

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.ImportFile::class.', '.get_class($object).' given.');
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getName(): string
    {
        return $this->object->getFilename();
    }

    public function getCreatedBy(): int
    {
        return $this->object->getUser()->getId();
    }

    public function getUploadedDate(): string
    {
        return $this->object->getCreatedAt()->format(\DateTimeInterface::ISO8601);
    }
}
