<?php

declare(strict_types=1);

namespace Mapper;

use Entity\Privilege;
use Entity\Role;
use InvalidArgumentException;
use Serializer\MapperInterface;

class RoleMapper implements MapperInterface
{
    private ?\Entity\Role $object = null;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof Role && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof Role) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException(
            'Invalid argument. It should be instance of ' . Role::class . ', ' . $object::class . ' given.'
        );
    }

    public function getCode(): string
    {
        return $this->object->getCode();
    }

    public function getName(): string
    {
        return $this->object->getName();
    }

    public function getPrivileges(): array
    {
        $fn = fn(Privilege $privilege) => $privilege->getCode();

        return array_values(array_map($fn, $this->object->getPrivileges()->toArray()));
    }
}
