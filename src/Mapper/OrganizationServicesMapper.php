<?php

declare(strict_types=1);

namespace Mapper;

use Entity\OrganizationServices;
use InvalidArgumentException;
use Serializer\MapperInterface;

class OrganizationServicesMapper implements MapperInterface
{
    private ?\Entity\OrganizationServices $object = null;

    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof OrganizationServices && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    public function populate(object $object)
    {
        if ($object instanceof OrganizationServices) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException(
            'Invalid argument. It should be instance of ' . OrganizationServices::class . ', ' . $object::class . ' given.'
        );
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getName(): string
    {
        return $this->object->getService()->getName();
    }

    public function getIso3(): ?string
    {
        return $this->object->getService()->getCountry();
    }

    public function getEnabled(): bool
    {
        return $this->object->getEnabled();
    }

    public function getParameters(): array
    {
        return $this->object->getParametersValue();
    }
}
