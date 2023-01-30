<?php

declare(strict_types=1);

namespace Utils;

class ProductTransformData
{
    /**
     * Returns an array representation of products in order to prepare the export
     *
     * @param $products
     *
     * @return array
     */
    public function transformData($products): array
    {
        $exportableTable = [];

        foreach ($products as $product) {
            $exportableTable [] = [
                'Name' => $product->getName(),
                'Unit' => $product->getUnit(),
            ];
        }

        return $exportableTable;
    }
}
