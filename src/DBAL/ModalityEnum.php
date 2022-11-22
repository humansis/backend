<?php

declare(strict_types=1);

namespace DBAL;

use Enum\Modality;

class ModalityEnum extends AbstractEnum
{
    use EnumTrait;

    public static function all(): array
    {
        return Modality::values();
    }

    public function getName(): string
    {
        return 'enum_modality';
    }

    public static function databaseMap(): array
    {
        return array_combine(Modality::values(), Modality::values());
    }
}
