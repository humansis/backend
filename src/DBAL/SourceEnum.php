<?php

declare(strict_types=1);

namespace DBAL;

use Enum\SourceType;

class SourceEnum extends AbstractEnum
{
    public function getName(): string
    {
        return 'enum_source_type';
    }

    public static function all(): array
    {
        return SourceType::values();
    }
}
