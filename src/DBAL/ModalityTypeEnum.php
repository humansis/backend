<?php

declare(strict_types=1);

namespace DBAL;

use Enum\ModalityType;

class ModalityTypeEnum extends AbstractEnum
{
    use EnumTrait;

    public function getName(): string
    {
        return 'enum_modality_type';
    }

    public static function all(): array
    {
        return ModalityType::values();
    }

    public static function databaseMap(): array
    {
        return array_combine(ModalityType::values(), ModalityType::values());
    }
}
