<?php

declare(strict_types=1);

namespace Mapper;

use Entity\ProductCategory;
use InvalidArgumentException;
use Serializer\MapperInterface;

class ProductCategoryMapper implements MapperInterface
{
    /** @var ProductCategory */
    private $object;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof ProductCategory && isset($context[self::NEW_API]) && true === $context[self::NEW_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof ProductCategory) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException('Invalid argument. It should be instance of ' . ProductCategory::class . ', ' . get_class($object) . ' given.');
    }

    public function getId(): int
    {
        return $this->object->getId();
    }

    public function getName(): string
    {
        return $this->object->getName();
    }

    public function getType(): string
    {
        return $this->object->getType();
    }

    public function getImage(): ?string
    {
        return $this->object->getImage();
    }
}
