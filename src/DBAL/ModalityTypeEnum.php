<?php
declare(strict_types=1);

namespace DBAL;

use Enum\ModalityType;

class ModalityTypeEnum extends \DBAL\AbstractEnum
{
    public function getName(): string
    {
        return 'enum_modality_type';
    }

    public static function all(): array
    {
        return ModalityType::values();
    }
}
