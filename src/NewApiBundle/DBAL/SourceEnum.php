<?php
declare(strict_types=1);

namespace NewApiBundle\DBAL;

use NewApiBundle\Enum\SourceType;

class SourceEnum extends \CommonBundle\DBAL\AbstractEnum
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
