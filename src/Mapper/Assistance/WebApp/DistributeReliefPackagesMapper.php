<?php

declare(strict_types=1);

namespace Mapper\Assistance\WebApp;

use Entity\Assistance\ReliefPackage;
use Enum\ProductCategoryType;
use InvalidArgumentException;
use OutputType\Assistance\DistributeReliefPackagesOutputType;
use Serializer\MapperInterface;

class DistributeReliefPackagesMapper implements MapperInterface
{
    private ?\OutputType\Assistance\DistributeReliefPackagesOutputType $object = null;

    /**
     * {@inheritdoc}
     */
    public function supports(object $object, $format = null, array $context = null): bool
    {
        return $object instanceof DistributeReliefPackagesOutputType
            && isset($context[self::WEB_API])
            && true === $context[self::WEB_API];
    }

    /**
     * {@inheritdoc}
     */
    public function populate(object $object)
    {
        if ($object instanceof DistributeReliefPackagesOutputType) {
            $this->object = $object;

            return;
        }

        throw new InvalidArgumentException(
            'Invalid argument. It should be instance of ' . DistributeReliefPackagesOutputType::class . ', ' . $object::class . ' given.'
        );
    }

    public function getSuccessfullyDistributed(): array
    {
        return $this->object->getSuccessfullyDistributed();
    }

    public function getPartiallyDistributed(): array
    {
        return $this->object->getPartiallyDistributed();
    }

    public function getAlreadyDistributed(): array
    {
        return $this->object->getAlreadyDistributed();
    }

    public function getFailed(): array
    {
        return $this->object->getFailed();
    }

    public function getConflicts(): array
    {
        return $this->object->getConflicts();
    }

    public function getNotFound(): array
    {
        return $this->object->getNotFound();
    }
}
