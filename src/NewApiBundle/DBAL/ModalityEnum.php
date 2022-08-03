<?php

declare(strict_types=1);

namespace NewApiBundle\DBAL;

use CommonBundle\DBAL\AbstractEnum;
use NewApiBundle\Enum\Modality;

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
        return [
            Modality::CASH => Modality::CASH,
            Modality::VOUCHER => Modality::VOUCHER,
            Modality::IN_KIND => Modality::IN_KIND,
            Modality::OTHER => Modality::OTHER,
        ];
    }
}
