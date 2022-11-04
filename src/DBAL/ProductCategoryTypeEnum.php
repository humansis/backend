<?php

declare(strict_types=1);

namespace DBAL;

use Enum\ProductCategoryType;

class ProductCategoryTypeEnum extends AbstractEnum
{
    public static function all(): array
    {
        return ProductCategoryType::values();
    }

    public function getName(): string
    {
        return 'enum_product_category_type';
    }
}
