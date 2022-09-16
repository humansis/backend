<?php
declare(strict_types=1);

namespace Component\Product;

use Entity\ProductCategory;
use InputType\ProductCategoryInputType;

class ProductCategoryService
{
    public function create(ProductCategoryInputType $inputType): ProductCategory
    {
        $category = new ProductCategory($inputType->getName(), $inputType->getType());
        $category->setImage($inputType->getImage());

        return $category;
    }

    public function update(ProductCategory $productCategory, ProductCategoryInputType $inputType): ProductCategory
    {
        $productCategory->setName($inputType->getName());
        $productCategory->setType($inputType->getType());
        $productCategory->setImage($inputType->getImage());

        return $productCategory;
    }

    public function archive(ProductCategory $category)
    {
        $category->setArchived(true);
    }
}
