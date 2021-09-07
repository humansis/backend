<?php
declare(strict_types=1);

namespace NewApiBundle\Component\Product;

use NewApiBundle\Entity\ProductCategory;
use NewApiBundle\InputType\ProductCategoryInputType;

class ProductCategoryService
{
    public function create(ProductCategoryInputType $inputType): ProductCategory
    {
        $category = new ProductCategory($inputType->getName(), $inputType->getType());
        $category->setImage($inputType->getImage());

        return $category;
    }
}
