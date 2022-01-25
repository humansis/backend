<?php
declare(strict_types=1);

namespace NewApiBundle\DBAL;

use NewApiBundle\Enum\ModalityType;

class ModalityTypeEnum extends \CommonBundle\DBAL\AbstractEnum
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
