<?php

declare(strict_types=1);

namespace NewApiBundle\InputType;

use NewApiBundle\Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ProductUpdateInputType implements InputTypeInterface
{
    /**
     * @Assert\Type("string")
     * @Assert\Length(max="20")
     */
    private $unit;

    /**
     * @Assert\Type("string")
     * @Assert\Length(max="255")
     */
    private $image;

    /**
     * @var int|null
     *
     * @Assert\Type("integer")
     */
    private $productCategoryId;

    /**
     * @return string|null
     */
    public function getUnit()
    {
        return $this->unit;
    }

    public function setUnit($unit)
    {
        $this->unit = $unit;
    }

    /**
     * @return string|null
     */
    public function getImage()
    {
        return $this->image;
    }

    public function setImage($image)
    {
        $this->image = $image;
    }

    /**
     * @return int|null
     */
    public function getProductCategoryId()
    {
        return $this->productCategoryId;
    }

    /**
     * @param int|null $productCategoryId
     */
    public function setProductCategoryId($productCategoryId)
    {
        $this->productCategoryId = $productCategoryId;
    }
}
