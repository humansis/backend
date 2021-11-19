<?php declare(strict_types=1);

namespace NewApiBundle\Mapper;

use NewApiBundle\Serializer\MapperInterface;

trait MapperContextTrait
{
    /**
     * @param array $context
     *
     * @return bool
     */
    private function isNewApi(array $context): bool
    {
        return isset($context[MapperInterface::NEW_API]) &&
            $context[MapperInterface::NEW_API] === true;
    }

    /**
     * @param array $context
     *
     * @return bool
     */
    private function isOfflineApp(array $context): bool
    {
        return isset($context[MapperInterface::OFFLINE_APP]) &&
            $context[MapperInterface::OFFLINE_APP] === true;
    }

}
