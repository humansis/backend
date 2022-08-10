<?php
declare(strict_types=1);

namespace NewApiBundle\DBAL;

use NewApiBundle\Enum\ProductCategoryType;

class ProductCategoryTypeEnum extends \NewApiBundle\DBAL\AbstractEnum
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
