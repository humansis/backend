<?php

declare(strict_types=1);

namespace Mapper;

use Doctrine\ORM\Tools\Pagination\Paginator;
use InvalidArgumentException;
use Serializer\MapperInterface;

class PaginatorMapper implements MapperInterface
{
    private ?\Doctrine\ORM\Tools\Pagination\Paginator $object = null;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof Paginator;
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof Paginator) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException(
            'Invalid argument. It should be instance of ' . Paginator::class . ', ' . $object::class . ' given.'
        );
    }

    public function getTotalCount(): int
    {
        return $this->object->count();
    }

    public function getData()
    {
        return $this->object->getIterator();
    }
}
