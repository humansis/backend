<?php

declare(strict_types=1);

namespace DBAL;

use Enum\ProductCategoryType;

class ProductCategoryTypeEnum extends AbstractEnum
{
    public static function all()
    {
        return ProductCategoryType::values();
    }

    public function getName()
    {
        return 'enum_product_category_type';
    }
}
