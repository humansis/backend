<?php

declare(strict_types=1);

namespace InputType;

use Request\InputTypeInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ProductUpdateInputType implements InputTypeInterface
{
    #[Assert\Type('string')]
    #[Assert\Length(max: 20)]
    private $unit;

    #[Assert\Type('string')]
    #[Assert\Length(max: 255)]
    private $image;

    #[Assert\Type('integer')]
    private ?int $productCategoryId = null;

    /**
     * @var numeric|null
     */
    #[Assert\Type('numeric')]
    private $unitPrice;

    #[Assert\Type('string')]
    private ?string $currency = null;

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

    /**
     * @return float|int|string|null
     */
    public function getUnitPrice()
    {
        return $this->unitPrice;
    }

    /**
     * @param float|int|string|null $unitPrice
     */
    public function setUnitPrice($unitPrice): void
    {
        $this->unitPrice = $unitPrice;
    }

    /**
     * @return string|null
     */
    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): void
    {
        $this->currency = $currency;
    }
}
