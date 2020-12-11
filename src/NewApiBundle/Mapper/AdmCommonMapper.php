<?php
declare(strict_types=1);

namespace NewApiBundle\Mapper;

use CommonBundle\Entity\Adm2;
use CommonBundle\Entity\Adm3;
use CommonBundle\Entity\Adm4;
use NewApiBundle\Serializer\MapperInterface;

class AdmCommonMapper implements MapperInterface
{
    /** @var Adm2|Adm3|Adm4 */
    private $object;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        $isNewApi = isset($context[self::NEW_API]) && true === $context[self::NEW_API];

        return $isNewApi && ($object instanceof Adm2 || $object instanceof Adm3 || $object instanceof Adm4);
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof Adm2 || $object instanceof Adm3 || $object instanceof Adm4) {
            $this->object = $object;

            return;
        }

        throw new \InvalidArgumentException('Invalid argument. It should be instance of Adm2, Adm3 or Adm4, '.get_class($object).' given.');
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getName(): string
    {
        return $this->object->getName();
    }

    public function getCode(): string
    {
        return $this->object->getCode();
    }
}
