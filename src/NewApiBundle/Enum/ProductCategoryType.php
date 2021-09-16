<?php
declare(strict_types=1);

namespace NewApiBundle\Enum;

final class ProductCategoryType
{
    public const FOOD = 'Food';
    public const NONFOOD = 'Non-Food';
    public const CASHBACK = 'Cashback';

    public static function values(): array
    {
        return [
            self::FOOD,
            self::NONFOOD,
            self::CASHBACK,
        ];
    }
}
