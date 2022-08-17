<?php
declare(strict_types=1);

namespace Mapper;

use Entity\Privilege;
use Entity\Role;
use Serializer\MapperInterface;

class RoleMapper implements MapperInterface
{
    /** @var Role */
    private $object;

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

        throw new \InvalidArgumentException('Invalid argument. It should be instance of '.Role::class.', '.get_class($object).' given.');
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
        $fn = function (Privilege $privilege) {
            return $privilege->getCode();
        };

        return array_values(array_map($fn, $this->object->getPrivileges()->toArray()));
    }
}
