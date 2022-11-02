<?php

declare(strict_types=1);

namespace Mapper;

use Serializer\MapperInterface;

trait MapperContextTrait
{
    private function isNewApi(array $context): bool
    {
        return isset($context[MapperInterface::NEW_API]) &&
            $context[MapperInterface::NEW_API] === true;
    }

    private function isOfflineApp(array $context): bool
    {
        return isset($context[MapperInterface::OFFLINE_APP]) &&
            $context[MapperInterface::OFFLINE_APP] === true;
    }
}
