<?php

declare(strict_types=1);

namespace Mapper;

use Entity\ProjectSector;
use InvalidArgumentException;
use Serializer\MapperInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SectorMapper implements MapperInterface
{
    private ?\Entity\ProjectSector $object = null;

    /**
     * SectorMapper constructor.
     *
     * @param TranslatorInterface $translator
     */
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof ProjectSector && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    public function populate(object $object)
    {
        if ($object instanceof ProjectSector) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException(
            'Invalid argument. It should be instance of ' . ProjectSector::class . ', ' . $object::class . ' given.'
        );
    }

    public function getId()
    {
        return $this->object->getId();
    }

    public function getName()
    {
        return $this->translator->trans('label_sector_' . $this->object->getName(), [], 'messages', 'en');
    }

    public function getProject()
    {
        return $this->object->getProject();
    }

    public function getSector()
    {
        return $this->object->getSector();
    }

    public function getSubSector()
    {
        return $this->object->getSubSector();
    }
}
