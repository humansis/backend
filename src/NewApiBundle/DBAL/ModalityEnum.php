<?php

declare(strict_types=1);

namespace NewApiBundle\DBAL;

use CommonBundle\DBAL\AbstractEnum;
use NewApiBundle\Enum\Modality;

class ModalityEnum extends AbstractEnum
{
    public static function all(): array
    {
        return Modality::values();
    }

    public function getName(): string
    {
        return 'enum_modality';
    }
}
