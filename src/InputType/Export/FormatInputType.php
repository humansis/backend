<?php

declare(strict_types=1);

namespace InputType\Export;

use Request\FormatInputType\AbstractFormatInputType;

class FormatInputType extends AbstractFormatInputType
{
    final public const TYPE_XLSX = 'xlsx';
    final public const TYPE_CSV = 'csv';

    protected function getValidNames(): array
    {
        return [
            self::TYPE_XLSX,
            self::TYPE_CSV,
            ];
    }
}
